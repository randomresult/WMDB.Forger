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
	 * @var \WMDB\Forger\Domain\Repository\BoardConfigRepository
	 * @Flow\Inject
	 */
	protected $sprintBoardRepo;

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
			$boardData = $this->getBoardData($boardId);
			$ticketCount = [];
			foreach ($boardData as $status => $tickets) {
				$ticketCount[$status] = count($tickets);
			}
			$this->view->assign('ticketCount', $this->renderJsForGraph($ticketCount));
			$this->view->assign('board', $boardData);
		}
		$this->view->assignMultiple([
			'boardMenu' => $this->makeBoardMenu($boardId),
			'context' => $this->context
		]);
	}

	public function adminAction() {
		$boards = $this->sprintBoardRepo->findAll();
		$this->view->assign('boards', $boards);
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
//		\TYPO3\Flow\var_dump(json_encode($this->sprintConfig['WMDB']['Forger']['Boards'][$boardId]['Query'], JSON_PRETTY_PRINT));
		$out = [
			'Open' => [],
			'WIP' => [],
			'Review' => [],
			'Done' => [],
			'BLOCKED' => []
		];
		$fullRequest = [
			'query' => $this->sprintConfig['WMDB']['Forger']['Boards'][$boardId]['Query'],
			'filter' => $this->queryFilters(),
			'size' => 1000
		];
		$search = $this->connection->getIndex()->createSearch($fullRequest);
		$search->addType('issue');
		$resultSet = $search->search();
		foreach ($resultSet->getResults() as $ticket) {
			$status = $ticket->__get('status');
			$getBoardGroup = $this->defineBoardGroup($status['name']);
			if ($getBoardGroup !== 'Review') {
				$out[$getBoardGroup][] = $ticket->getData('id');
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
				if (isset($journalEntry['notes']) && strstr($journalEntry['notes'], 'It is available at http://review.typo3.org/')) {
					$notes = $journalEntry['notes'];
				}
			}
			if ($notes === '') {
				return $ticket;
			}
			$pattern = '/.*http:\/\/review.typo3.org\/(?<reviewId>[0-9]{1,6})/';
			preg_match($pattern, $notes, $matches);
			if (!isset($matches['reviewId'])) {
				throw new Exception('There should be a review id in '.$notes);
			}
			$reviewType = $this->connection->getIndex()->getType('review');
			$item = $reviewType->getDocument($matches['reviewId']);
			return $item->getData();
		}
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
	 * @return string
	 */
	protected function renderJsForGraph(array $data) {
		$fullCount = 0;
		$chartConfig = [
			'type' => 'serial',
			'theme' => 'none',
			'rotate' => true,
			'marginTop' => 0,
			'dataProvider' => [$data],
			'valueAxes' => [
				[
					'stackType' => 'regular',
					'axisAlpha' => 0,
					'gridAlpha' => 0,
					'inside' => true,
					'offset' => -200
				]
			],
			'categoryAxis' => [
				'inside' => true,
				'offset' => -1000000
			]
		];
		foreach ($data as $key => $count) {
			switch($key) {
				case 'Open':
					$lineColor = '#fb7e7e';
					break;
				case 'WIP':
					$lineColor = '#A9E2FF';
					break;
				case 'Review':
					$lineColor = '#aeff91';
					break;
				case 'BLOCKED':
					$lineColor = '#FBC3C3';
					break;
				default:
					$lineColor = '#CECDCC';
			}
			$fullCount += $count;
			$chartConfig['graphs'][] = [
//				'balloonText' => '<b>[[title]]</b> => <b>[[value]]</b></span>',
				'fillAlphas' => 1,
				'labelText' => $key.': [[value]]',
//				'lineAlpha' => 0.3,
//				'title' => ucwords($key),
				'type' => 'column',
				'color' => '#000000',
				'valueField' => $key,
				'lineColor' => $lineColor
			];
		}
		$chartConfig['valueAxes'][0]['maximum'] = $fullCount;
		$content = '
		<script type="application/javascript">
		var chart = AmCharts.makeChart("stackedBoardChart", '.json_encode($chartConfig).');
		</script>
		';
		return $content;
	}
}