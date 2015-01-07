<?php
namespace WMDB\Forger\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "WMDB.Forger".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class ManagementController
 * @package WMDB\Forger\Controller
 */
class ManagementController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $env;

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * @var Es\ElasticSearchConnection
	 */
	private $connection;

	/**
	 * Initializes the controller
	 */
	protected function initializeAction() {
		$context = $this->env->getContext();
		if($context == 'Development') {
			$this->context = 'DEV';
		} else {
			$this->context = 'PRD';
		}
		$this->connection = new Es\ElasticSearchConnection();
		$this->connection->init();
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$out = [];
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
			'filter' => $this->queryFilters(),
			'aggregations' => [
				'over_time' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'month'
					],
					'aggregations' => [
						'status' => [
							'terms' => [
								'field' => 'status.name'
							]
						]
					]
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		foreach ($resultSet->getAggregations() as $aggregation) {
			foreach ($aggregation['buckets'] as $bucket) {
				$date = $bucket['key_as_string'];
				$dateParts = explode('/', $date);
				foreach ($bucket['status']['buckets'] as $status) {
					$out[$dateParts[0]][$dateParts[1]][] = [
						'name' => $status['key'],
						'count' => $status['doc_count']
					];
				}
			}

		}
		$this->view->assignMultiple([
			'aggs' => $out,
			'context' => $this->context
		]);
	}

	/**
	 * @return array
	 */
	protected function queryFilters() {
		return [
			'bool' => [
				'must' => [
					'type' => [
						'value' => 'issue'
					],
				],
				'must_not' => [
//					'term' => [
//						'subject' => 'wip'
//					]
				]
			],
		];
	}


}