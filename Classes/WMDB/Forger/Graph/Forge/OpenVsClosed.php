<?php
namespace WMDB\Forger\Graph\Forge;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;
use TYPO3\Flow\Annotations as Flow;

/**
 * Class OpenVsClosed
 * @package WMDB\Forger\Graph\Gerrit
 */
class OpenVsClosed extends AbstractGraph {


	/**
	 * Get's the data
	 */
	protected function getData() {
		$this->chartData = [
			'guides' => $this->getGuides(),
			'chartData' => $this->getClosed(),
			'lines' => [
				'open' => [
					'color' => '#ff0000',
					'title' => 'Opened Tickets'
				],
				'closed' => [
					'color' => '#43ac6a',
					'title' => 'Closed Tickets'
				]
			]
		];
	}

	/**
	 * @return string
	 */
	protected function getClosed() {
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
							]
						],
					],
					'must_not' =>  [
						'range' => [
							'updated_on' => [
								'lte' => date('Y/m/d h:i:s', (time() - (86400 * 60)))
							]
						]
					]
				]
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'day'
					]
				]
			]
		];
		$openedTickets = $this->getOpened();
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['over_time']['buckets'] as $frame) {
			$time = $this->fixTime($frame['key_as_string']);
			if(isset($openedTickets[$time])) {
				$open = $openedTickets[$time];
			} else {
				$open = 0;
			}
			$chartData[] = [
				'date' => $time,
				'closed' => $frame['doc_count'],
				'open' => $open
			];
		}

		return $chartData;
	}

	/**
	 * @return array
	 */
	protected function getOpened() {
		$chartData = [];
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [

					],
					'must_not' =>  [
						[
							'range' => [
								'created_on' => [
									'lte' => date('Y/m/d h:i:s', (time() - (86400 * 60)))
								]
							],
						],
					]
				]
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'created_on',
						'interval' => 'day'
					]
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['over_time']['buckets'] as $frame) {
			$chartData[$this->fixTime($frame['key_as_string'])] = $frame['doc_count'];
		}
		return $chartData;
	}


}