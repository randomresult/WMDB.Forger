<?php
namespace WMDB\Forger\Graph\Forge;

use WMDB\Forger\Graph\AbstractGraph;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class Full
 * @package WMDB\Forger\Graph\Gerrit
 */
class Full extends AbstractGraph {

	protected function getData() {
		$this->chartData = $this->getAllOpenIssues();
	}

	/**********************************************
	 * GERRIT RELATED THINGS
	 ***********************************************/
	protected function getAllOpenIssues() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must_not' => [
						[
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
			],
			'size' => 0,
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$result = $search->search();
		$result2 = $result->getTotalHits();
		return $result2;
	}
}