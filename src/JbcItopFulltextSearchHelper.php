<?php

/**
 * Shared helpers and configuration access for jbc-itop-fulltext-search.
 */
class JbcItopFulltextSearchHelper
{
	public const MODULE_CODE = 'jbc-itop-fulltext-search';

	public static function GetTableName(): string
	{
		return 'JBC_fulltext_doc';
	}

	public static function IsEnabled(): bool
	{
		try {
			return (bool) MetaModel::GetModuleSetting(static::MODULE_CODE, 'enabled', true);
		} catch (Exception $e) {
			return true;
		}
	}

	/**
	 * Absolute URL for pages/exec.php targeting this module's search page (includes exec_env).
	 */
	public static function GetExecSearchAbsoluteUrl(): string
	{
		$sEnv = utils::GetCurrentEnvironment();

		return utils::GetAbsoluteUrlAppRoot()
			.'pages/exec.php?exec_module='.rawurlencode(static::MODULE_CODE)
			.'&exec_page='.rawurlencode('pages/search.php')
			.'&exec_env='.rawurlencode($sEnv);
	}

	public static function GetExcludedClasses(): array
	{
		try {
			$a = MetaModel::GetModuleSetting(static::MODULE_CODE, 'excluded_classes', []);
			return is_array($a) ? $a : [];
		} catch (Exception $e) {
			return [];
		}
	}

	public static function GetIncludedClasses(): array
	{
		try {
			$a = MetaModel::GetModuleSetting(static::MODULE_CODE, 'included_classes', []);
			return is_array($a) ? $a : [];
		} catch (Exception $e) {
			return [];
		}
	}

	public static function GetMaxDocumentChars(): int
	{
		try {
			return max(4096, (int) MetaModel::GetModuleSetting(static::MODULE_CODE, 'max_document_chars', 65535));
		} catch (Exception $e) {
			return 65535;
		}
	}

	public static function GetClassWeights(): array
	{
		try {
			$a = MetaModel::GetModuleSetting(static::MODULE_CODE, 'object_weight_factor', []);
			return is_array($a) ? $a : [];
		} catch (Exception $e) {
			return [];
		}
	}

	public static function GetWeightForClass(string $sClass): float
	{
		$a = static::GetClassWeights();
		if (isset($a[$sClass])) {
			return (float) $a[$sClass];
		}

		return 1.0;
	}

	public static function ShouldIndexClass(string $sClass): bool
	{
		if (!MetaModel::IsValidClass($sClass)) {
			return false;
		}
		if (!MetaModel::HasCategory($sClass, 'searchable')) {
			return false;
		}
		if (MetaModel::IsAbstract($sClass)) {
			return false;
		}

		foreach (static::GetExcludedClasses() as $sExcluded) {
			if (!MetaModel::IsValidClass($sExcluded)) {
				continue;
			}
			if ($sClass === $sExcluded || is_a($sClass, $sExcluded, true)) {
				return false;
			}
		}

		$aIncluded = static::GetIncludedClasses();
		if (count($aIncluded) > 0) {
			$bOk = false;
			foreach ($aIncluded as $sInc) {
				if (!MetaModel::IsValidClass($sInc)) {
					continue;
				}
				if ($sClass === $sInc || is_a($sClass, $sInc, true)) {
					$bOk = true;
					break;
				}
			}
			if (!$bOk) {
				return false;
			}
		}

		return true;
	}

	public static function SanitizeBooleanQuery(string $sQuery): string
	{
		$sQuery = trim($sQuery);
		if (strlen($sQuery) > 512) {
			$sQuery = substr($sQuery, 0, 512);
		}

		return preg_replace('/[^\p{L}\p{N}\s\+\-\*\"_@\.]/u', ' ', $sQuery);
	}
}
