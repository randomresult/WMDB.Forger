<?php
namespace WMDB\Forger\Controller;

use TYPO3\Flow\Annotations as Flow;

/**
 * LoginController
 *
 * Handles all stuff that has to do with the login
 */
class LoginController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 * @Flow\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 * @Flow\Inject
	 */
	protected $accountRepository;

	/**
	 * @var \TYPO3\Flow\Security\AccountFactory
	 * @Flow\Inject
	 */
	protected $accountFactory;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * Injects the security context
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The security context
	 * @return void
	 */
	public function injectSecurityContext(\TYPO3\Flow\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Index action
	 *
	 * @return void
	 */
	public function indexAction() {
		// example how to access account informations
		$account = $this->securityContext->getAccount();
		//\TYPO3\Flow\var_dump($account);
	}

	/**
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 * @return void
	 */
	public function authenticateAction() {
		try {
			$this->authenticationManager->authenticate();
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Successfully logged in.'));
			$this->redirect('admin', 'Sprint');
		} catch (\TYPO3\Flow\Security\Exception\AuthenticationRequiredException $exception) {
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Wrong username or password.'));
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error($exception->getMessage()));
			throw $exception;
		}
	}

	/**
	 * @return void
	 */
	public function registerAction() {
		$this->redirect('index', 'Standard');
		// do nothing more than display the register form
	}

	/**
	 * save the registration
	 * @param string $name
	 * @param string $pass
	 * @param string $pass2
	 */
	public function createAction($name, $pass, $pass2) {

		$defaultRole = 'WMDB.Forger:Admin';

		if($name == '' || strlen($name) < 3) {
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Username too short or empty'));
			$this->redirect('register', 'Login');
		} else if($pass == '' || $pass != $pass2) {
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Password too short or does not match'));
			$this->redirect('register', 'Login');
		} else {

			// create a account with password an add it to the accountRepository
			$account = $this->accountFactory->createAccountWithPassword($name, $pass, array($defaultRole));
			$this->accountRepository->add($account);

			// add a message and redirect to the login form
			$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Account created. Please login.'));
			$this->redirect('index');
		}
		// redirect to the login form
		$this->redirect('index', 'Login');
	}

	public function logoutAction() {
		$this->authenticationManager->logout();
		$this->flashMessageContainer->addMessage(new \TYPO3\Flow\Error\Error('Successfully logged out.'));
		$this->redirect('index', 'Login');
	}

}