<?php

namespace WMDB\Forger\Utilities\Slack;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;
/**
 * Class Message
 * @package WMDB\Forger\Utilities\Slack
 */
class Message {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @param array $issue
	 */
	public function sendMessage(array $issue) {
		$slackConfig = $this->configurationManager->getConfiguration( \TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'WMDB.Forger.Slack');
		$browser = new \TYPO3\Flow\Http\Client\Browser();
		$engine = new \TYPO3\Flow\Http\Client\CurlEngine();
		$browser->setRequestEngine($engine);
		if($issue['issue']['tracker']['name'] === 'Bug') {
			$color = 'danger';
		} else {
			$color = 'good';
		}
		$jsonString = json_encode([
			'channel' => '#typo3-cms-issues',
			'username' => 'Forger found a new issue',
			'icon_url' => 'https://typo3.slack.com/services/B02KBL9GG',
			'attachments' => [
				[
					'title' => $issue['issue']['subject'],
					'title_link' => 'https://forge.typo3.org/issues/'.$issue['issue']['id'],
					'text' => $issue['issue']['description'],
					'fallback' => $issue['issue']['subject'],
					'author_name' => $issue['issue']['author']['name'],
					'color' => $color,
					'fields' => [
						[
							'title' => 'Status',
							'value' => $issue['issue']['status']['name'],
							'short' => TRUE
						],
						[
							'title' => 'Tracker',
							'value' => $issue['issue']['tracker']['name'],
							'short' => TRUE
						]
					]
				]
			]
		], JSON_PRETTY_PRINT);
		$browser->request($slackConfig['Url'], 'POST', [], [], [], $jsonString);
	}
}