<?php

/**
 * Helps to translates a Wikipedia article.
 * Released under BSD license.
 */

require('lib/core.php');
include("lib/LinksTranslate.php");
include("templates/header.html");

if (isset($_REQUEST['from']) && isset($_REQUEST['to']) && isset($_REQUEST['article'])) {
    echo '<h3>Your query</h3>';
    try {
		$ns = array_key_exists('ns', $_REQUEST) ? $_REQUEST['ns'] : 0;
		$trim = array_key_exists('trim', $_REQUEST) ? (bool)$_REQUEST['trim'] : false;
		$translate = new LinksTranslate($_REQUEST['from'], $_REQUEST['to'], $_REQUEST['article'], $ns);
	    $links = $translate->translateLinks();

	    echo "<p><a href=\"/translate/text.php?article=$_REQUEST[article]&from=$_REQUEST[from]&to=$_REQUEST[to]&ns=$ns\">Replace these links in the source article text.</a></p>";
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>Source wiki link</th><th>Target wiki link</th><th colspan="2">Go to</th></thead><tbody>';
        foreach ($links as $link) {
            $links = "<a href=\"http://$_REQUEST[from].wikipedia.org/wiki/$link[0]\">$_REQUEST[from]</a>";
            if ($link[1]) {
                 $links .= " <a href=\"http://$_REQUEST[to].wikipedia.org/wiki/$link[1]\">$_REQUEST[to]</a>";
            } elseif ($trim) {
                 continue;
            }
            echo "<tr><td>$link[0]</td><td>$link[1]</td><td>$links</td></tr>";
        }
        echo '</tbody></table>';
		echo "<p><a href=\"/translate/text.php?article=$_REQUEST[article]&from=$_REQUEST[from]&to=$_REQUEST[to]&ns=$ns\">Replace these links in the source article text.</a></p>";
    } catch (Exception $e) {
        echo '<p style="color: red; font-width: 900;">', $e->getMessage(), '</p>';
    }
}
$article = isset($_REQUEST['article']) ? $_REQUEST['article'] : '';
$from = isset($_REQUEST['from']) ? $_REQUEST['from'] : 'en';
$to = isset($_REQUEST['to']) ? $_REQUEST['to'] : 'fr';
?>
<h3>New query</h3>
<form method="GET">
    <p><label for="article">Source article:</label> <input id="article" name="article" value="<?= $article ?>" size="48" /></p>
    <p><label for="from">Source project:</label> <input id="from" name="from" value="<?= $from ?>" size="4" />.wikipedia.org</p>
    <p><label for="to">Target project:</label> <input id="to" name="to" value="<?= $to ?>" size="4" />.wikipedia.org</p>
    <p><input name="trim" id="trim" type="checkbox" value="1" <?= (!$_REQUEST || $_REQUEST['trim']) ? 'checked' : '' ?> /> <label for="trim">Don't print links without interwiki</label>
    <p><input type="submit" class="btn btn-default" /></p>
</form>

<hr />
<h3>Description</h3>
<p>This tool helps to translate the internal links, looking for each of them if an interwiki exists to the target wiki.</p>

<h3>Instructions</h3>
<p>The source article and project are the article to translate.</p>
<p>The target project is the Wikipedia project where you'll publish the translation.</p>
<p><a href="/translate/?article=Metasyntactic+variable&from=en&to=fr&trim=1">Example, where we want to translate [[en:Metasyntactic variable]] to fr.</a>

<h3>Note</h3>
<p>The interwikis are stored by alphabetical order in the MediaWiki database. To sort by apparition order would require to read source text, and then, would slow the process.</p>

<?php
include('templates/footer.html');
