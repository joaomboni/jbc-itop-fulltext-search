<?php

/**
 * Keeps the FULLTEXT table in sync with CMDB changes.
 */
class JbcItopFulltextPlugin extends AbstractApplicationObjectExtension
{
	public function OnDBInsert($oObject, $oChange = null)
	{
		try {
			JbcItopFulltextIndexer::UpsertObject($oObject);
		} catch (Exception $e) {
			IssueLog::Error('jbc-itop-fulltext-search: OnDBInsert failed: '.$e->getMessage(), LogChannels::CORE);
		}
	}

	public function OnDBUpdate($oObject, $oChange = null)
	{
		try {
			JbcItopFulltextIndexer::UpsertObject($oObject);
		} catch (Exception $e) {
			IssueLog::Error('jbc-itop-fulltext-search: OnDBUpdate failed: '.$e->getMessage(), LogChannels::CORE);
		}
	}

	public function OnDBDelete($oObject, $oChange = null)
	{
		try {
			$sClass = get_class($oObject);
			$iKey = (int) $oObject->GetKey();
			JbcItopFulltextIndexer::DeleteByKey($sClass, $iKey);
		} catch (Exception $e) {
			IssueLog::Error('jbc-itop-fulltext-search: OnDBDelete failed: '.$e->getMessage(), LogChannels::CORE);
		}
	}
}
