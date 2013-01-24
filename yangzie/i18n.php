<?php


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
	if(!class_exists("Translations"))return $text;
	
	$l10n = get_i18n_cache();
	$empty = new Translations();
	if ( isset($l10n[$domain]) )
		$translations = $l10n[$domain];
	else
		$translations = $empty;
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
	$l10n = get_i18n_cache();

	if ( !is_readable( $mofile ) ) return false;

	$mo = new MO();
	if ( !$mo->import_from_file( $mofile ) ) return false;

	if ( isset( $l10n[$domain] ) )
		$mo->merge_with( $l10n[$domain] );

	$l10n[$domain] = &$mo;
	set_i18n_cache($l10n);
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
	if(!function_exists("get_locale")){
		return;
	}
	$locale = get_locale();
	$app_module = new App_Module();
	$mofile =  "components/i18n/$locale.mo";
	return load_textdomain('default', $mofile);
}

function get_i18n_cache(){
	return Session::get_instance()->get_('i18n');
}
function set_i18n_cache($i18n){
	return Session::get_instance()->set_('i18n', $i18n);
}
?>