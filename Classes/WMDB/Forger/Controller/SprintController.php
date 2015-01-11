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
	 * @var Es\ElasticSearchConnection
	 */
	private $connection;

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $ConfigurationManager;

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
		$this->sprintConfig = $this->ConfigurationManager->getConfiguration('Sprints');
	}

	/**
	 * @param string $boardId
	 */
	public function indexAction($boardId = '') {
		if ($boardId !== '' && isset($this->sprintConfig['WMDB']['Forger']['Boards'][$boardId])) {
			$this->view->assign('board', $this->getBoardData($boardId));
		}
		$this->view->assignMultiple([
			'boardMenu' => $this->makeBoardMenu($boardId),
			'context' => $this->context
		]);
	}

	/**
	 * Generates a list of boards to link to
	 * @param string $active
	 * @return array
	 */
	protected function makeBoardMenu($active = '') {
		$out = [];
		foreach ($this->sprintConfig['WMDB']['Forger']['Boards'] as $boardId => $boardSetup) {
			$out[] = [
				'id' => $boardId,
				'name' => $boardSetup['Name'],
				'active' => ($boardId === $active) ? true : false,
			];
		}
		return $out;
	}

	/**
	 * @param string $boardId
	 * @return array
	 * @throws Exception
	 */
	protected function getBoardData($boardId) {
		if (!isset($this->sprintConfig['WMDB']['Forger']['Boards'][$boardId]['Query'])) {
			throw new Exception('No sprint query found');
		}
		$out = [
			'Open' => [],
			'WIP' => [],
			'Done' => [],
			'BLOCKED' => []
		];
		$fullRequest = [
			'query' => $this->sprintConfig['WMDB']['Forger']['Boards'][$boardId]['Query'],
			'filter' => $this->queryFilters(),
		];
		#\TYPO3\Flow\var_dump($fullRequest);
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		foreach ($resultSet->getResults() as $ticket) {
			$status = $ticket->__get('status');
//			$out[$this->defineBoardGroup($status['name'])][$status['name']][] = $ticket->getData('id');
			$out[$this->defineBoardGroup($status['name'])][] = $ticket->getData('id');
		}
		return $out;
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
				$metaStatus = 'WIP';
				break;
			case 'Accepted':
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
}