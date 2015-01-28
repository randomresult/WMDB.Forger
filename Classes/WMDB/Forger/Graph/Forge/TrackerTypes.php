<?php
namespace WMDB\Forger\Graph\Forge;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class TrackerTypes
 * @package WMDB\Forger\Graph\Gerrit
 */
class TrackerTypes extends AbstractGraph {

	protected function getData() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must_not' => [
						'terms' => [
							'status.name' => [
								'Closed',
								'Rejected',
								'Resolved'
							]
						],
					]
				]
			],
			'size' => 0,
			'aggregations' => [
				'trackertype' => [
					'terms' => [
						'field' => 'tracker.name'
					],
					'aggregations' => [
						'status' => [
							'terms' => [
								'field' => 'status.name'
							]
						]
					]
				],
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		$i = 0;
		foreach ($data['trackertype']['buckets'] as $frame) {
			foreach ($frame['status']['buckets'] as $issueType) {
				$this->chartData['bars'][$i][$this->fixKey($issueType['key'])] = $issueType['doc_count'];
			}
			$this->chartData['bars'][$i]['panel'] = $frame['key'];
			$i++;
		}
	}

	/**
	 * Makes the array keys lowercase and removes spaces
	 * @param string $in
	 * @return string
	 */
	protected function fixKey($in) {
		$fixedString = strtolower(str_replace(' ', '', $in));
		/**
		 * Fixed Lookup colors
		 */
		$colors = [
			'new' => '#fb7e7e',
			'accepted' => '#fbee99',
			'needsfeedback' => '#FBC3C3',
			'inprogress' => '#A9E2FF',
			'onhold' => '#D1F412',
			'underreview' => '#aeff91',

		];
		$this->chartData['lookup'][$fixedString] = [
			'lookup' => $fixedString,
			'title' => $in,
			'color' => $colors[$fixedString]
		];
		return $fixedString;
	}
}