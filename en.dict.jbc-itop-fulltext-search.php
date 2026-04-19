<?php
/**
 * @copyright Copyright (C) 2010-2023 JBC / iTop
 * @license   http://opensource.org/licenses/AGPL-3.0
 */
Dict::Add('EN US', 'English', 'en', array(
	'JbcItopFulltextSearch:PageTitle' => 'Search (fulltext)',
	'JbcItopFulltextSearch:PageTitle+' => 'Global search using the MySQL FULLTEXT index (jbc-itop-fulltext-search).',
	'JbcItopFulltextSearch:Disabled' => 'The fulltext search module is disabled in the configuration.',
	'JbcItopFulltextSearch:SearchError' => 'The fulltext search could not be completed. See the log for details.',

	'Menu:JbcFulltextRebuildMenu' => 'Rebuild FULLTEXT search index',
	'Menu:JbcFulltextRebuildMenu+' => 'Truncate and rebuild the MySQL FULLTEXT auxiliary table used for global search.',
	'JbcItopFulltextSearch:RebuildTitle' => 'Rebuild FULLTEXT index',
	'JbcItopFulltextSearch:RebuildTitle+' => 'Rebuild the MySQL FULLTEXT auxiliary table for global search.',
	'JbcItopFulltextSearch:RebuildIntro' => 'This operation truncates the fulltext index table and re-indexes all eligible objects. It may take several minutes.',
	'JbcItopFulltextSearch:RebuildSubmit' => 'Start rebuild',
	'JbcItopFulltextSearch:RebuildDone' => 'Rebuild finished. Objects indexed: %1$s. Duration: %2$s s.',
	'JbcItopFulltextSearch:RebuildFailed' => 'The rebuild failed. See the application log for details.',
	'JbcItopFulltextSearch:RebuildForbidden' => 'You are not allowed to run this action (administrators only).',
));
