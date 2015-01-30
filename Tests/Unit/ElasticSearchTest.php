<?php
/**
 * Created by PhpStorm.
 * User: mathiasschreiber
 * Date: 27.01.15
 * Time: 17:54
 */

namespace WMDB\Forger\Utilities\ElasticSearch;

/**
 * Class ElasticSearchTest
 * @package WMDB\Forger\Utilities\ElasticSearch
 */
class ElasticSearchTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function PagingTestPositiveTest() {
		$myObject = $this->getAccessibleMock('\\WMDB\Forger\\Utilities\\ElasticSearch\\ElasticSearch', array('dummy'));
		$myObject->_set('totalHits', 101);
		$myObject->_set('perPage', 33);
		$ret = $myObject->_call('getPages');

		$this->assertEquals([
			0 => 1,
			1 => 2,
			2 => 3,
			3 => 4,
		], $ret);
	}
	/**
	 * @test
	 */
	public function PagingTestNegativeTest() {
		$myObject = $this->getAccessibleMock('\\WMDB\Forger\\Utilities\\ElasticSearch\\ElasticSearch', array('dummy'));
		$myObject->_set('totalHits', 101);
		$myObject->_set('perPage', 33);
		$ret = $myObject->_call('getPages');

		$this->assertNotEquals([
			0 => 1,
			1 => 2,
			2 => 3
		], $ret);
	}
}
