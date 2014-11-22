<?php

/**
 * Helps to translates a Wikipedia article.
 * Released under BSD license.
 */

define('USER_AGENT', 'WikimediaToolLabsTranslate/0.4');

require('lib/core.php');
include("lib/LinksTranslate.php");
include("templates/header.html");

if ($_REQUEST['from'] && $_REQUEST['to'] && $_REQUEST['article']) {
    echo '<h3>Text with translated links</h3>';
    try {
        $page_id = -1;
        $ns = array_key_exists('ns', $_REQUEST) ? $_REQUEST['ns'] : 0;
        $prefix = array_key_exists('prefix', $_REQUEST) ? $_REQUEST['prefix'] : '';
		$articleTitle = $_REQUEST['article'];
		$sourceProject = $_REQUEST['from'] . 'wiki';

        $translate = new LinksTranslate($_REQUEST['from'], $_REQUEST['to'], $articleTitle, $ns);
        $links = $translate->translateLinks();

        $page = new MediaWikiPage($sourceProject, $articleTitle, $ns);
        $pageText = $page->getText();
        $translate->protectText($pageText, '{{', '}}');
        $text = $translate->substituteLinks($page->getText());
        $translate->injectProtectedText($text);
        $template = $translate->getTranslationReferenceTemplate($page->getLastRevisionId());

        echo '<p id="TranslationReferenceTemplate">', $template, '</p>';
        echo '<textarea cols=80 rows=20>';
        echo $text;
        echo '</textarea>';
        echo "<p><a href=\"/translate/?article=$articleTitle&from=$_REQUEST[from]&to=$_REQUEST[to]&ns=$ns\">See only wikilinks.</a></p>";
    } catch (Exception $e) {
        echo '<p style="color: red; font-width: 900;">', $e->getMessage(), '</p>';
    }
}
?>
<h3>New query</h3>
<form method="GET">
    <p><label for="article">Source article:</<label> <input id="article" name="article" value="<?= $_REQUEST['article'] ?>" size="48"/></p>
    <p><label for="from">Source project:</<label> <input id="from" name="from" value="<?= $_REQUEST['from'] ? $_REQUEST['from'] : 'en' ?>" size="4"/>.wikipedia.org</p>
    <p><label for="to">Target project:</<label> <input id="to" name="to" value="<?= $_REQUEST['to'] ? $_REQUEST['to'] : 'fr' ?>" size="4"/>.wikipedia.org</p>
    <p><input type="submit" /></p>
</form>
</div>

<hr />
<h3>Description</h3>
<p>This tool helps to translate the internal links, looking for each of them if an interwiki exists to the target wiki.</p>

<h3>Instructions</h3>
<p>The source article and project are the article to translate.</p>
<p>The target project is the Wikipedia project where you'll publish the translation.</p>
<p><a href="http://toolserver.org/~dereckson/translate/text.php?article=Metasyntactic+variable&from=en&to=fr&trim=1">Example, where we want to 
translate [[en:Metasyntactic variablevariable]] to fr.</a>

<h3>Note</h3>
<p>The interwikis are stored by alphabetical order in the MediaWiki database. To sort by apparition order would require to read source text, and then, would slow the process.</p>

<?php
include('templates/footer.html');
