<?php
namespace WMDB\Forger\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "WMDB.Forger".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Class DashboardController
 * @package WMDB\Forger\Controller
 */
class DashboardController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * The Index Action
	 * @param   string    $graph    The Name of the Graph Class to call
	 * @param   string    $type     The Document Type from Elastic to work on
	 * @return  string
	 */
	public function indexAction($graph = 'Velocity', $type = 'Gerrit') {
		$graph = $this->getGraphName($graph, $type);
		return $graph->render();
	}

	/**
	 * @param $graph
	 * @param $docType
	 * @return \WMDB\Forger\Graph\AbstractGraph
	 */
	protected function getGraphName($graph, $docType) {
		$graphName = '\\WMDB\\Forger\\Graph\\'.$docType.'\\'.$graph;
		try {
			$graph = new $graphName;
		} catch (\Exception $e) {

		}
		return $graph;
	}


}