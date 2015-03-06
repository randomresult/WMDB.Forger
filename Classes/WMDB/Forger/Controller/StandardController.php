<?php
namespace WMDB\Forger\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "WMDB.Forger".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use WMDB\Forger\Graph\Forge\Full as IssueFull;
use WMDB\Forger\Graph\Gerrit\Full as GerritFull;
use WMDB\Forger\Utilities\ElasticSearch\ElasticSearch;
use WMDB\Utilities\Utility\GeneralUtility;

/**
 * Class StandardController
 * @package WMDB\Forger\Controller
 */
class StandardController extends ActionController {

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
	 * Initializes the controller
	 */
	protected function initializeAction() {
		$context = $this->env->getContext();
		if($context == 'Development') {
			$this->context = 'DEV';
		} else {
			$this->context = 'PRD';
		}
	}

	public function helpAction() {
		$this->view->assignMultiple([
			'context' => $this->context
		]);
	}

	/**
	 * @return void
	 */
	public function indexAction() {
		$reviewGraph = new GerritFull();
		$issueGraph = new IssueFull();
		$this->view->assignMultiple([
			'openIssues' => $issueGraph->render(),
			'openReviews' => $reviewGraph->render(),
			'context' => $this->context
		]);
	}

	/**
	 * @param string $query
	 */
	public function searchAction($query) {
		$this->view->assign('query', htmlspecialchars($query));
		if(is_numeric($this->checkIfQueryIsIssue($query))) {
			$issueData = $this->findIssue($query);
			$this->findDupes($issueData);
		} else {
			$this->findByQuery($query);
			$this->view->assignMultiple([
				'mode' => 'query'
			]);
		}
		$this->view->assign('context', $this->context);
		if (isset($_GET['filters'])) {
			$this->view->assign('filters', $_GET['filters']);
		}
	}

	/**
	 * @param $query
	 * @return bool
	 */
	protected function checkIfQueryIsIssue($query) {
		$result = FALSE;
		$pattern = '(^[0-9]{4,6}$)';
		preg_match($pattern, $query, $matches);
		if(!empty($matches)) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * @param $singleScore
	 * @param $maxScore
	 * @return string
	 */
	protected function calculateScoring($singleScore, $maxScore) {
		return number_format(($singleScore * 100) / $maxScore);
	}

	/**
	 * @param array $issueData
	 */
	private function findDupes(array $issueData) {
		$search = new ElasticSearch();

		$searchWords = $this->splitSearchwords('-'.$issueData['id']. ' '.$issueData['subject']. ' ' . $issueData['description']);


		$search->setSearchTerms($searchWords);
		$results = $search->doSearch();

		$this->view->assignMultiple([
			'result' => $results,
			'mode' => 'dupes'
		]);
	}

	/**
	 * @param int $issueId
	 * @return array|string
	 */
	private function findIssue($issueId) {
		$con = new \WMDB\Forger\Utilities\ElasticSearch\ElasticSearchConnection();
		$con->init();
		$index = $con->getIndex();
		$forger = $index->getType('issue');
		$res = $forger->getDocument($issueId);
		// Need to use an ugly hack
		// funny noone found that one earlier
//		\TYPO3\Flow\var_dump($res);
		$this->view->assign('issue', [
			'hit' => [
				'_source' => $res->getData()
			]
		]);
		return $res->getData();
	}

	/**
	 * @param string $query
	 */
	private function findByQuery($query) {
		$searchWords = $this->splitSearchwords($query);
		$search = new ElasticSearch();

		$search->setSearchTerms($searchWords);
		$results = $search->doSearch();
		$this->view->assignMultiple([
			'result' => $results,
			'mode' => 'dupes'
		]);
	}

	/**
	 * @param string $query
	 * @return array
	 */
	private function splitSearchwords($query) {
		$splitList = [
			'shouldHave' => array(),
			'must' => array(),
			'mustNot' => array()
		];
		$wordList = GeneralUtility::trimExplode(' ', $query);
		foreach ($wordList as $key => $word) {
			switch(true) {
				case substr($word, 0, 1) === '+':
					$splitList['must'][] = trim(str_replace('+', '', $word));
					break;
				case substr($word, 0, 1) === '-':
					$splitList['mustNot'][] = trim(str_replace('-', '', $word));
					break;
				default:
					$splitList['shouldHave'][] = trim($word);
			}
		}
		return $splitList;
	}

}