<?php
namespace WMDB\Forger\Graph\Gerrit;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class Overview
 * @package WMDB\Forger\Graph\Gerrit
 */
class Overview extends AbstractGraph {

	protected function getData() {
		$this->chartData = [
			'chartData' => $this->gerritOpenVsClosedAction(),
			'guides' => $this->getGuides(),
		];
	}

	/**********************************************
	 * GERRIT RELATED THINGS
	 ***********************************************/
	protected function gerritOpenVsClosedAction() {
		$row = [];
		$opened = $this->gerritGetOpened();
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'terms' => [
								'status' => [
									'ABANDONED',
									'MERGED'
								]
							],
						],
						[
							'range' => [
								'updated_on' => [
									'gte' => '2013/05/28 00:00:00'
								]
							]
						]

					]
				],

			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'day'
					],
					'aggregations' => [
						'issue_type' => [
							'terms' => [
								'field' => 'status'
							]
						]
					]
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('review');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		$i = 0;
		foreach ($data['over_time']['buckets'] as $frame) {
			$date = $this->fixTime($frame['key_as_string']);
			$row[$i]['date'] = $date;
			foreach ($frame['issue_type']['buckets'] as $issueType) {
				$row[$i][$issueType['key']] = $issueType['doc_count'];
			}
			if(isset($opened[$date])) {
				$row[$i]['NEW'] = $opened[$date];
			}
			$i++;
		}
		return $row;
	}

	/**
	 * @return array
	 */
	protected function gerritGetOpened() {
		$row = [];
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'range' => [
								'created_on' => [
									'gte' => '2013/05/28 00:00:00'
								]
							]
						]
					]
				],
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
		$search->addType('review');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['over_time']['buckets'] as $frame) {
			$row[$this->fixTime($frame['key_as_string'])] = $frame['doc_count'];
		}
		return $row;
	}
}