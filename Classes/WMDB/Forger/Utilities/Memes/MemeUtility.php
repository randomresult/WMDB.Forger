<?php

namespace WMDB\Forger\Utilities\Memes;

/**
 * Class MemeUtility
 *
 * @package WMDB\Forger\Utilities\Memes
 */
class MemeUtility {
	/**
	 * @return mixed
	 * @throws \TYPO3\Flow\Utility\Exception
	 */
	static public function getRandomMeme() {
		$folder = FLOW_PATH_PACKAGES.'Application/WMDB.Forger/Resources/Public/images/memes/';
		$listOfFiles = \TYPO3\Flow\Utility\Files::readDirectoryRecursively($folder);
		return basename($listOfFiles[rand(0,(count($listOfFiles)-1))]);
	}
}