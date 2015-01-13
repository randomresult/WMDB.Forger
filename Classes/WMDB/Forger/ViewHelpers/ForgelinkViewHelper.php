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
	 * @param string $statusId
	 * @return string
	 */
	public function render($linkText = '', $year = '2015', $month = '01', $statusId = 'o') {
		$redmineUrl = 'https://forge.typo3.org/projects/typo3cms-core/issues?set_filter=1';
		$content = '<a href="'.$redmineUrl. $this->getFiltersString($year, $month, $statusId) . '&f[]='.$this->getColumnsString().'&group_by=" target="_blank">';
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

	/**
	 * @return string
	 */
	protected function getColumnsString() {
		$string = '';
		$columns = [
			'tracker',
			'status',
			'priority',
			'subject',
			'assigned_to',
			'category',
			'fixed_version'
		];
		foreach ($columns as $colname) {
			$string .= '&c[]='.$colname;
		}
		return $string;
	}

	/**
	 * @param string $year
	 * @param string $month
	 * @param string $statusId
	 * @return string
	 */
	protected function getFiltersString($year, $month, $statusId) {
		$end = $this->getLastDayOfMonth($year, $month);
		$string = '';
		$filters = [
			'subproject_id' => [
				'type' => '!*',
				'values' => []
			],
			'status_id' => [
				'type' => '=',
				'values' => [
					$statusId
				]
			],
			'updated_on' => [
				'type' => '><',
				'values' => [
					$year.'-'.$month.'-01',
					$end
				]
			]
		];
		if ($statusId === 'o') {
			$filters['status_id']['type'] = 'o';
			$filters['status_id']['values'] = [];
		}
		foreach ($filters as $fieldName => $config) {
			$string .= '&f[]='.$fieldName;
			$string .= '&op['.$fieldName.']='.$config['type'];
			foreach ($config['values'] as $value) {
				$string .= '&v['.$fieldName.'][]='.$value;
			}

		}
		return $string;
	}
}