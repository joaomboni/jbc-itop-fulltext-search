#!/usr/bin/env php
<?php

/**
 * Rebuild the FULLTEXT index for all searchable objects (CLI).
 *
 * Usage (from iTop root):
 *   php extensions/jbc-itop-fulltext-search/script/populate-index.php
 *
 * Requires the same config as the web app: conf/{env}/config-itop.php (default env: production).
 *
 * @copyright JBC / AGPL
 */

if (php_sapi_name() !== 'cli') {
	echo "CLI only.\n";
	exit(1);
}

// iTop core still triggers many PHP 8.4 "implicitly nullable" deprecations; keep CLI output readable.
if (defined('E_DEPRECATED') && defined('E_USER_DEPRECATED')) {
	error_reporting(E_ALL & ~(E_DEPRECATED | E_USER_DEPRECATED));
}

$sRoot = dirname(__FILE__).'/../../../';
$sRootReal = realpath($sRoot);
if ($sRootReal !== false) {
	$sRoot = $sRootReal.'/';
} else {
	$sRoot = rtrim($sRoot, '/').'/';
}

require_once $sRoot.'approot.inc.php';
require_once APPROOT.'/application/application.inc.php';

$sExpectedConfig = APPCONF.ITOP_DEFAULT_ENV.'/'.ITOP_CONFIG_FILE;
if (!is_readable($sExpectedConfig)) {
	fwrite(STDERR, "Configuration file missing or unreadable:\n  {$sExpectedConfig}\n\n");
	fwrite(STDERR, 'This script loads iTop like cron/webservices: `conf/'.ITOP_DEFAULT_ENV.'/'.ITOP_CONFIG_FILE."` must exist.\n");
	fwrite(STDERR, "Usually it is created by Setup. If you only have toolkit/dev config:\n");
	if (is_readable(APPCONF.'toolkit/'.ITOP_CONFIG_FILE)) {
		fwrite(STDERR, '  mkdir -p '.APPCONF.ITOP_DEFAULT_ENV."\n");
		fwrite(STDERR, '  cp '.APPCONF.'toolkit/'.ITOP_CONFIG_FILE.' '.$sExpectedConfig."\n");
	}
	exit(1);
}

require_once APPROOT.'/application/startup.inc.php';

$oCtx = new ContextTag(ContextTag::TAG_CRON);

$iTotal = JbcItopFulltextSearchPopulateRunner::Run(static function (int $iTotal) {
	if ($iTotal % 500 === 0) {
		echo "Indexed $iTotal objects...\n";
	}
});

echo "Done. Indexed approximately $iTotal row updates.\n";
exit(0);
