<?php

require_once 'MediaWikiProject.php';

/**
 * Class WikipediaProject
 *
 * Represents a Wikipedia project
 */
class WikipediaProject extends MediaWikiProject {
	/**
	 * Initialize a new instance of a WikipediaProject class
	 *
	 * @param string $languageCode The Wikipedia language code (e.g. en)
	 */
	public function __construct ($languageCode) {
		parent::__construct("http://$languageCode.wikipedia.org", "/w");
	}

	/**
	 * Gets an initialized instance of a WikipediaProject
	 *
	 * @param string $languageCode The Wikipedia language code (e.g. en)
	 * @return WikipediaProject The initialized instance
	 */
	public static function get ($languageCode) {
		return new self($languageCode);
	}

	/**
	 * Determines if the specified project code matches a valid Wikipedia project
	 *
	 * @param string $projectCode The project code to check
	 * @return bool true if the project code syntax is valid for a Wikipedia project code; otherwise, false.
	 */
	public static function isValidCode ($projectCode) {
		//Project names be alphanumerical, with dashes allowed
		if (!preg_match('/^[a-z][a-z\-]*$/', $projectCode)) {
			return false;
		}
		return substr($projectCode, -1) != '-';
	}
}
