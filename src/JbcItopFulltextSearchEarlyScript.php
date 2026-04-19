<?php

/**
 * Substitui o destino do formulário da lupa (global search) para exec.php do módulo, sem override Twig no core.
 *
 * @see iBackofficeEarlyScriptExtension
 */
class JbcItopFulltextSearchEarlyScript implements iBackofficeEarlyScriptExtension
{
	/**
	 * @inheritDoc
	 */
	public function GetEarlyScript(): string
	{
		if (!class_exists('JbcItopFulltextSearchHelper', false) || !JbcItopFulltextSearchHelper::IsEnabled()) {
			return '';
		}

		$sJsBase = $this->QuoteJs(JbcItopFulltextSearchHelper::GetExecSearchAbsoluteUrl());

		return <<<JS
<script type="text/javascript">
(function () {
	var sBase = {$sJsBase};
	function patchForms() {
		document.querySelectorAll('form[data-role="ibo-global-search--head"]').forEach(function (f) {
			if (f.getAttribute('data-JBC-ft')) {
				return;
			}
			f.setAttribute('data-JBC-ft', '1');
			try {
				var o = new URL(sBase);
				f.setAttribute('action', o.pathname + o.search);
			} catch (e) {
				f.setAttribute('action', sBase);
			}
			var op = f.querySelector('input[name="operation"]');
			if (op) {
				op.remove();
			}
		});
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', patchForms);
	} else {
		patchForms();
	}
	if (typeof MutationObserver !== 'undefined') {
		new MutationObserver(patchForms).observe(document.documentElement, { childList: true, subtree: true });
	}
})();
</script>
JS;
	}

	protected function QuoteJs(string $s): string
	{
		return json_encode($s, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
	}
}
