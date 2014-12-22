<?php
namespace WMDB\Forger\Graph;

use WMDB\Forger\Utilities\ElasticSearch as Es;
use Elastica as El;
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
}