<?php
namespace WMDB\Forger\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "WMDB.Forger".           *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class BoardConfig {
	/**
	 * The name of the SprintBoard
	 *
	 * @var string
	 * @Flow\Validate(type="StringLength", options = {"minimum"=1, "maximum"=60})
	 * @Flow\Validate(type="NotEmpty")
	 */
	protected $name;

	/**
	 * The SprintBoard Config
	 *
	 * @var string
	 * @Flow\Validate(type="NotEmpty")
	 * @ORM\Column(type="text")
	 */
	protected $config;

	/**
	 * Archived Flag
	 *
	 * @var bool
	 */
	protected $archived;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @param mixed $config
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * @return boolean
	 */
	public function isArchived() {
		return $this->archived;
	}

	/**
	 * @param boolean $archived
	 */
	public function setArchived($archived) {
		$this->archived = $archived;
	}

}