<?php
namespace yangzie;

use MO;
use Translations;
use const YZE_APP_INC;

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

//    if ( isset( $l10n[$domain] ) )
//        $mo->merge_with( $l10n[$domain] );

	$l10n[$domain] = &$mo;
	set_i18n_cache($l10n);
	return true;
}

function load_default_textdomain() {
	$local = YZE_Hook::do_hook("get_locale", "zh-cn");
	if(!function_exists("\yangzie\script_locale") && !$local){
		return;
	}
	if(function_exists("\yangzie\script_locale")){// for script tool
		$locale = script_locale();
	}else{
		$locale = $local;
	}

	$mofile =  YZE_APP_INC."vendor/i18n/$locale.mo";
	return load_textdomain('default', $mofile);
}

function get_i18n_cache(){
	return YZE_Session_Context::get_instance()->get('i18n');
}
function set_i18n_cache($i18n){
	return YZE_Session_Context::get_instance()->set('i18n', $i18n);
}
?>
