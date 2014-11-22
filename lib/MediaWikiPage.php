<?php

/**
 * Class MediaWikiPage
 *
 * Represents a MediaWiki page
 */
class MediaWikiPage {
	///
	/// Core properties and constructor
	///

	/**
	 * The code of the project the page is stored on (e.g. enwiki)
	 *
	 * @var string
	 */
	private $projectCode;

	/**
	 * The page's title
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The page's namespace
	 *
	 * @var int
	 */
	private $namespace;

	/**
	 * Initializes a new instance of the MediaWikiPage class
	 *
	 * @param string $projectCode The page's project (e.g. enwiki)
	 * @param string $title The page's title
	 * @param int $namespace The page's namespace, as a numeric identifier [facultative]
	 */
	public function __construct ($projectCode, $title, $namespace = 0) {
		$this->projectCode = $projectCode;
		$this->title = $title;
		$this->namespace = $namespace;
	}

	///
	/// Helper methods — database
	///

	/**
	 * Gets a database instance
	 *
	 * @return mysqli a MySQL improved instance allowing to use the table of the page's project
	 */
	private function getDb() {
		return ReplicationDatabaseFactory::get($this->projectCode);
	}

	///
	/// Methods using core properties and database access
	///

	/**
	 * The page identifier
	 *
	 * @return int|null
	 */
	function getId () {
		$title = static::getNormalizedTitleForPage($this->title);

		$sql = "SELECT page_id FROM page WHERE page_title = '$title' AND page_namespace = '$this->namespace'";
		$row = $this->getDb()
			->query($sql)
			->fetch_array();

		if ($row) {
			return $row['page_id'];
		}
		return null;
	}

	/**
	 * Gets normalized title
	 *
	 * @return string the normalized title
	 */
	function getNormalizedTitle () {
		return static::getNormalizedTitleForPage($this->title);
	}

	/**
	 * Gets display title
	 *
	 * @return string the display title
	 */
	function getDisplayTitle () {
		return static::getDisplayTitleForPage($this->title);
	}

	/**
	 * Gets full display title
	 *
	 * @return string the full display title
	 */
	function getFullDisplayTitle () {
		$title = "";

		if ($this->namespace != 0) {
			$namespaces = MediaWikiProject::getWikimediaProject($this->projectCode)->getNamespaces();
			$title .= $namespaces[$this->namespace];
			$title .= ':';
		}
		$title .= $this->getDisplayTitle();

		return $title;
	}

	/**
	 * Resolves a redirect
	 *
	 * @return int The page id of the redirect target
	 */
	public function resolveRedirect () {
		return self::resolveRedirectFromPageId($this->projectCode, $this->getId());
	}

	/**
	 * Resolves a redirect
	 *
	 * @param string $project The project the page is stored (e.g. enwiki)
	 * @param int $pageId The page id of the redirect source
	 * @return int The page id of the redirect target
	 * @throws InvalidArgumentException if the page id syntax isn't valid
	 */
	public static function resolveRedirectFromPageId ($project, $pageId) {
		//Ensures $page_id is numeric
		if (!preg_match('/^[0-9][0-9]*$/', $pageId)) {
			throw new InvalidArgumentException("$pageId isn't a valid page id (an integer is expected).");
		}

		$sql = "SELECT rd_title FROM redirect WHERE rd_from = $pageId";
		$row = ReplicationDatabaseFactory::get($project)
			->query($sql)
			->fetch_array();
		return $row['rd_title'];
	}

	/**
	 * Gets last revision id of the page
	 *
	 * @return int The article last revision ID
	 */
	function getLastRevisionId () {
		$db = $this->getDb();

		$title = $db->real_escape_string($this->getNormalizedTitle());

		$sql = "SELECT page_latest FROM page WHERE page_title = '$title' AND page_namespace = 0";
		$row = $db->query($sql)->fetch_array();
		if (!$page_id = $row['page_latest']) {
			throw new Exception("This page doesn't exist.");
		}

		return $row['page_latest'];
	}

	///
	/// Methods fetching data from wiki
	///

	/**
	 * @return MediaWikiProject
	 */
	function getProjectCode () {
		return MediaWikiProject::getWikimediaProject($this->projectCode);
	}

	/**
	 * Gets the URL of the page
	 *
	 * @return string
	 */
	function getURL () {
		$project = $this->getProjectCode();
		$url  = $project->getMainEntryPointURL();
		$url .= "?title=";
		if ($this->namespace > 0) {
			$url .= $project->getNamespaceCanonicalName($this->namespace);
			$url .= ':';
		}
		$url .= $this->getNormalizedTitle();

		return $url;
	}

	/**
	 * Gets the text of the specified article
	 *
	 * @param string $language The wikipedia project (e.g. en)
	 * @param string $article The article to read
	 * @param int $ns The namespace id
	 * @return string The article text
	 */
	function getText () {
		$opts = array('http' =>
			array(
				'user_agent'  => getUserAgent()
			)
		);
		$url = $this->getURL() . '&action=raw';
		return file_get_contents($url, false, stream_context_create($opts));
	}

	///
	/// Static helper methods
	///

	/**
	 * Gets the normalized title of a page
	 *
	 * @param string $title The page title
	 * @return string The normalized page title
	 */
	public static function getNormalizedTitleForPage ($title) {
		return str_replace(' ', '_', $title);
	}

	/**
	 * Gets the display title of a page
	 *
	 * @param string $title The page title
	 * @return string The page title to display
	 */
	public static function getDisplayTitleForPage ($title) {
		return str_replace('_', ' ', $title);
	}

	/**
	 * Determines if an identifier is a valid namespace identifier for a page
	 *
	 * @param int $namespaceId The namespace identifier
	 * @return bool true if the id is a valid namespace for a page; otherwise, false.
	 */
	public static function isValidNamespaceIdentifier ($namespaceId) {
		//Must be a positive integer or zero (we don't allow to use negative namespaces for pages).
		return is_numeric($namespaceId) && is_int((int)$namespaceId) && $namespaceId >= 0;
	}
}
