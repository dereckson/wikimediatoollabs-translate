<?php

/**
 * Prints human-readable information about a variable.
 *
 * It behaves like the print_r command, but the output is enclosed in pre tags,
 * to have a preformatted HTML output.
 *
 * @param mixed $expression The expression to be printed
 */
function dprint_r ($expression) {
	echo '<pre>';
	print_r($expression);
	echo '</pre>';
}

/**
 * Gets the user agent to use in HTTP connections initiated by the application
 *
 * @return string The user agent
 */
function getUserAgent () {
	if (defined("USER_AGENT")) {
		return USER_AGENT;
	}

	return 'WikimediaToolLabsTool/0.0';
}
