<?php

/**
 * Builds consolidated text for objects and stores in the FULLTEXT table.
 */
class JbcItopFulltextIndexer
{
	/**
	 * @param \DBObject $oObject
	 */
	public static function BuildDocument($oObject): string
	{
		$sClass = get_class($oObject);
		$aParts = array();

		try {
			$sName = $oObject->GetName();
			if (is_string($sName) && $sName !== '') {
				$aParts[] = $sName;
			}
		} catch (Exception $e) {
			// ignore
		}

		foreach (MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef) {
			if (!$oAttDef->IsScalar()) {
				continue;
			}
			if ($oAttDef->IsExternalKey()) {
				continue;
			}
			if (method_exists($oAttDef, 'IsExternalField') && $oAttDef->IsExternalField()) {
				continue;
			}
			if (!$oAttDef->IsSearchable()) {
				continue;
			}

			try {
				$mVal = $oObject->Get($sAttCode);
				if ($oAttDef->IsNull($mVal)) {
					continue;
				}
				$sText = self::NormalizeValue($mVal);
				if ($sText !== '') {
					$aParts[] = $sText;
				}
			} catch (Exception $e) {
				continue;
			}
		}

		$sDoc = implode("\n", $aParts);
		$sDoc = strip_tags($sDoc);
		$iMax = JbcItopFulltextSearchHelper::GetMaxDocumentChars();
		if (strlen($sDoc) > $iMax) {
			$sDoc = substr($sDoc, 0, $iMax);
		}

		return $sDoc;
	}

	/**
	 * @param mixed $mVal
	 */
	protected static function NormalizeValue($mVal): string
	{
		if ($mVal === null) {
			return '';
		}
		if (is_scalar($mVal)) {
			return trim((string) $mVal);
		}
		if (is_array($mVal)) {
			return trim(implode(' ', array_map('strval', $mVal)));
		}

		return '';
	}

	/**
	 * @param \DBObject $oObject
	 *
	 * @throws \CoreException|\MySQLException
	 */
	public static function UpsertObject($oObject): void
	{
		if (!JbcItopFulltextSearchHelper::IsEnabled()) {
			return;
		}

		$sClass = get_class($oObject);
		if (!JbcItopFulltextSearchHelper::ShouldIndexClass($sClass)) {
			static::DeleteByKey($sClass, (int) $oObject->GetKey());

			return;
		}

		$sDoc = static::BuildDocument($oObject);
		if (trim($sDoc) === '') {
			static::DeleteByKey($sClass, (int) $oObject->GetKey());

			return;
		}

		$sTable = JbcItopFulltextSearchHelper::GetTableName();
		$iKey = (int) $oObject->GetKey();

		$sClassSQL = CMDBSource::Quote($sClass, true);
		$sDocSQL = CMDBSource::Quote($sDoc, true);

		$sSql = <<<SQL
INSERT INTO `$sTable` (`obj_class`, `obj_key`, `doc`)
VALUES ($sClassSQL, $iKey, $sDocSQL)
ON DUPLICATE KEY UPDATE `doc` = VALUES(`doc`)
SQL;

		CMDBSource::Query($sSql);
	}

	public static function DeleteByKey(string $sClass, int $iKey): void
	{
		$sTable = JbcItopFulltextSearchHelper::GetTableName();
		$sClassSQL = CMDBSource::Quote($sClass, true);
		$sSql = "DELETE FROM `$sTable` WHERE `obj_class` = $sClassSQL AND `obj_key` = ".(int) $iKey;
		CMDBSource::Query($sSql);
	}
}
