<?php

/**
 * Integração **sem alterar ficheiros do núcleo iTop**: configure o PHP para executar este ficheiro **antes**
 * de cada script (apenas no virtual host da aplicação).
 *
 * Apache (exemplo):
 *
 *   php_admin_value auto_prepend_file "/caminho/absoluto/para/extensions/jbc-itop-fulltext-search/include/http-bootstrap.php"
 *
 * PHP-FPM pool (exemplo):
 *
 *   php_value[auto_prepend_file] = /caminho/absoluto/para/extensions/jbc-itop-fulltext-search/include/http-bootstrap.php
 *
 * Comportamento:
 * - `pages/exec.php?text=…` sem `exec_module` → redirecionamento 302 para o URL completo do módulo.
 * - `pages/UI.php?operation=full_text&text=…` → idem (evita o fluxo nativo lento).
 *
 * Não usa MetaModel (corre antes do `approot.inc.php`).
 */

if (\PHP_SAPI === 'cli') {
	return;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
	return;
}

$sScript = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
$sQueryString = $_SERVER['QUERY_STRING'] ?? '';
$aGet = array();
if ($sQueryString !== '') {
	parse_str($sQueryString, $aGet);
}

$sTrimText = static function ($s): string {
	$s = trim((string) $s);

	return (strlen($s) > 512) ? substr($s, 0, 512) : $s;
};

if ($sScript === 'exec.php') {
	if (!empty($aGet['exec_module'])) {
		return;
	}
	$sText = isset($aGet['text']) ? $sTrimText($aGet['text']) : '';
	if ($sText === '') {
		return;
	}
	$aParams = array(
		'exec_module' => 'jbc-itop-fulltext-search',
		'exec_page' => 'pages/search.php',
		'text' => $sText,
	);
	if (isset($aGet['tune']) && (string) $aGet['tune'] !== '' && (string) $aGet['tune'] !== '0') {
		$aParams['tune'] = $aGet['tune'];
	}
	header('Location: exec.php?'.http_build_query($aParams), true, 302);
	exit;
}

if ($sScript === 'UI.php') {
	if (($aGet['operation'] ?? '') !== 'full_text') {
		return;
	}
	$sText = isset($aGet['text']) ? $sTrimText($aGet['text']) : '';
	if ($sText === '') {
		return;
	}
	$aParams = array(
		'exec_module' => 'jbc-itop-fulltext-search',
		'exec_page' => 'pages/search.php',
		'text' => $sText,
	);
	if (isset($aGet['tune']) && (string) $aGet['tune'] !== '' && (string) $aGet['tune'] !== '0') {
		$aParams['tune'] = $aGet['tune'];
	}
	header('Location: exec.php?'.http_build_query($aParams), true, 302);
	exit;
}
