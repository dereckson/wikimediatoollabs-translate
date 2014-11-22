<?php

/**
 * Helps to translates a Wikipedia article.
 * Released under BSD license.
 */

include("templates/header.html");
?>
<h3>Get support</h3>
<p>Contact Dereckson on IRC on #wikipedia-fr (French support) or #wikimedia-tech (English support).</p>

<h3>Add a translate tab to the wiki interface</h3>
<p>On the <strong>source</strong> wiki, open your common.js file (e.g. on en., mine is [[User:Dereckson/vector.js]].</p>
<p>Replace 'fr' by your default target language.</p>
<p>If you use the English Wikipedia:</p>
<pre>
dckTranslateTargetLanguage = 'fr';
importScript('User:Dereckson/translate-dev.js');
</pre>
<p>If you use another Wikipedia:</p>
<pre>
addOnloadHook(function () {
	var translate = {
		//The language you translate to
		targetLanguage: 'fr',
 
		//The language you translate from
		sourceLanguage: wgDBname.substr(0, wgDBname.length - 4),
 
		//The name of the link
		tabLinkName: 'Translate',
 
	        getLink: function () {
        	        return 'http://tools.wmflabs.org/translate/text.php?article=' + wgPageName + '&from=' + this.sourceLanguage + '&to=' + this.targetLanguage;
	        },
 
		//Initializes script
		initialize: function() {
			if (wgNamespaceNumber == 0) {
				addPortletLink('p-cactions', this.getLink(), this.tabLinkName, "tab-translate", 'Translate to ' + this.targetLanguage, "t");
			}
		}
	};
 
	translate.initialize();
})
</pre>
<?php
include("templates/footer.html");

