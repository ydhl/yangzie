<?php
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

function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

function _e( $text, $domain = 'default' ) {
	echo translate( $text, $domain );
}

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

function load_default_textdomain() {
	if(!function_exists("get_locale")){
		return;
	}
	$locale = get_locale();
	$mofile =  "vendor/i18n/$locale.mo";
	return load_textdomain('default', $mofile);
}

function get_i18n_cache(){
	return YZE_Session::get_instance()->get_('i18n');
}
function set_i18n_cache($i18n){
	return YZE_Session::get_instance()->set_('i18n', $i18n);
}
?>