<?php
//
// iTop module: jbc-itop-fulltext-search — MySQL FULLTEXT based global search acceleration
//

SetupWebPage::AddModule(
	__FILE__,
	'jbc-itop-fulltext-search/1.0.0',
	array(
		'label' => 'JBC iTop Fulltext search (MySQL)',
		'category' => 'search',

		'dependencies' => array(
			'itop-structure/3.1.1',
		),
		'mandatory' => false,
		'visible' => true,
		'installer' => 'JbcItopFulltextSearchInstaller',

		'datamodel' => array(
			'datamodel.jbc-itop-fulltext-search.xml',
			'model.jbc-itop-fulltext-search.php',
			'en.dict.jbc-itop-fulltext-search.php',
			'pt_br.dict.jbc-itop-fulltext-search.php',
			'src/JbcItopFulltextSearchHelper.php',
			'src/JbcItopFulltextIndexer.php',
			'src/JbcItopFulltextSearchService.php',
			'src/JbcItopFulltextPlugin.php',
			'src/JbcItopFulltextSearchEarlyScript.php',
			'src/JbcItopFulltextSearchPopulateRunner.php',
		),
		'webservice' => array(
		),
		'data.struct' => array(
		),
		'data.sample' => array(
		),
		'doc.manual_setup' => '',
		'doc.more_information' => '',

		'settings' => array(
			'enabled' => true,
			'max_document_chars' => 65535,
			'excluded_classes' => array(),
			'included_classes' => array(),
			'object_weight_factor' => array(),
		),
	)
);

if (!class_exists('JbcItopFulltextSearchInstaller', false)) {
	class JbcItopFulltextSearchInstaller extends ModuleInstallerAPI
	{
		public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
		{
			$sTable = 'JBC_fulltext_doc';
			$sSql = <<<SQL
CREATE TABLE IF NOT EXISTS `$sTable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `obj_class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `obj_key` int(11) NOT NULL,
  `doc` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_obj` (`obj_class`,`obj_key`),
  FULLTEXT KEY `ft_doc` (`doc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
			try {
				CMDBSource::Query($sSql);
				SetupLog::Info('jbc-itop-fulltext-search: table '.$sTable.' ensured.');
			} catch (Exception $e) {
				SetupLog::Error('jbc-itop-fulltext-search: failed creating table: '.$e->getMessage());
			}
		}
	}
}
