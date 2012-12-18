<?php

function get_locale() {
	return "zh-cn";
}

/**
 * Retrieves the translation of $text. If there is no translation, or
 * the domain isn't loaded the original text is returned.
 *
 * @see __() Don't use translate() directly, use __()
 * @since 2.2.0
 * @uses apply_filters() Calls 'gettext' on domain translated text
 *		with the untranslated text as second parameter.
 *
 * @param string $text Text to translate.
 * @param string $domain Domain to retrieve the translated text.
 * @return string Translated text
 */
function translate( $text, $domain = 'default' ) {
	$translations = &get_translations_for_domain( $domain );
	return $translations->translate($text);
}


/**
 * Retrieves the translation of $text. If there is no translation, or
 * the domain isn't loaded the original text is returned.
 *
 * @see translate() An alias of translate()
 * @since 2.1.0
 *
 * @param string $text Text to translate
 * @param string $domain Optional. Domain to retrieve the translated text
 * @return string Translated text
 */
function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

/**
 * Displays the returned translated text from translate().
 *
 * @see translate() Echoes returned translate() string
 * @since 1.2.0
 *
 * @param string $text Text to translate
 * @param string $domain Optional. Domain to retrieve the translated text
 */
function _e( $text, $domain = 'default' ) {
	echo translate( $text, $domain );
}

/**
 * Loads a MO file into the domain $domain.
 *
 * If the domain already exists, the translations will be merged. If both
 * sets have the same string, the translation from the original value will be taken.
 *
 * On success, the .mo file will be placed in the $l10n global by $domain
 * and will be a MO object.
 *
 * @since 1.5.0
 * @uses $l10n Gets list of domain translated string objects
 *
 * @param string $domain Unique identifier for retrieving translated strings
 * @param string $mofile Path to the .mo file
 * @return bool true on success, false on failure
 */
function load_textdomain($domain, $mofile) {
	$l10n = get_l10n();

	if ( !is_readable( $mofile ) ) return false;

	$mo = new MO();
	if ( !$mo->import_from_file( $mofile ) ) return false;

	if ( isset( $l10n[$domain] ) )
		$mo->merge_with( $l10n[$domain] );

	$l10n[$domain] = &$mo;
	set_l10n($l10n);
	return true;
}

/**
 * Loads default translated strings based on locale.
 *
 * Loads the .mo file in WP_LANG_DIR constant path from WordPress root. The
 * translated (.mo) file is named based off of the locale.
 *
 * @since 1.5.0
 */
function load_default_textdomain() {
	$locale = get_locale();
	$app_module = new App_Module();
	$mofile = $app_module->get_module_config('default_theme') . "/$locale.mo";
	return load_textdomain('default', $mofile);
}

/**
 * Returns the Translations instance for a domain. If there isn't one,
 * returns empty Translations instance.
 *
 * @param string $domain
 * @return object A Translation instance
 */
function &get_translations_for_domain( $domain ) {
	$l10n = get_l10n();
	$empty = new Translations();
	if ( isset($l10n[$domain]) )
		return $l10n[$domain];
	else
		return $empty;
}

function get_l10n(){
	return Session::get_instance()->get_('l10n');
}
function set_l10n($l10n){
	return Session::get_instance()->set_('l10n',$l10n);
}
?>