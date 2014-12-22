<?php
namespace WMDB\Forger\Graph\Gerrit;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class Full
 * @package WMDB\Forger\Graph\Gerrit
 */
class Full extends AbstractGraph {

	protected function getData() {
		$this->chartData = $this->getAllOpenReviews();
	}

	/**********************************************
	 * GERRIT RELATED THINGS
	 ***********************************************/
	protected function getAllOpenReviews() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must_not' => [
						[
							'terms' => [
								'status' => [
									'ABANDONED',
									'MERGED'
								]
							],
						]
					]
				],
			],
			'size' => 0,
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('review');
		$result = $search->search();
		$result2 = $result->getTotalHits();
		return $result2;
	}
}