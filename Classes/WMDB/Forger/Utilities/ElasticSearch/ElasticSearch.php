<?php

namespace WMDB\Forger\Utilities\ElasticSearch;

use Elastica\Aggregation\Terms;
use Elastica\Filter\Bool;
use Elastica\Filter\BoolOr;
use Elastica\Filter\Term;
use Elastica\Filter\Type;
use Elastica\Query;
use Elastica\Query\QueryString;
use Elastica\Util;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Exception;

/**
 * Class ElasticSearch
 * @package WMDB\Forger\Utilities\ElasticSearch
 */
class ElasticSearch {

	/**
	 * @var ElasticSearchConnection
	 */
	private $connection;

	/**
	 * @var Query\Bool
	 */
	private $whereClause;

	/**
	 * @var array
	 */
	private $searchTerms;

	/**
	 * @var array
	 */
	private $fieldMapping;

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var string
	 */
	private $divider = ' ';

	protected $perPage = 25;

	protected $currentPage = 1;

	protected $totalHits;

	/**
	 * @var Util
	 */
	protected $utility;

	/**
	 * @param array $searchTerms
	 */
	public function setSearchTerms($searchTerms) {
		$this->searchTerms = $searchTerms;
	}

	/**
	 * @return array
	 */
	public function getSearchTerms() {
		return $this->searchTerms;
	}



	/**
	 * @throws Exception
	 */
	function __construct() {

	}

	/**
	 * @return \Elastica\ResultSet
	 */
	public function doSearch() {
		$this->connection = new ElasticSearchConnection();
		$this->connection->init();
		$this->whereClause = new Query\Bool();
		$this->utility = new Util;
		if(isset($_GET['page'])) {
			$this->currentPage = intval($_GET['page']);
		}
		$this->fieldMapping = $this->configurationManager->getConfiguration( ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'WMDB.Forger.SearchTermMapping');
		$this->buildQueryString($this->searchTerms);

		$elasticaQuery = new Query();
		$elasticaQuery->setQuery($this->whereClause);
		$elasticaQuery->setSize($this->perPage);
		$elasticaQuery->setFrom(($this->currentPage * $this->perPage) - $this->perPage);

		$usedFilters = $this->addFilters();
		if ($usedFilters !== false) {
			$elasticaQuery->setPostFilter($usedFilters);
		}

		$this->addAggregations($elasticaQuery);
		$elasticaResultSet = $this->connection->getIndex()->search($elasticaQuery);
		$results = $elasticaResultSet->getResults();
		$maxScore = $elasticaResultSet->getMaxScore();
		$aggs = $elasticaResultSet->getAggregations();
		$this->totalHits = $elasticaResultSet->getTotalHits();

		$out = array(
			'pagesToLinkTo'		=> $this->getPages(),
			'currentPage'		=> $this->currentPage,
			'prev'              => $this->currentPage - 1,
			'next'              => $this->currentPage < ceil($this->totalHits / $this->perPage) ? $this->currentPage + 1 : 0,
			'totalResults'		=> $this->totalHits,
			'startingAtItem'	=> ($this->currentPage * $this->perPage) - ($this->perPage - 1),
			'endingAtItem'		=> ($this->currentPage * $this->perPage),
			'results' => $results,
			'maxScore' => $maxScore,
			'aggs' => $aggs
		);
		if(intval($this->totalHits) <=  intval($out['endingAtItem'])) {
			$out['endingAtItem'] = intval($this->totalHits);
		}
		return $out;
	}

	/**
	 * @param $searchTerms
	 * @throws \TYPO3\Flow\Exception
	 * @return Query\Bool $whereClause
	 */
	public function buildQueryString($searchTerms) {
		/**
		 * Filter to type = issue
		 */
//		$this->whereClause->addMust([
//			'term' => [
//				'type' => 'issue'
//			]
//		]);

		if (isset($searchTerms['shouldHave']) && isset($searchTerms['must']) && isset($searchTerms['mustNot'])) {
			// Query search
			foreach ($searchTerms['shouldHave'] as $word) {
				$this->addShouldQuery($word);
			}
			foreach ($searchTerms['must'] as $word) {
				$this->addMustQuery($word);
			}
			foreach ($searchTerms['mustNot'] as $word) {
				$this->addMustNotQuery($word);
			}
			/**
			 * Hide closed by default
			 */
			$searchTerm = new QueryString();
			$searchTerm->setDefaultField('status.name');
			$searchTerm->setQuery('Closed Resolved Rejected');
			$this->whereClause->addMustNot($searchTerm);
		} else {
			foreach ($searchTerms as $field => $term) {
				if ($term != '') {

					if (!array_key_exists($field, $this->fieldMapping)) {
						throw new Exception ('Field: ' . $field . ' is not mapped', 1413390545);
					}

					switch ($this->fieldMapping[$field]) {
						case 'must':
							$this->addMustQuery($term);
							break;
						case 'must_not':
							$this->addMustNotQuery($term);
							break;
						case 'should':
							$this->addShouldQuery($term);
							break;
						default:
							throw new Exception('Condition: ' . $this->fieldMapping[$field] . ' does not exists!', 1413391351);
					}
				}
			}
		}
	}

	/**
	 * @param $term
	 */
	private function addMustQuery($term) {
		$searchTerm = new QueryString();
		$searchWordArray = explode($this->divider, $term);
		for($iterator = 0; count($searchWordArray); $iterator++) {
			$tmp = implode($this->divider, $searchWordArray);
			$tmp = $this->utility->escapeTerm($tmp);
			$searchTerm->setQuery($tmp);
			$this->whereClause->addMust($searchTerm);
			array_pop($searchWordArray);
		}
	}

	/**
	 * @param string $term
	 */
	private function addShouldQuery($term) {
		$searchTerm = new QueryString();
		$searchWordArray = explode($this->divider, $term);
		for($iterator = 0; count($searchWordArray); $iterator++) {
			$tmp = implode($this->divider, $searchWordArray);
			$tmp = $this->utility->escapeTerm($tmp);
			$searchTerm->setQuery($tmp);
			$this->whereClause->addShould($searchTerm);
			array_pop($searchWordArray);
		}

	}

	/**
	 * @param string $term
	 */
	private function addMustNotQuery($term) {
		$searchTerm = new QueryString();
		$searchWordArray = explode($this->divider, $term);
		for($iterator = 0; count($searchWordArray); $iterator++) {
			$tmp = implode($this->divider, $searchWordArray);
			$tmp = $this->utility->escapeTerm($tmp);
			$searchTerm->setQuery($tmp);
			$this->whereClause->addMustNot($searchTerm);
			array_pop($searchWordArray);
		}
	}

	/**
	 * @return BoolOr
	 */
	private function addFilters() {

//		$filterCount = 0;
		$filters = new Bool();

		$typeFilter = new Type('issue');

		$filters->addMust($typeFilter);


		if(!isset($_GET['filters'])) {
			return $filters;
		}
		foreach ($_GET['filters'] as $key => $filterValue) {
			$filterCatCount = 0;
			$filterPart = new BoolOr();
			foreach ($filterValue as $term => $enabled) {
				if($enabled == 'true') {
					$term = str_replace('_', ' ', $term);
					$filter = new Term();
					if (
						$key == 'tracker'
						|| $key == 'project'
						|| $key == 'category'
						|| $key == 'status'
						|| $key == 'author'
						|| $key == 'priority'
						|| $key == 'fixed_version'
					) {
						$filter->setTerm($key . '.name', $term);
					} else {
						$filter->setTerm($key, $term);
					}
					$filterPart->addFilter($filter);
//					$filterCount++;
					$filterCatCount++;
				}
			}
			if ($filterCatCount > 0) {
				$filters->addMust($filterPart);
			}
		}
//		if($filterCount === 0) {
//			return false;
//		}
		return $filters;
	}

	/**
	 * @param Query $elasticaQuery
	 */
	private function addAggregations($elasticaQuery) {
		$catAggregation = new Terms('Category');
		$catAggregation->setField('category.name');
		$elasticaQuery->addAggregation($catAggregation);

		$trackerAggregation = new Terms('Tracker');
		$trackerAggregation->setField('tracker.name');
		$elasticaQuery->addAggregation($trackerAggregation);

		$status = new Terms('Status');
		$status->setField('status.name');
		$elasticaQuery->addAggregation($status);

		$priority = new Terms('Priority');
		$priority->setField('priority.name');
		$elasticaQuery->addAggregation($priority);

		$t3ver = new Terms('TYPO3 Version');
		$t3ver->setField('typo3_version');
		$elasticaQuery->addAggregation($t3ver);

		$targetver = new Terms('Target Version');
		$targetver->setField('fixed_version.name');
		$elasticaQuery->addAggregation($targetver);

		$phpVer = new Terms('PHP Version');
		$phpVer->setField('php_version');
		$elasticaQuery->addAggregation($phpVer);

		/*$age = new \Elastica\Aggregation\Range('Age');
		$age->setParams(array(
			'script' => 'DateTime.now().year - doc[\'updated_on\'].date.year',
			'ranges' => array(
				array('from' => 0,'to' => 1),
				array('from' => 2,'to' => 3),
				array('from' => 4,'to' => 5),
			)
		));
		$elasticaQuery->addAggregation($age);*/
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