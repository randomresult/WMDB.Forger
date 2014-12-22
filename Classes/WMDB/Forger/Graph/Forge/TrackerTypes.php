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
					]
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$data = $resultSet->getAggregations();
		foreach ($data['trackertype']['buckets'] as $frame) {
			$this->chartData[] = [
				'name' => $frame['key'],
				'value' => $frame['doc_count']
			];
		}
	}
}