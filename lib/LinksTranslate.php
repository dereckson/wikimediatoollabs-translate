<?php

require_once 'MediaWikiPage.php';
require_once 'ReplicationDatabaseFactory.php';
require_once 'WikipediaProject.php';

/**
 * Class LinksTranslate
 *
 * Allows to get links translation following interwiki
 */
class LinksTranslate {
	/**
	 * The project to translate from
	 *
	 * @var string
	 */
	private $sourceProject;

	/**
	 * The project to translate to
	 *
	 * @var string
	 */
	private $targetProject;

	/**
	 * The article
	 *
	 * @var string
	 */
	private $pageTitle;

	/**
	 * The namespace
	 *
	 * @var int
	 */
	private $namespace;

	/**
	 * An array with the links of the source article as keys, and their translation in the target project as values.
	 *
	 * @var Array
	 */
	private $links;

	/**
	 * Initializes a new LinksTranslationTable instance
	 *
	 * @param string $sourceProject the project to translate from (e.g. en)
	 * @param string $targetProject the project to translate to   (e.g. fr)
	 * @param string $pageTitle the article on the source project where to find links
	 * @throws InvalidArgumentException if the source or target project doesn't seem to be a valid Wikipedia project
	 */
	public function __construct ($sourceProject, $targetProject, $pageTitle, $namespace = 0) {
		if (!WikipediaProject::isValidCode($sourceProject)) {
			throw new InvalidArgumentException("$sourceProject doesn't seem to be a valid Wikipedia project.");
		}
		if (!WikipediaProject::isValidCode($targetProject)) {
			throw new InvalidArgumentException("$targetProject doesn't seem to be a valid Wikipedia project.");
		}

		$this->sourceProject = $sourceProject;
		$this->targetProject = $targetProject;
		$this->pageTitle = $pageTitle;
		$this->namespace = $namespace;
	}

	/**
	 * Gets interwiki link from the specified article
	 *
	 * @param string $article The article to find the interwiki
	 * @param int $namespace The namespace number
	 * @return string the interwiki or, if it doesn't exist, an empty string
	 */
	public function getInterwiki ($article, $namespace = 0) {
		//Validates arguments
		if (!MediaWikiPage::isValidNamespaceIdentifier($namespace)) {
			throw new InvalidArgumentException("Namespace must be a positive integer.");
		}

		$db = ReplicationDatabaseFactory::get($this->sourceProject . "wiki");

		$article = $db->real_escape_string(
			MediaWikiPage::getNormalizedTitleForPage($article)
		);
		$to = strtolower($this->targetProject);

		$sql = "SELECT page_id, page_is_redirect FROM page WHERE page_title = '$article' AND page_namespace = $namespace";
		$result = $db->query($sql);
		if ($row = $result->fetch_array()) {
			if ($row['page_is_redirect'] == 0) {
				$sql = "SELECT ll_title FROM langlinks WHERE ll_from = $row[page_id] AND ll_lang = '$to'";
				$subRow = $db->query($sql)->fetch_array();
				return $subRow['ll_title'];
			} else {
				$redirect = MediaWikiPage::resolveRedirectFromPageId($this->sourceProject . 'wiki',  $row['page_id']);
				return $this->getInterwiki($redirect, $namespace);
			}
		}
		return '';
	}

	/**
	 * Gets link from the specified articles, and finds interwiki links for them.
	 *
	 * @return Array A 2D array of results, one line per link in the source article. Each line is an array containing two string items : the source link and the target link, or null, if the source link doesn't have an interwiki to the target project.
	 * @throws InvalidArgumentException if the namespace is invalid
	 * @throws InvalidArgumentException if the page doesn't exist
	 */
	public function translateLinks () {
		//Validates and normalizes arguments
		if (!MediaWikiPage::isValidNamespaceIdentifier($this->namespace)) {
			throw new InvalidArgumentException("Namespace must be a positive integer.");
		}

		$page = new MediaWikiPage($this->sourceProject . 'wiki', $this->pageTitle, $this->namespace);
		if (!$pageId = $page->getId()) {
			throw new InvalidArgumentException("This page doesn't exist.");
		}

		$links = array();
		$sql = "SELECT pl_namespace, pl_title FROM pagelinks WHERE pl_from = $pageId";
		$result = ReplicationDatabaseFactory::get($this->sourceProject . "wiki")->query($sql);
		while ($row = $result->fetch_array()) {
			$ns = $row['pl_namespace'];
			$title = $row['pl_title'];

			$page = new MediaWikiPage($this->sourceProject . 'wiki', $title, $ns);
			$links[] = array(
				$page->getFullDisplayTitle(),
				$this->getInterwiki($page->getDisplayTitle(), $ns)
			);
		}
		$this->links = $links;
		return $links;
	}

	/**
	 * Replaces the wiki links in the specified text.
	 *
	 * @param string $text The source text
	 * @return string The substituted text
	 *
	 */
	public function substituteLinks ($text) {
		foreach ($this->links as $link) {
			if ($link[1] == '') {
				//Quick test to gather feedback from Ælfgar and Feldo following a request of the last.
				//This doesn't handle pipes or anchors.
				//The final implementation will use preg_replace with a callback supporting several templates,
				//like {{ill}} on English Wikipedia.
				if ($this->targetProject == 'fr') {
					$text = str_replace(
						'[[' . $link[0] . ']]',
						'{{Lien|trad=' . $link[0] . "|lang=$this->sourceProject|fr=$link[0]}}",
						$text
					);
					$text = str_replace(
						'[[' . lcfirst($link[0]) . ']]',
						'{{Lien|trad=' . $link[0] . "|lang=$this->sourceProject|fr=$link[0]|texte=" . lcfirst($link[0]) . '}}',
						$text
					);
				}
			} else {
				$text = str_replace('[[' . $link[0] . '|',  '[[' . $link[1] . '|',  $text);
				$text = str_replace('[[' . $link[0] . ']]', '[[' . $link[1] . ']]', $text);
				$text = str_replace('[[' . $link[0] . '#',  '[[' . $link[1] . '#',  $text);
				$text = str_replace('[[' . lcfirst($link[0]) . '|',  '[[' . lcfirst($link[1]) . '|',  $text);
				$text = str_replace('[[' . lcfirst($link[0]) . ']]', '[[' . lcfirst($link[1]) . ']]', $text);
				$text = str_replace('[[' . lcfirst($link[0]) . '#',  '[[' . lcfirst($link[1]) . '#',  $text);
			}
		}
		return $text;
	}

	/**
	 * Gets the translation reference template
	 *
	 * @param int $revisionId The source article's last revision id
	 * @return string The translation reference template
	 */
	public function getTranslationReferenceTemplate ($revisionId) {
		switch ($this->targetProject) {
			case 'af': return "{{Vertaling/Verwysing|$this->sourceProject|$this->pageTitle}}";
			case 'el': return "{{Ενσωμάτωση κειμένου|$this->sourceProject|$this->pageTitle|oldid=$revisionId}}";
			case 'es': return "{{Traducido ref|$this->sourceProject|$this->pageTitle|oldid=$revisionId|trad=total}}<br />{{Traducido ref|$this->sourceProject|$this->pageTitle|oldid=$revisionId|trad=parcial}}";
			case 'et': return "{{Tõlkimine/Ref|$this->sourceProject|$this->pageTitle|oldid=$revisionId}}";
			case 'fr': return "{{Traduction/Référence|$this->sourceProject|$this->pageTitle|$revisionId}}";
			case 'hu': return "{{Fordítás|$this->sourceProject|$this->pageTitle|oldid=$revisionId}}";
			case 'id': return "{{Translation/Ref|$this->sourceProject|$this->pageTitle}}";
			case 'pt': return "{{Tradução/ref|$this->sourceProject|$this->pageTitle|oldid=$revisionId}}";
			case 'ru': return "{{Источник/перевод|$this->sourceProject|$this->pageTitle|версия=$revisionId}}";
			case 'sv': return "{{" . $this->sourceProject . "wp|artikel=$this->pageTitle}}";
			case 'zh': return "{{Translation/Ref|lang=$this->sourceProject|article=$this->pageTitle|oldid=$revisionId}}";

			case 'en':
			case 'gu':
			case 'ja':
			case 'ne':
			           return "{{Translation/Ref|$this->sourceProject|$this->pageTitle|oldid=$revisionId}}";

			default:   return "Last revision id on $this->sourceProject (<em>oldid</em>): $revisionId";
		}
	}
}
