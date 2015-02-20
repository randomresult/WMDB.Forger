<?php
namespace WMDB\Forger\Graph;

use TYPO3\Flow\Configuration\ConfigurationManager;
use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;
use TYPO3\Flow\Annotations as Flow;
/**
 * Class AbstractGraph
 * @package WMDB\Forger\Graph
 */
class AbstractGraph {
	/**
	 * @var Es\ElasticSearchConnection
	 */
	protected $connection;

	protected $chartData = [];
	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @throws \TYPO3\Flow\Exception
	 */
	public function __construct() {
		$this->connection = new Es\ElasticSearchConnection();
		$this->connection->init();
	}

	/**
	 * Makes the date string from elastic JS compatible
	 * @param string $in
	 * @return string
	 */
	protected function fixTime($in) {
		$date = substr($in, 0, 10);
		$out = str_replace('/', '-', $date);
		return $out;
	}

	/**
	 * @return string
	 */
	public function render() {
		$this->getData();
		return json_encode($this->chartData);
	}

	/**
	 * @return array
	 */
	protected function getData() {
		return [];
	}

	/**
	 * @return array
	 */
	protected function getGuides() {
		$out = [];
		$chartSettings =
			$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			                                              'WMDB.Forger.Charts');
		foreach ($chartSettings['Phases'] as $version => $phases) {
			foreach ($phases as $phaseName => $dates) {
				$out[] = [
					'fillAlpha' => 0.5,
					'date' => $dates['start'],
					'toDate' => $dates['end'],
					'label' => $phaseName,
					'position' => 'top',
					'inside' => TRUE,
					'fillColor' => $chartSettings['Colors'][$phaseName]
				];
			}
		}

		return $out;
	}
}