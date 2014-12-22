<?php
namespace WMDB\Forger\Graph\Forge;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class OpenReviews
 * @package WMDB\Forger\Graph\Gerrit
 */
class OpenReviews extends AbstractGraph {

	protected function getData() {
		$this->chartData = [
			'chartData' => $this->getReviewsOverTime(),
			'lines' => [
				'open' => [
					'color' => '#CB00E6',
					'title' => 'Reviews'
				]
			]
		];
	}

	/**
	 * @return string
	 */
	protected function getReviewsOverTime() {
		$chartData = [];
		$fullRequest = [
			'query' => [
				'term' => [
					'status.name' => 'Under Review'
				]
			],
			'size' => 0,
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'week'
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
				'open' => $frame['doc_count']
			];
		}
		return $chartData;
	}
}