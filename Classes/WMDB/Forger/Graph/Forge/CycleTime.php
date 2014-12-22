<?php
namespace WMDB\Forger\Graph\Forge;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class CycleTime
 * @package WMDB\Forger\Graph\Gerrit
 */
class CycleTime extends AbstractGraph {

	protected function getData() {
		$this->chartData = [
			'chartData' => $this->getCycleTime(),
			'lines' => [
				'cycleTime' => [
					'color' => '#E69D00',
					'title' => 'Days'
				]
			]
		];
	}
	/**
	 * @return string
	 */
	protected function getCycleTime() {
		$chartData = [];
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						'terms' => [
							'status.name' => [
								'Closed',
								'Rejected',
								'Resolved'
							],
						],
					]
				]
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'month'
					],
					'aggregations' => [
						'cycle' => [
							'stats' => [
								'script' => '((doc["updated_on"].value-doc["created_on"].value) / (1000 * 86400))',
							]
						]
					]
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['over_time']['buckets'] as $frame) {
			$chartData[] = [
				'date' => $this->fixTime($frame['key_as_string']),
				'cycleTime' => floor($frame['cycle']['avg']),
			];
		}
		return $chartData;
	}
}