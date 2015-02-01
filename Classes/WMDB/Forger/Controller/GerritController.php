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

	private $perPage = 25;

	private $currentPage = 1;

	private $totalHits;

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
		if(isset($_GET['page'])) {
			$this->currentPage = intval($_GET['page']);
		}
	}

	/**
	 * @param array $fullRequest
	 */
	protected function search(array $fullRequest) {
		$query = new El\Query($fullRequest);
		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$query->setPostFilter($usedFilters);
		}
		$resultSet = $this->connection->getIndex()->search($query);
		$this->totalHits = $resultSet->getTotalHits();
		if(intval($this->totalHits) <=  intval(($this->currentPage * $this->perPage))) {
			$this->view->assign('endingAtItem',intval($this->totalHits));
		} else {
			$this->view->assign('endingAtItem',($this->currentPage * $this->perPage));
		}
		$this->view->assignMultiple([
			'results' => $resultSet->getResults(),
			'pagesToLinkTo'		=> $this->getPages(),
			'currentPage'		=> $this->currentPage,
			'prev'              => $this->currentPage - 1,
			'next'              => $this->currentPage < ceil($this->totalHits / $this->perPage) ? $this->currentPage + 1 : 0,
			'totalResults'		=> $this->totalHits,
			'startingAtItem'	=> ($this->currentPage * $this->perPage) - ($this->perPage - 1),
			'aggregations' => $resultSet->getAggregations(),
			'context' => $this->context

		]);
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
			'from' => (($this->currentPage * $this->perPage) - $this->perPage),
			'aggregations' => $this->queryAggregations()
		];
		$this->search($fullRequest);
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
			'from' => (($this->currentPage * $this->perPage) - $this->perPage),
			'aggregations' => $this->queryAggregations()
		];
		$this->search($fullRequest);
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
			'from' => (($this->currentPage * $this->perPage) - $this->perPage),
			'aggregations' => $aggs
		];
		$this->search($fullRequest);
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
			'from' => (($this->currentPage * $this->perPage) - $this->perPage),
			'aggregations' => $this->queryAggregations()
		];
		$this->search($fullRequest);
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
			'from' => (($this->currentPage * $this->perPage) - $this->perPage),
			'aggregations' => $this->queryAggregations()
		];
		$this->search($fullRequest);
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
					'size' => 0,
					'order' => [
						'_term' => 'asc'
					]
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

	/**
	 * @return array
	 */
	protected function getPages() {
		$numPages = ceil($this->totalHits / $this->perPage);
		$i = 0;
		/**
		 *
		 */
		$maxPages = $numPages;
		if ($numPages > 15 && $this->currentPage <= 7) {
			$numPages = 15;
		}
		if ($this->currentPage > 7) {
			$i = $this->currentPage - 7;
			$numPages = $this->currentPage + 6;
		}
		if ($numPages > $maxPages) {
			$numPages = $maxPages;
			$i = $maxPages - 15;
		}

		if($i < 0) {
			$i = 0;
		}

		$out = array();
		while($i < $numPages) {
			$out[$i] = ($i+1);
			$i++;
		}
		return $out;
	}

}