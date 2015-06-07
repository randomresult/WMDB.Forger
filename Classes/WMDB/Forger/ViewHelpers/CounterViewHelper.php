<?php
namespace WMDB\Forger\ViewHelpers;

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class CounterViewHelper
 * @package WMDB\Forger\ViewHelpers
 */
class CounterViewHelper extends AbstractViewHelper {

	/**
	 * @param int $increase
	 * @param bool $output
	 * @param string $id
	 *
	 * @return int
	 */
	public function render($increase = 0, $output = FALSE, $id = NULL) {
		$counterId = 'CounterViewHelper_counter';
		if ($id !== NULL) {
			$counterId .= ':' . $id;
		}
		if (empty($GLOBALS[$counterId])) {
			$GLOBALS[$counterId] = 0;
		}
		$GLOBALS[$counterId] = ($GLOBALS[$counterId] + (int)$increase);
		if ($output) {
			return $GLOBALS[$counterId];
		}
	}
}