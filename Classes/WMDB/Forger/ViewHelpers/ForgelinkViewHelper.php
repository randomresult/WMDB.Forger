<?php
namespace WMDB\Forger\ViewHelpers;

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ForgelinkViewHelper
 * @package WMDB\Forger\ViewHelpers
 */
class ForgelinkViewHelper extends AbstractViewHelper {

	/**
	 * @param string $linkText
	 * @param string $year
	 * @param string $month
	 * @return string
	 */
	public function render($linkText = '', $year = '2015', $month = '01') {
		$end = $this->getLastDayOfMonth($year, $month);
		$content = '<a href="https://forge.typo3.org/projects/typo3cms-core/issues?set_filter=1&f[]=status_id&op[status_id]=o&f[]=updated_on&op[updated_on]=%3E%3C&v[updated_on][]='.$year.'-'.$month.'-01&v[updated_on][]='.$end.'&f[]=&c[]=tracker&c[]=status&c[]=priority&c[]=subject&c[]=assigned_to&c[]=category&c[]=fixed_version&group_by=" target="_blank">';
		$content .= $linkText;
		$content .= '</a>';
		return $content;
	}

	/**
	 * @param string $year
	 * @param string $month
	 * @return string
	 */
	protected function getLastDayOfMonth($year = '', $month = '') {
		$dateString = $year.'-'.$month.'-16';
		return date("Y-m-t", strtotime($dateString));
	}
}