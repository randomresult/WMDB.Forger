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
 * Class GerritController
 * @package WMDB\Forger\Controller
 */
class GerritController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $env;

	/**
	 * @var string
	 */
	protected $context;

	protected $perPage = 200;

	/**
	 * @var Es\ElasticSearchConnection
	 */
	private $connection;

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->redirect('index', 'standard');
	}

	protected function initializeAction() {
		$this->connection = new Es\ElasticSearchConnection();
		$this->connection->init();
		$context = $this->env->getContext();
		if($context == 'Development') {
			$this->context = 'DEV';
		} else {
			$this->context = 'PRD';
		}
	}

	/**
	 * @return string
	 */
	public function statusAction() {
		$fullRequest = [
			'query' => [
				'match' => [
					'status' => 'NEW'
				]
			],
			'filter' => $this->queryFilters(),
			'sort' => $this->querySort(),
			'size' => $this->perPage,
			'aggregations' => $this->queryAggregations()
		];
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'total' => $resultSet->getTotalHits(),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context

		]);
	}
	/**
	 * @return string
	 */
	public function mergeReadyAction() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'range' => [
								'positive_reviews' => [
									'gte' => 1
								]
							],
						],
						[	'range' => [
								'positive_verifications' => [
									'gte' => 1
								]
							],
						]
					],
					'must_not' =>  [
						'terms' => [
							'status' => [
								'MERGED',
								'ABANDONED'
							]
						],
					]
				]
			],
			'filter' => $this->queryFilters(),
			'sort' => $this->querySort(),
			'size' => $this->perPage,
			'aggregations' => $this->queryAggregations()
		];
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'total' => $resultSet->getTotalHits(),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context
		]);
	}
	/**
	 * @return string
	 */
	public function rebaseAction() {
		$aggs = $this->queryAggregations();
		unset($aggs['Mergeable']);
		unset($aggs['Patchsets']);
		unset($aggs['Releases']);
		unset($aggs['Topic']);
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'term' => [
								'mergeable' => 'no'
							],
						],
//						[	'range' => [
//								'positive_verifications' => [
//									'gte' => 1
//								]
//							],
//						]
					],
					'must_not' =>  [
						'terms' => [
							'status' => [
								'MERGED',
								'ABANDONED'
							]
						],
					]
				]
			],
			'filter' => $this->queryFilters(),
			'sort' => $this->querySort(),
			'size' => $this->perPage,
			'aggregations' => $aggs
		];
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'total' => $resultSet->getTotalHits(),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context
		]);
	}
	/**
	 * @return string
	 */
	public function missesVerificationAction() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'range' => [
								'positive_reviews' => [
									'gte' => 1
								]
							],
						],
						[	'range' => [
								'positive_verifications' => [
									'lt' => 1
								]
							],
						]
					],
					'must_not' =>  [
						'terms' => [
							'status' => [
								'MERGED',
								'ABANDONED'
							]
						],
					]
				]
			],
			'filter' => $this->queryFilters(),
			'sort' => $this->querySort(),
			'size' => $this->perPage,
			'aggregations' => $this->queryAggregations()
		];
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'total' => $resultSet->getTotalHits(),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context
		]);
	}
	/**
	 * @return string
	 */
	public function missesReviewAction() {
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						[
							'range' => [
								'positive_reviews' => [
									'lt' => 1
								]
							],
						],
						[	'range' => [
								'positive_verifications' => [
									'gte' => 1
								]
							],
						]
					],
					'must_not' =>  [
						'terms' => [
							'status' => [
								'MERGED',
								'ABANDONED'
							]
						],
					]
				]
			],
			'filter' => $this->queryFilters(),
			'sort' => $this->querySort(),
			'size' => $this->perPage,
			'aggregations' => $this->queryAggregations()
		];
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'total' => $resultSet->getTotalHits(),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context
		]);
	}

	/**
	 * Makes the date string from elastic JS compatible
	 * @param string $in
	 * @return string
	 */
	protected function fixTime($in) {
		$date = substr($in, 0, 10);
		$out = str_replace('/', '-', $date);
		return $out;
	}

	/**
	 * @return array
	 */
	protected function addFilters() {
		if(!isset($_GET['filters'])) {
			return false;
		}
		$filterCount = 0;
		$filters = new El\Filter\Bool();

		foreach ($_GET['filters'] as $key => $filterValue) {
			$filterCatCount = 0;
			$filterPart = new El\Filter\BoolOr();
			foreach ($filterValue as $term => $enabled) {
				if($enabled == 'true') {
					$term = str_replace('_', ' ', $term);
					$filter = new El\Filter\Term();
					$filter->setTerm($key, $term);
					$filterPart->addFilter($filter);
					$filterCount++;
					$filterCatCount++;
				}
			}
			if ($filterCatCount > 0) {
				$filters->addMust($filterPart);
			}
		}
		if($filterCount === 0) {
			return false;
		}
		return $filters;
	}

	/**
	 * @return array
	 */
	protected function queryAggregations() {
		return [
			'Releases' => [
				'terms' => [
					'field' => 'releases',
					'size' => 0
				]
			],
			'Affected Files' => [
				'terms' => [
					'field' => 'affected_files'
				]
			],
			'Topic' => [
				'terms' => [
					'field' => 'topic',
					'size' => 0
				]
			],
			'Patchsets' => [
				'terms' => [
					'field' => 'patchsets'
				]
			],
			'Mergeable' => [
				'terms' => [
					'field' => 'mergeable'
				]
			],
		];
	}

	/**
	 * @return array
	 */
	protected function queryFilters() {
		return [
			'bool' => [
				'must' => [
					'type' => [
						'value' => 'review'
					],
				],
				'must_not' => [
					'term' => [
						'subject' => 'wip'
					]
				]
			],
		];
	}

	/**
	 * @return array
	 */
	protected function querySort() {
		return [
			'insertions' => 'asc',
			'deletions' => 'asc',
			'patchsets' => 'desc'
		];
	}

}