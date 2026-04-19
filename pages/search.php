<?php

/**
 * Fast global search results (FULLTEXT index).
 *
 * Invoked via pages/exec.php?exec_module=jbc-itop-fulltext-search&exec_page=pages%2Fsearch.php
 *
 * @copyright JBC / iTop AGPL
 */

require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');
require_once(APPROOT.'/application/displayblock.class.inc.php');
require_once(APPROOT.'/application/loginwebpage.class.inc.php');

IssueLog::Trace('----- jbc-itop-fulltext-search/search: '.utils::GetRequestUri(), LogChannels::WEB_REQUEST);

try {
	new ContextTag(ContextTag::TAG_CONSOLE);
	$sLoginMessage = LoginWebPage::DoLogin();

	$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:PageTitle'));
	$oP->SetBreadCrumbEntry('jbc-itop-fulltext-search', Dict::S('JbcItopFulltextSearch:PageTitle'), Dict::S('JbcItopFulltextSearch:PageTitle+'), '', 'fas fa-search',
		iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);

	if ($sLoginMessage !== '') {
		$oP->p($sLoginMessage);
	}

	$sQuery = trim(utils::ReadParam('text', '', false, 'raw_data'));

	if ($sQuery === '') {
		$oP->p(Dict::S('UI:Search:NoSearch'));
		$oP->output();

		return;
	}

	if (!JbcItopFulltextSearchHelper::IsEnabled()) {
		$oP->p(Dict::S('JbcItopFulltextSearch:Disabled'));
		$oP->output();

		return;
	}

	try {
		$aHits = JbcItopFulltextSearchService::Search($sQuery);
	} catch (Exception $e) {
		IssueLog::Error('jbc-itop-fulltext-search search failed: '.$e->getMessage(), LogChannels::CORE);
		$oP->p(Dict::S('JbcItopFulltextSearch:SearchError'));
		$oP->output();

		return;
	}

	$oP->set_title(Dict::S('UI:SearchResultsPageTitle'));
	$oP->add('<div class="search-page-padded" style="padding:10px;">');
	$oP->add('<h2>'.Dict::Format('UI:FullTextSearchTitle_Text', utils::EscapeHtml($sQuery)).'</h2>');

	if (count($aHits) === 0) {
		$oP->add('<p>'.utils::EscapeHtml(Dict::S('UI:Search:NoObjectFound')).'</p>');
		$oP->add('</div>');
		$oP->output();

		return;
	}

	$aGrouped = JbcItopFulltextSearchService::GroupIdsByClassOrdered($aHits);

	foreach ($aGrouped as $sClassName => $aLeafs) {
		if (count($aLeafs) === 0) {
			continue;
		}

		$oLeafsFilter = new DBObjectSearch($sClassName);
		$oLeafsFilter->AddCondition('id', $aLeafs, 'IN');

		$oP->add("<div class=\"search-class-result search-class-$sClassName\">\n");
		$oP->add('<div class="page_header">');
		$oP->add('<h2 class="ibo-global-search--result--title">'.MetaModel::GetClassIcon($sClassName).Dict::Format('UI:Search:Count_ObjectsOf_Class_Found', count($aLeafs),
				MetaModel::GetName($sClassName)).'</h2>');
		$oP->add('</div>');

		$oBlock = new DisplayBlock($oLeafsFilter, 'list', false);
		$sBlockId = 'JBC_fulltext_search_'.$sClassName;
		$oP->add('<div id="'.$sBlockId.'">');
		$oBlock->RenderContent($oP, array('table_id' => $sBlockId, 'currentId' => $sBlockId));
		$oP->add('</div>');
		$oP->add('</div>');
		$oP->p('&nbsp;');
	}

	$oP->add('</div>');
	$oP->output();
} catch (Exception $e) {
	IssueLog::Error('jbc-itop-fulltext-search page: '.$e->getMessage(), LogChannels::CORE);
	if (!headers_sent()) {
		http_response_code(500);
	}
	echo '<p>Search error</p>';
}
