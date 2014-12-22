<?php
namespace WMDB\Forger\Graph\Gerrit;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class Mergeable
 * @package WMDB\Forger\Graph\Gerrit
 */
class Mergeable extends AbstractGraph {

	/**
	 * @return array
	 */
	protected function getData() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						'terms' => [
							'status' => [
								'NEW',
							]
						],
					],

				],
			],
			'size' => 0,
			'aggregations' => [
				'Mergeable' => [
					'terms' => [
						'field' => 'mergeable',
					],
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('review');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['Mergeable']['buckets'] as $bucket) {
			$this->chartData['chartData'][] = [
				'name' => $bucket['key'],
				'value' => $bucket['doc_count']
			];
		}
		$this->chartData['lines'] = [
			'no' => [
				'color' => '#f04124',
				'title' => 'Cannot be merged'
			],
			'yes' => [
				'color' => '#43ac6a',
				'title' => 'Can be merged'
			]
		];
	}
}