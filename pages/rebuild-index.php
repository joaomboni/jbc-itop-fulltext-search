<?php

/**
 * Rebuild the FULLTEXT index (same as script/populate-index.php).
 *
 * Invoked via exec.php — menu "JbcFulltextRebuildMenu" (admins only) links here.
 */

require_once(APPROOT.'/application/application.inc.php');
require_once(APPROOT.'/application/startup.inc.php');
require_once(APPROOT.'/application/loginwebpage.class.inc.php');

IssueLog::Trace('----- jbc-itop-fulltext-search/rebuild-index: '.utils::GetRequestUri(), LogChannels::WEB_REQUEST);

$sRemote = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

try {
	new ContextTag(ContextTag::TAG_CRON);

	$sLoginMessage = LoginWebPage::DoLogin(true);
	if ($sLoginMessage !== '') {
		$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:RebuildTitle'));
		$oP->p($sLoginMessage);
		$oP->output();
		exit;
	}

	if (!UserRights::IsAdministrator()) {
		IssueLog::Warning(
			'jbc-itop-fulltext-search: rebuild denied (not administrator)',
			LogChannels::CORE,
			array('remote' => $sRemote)
		);
		if (!headers_sent()) {
			http_response_code(403);
		}
		$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:RebuildTitle'));
		$oP->p(Dict::S('JbcItopFulltextSearch:RebuildForbidden'));
		$oP->output();
		exit;
	}

	$sOp = utils::ReadParam('operation', '', false, 'raw_data');

	if ($sOp !== 'exec') {
		$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:RebuildTitle'));
		$oP->SetBreadCrumbEntry('jbc-itop-fulltext-search-rebuild', Dict::S('JbcItopFulltextSearch:RebuildTitle'), Dict::S('JbcItopFulltextSearch:RebuildTitle+'), '', 'fas fa-database',
			iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);
		$oP->p(Dict::S('JbcItopFulltextSearch:RebuildIntro'));
		$sAction = utils::EscapeHtml(utils::GetAbsoluteUrlExecPage());
		$oP->add('<form method="post" action="'.$sAction.'">');
		$oP->add('<input type="hidden" name="exec_module" value="'.utils::EscapeHtml(JbcItopFulltextSearchHelper::MODULE_CODE).'" />');
		$oP->add('<input type="hidden" name="exec_page" value="pages/rebuild-index.php" />');
		$oP->add('<input type="hidden" name="exec_env" value="'.utils::EscapeHtml(utils::GetCurrentEnvironment()).'" />');
		$oP->add('<input type="hidden" name="operation" value="exec" />');
		$oP->add('<p><button type="submit" class="ibo-btn ibo-is-primary">'.utils::EscapeHtml(Dict::S('JbcItopFulltextSearch:RebuildSubmit')).'</button></p>');
		$oP->add('</form>');
		$oP->output();
		exit;
	}

	IssueLog::Info(
		'jbc-itop-fulltext-search: FULLTEXT rebuild started',
		LogChannels::CLI,
		array('remote' => $sRemote)
	);

	$iStart = microtime(true);
	try {
		$iTotal = JbcItopFulltextSearchPopulateRunner::Run();
	} catch (Throwable $e) {
		IssueLog::Error(
			'jbc-itop-fulltext-search: FULLTEXT rebuild failed: '.$e->getMessage(),
			LogChannels::CORE,
			array('remote' => $sRemote)
		);
		if (!headers_sent()) {
			http_response_code(500);
		}
		$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:RebuildTitle'));
		$oP->p(Dict::S('JbcItopFulltextSearch:RebuildFailed'));
		$oP->output();
		exit;
	}

	$fElapsed = microtime(true) - $iStart;
	IssueLog::Info(
		'jbc-itop-fulltext-search: FULLTEXT rebuild finished count='.$iTotal.' seconds='.round($fElapsed, 3),
		LogChannels::CLI,
		array('remote' => $sRemote)
	);

	$oP = new iTopWebPage(Dict::S('JbcItopFulltextSearch:RebuildTitle'));
	$oP->SetBreadCrumbEntry('jbc-itop-fulltext-search-rebuild', Dict::S('JbcItopFulltextSearch:RebuildTitle'), Dict::S('JbcItopFulltextSearch:RebuildTitle+'), '', 'fas fa-database',
		iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);
	$oP->p(Dict::Format('JbcItopFulltextSearch:RebuildDone', $iTotal, round($fElapsed, 2)));
	$oP->output();
} catch (Throwable $e) {
	IssueLog::Error('jbc-itop-fulltext-search rebuild-index: '.$e->getMessage(), LogChannels::CORE);
	if (!headers_sent()) {
		http_response_code(500);
	}
	echo '<p>Error</p>';
}
