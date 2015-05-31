<?php
/**
 * Created by PhpStorm.
 * User: florianpeters
 * Date: 07.10.14
 * Time: 15:34
 */

namespace WMDB\Forger\Utilities\ElasticSearch;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Exception;

/**
 * Class ElasticSearchConnection
 * @package WMDB\Forger\Utilities\ElasticSearch
 */
class ElasticSearchConnection {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \Elastica\Index
	 */
	protected $index;

	/**
	 * @return \Elastica\Index
	 */
	public function getIndex() {
		return $this->index;
	}

	public function init() {
		$conf = $this->configurationManager->getConfiguration( \TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'WMDB.Forger.Elasticsearch.Credentials');
		if(is_array($conf) && count($conf) > 0 && isset($conf['host']) && isset($conf['port']) && isset($conf['path']) && isset($conf['transport'])) {

			$elasticClientConfiguration = array(
				'host' => $conf['host'],
				'port' => $conf['port'],
				'path' => $conf['path'],
				'transport' => $conf['transport']
			);
			if (isset($conf['username']) && isset($conf['password'])) {
				$elasticClientConfiguration['headers'] = array(
					'Authorization' => 'Basic ' . base64_encode($conf['username'] . ':' . $conf['password']) . '=='
				);
			}

			$elasticaClient = new \Elastica\Client($elasticClientConfiguration);

			$this->index = $elasticaClient->getIndex($conf['index']);
		} else {
			throw new Exception('Could not load elastic-credentials! Please check your setting.yaml!');
		}
	}

}