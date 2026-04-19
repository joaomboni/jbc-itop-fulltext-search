<?php

/**
 * Rebuilds the FULLTEXT auxiliary table (same logic as script/populate-index.php).
 */
class JbcItopFulltextSearchPopulateRunner
{
	/**
	 * Truncate and re-index all configured searchable objects.
	 *
	 * @param callable|null $fnProgress function (int $totalSoFar): void
	 *
	 * @return int number of objects successfully upserted
	 */
	public static function Run(?callable $fnProgress = null): int
	{
		$sTable = JbcItopFulltextSearchHelper::GetTableName();
		CMDBSource::Query("TRUNCATE TABLE `$sTable`");

		$iTotal = 0;
		foreach (MetaModel::GetClasses('searchable') as $sClass) {
			if (MetaModel::IsAbstract($sClass)) {
				continue;
			}
			if (!JbcItopFulltextSearchHelper::ShouldIndexClass($sClass)) {
				continue;
			}

			$oSearch = new DBObjectSearch($sClass);
			$oSearch->AllowAllData(true);
			$oSet = new DBObjectSet($oSearch);
			while ($oObj = $oSet->Fetch()) {
				try {
					JbcItopFulltextIndexer::UpsertObject($oObj);
					$iTotal++;
					if ($fnProgress !== null) {
						$fnProgress($iTotal);
					}
				} catch (Exception $e) {
					IssueLog::Warning(
						'jbc-itop-fulltext-search: populate skip '.get_class($oObj).'#'.$oObj->GetKey().': '.$e->getMessage(),
						LogChannels::CLI
					);
				}
			}
		}

		return $iTotal;
	}
}
