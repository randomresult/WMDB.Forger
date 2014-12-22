<?php
namespace WMDB\Forger\ViewHelpers;

use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class TextileViewHelper
 * @package WMDB\Forger\ViewHelpers
 */
class TextileViewHelper extends AbstractViewHelper {

	/**
	 * @return string
	 */
	public function render() {
		$parser = new \Netcarver\Textile\Parser();
		$content = $this->renderChildren();
		$content = nl2br($content);
		$content = $parser->textileThis($content);
		$content =  $this->keepTags($content);
		return $content;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function keepTags($content) {
		$search = [
			'&lt;pre&gt;',
			'&lt;/pre&gt;'
		];
		$replace = [
			'<pre><code>',
			'</code></pre>'
		];
		$content = str_replace($search, $replace, $content);
		return $this->fixCodeParts($content);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function fixCodeParts($content = '') {
		$content = str_replace("<code><br />\n", '<code>', $content);
		$pattern = '/(?s)<code>((?:(?!<\/code>).)*)<\/code>/m';
		preg_match_all($pattern, $content, $matches);
		if(isset($matches[1])) {
			foreach($matches[1] as $key => $hit) {
				$strippedHit = str_replace("\n\n", "\n", strip_tags($hit));
				$content = str_replace($hit, $strippedHit, $content);
			}
		}
		return $content;
	}
}