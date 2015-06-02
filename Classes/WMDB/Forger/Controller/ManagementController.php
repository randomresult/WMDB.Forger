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
						],
						[
							'terms' => [
								'project.id' => [
									'78'
								]
							],
						]
					]
				],
			],
			'size' => 0,
			'filter' => $this->queryFilters(),
			'aggregations' => [
				'year' => [
					'date_histogram' => [
						'field' => 'updated_on',
						'interval' => 'year'
					],
					'aggregations' => [
						'month' => [
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
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		foreach ($resultSet->getAggregations() as $aggregation) {
			foreach ($aggregation['buckets'] as $bucket) {
				$dateParts = explode('/', $bucket['key_as_string']);
				$yearName = $dateParts[0];
				// prefill array with 12 index, for 12 month
				$months = array_fill(0, 12, array());
				foreach ($bucket['month']['buckets'] as $month) {
					$dateParts = explode('/', $month['key_as_string']);
					$monthName = $dateParts[1];
					$stati = [];
					foreach ($month['status']['buckets'] as $status) {
						$stati[] = [
							'name' => $status['key'],
							'total' => $status['doc_count']
						];
					}
					$monthIndex = (int)$monthName - 1;
					$months[$monthIndex] = [
						'month' => $monthName,
						'total' => $month['doc_count'],
						'stati' => $stati
					];
				}
				$out[] = [
					'year' => $yearName,
					'total' => $bucket['doc_count'],
					'months' => $months
				];
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