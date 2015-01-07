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
		$out = [
			'Open' => [],
			'WIP' => [],
			'Done' => [],
			'BLOCKED' => []
		];
		$fullRequest = [
			'query' => [
				'bool' => [
					'must' => [
						'term' => [
							'focus.name' => 'On Location Sprint'
						],
					],
					'must_not' => [
						'term' => [
							'subject' => 'wip'
						]
					]
				],
			],
			'filter' => $this->queryFilters(),
		];
		$query = new El\Query($fullRequest);
		$resultSet = $this->connection->getIndex()->search($query);
		foreach ($resultSet->getResults() as $ticket) {
			$status = $ticket->__get('status');
			$out[$this->defineBoardGroup($status['name'])][$status['name']][] = $ticket->getData('id');
		}
		$this->view->assignMultiple([
			'board' => $out,
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
}