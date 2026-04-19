<?php

/**
 * @deprecated Prefer `include/http-bootstrap.php` via PHP `auto_prepend_file` (integração sem patch em `pages/UI.php`).
 *
 * When enabled, bypass native UI.php full_text and redirect to exec.php search.php with the same query parameters.
 */

if (!class_exists('JbcItopFulltextSearchHelper', false)) {
	return;
}

if (!JbcItopFulltextSearchHelper::IsEnabled()) {
	return;
}

$sQuery = trim(utils::ReadParam('text', '', false, 'raw_data'));
if ($sQuery === '') {
	return;
}

$aParams = [
	'exec_module' => 'jbc-itop-fulltext-search',
	'exec_page' => 'pages/search.php',
	'text' => $sQuery,
	'exec_env' => utils::GetCurrentEnvironment(),
];

$iTune = (int) utils::ReadParam('tune', 0);
if ($iTune !== 0) {
	$aParams['tune'] = $iTune;
}

$sUrl = rtrim(utils::GetAbsoluteUrlAppRoot(), '/').'/pages/exec.php?'.http_build_query($aParams);
header('Location: '.$sUrl, true, 302);
exit;
