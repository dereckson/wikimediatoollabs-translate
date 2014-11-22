<?php

/**
 * Class MediaWikiProject
 *
 * Represents a MediaWiki project
 */
class MediaWikiProject {
	/**
	 * The URL of the project wiki
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The script path (e.g. "/w" or "")
	 *
	 * @var string
	 */
	private $scriptPath;

	/**
	 * Initializes a new instance of the MediaWikiProject class
	 */
	public function __construct ($url, $scriptPath) {
		$this->url = $url;
		$this->scriptPath = $scriptPath;
	}

	/**
	 * Gets a MediaWikiProject instance for a specified Wikimedia project
	 *
	 * @param string $projectCode Wikimedia project code
	 * @return MediaWikiProject the initialized instance
	 */
	public static function getWikimediaProject ($projectCode) {
		if (substr($projectCode, -4) != "wiki") {
			throw new InvalidArgumentException("Not currently handled project code: $projectCode");
		}

		$project = substr($projectCode, 0, -4);
		switch ($project) {
			case "commons":
			case "meta":
			case "species":
				return new self("http://$project.wikimedia.org", "/w");

			case "wikidata":
				return new self("http://www.wikidata.org", "/w");

			default:
				return WikipediaProject::get($project);
		}
	}

	/**
	 * Gets specified namespace canonical name
	 *
	 * @param int $namespaceId The namespace identifier
	 */
	public function getNamespaceCanonicalName ($namespaceId) {
		if ($namespaceId == 0) {
			return "";
		}

		$namespaces = $this->getNamespaces();
		if (!array_key_exists($namespaceId, $namespaces)) {
			throw new InvalidArgumentException("Not a valid namespace identifier for this wiki.");
		}

		return $namespaces[$namespaceId];
	}

	/**
	 * Gets project's namespaces
	 *
	 * @return Array The namespaces (unstable format, yet to be decided and documented)
	 */
	public function getNamespaces () {
		//Gets information from API
		$url = $this->getAPIEntryPointURL() . "?action=query&meta=siteinfo&siprop=namespaces|namespacealiases&format=php";
		$data = file_get_contents($url);
		$data = unserialize($data);

		//Reads canonical namespaces
		$namespaces = array();
		foreach ($data['query']['namespaces'] as $ns => $nsinfo) {
			if ($ns == 0) continue;
			$namespaces[$ns] = $nsinfo['canonical'];
		}

		return $namespaces;
	}

	/**
	 * Gets the API entry point URL
	 *
	 * @return string the API entry point URL
	 */
	public function getAPIEntryPointURL () {
		return $this->getEntryPointURL("api.php");
	}

	public function getMainEntryPointURL () {
		return $this->getEntryPointURL("index.php");
	}

	/**
	 * Gets the entry point URL for the specified application entry point file
	 *
	 * @param $file the entry point application file
	 * @return string the entry point URL
	 */
	public function getEntryPointURL ($file) {
		return $this->getApplicationURL() . "/" . $file;
	}

	/**
	 * Gets the application URL
	 *
	 * @return string the service URL
	 */
	public function getApplicationURL () {
		return $this->url . $this->scriptPath;
	}
}
