<?php
namespace WMDB\Forger\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "WMDB.Forger".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;

/**
 * Class SprintController
 * @package WMDB\Forger\Controller
 */
class SprintController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 * @Flow\Inject
	 */
	protected $env;

	/**
	 * Settings from the YAML files
	 * @var array
	 */
	protected $settings;

	/**
	 * Board settings
	 * @var array
	 */
	protected $sprintConfig;

	/**
	 * @var string
	 */
	protected $context;

	/**
	 * @var array
	 */
	protected $users;

	/**
	 * @var Es\ElasticSearchConnection
	 */
	private $connection;

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $ConfigurationManager;

	/**
	 * @var \WMDB\Forger\Domain\Repository\BoardConfigRepository
	 * @Flow\Inject
	 */
	protected $sprintBoardRepo;

	/**
	 * Initializes the controller
	 */
	protected function initializeAction() {

		date_default_timezone_set('UTC');

		$context = $this->env->getContext();
		if($context == 'Development') {
			$this->context = 'DEV';
		} else {
			$this->context = 'PRD';
		}
		$this->connection = new Es\ElasticSearchConnection();
		$this->connection->init();
		$this->sprintConfig = $this->ConfigurationManager->getConfiguration('Sprints');
	}

	/**
	 * @param string $boardId
	 */
	public function indexAction($boardId = '') {
		$this->prepareBoardData($boardId);
	}
	/**
	 * @param string $boardId
	 */
	public function focusAction($boardId = '') {
		$this->prepareBoardData($boardId, 'FocusBoards');
	}
	/**
	 * @param string $boardId
	 */
	public function listAction($boardId = '') {
		$this->prepareBoardData($boardId);
	}

	/**
	 * @param string $boardId
	 * @param string $boardType
	 * @return array
	 */
	protected function prepareBoardData($boardId = '', $boardType = 'Boards') {
		$this->getAllUsers();
		$this->view->assignMultiple(
			[
				'boardMenu' => $this->makeBoardMenu($boardId, $boardType),
				'context' => $this->context
			]
		);
		if(!isset($this->sprintConfig['WMDB']['Forger'][$boardType][$boardId]) && !is_numeric($boardId)) {
			return [];
		}
		$query = '';
		if (isset($this->sprintConfig['WMDB']['Forger'][$boardType][$boardId])) {
			$query = $this->sprintConfig['WMDB']['Forger'][$boardType][$boardId]['Query'];
			$boardInfo = $this->sprintConfig['WMDB']['Forger'][$boardType][$boardId];
		}
		if(is_numeric($boardId)) {
			$query = array(
				'bool' => array(
					'must' => array(
//						array(
//							'term' => array(
//								'focus.name' => 'Remote Sprint'
//							)
//						),
						array(
							'term' => array(
								'assigned_to.id' => (int)$boardId
							)
						),
					)
				)
			);
			if (isset($this->users[$boardId])) {
				$boardInfo['Name'] = 'Private Board: '.$this->users[$boardId]['fullname'];
			} else {
				$boardInfo['Name'] = '';
			}
		}
		$boardData = $this->getBoardData($query);
		$ticketCount = [];
		foreach ($boardData as $status => $tickets) {
			$ticketCount[$status] = count($tickets);
		}
		$this->view->assign('progress', $this->calculateProgressBars($ticketCount));
		$this->view->assign('board', $boardData);
		$this->view->assign('boardInfo', $boardInfo);

	}

	public function adminAction() {
		$boards = $this->sprintBoardRepo->findAll();
		$this->view->assign('boards', $boards);
	}

	/**
	 * Generates a list of boards to link to
	 *
	 * @param string $active
	 * @param string $boardType
	 * @return array
	 */
	protected function makeBoardMenu($active = '', $boardType = 'Boards') {
		$out = [];
		foreach ($this->sprintConfig['WMDB']['Forger'][$boardType] as $boardId => $boardSetup) {
			$resultCount = $this->getTicketByBoardQuery($boardSetup['Query']);
			$out[] = [
				'id' => $boardId,
				'name' => $boardSetup['Name'],
				'count' => $resultCount,
				'active' => ($boardId === $active) ? true : false,
			];
		}
		return $out;
	}

	/**
	 * @param array $query
	 * @return string
	 */
	protected function getTicketByBoardQuery(array $query) {
		$fullRequest = [
			'query' => $query,
			'filter' => $this->queryFilters(),
			'size' => 0,
			'aggregations' => [
				'status' => [
					'terms' => [
						'field' => 'status.id',
					],
				]
			]
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		$count = [
			'open' => 0,
			'closed' => 0,
			'total' => 0
		];
		foreach ($resultSet->getAggregation('status')['buckets'] as $bucket) {
			if($bucket['key'] === 3 || $bucket['key'] === 5 || $bucket['key'] === 6) {
				$count['closed'] = $count['closed'] + $bucket['doc_count'];
			} else {
				$count['open'] = $count['open'] + $bucket['doc_count'];
			}
			$count['total'] = $count['total'] + $bucket['doc_count'];
		}
		if ($count['closed'] > 0 && $count['total'] > 0) {
			$count['percentage'] = round($count['closed'] * 100.0 / $count['total']);
		} else {
			$count['percentage'] = 0;
		}

		return $count;
	}

	/**
	 * @param string $query
	 * @return array
	 * @throws Exception
	 */
	protected function getBoardData($query) {
		$this->view->assign('boardConfig' ,json_encode($query, JSON_PRETTY_PRINT));
		$out = [
			'BLOCKED' => [],
			'Open' => [],
			'WIP' => [],
			'Review' => [],
			'Done' => [],
		];
		$fullRequest = [
			'query' => $query,
			'filter' => $this->queryFilters(),
			'size' => 10000
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		foreach ($resultSet->getResults() as $ticket) {
			$status = $ticket->__get('status');
			$getBoardGroup = $this->defineBoardGroup($status['name']);
			if($getBoardGroup === 'Done') {
				//@todo yes, this is butt-ugly, but either like this or with two queries.
				$ticketTime = new \DateTime($ticket->__get('updated_on'));
				$now = new \DateTime();
				$ageDiff = $now->diff($ticketTime);
				$age = (int)$ageDiff->format('%a');
				if($age > 30) {
					continue;
				}
			}
			if ($getBoardGroup !== 'Review') {
				$ticketInfo = $ticket->getData('id');
				if (isset($ticketInfo['assigned_to']) && isset($this->users[$ticketInfo['assigned_to']['id']])) {
					$ticketInfo['avatar'] = $this->users[$ticketInfo['assigned_to']['id']];
				}
				$out[$getBoardGroup][] = $ticketInfo;
			} else {
				$out[$getBoardGroup][] = $this->getReviewCard($ticket->getData('id'));
			}
		}
		return $out;
	}

	/**
	 * Special handling for review cards
	 *
	 * @param array $ticket
	 * @return array
	 * @throws Exception
	 */
	protected function getReviewCard(array $ticket) {

		$notes = '';
		if (isset($ticket['journals'])) {
			foreach ($ticket['journals'] as $journalEntry) {
				if (isset($journalEntry['notes']) && (
					strstr($journalEntry['notes'], 'It is available at http://review.typo3.org/')
					||
					strstr($journalEntry['notes'], 'It is available at https://review.typo3.org/')
				)
				) {
					$notes = $journalEntry['notes'];
				}
			}
			if ($notes === '') {
				return $ticket;
			}
			$pattern = '|.*\/\/review.typo3.org\/(?<reviewId>[0-9]{1,6})|';
			preg_match($pattern, $notes, $matches);
			if (!isset($matches['reviewId'])) {
				throw new Exception('There should be a review id in '.$notes);
			}
			$reviewType = $this->connection->getIndex()->getType('review');
			$item = $reviewType->getDocument($matches['reviewId']);
			$returnValue = $item->getData();
			if (isset($ticket['assigned_to']) && isset($this->users[$ticket['assigned_to']['id']])) {
				$returnValue['avatar'] = $this->users[$ticket['assigned_to']['id']];
			}
			$returnValue['_redmine_issue_id'] = $ticket['id'];
			return $returnValue;
		}
		$returnValue['_redmine_issue_id'] = $ticket['id'];
		return $returnValue;
	}

	/**
	 * @return array
	 */
	protected function queryFilters() {
		return [
			'bool' => [
				'must' => [],
				'must_not' => []
			],
		];
	}

	/**
	 * @param string $status
	 * @return string
	 */
	protected function defineBoardGroup($status) {
		switch($status) {
			case 'Rejected':
				$metaStatus = 'Done';
				break;
			case 'Closed':
				$metaStatus = 'Done';
				break;
			case 'Resolved':
				$metaStatus = 'Done';
				break;

			case 'Under Review':
				$metaStatus = 'Review';
				break;

			case 'In Progress':
				$metaStatus = 'WIP';
				break;

			case 'Needs Feedback':
				$metaStatus = 'BLOCKED';
				break;
			default:
				$metaStatus = 'Open';
		}
		return $metaStatus;
	}

	/**
	 * Injects the settings from the yaml file into
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function calculateProgressBars(array $data) {
		$out = [];
		$fullCount = array_sum($data);
		$percentage = $fullCount / 100;
		$percentage = $percentage ?: 1;
		foreach ($data as $key => $count) {
			$out['sections'][strtolower($key)]['classes'] = strtolower($key);
			if($key === 'WIP') {
				$out['sections'][strtolower($key)]['classes'] = 'wip active progress-bar-striped';
			}

			$out['sections'][strtolower($key)]['value'] = ($count / $percentage);
			$out['sections'][strtolower($key)]['label'] = $key;
		}
		$out['total'] = $fullCount;
		return $out;
	}

	/**
	 * 
	 */
	protected function getAllUsers() {
		$res = $this->connection->getIndex()->getType('user')->search('', array('size'=> 100));
		$results = $res->getResults();
		foreach ($results as $hit) {
			$user = $hit->getSource();
			$this->users[$user['id']] = $user;
		}
	}
}