<?php

/**
 * @deprecated Prefer `include/http-bootstrap.php` via PHP `auto_prepend_file` (integração sem patch em `pages/exec.php`).
 *
 * Recovery for requests to pages/exec.php?text=… without exec_module/exec_page (broken forms, caches, bookmarks).
 */

if (utils::ReadParam('exec_module', '') !== '') {
	return;
}

$sText = utils::ReadParam('text', '', false, 'raw_data');
if ($sText === '') {
	return;
}

$sQuery = http_build_query([
	'exec_module' => 'jbc-itop-fulltext-search',
	'exec_page' => 'pages/search.php',
	'text' => $sText,
]);

header('Location: exec.php?'.$sQuery, true, 302);
exit;
