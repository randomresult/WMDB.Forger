<?php
namespace WMDB\Forger\ViewHelpers;

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class LowercaseViewHelper
 * @package WMDB\Forger\ViewHelpers
 */
class LowercaseViewHelper extends AbstractViewHelper {

	/**
	 * <f:form.checkbox id="{title -> wmdb:lowercase()}_{index}" name="filters[{title -> wmdb:lowercase()}][]" checked="{{title -> wmdb:lowercase({bucket.key})}}" value="true" />
	 * <label for="{title -> wmdb:lowercase()}_{index}">{bucket.key} ({bucket.doc_count})</label>
	 * @param string $category
	 * @param string $index
	 * @param string $bucket
	 * @param int $docCount
	 * @return string
	 */
	public function render($category = '', $index = '', $bucket = '', $docCount = 0) {
		$regularBucket = $bucket;
		$category = $this->fixWording($category);
		$bucket = $this->fixWording($bucket, false);

		// check if checkbox has been set
		if(isset($_GET['filters'][$category][$bucket]) && $_GET['filters'][$category][$bucket] === 'true') {
			$checked = ' checked';
			$labelStyle = ' style="font-weight:bold; color:#008cba;"';
		} else {
			$checked = '';
			$labelStyle = '';
		}

		$content = '
		<input type="checkbox" id="'.$category.'-'.$index.'" name="filters['.$category.']['.$bucket.']"'.$checked.' value="true" />
		<label'.$labelStyle.' for="'.$category.'-'.$index.'">'.$regularBucket.' ('.$docCount.')</label>
		';
		return $content;
	}

	/**
	 * @param $in
	 * @return mixed|string
	 */
	private function fixWording($in, $lowercase = true) {
		$in = str_replace(' ', '_', $in);
		if ($lowercase) {
			$in = strtolower($in);
		}
		return $in;
	}
}