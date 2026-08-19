<?php
namespace app\vendor\pomo;

/**
 * Contains NOOP_Translations class
 *
 * @version $Id: translations.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage translations
 */

if ( ! class_exists( __NAMESPACE__ . '\NOOP_Translations', false ) ) :
	/**
	 * Provides the same interface as Translations, but doesn't do anything.
	 *
	 * @since 2.8.0
	 */
	#[AllowDynamicProperties]
	class NOOP_Translations {
		/**
		 * List of translation entries.
		 *
		 * @since 2.8.0
		 *
		 * @var Translation_Entry[]
		 */
		public $entries = array();

		/**
		 * List of translation headers.
		 *
		 * @since 2.8.0
		 *
		 * @var array<string, string>
		 */
		public $headers = array();

		public function add_entry( $entry ) {
			return true;
		}

		/**
		 * Sets a translation header.
		 *
		 * @since 2.8.0
		 *
		 * @param string $header
		 * @param string $value
		 */
		public function set_header( $header, $value ) {
		}

		/**
		 * Sets translation headers.
		 *
		 * @since 2.8.0
		 *
		 * @param array $headers
		 */
		public function set_headers( $headers ) {
		}

		/**
		 * Returns a translation header.
		 *
		 * @since 2.8.0
		 *
		 * @param string $header
		 * @return false
		 */
		public function get_header( $header ) {
			return false;
		}

		/**
		 * Returns a given translation entry.
		 *
		 * @since 2.8.0
		 *
		 * @param Translation_Entry $entry
		 * @return false
		 */
		public function translate_entry( &$entry ) {
			return false;
		}

		/**
		 * Translates a singular string.
		 *
		 * @since 2.8.0
		 *
		 * @param string $singular
		 * @param string $context
		 */
		public function translate( $singular, $context = null ) {
			return $singular;
		}

		/**
		 * Returns the plural form to use.
		 *
		 * @since 2.8.0
		 *
		 * @param int $count
		 * @return int
		 */
		public function select_plural_form( $count ) {
			return 1 === (int) $count ? 0 : 1;
		}

		/**
		 * Returns the plural forms count.
		 *
		 * @since 2.8.0
		 *
		 * @return int
		 */
		public function get_plural_forms_count() {
			return 2;
		}

		/**
		 * Translates a plural string.
		 *
		 * @since 2.8.0
		 *
		 * @param string $singular
		 * @param string $plural
		 * @param int    $count
		 * @param string $context
		 * @return string
		 */
		public function translate_plural( $singular, $plural, $count, $context = null ) {
			return 1 === (int) $count ? $singular : $plural;
		}

		/**
		 * Merges other translations into the current one.
		 *
		 * @since 2.8.0
		 *
		 * @param Translations $other
		 */
		public function merge_with( &$other ) {
		}
	}
endif;
