<?php
namespace WMDB\Forger\ViewHelpers;

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class BucketCounterViewHelper
 * @package WMDB\Forger\ViewHelpers
 */
class BucketCounterViewHelper extends AbstractViewHelper {

	/**
	 * @param $category
	 * @param $buckets
	 *
	 * @return string
	 */
	public function render($category, $buckets) {
		$category = $this->fixWording($category);
		$activeCount = 0;
		foreach ($buckets as $bucket) {
			$key = $this->fixWording($bucket['key']);
			if (isset($_GET['filters'][$category][$key]) && $_GET['filters'][$category][$key] === 'true') {
				$activeCount++;
			}
		}
		return ($activeCount > 0) ? '<span class="label label-default pull-right">' . $activeCount . ' / ' . count($buckets) . '</span>' : '';
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