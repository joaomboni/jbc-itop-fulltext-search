<?php

/**
 * Fulltext search over the JBC_fulltext_doc table (MySQL / MariaDB FULLTEXT).
 */
class JbcItopFulltextSearchService
{
	/**
	 * @return array<int, array{class: string, id: int, score: float, rel: float}>
	 */
	public static function Search(string $sQuery, ?string $sRestrictClass = null, int $iLimit = 300): array
	{
		if (!JbcItopFulltextSearchHelper::IsEnabled()) {
			return array();
		}

		$sBody = $sQuery;
		$sResolvedPrefix = static::ExtractClassRestriction($sBody);
		if ($sResolvedPrefix !== null && $sResolvedPrefix !== '') {
			$sRestrictClass = $sResolvedPrefix;
		}

		$sBool = JbcItopFulltextSearchHelper::SanitizeBooleanQuery($sBody);
		if ($sBool === '') {
			return array();
		}

		$sTable = JbcItopFulltextSearchHelper::GetTableName();

		$sClassClause = '';
		if ($sRestrictClass !== null && $sRestrictClass !== '' && MetaModel::IsValidClass($sRestrictClass)) {
			$aLeaves = MetaModel::EnumChildClasses($sRestrictClass, ENUM_CHILD_CLASSES_ALL);
			$aConcrete = array();
			foreach ($aLeaves as $sLeaf) {
				if (!MetaModel::IsAbstract($sLeaf)) {
					$aConcrete[] = CMDBSource::Quote($sLeaf, true);
				}
			}
			if (count($aConcrete) === 0) {
				return array();
			}
			$sClassClause = ' AND `obj_class` IN ('.implode(',', $aConcrete).') ';
		}

		// BOOLEAN MODE treats "-" as a NOT operator between tokens (e.g. R-225598 breaks). Phrase quotes fix ticket refs.
		$aRaw = static::FetchMatchRows($sBool, $sClassClause, $sTable, $iLimit, true);
		if (count($aRaw) === 0) {
			$aRaw = static::FetchMatchRows($sBool, $sClassClause, $sTable, $iLimit, false);
		}
		if (count($aRaw) === 0) {
			$aRaw = static::FetchLikeRows($sBool, $sClassClause, $sTable, $iLimit);
		}

		$aRows = array();
		foreach ($aRaw as $aRow) {
			$sClass = $aRow['obj_class'];
			$iKey = (int) $aRow['obj_key'];
			$fRel = (float) $aRow['rel'];
			$fW = JbcItopFulltextSearchHelper::GetWeightForClass($sClass);
			if ($fW <= 0) {
				continue;
			}
			$aRows[] = array(
				'class' => $sClass,
				'id' => $iKey,
				'rel' => $fRel,
				'score' => $fRel * $fW,
			);
		}

		usort($aRows, function ($a, $b) {
			if ($a['score'] === $b['score']) {
				return 0;
			}

			return ($a['score'] > $b['score']) ? -1 : 1;
		});

		return static::FilterByReadRights($aRows);
	}

	/**
	 * Wraps tokens like R-225598 in double quotes for BOOLEAN MODE so "-" is not parsed as NOT.
	 */
	protected static function QuoteHyphenatedRefsForBoolean(string $sSanitized): string
	{
		return preg_replace_callback('/\b([A-Za-z][A-Za-z0-9]*-\d+)\b/u', static function (array $m): string {
			$sPhrase = str_replace(array('"', '\\'), '', $m[1]);

			return '"'.$sPhrase.'"';
		}, trim($sSanitized));
	}

	/**
	 * @return array<int, array{obj_class: string, obj_key: int|string, rel: float|string}>
	 */
	protected static function FetchMatchRows(string $sSanitized, string $sClassClause, string $sTable, int $iLimit, bool $bBooleanMode): array
	{
		$sExpr = $bBooleanMode ? static::QuoteHyphenatedRefsForBoolean($sSanitized) : $sSanitized;
		$sAgainst = CMDBSource::Quote($sExpr, true);
		$sMode = $bBooleanMode ? 'BOOLEAN MODE' : 'NATURAL LANGUAGE MODE';
		$sSql = <<<SQL
SELECT `obj_class`, `obj_key`, MATCH(`doc`) AGAINST ($sAgainst IN $sMode) AS rel
FROM `$sTable`
WHERE MATCH(`doc`) AGAINST ($sAgainst IN $sMode) $sClassClause
ORDER BY rel DESC
LIMIT $iLimit
SQL;

		$oRes = CMDBSource::Query($sSql);
		if (!$oRes) {
			return array();
		}

		$aOut = array();
		while ($aRow = $oRes->fetch_assoc()) {
			$aOut[] = $aRow;
		}

		return $aOut;
	}

	/**
	 * Last resort when FULLTEXT tokenization does not match (short tokens, hyphens, etc.).
	 *
	 * @return array<int, array{obj_class: string, obj_key: int|string, rel: float|string}>
	 */
	protected static function FetchLikeRows(string $sSanitized, string $sClassClause, string $sTable, int $iLimit): array
	{
		$s = trim($sSanitized);
		$iLen = strlen($s);
		if ($iLen < 2 || $iLen > 128) {
			return array();
		}

		$sEsc = str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $s);
		$sPattern = '%'.$sEsc.'%';
		$sQuoted = CMDBSource::Quote($sPattern, true);

		$sSql = <<<SQL
SELECT `obj_class`, `obj_key`, 1.0 AS rel
FROM `$sTable`
WHERE `doc` LIKE $sQuoted $sClassClause
LIMIT $iLimit
SQL;

		$oRes = CMDBSource::Query($sSql);
		if (!$oRes) {
			return array();
		}

		$aOut = array();
		while ($aRow = $oRes->fetch_assoc()) {
			$aOut[] = $aRow;
		}

		return $aOut;
	}

	/**
	 * Parses leading "ClassName: query" (same idea as UI.php full_text).
	 *
	 * @param string $sText in/out remaining text after class prefix
	 *
	 * @return string|null resolved PHP class name or null
	 */
	public static function ExtractClassRestriction(string &$sText): ?string
	{
		if (preg_match('/^([^:]+):(.+)$/u', $sText, $aMatches)) {
			$sCandidate = trim($aMatches[1]);
			$sRest = trim($aMatches[2]);
			if (MetaModel::IsValidClass($sCandidate)) {
				$sText = $sRest;

				return $sCandidate;
			}
			$sResolved = MetaModel::GetClassFromLabel($sCandidate, false);
			if ($sResolved !== false && MetaModel::IsValidClass($sResolved)) {
				$sText = $sRest;

				return $sResolved;
			}
		}

		return null;
	}

	/**
	 * @param array<int, array{class: string, id: int, score: float, rel: float}> $aRows
	 *
	 * @return array<int, array{class: string, id: int, score: float, rel: float}>
	 */
	protected static function FilterByReadRights(array $aRows): array
	{
		$aOut = array();
		foreach ($aRows as $aRow) {
			$sClass = $aRow['class'];
			$iId = $aRow['id'];
			try {
				$oObj = MetaModel::GetObject($sClass, $iId, false);
			} catch (Exception $e) {
				continue;
			}
			if ($oObj === null) {
				continue;
			}

			$oSet = DBObjectSet::FromObject($oObj);
			if (UserRights::IsActionAllowed($sClass, UR_ACTION_READ, $oSet) == UR_ALLOWED_NO) {
				continue;
			}

			$aOut[] = $aRow;
		}

		return $aOut;
	}

	/**
	 * Group hits by class for rendering.
	 *
	 * @param array<int, array{class: string, id: int, score: float, rel: float}> $aHits
	 *
	 * @return array<string, int[]> class => list of ids (order preserved from hits)
	 */
	public static function GroupIdsByClass(array $aHits): array
	{
		return static::GroupIdsByClassOrdered($aHits);
	}

	/**
	 * Preserves relevance order within each class (first occurrence wins).
	 *
	 * @param array<int, array{class: string, id: int, score: float, rel: float}> $aHits
	 *
	 * @return array<string, int[]>
	 */
	public static function GroupIdsByClassOrdered(array $aHits): array
	{
		$aGrouped = array();
		foreach ($aHits as $aRow) {
			$sClass = $aRow['class'];
			$iId = (int) $aRow['id'];
			if (!isset($aGrouped[$sClass])) {
				$aGrouped[$sClass] = array();
			}
			if (!in_array($iId, $aGrouped[$sClass], true)) {
				$aGrouped[$sClass][] = $iId;
			}
		}

		return $aGrouped;
	}
}
