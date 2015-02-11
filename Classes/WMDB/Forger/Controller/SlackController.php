<?php
namespace WMDB\Forger\Controller;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Http\Client\CurlEngine;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * LoginController
 *
 * Handles all stuff that has to do with the login
 */
class SlackController extends ActionController {


	/**
	 * Index action
	 *
	 * @return void
	 */
	public function indexAction() {}

	/**
	 * @param string $email
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function sendInviteAction($email, $firstName, $lastName) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL) && trim($firstName) === '' && trim($lastName) === '') {
			$this->redirect('index');
		}
		$this->sendApiRequest($email, $firstName, $lastName);
		$this->view->assignMultiple(
			[
				'email' => $email,
				'firstName' => $firstName,
				'lastName' => $lastName,
			]
		);
	}

	/**
	 * @param string $email
	 * @param string $firstName
	 * @param string $lastName
	 * @return bool
	 * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 */
	protected function sendApiRequest($email, $firstName, $lastName) {
		$browser = new Browser();
		$engine = new CurlEngine();
		$browser->setRequestEngine($engine);

		$jsonString = 'email='.$email.'&first_name='.$firstName.'&last_name='.$lastName.'&token='.$this->settings['Slack']['token'].'&set_active=true';
		return $browser->request($this->settings['Slack']['TeamUrl'] . '/api/users.admin.invite?t=' . time(), 'POST', [], [], [], $jsonString);
	}

}