<?php
namespace WMDB\Forger\Command;

use TYPO3\Flow\Annotations as Flow;
use Elastica;
use WMDB\Forger\Utilities\ElasticSearch;
use TYPO3\Flow\Cli;


/**
 * Class testCommandController
 * @package WMDB\Forger\Command
 */
class SetupCommandController extends Cli\CommandController {

	/**
	 * Settings from the YAML files
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \Elastica\Index
	 */
	protected $index;

	/**
	 * Sets up the mapping for Elasticsearch
	 */
	public function elasticMappingCommand() {
		$this->index = $this->connectToElastic();
		$this->applySettings($this->settings['Elasticsearch']['Settings']);
		foreach ($this->settings['Elasticsearch']['Mapping'] as $typeName => $mappingInstructions) {
//			if($typeName === 'review') {
				$type = $this->deleteMapping($typeName);
//			} else {
				$type = new Elastica\Type($this->index, $typeName);
//			}
			$this->setMapping($mappingInstructions, $type);
		}
	}

	/**
	 * @return \Elastica\Index
	 * @throws \TYPO3\Flow\Exception
	 */
	protected function connectToElastic() {
		$connection = new ElasticSearch\ElasticSearchConnection();
		$connection->init();
		return $connection->getIndex();
	}

	/**
	 * @param string $typeName
	 * @return \Elastica\Type
	 * @throws \Exception
	 */
	protected function deleteMapping($typeName) {
		$type = new Elastica\Type($this->index, $typeName);
		if($type->exists()) {
			try {
				$type->delete();
			} catch (\Exception $e) {
				throw new \Exception('Could not delete type '.$type->getName().'');
			}
		}
		$type = new Elastica\Type($this->index, $typeName);
		return $type;
	}

	/**
	 * Stores the settings in the index
	 * This is needed for things like tokenizers and analyzers
	 * @param array $settings
	 */
	protected function applySettings(array $settings) {
		$this->index->close();
		sleep(2);
		try {
			$this->index->setSettings($settings);
		} catch (\Exception $e) {
			$this->outputLine($e->getMessage());
		}
		sleep(2);
		$this->index->open();
	}

	/**
	 * @param array $mappingArray
	 * @param Elastica\Type $type
	 */
	protected function setMapping(array $mappingArray, Elastica\Type $type) {
		try {
			$mapping = new Elastica\Type\Mapping();
			$mapping->setType($type);
			$mapping->setProperties($mappingArray);
			$mapping->send();
		} catch (\Exception $e) {
			$this->outputLine('Could not add mapping for type '.$type->getName().'. Message was '.$e->getMessage());
		}
	}

	/**
	 * Injects the settings from the yaml file into
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}
}