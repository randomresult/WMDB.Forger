<?php
namespace WMDB\Forger\Graph\Gerrit;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class Velocity
 * @package WMDB\Forger\Graph\Gerrit
 */
class Velocity extends AbstractGraph {

	protected $dateInterval = 'month';

	/**
	 * @return array
	 */
	protected function getData() {
		$lastRow = [
			'closed' => 1,
			'open' => 1
		];
		$open = $this->getOpenedReviews();
		$close = $this->getClosedReviews();
		$result = array_replace_recursive($open, $close);
		ksort($result);
		foreach ($result as $date => $rows) {
			$this->chartData[] = [
				'date' => $date,
				'open' => (isset($rows['open']) ? $rows['open'] : $lastRow['open']),
				'closed' => (isset($rows['closed']) ? $rows['closed'] : $lastRow['closed'])
			];
			if(!isset($rows['closed'])) {
				$rows['closed'] = $lastRow['closed'];
			}
			if(!isset($rows['open'])) {
				$rows['open'] = $lastRow['open'];
			}
			$lastRow = $rows;
		}
	}

	/**
	 * Get's opened reviews over time
	 * @return array
	 */
	private function getOpenedReviews() {
		$opened = [];
		$fullRequest = [
			'query' => [
				'match_all' => []
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'created_on',
						'interval' => $this->dateInterval
					],
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('review');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		$openSum = 0;
		foreach ($data['over_time']['buckets'] as $frame) {
			$openSum = $openSum + $frame['doc_count'];
			$date = $this->fixTime($frame['key_as_string']);
			$opened[$date]['open'] = $openSum;
		}
		return $opened;
	}

	/**
	 * Get's closed reviews over time
	 * @return array
	 */
	private function getClosedReviews() {
		$closed = [];
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						'terms' => [
							'status' => [
								'MERGED',
								'ABANDONED'
							]
						],
					],

				],
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => $this->dateInterval
					],
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('review');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		$closeSum = 0;
		foreach ($data['over_time']['buckets'] as $frame) {
			$closeSum = $closeSum + $frame['doc_count'];
			$date = $this->fixTime($frame['key_as_string']);
			$closed[$date]['closed'] = $closeSum;
		}
		return $closed;
	}
}