<?php
namespace WMDB\Forger\Command;

use TYPO3\Flow\Annotations as Flow;
use Elastica;
use Redmine;
use WMDB\Forger\Utilities\ElasticSearch;
use WMDB\Utilities\Utility\GeneralUtility;
use TYPO3\Flow\Cli;


/**
 * Class GerritCommandController
 * @package WMDB\Forger\Command
 */
class GerritCommandController extends Cli\CommandController {

	/**
	 * Settings from the YAML files
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \Redmine\Client;
	 */
	protected $redmineClient;
	
	protected $counter = array(
		'noTicket' => 0,
		'noArray' => 0,
		'close' => 0,
	);

	/**
	 * Generates a list of abandoned reviews with open issues in Redmine Wiki Syntax
	 */
	public function abandonedCommand() {
		GeneralUtility::writeLine('|_.Issue|_.Subject|_. Gerrit Link|', 'yellow');
		$this->getAbandonedPatches();
		\TYPO3\Flow\var_dump($this->counter);
	}

	/**
	 * Injects the settings from the yaml file into
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	protected function getAbandonedPatches() {
		$json = file_get_contents('https://review.typo3.org/changes/?q=project:Packages/TYPO3.CMS%20AND%20status:abandoned&n=200000&o=CURRENT_REVISION');
		$data = json_decode(str_replace(")]}'", '', $json));
		foreach ($data as $change) {
			$ticketId = $this->getTicketId($change->id, $change->current_revision);
			if($ticketId > 0) {
				$this->getTicketStatus($ticketId, $change->_number);
			} else {
				$this->counter['noTicket']++;
			}
		}

	}

	/**
	 * @param string $changeId
	 * @param string $revision
	 * @return int
	 */
	protected function getTicketId($changeId, $revision) {
		$json = file_get_contents('https://review.typo3.org/changes/' . $changeId .'/revisions/' . $revision .'/commit');
		$pattern = '/#([0-9]{4,5})/';
		preg_match($pattern, $json, $matches);
		if(isset($matches[1])) {
			return $matches[1];
		} else {
			return 0;
		}
	}

	/**
	 * @param int $issueId
	 * @param $gerritNumber
	 */
	protected function getTicketStatus($issueId, $gerritNumber) {
		$this->redmineClient = new Redmine\Client(
			$this->settings['Redmine']['url'],
			$this->settings['Redmine']['apiKey']
		);
		$res = $this->redmineClient->api('issue')->show($issueId);
		if (is_array($res)) {
			switch($res['issue']['status']['name']) {
				case 'Closed':
					break;
				case 'Resolved':
					break;
				case 'Rejected':
					break;
				default:
					$this->counter['close']++;
					GeneralUtility::writeLine('|Issue #'.$issueId.'|'.$res['issue']['subject'].'|https://review.typo3.org/#/c/'.$gerritNumber.'/|', 'yellow');
			}
		} else {
			$this->counter['noArray']++;
		}
	}
}