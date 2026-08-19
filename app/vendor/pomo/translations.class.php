<?php
namespace app\vendor\pomo;

/**
 * Class for a set of entries for translation and their associated headers
 *
 * @version $Id: translations.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage translations
 * @since 2.8.0
 */

if ( ! class_exists( __NAMESPACE__ . '\Translations', false ) ) :
	/**
	 * Translations class.
	 *
	 * @since 2.8.0
	 */
	#[AllowDynamicProperties]
	class Translations {
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

		/**
		 * Adds an entry to the PO structure.
		 *
		 * @since 2.8.0
		 *
		 * @param array|Translation_Entry $entry
		 * @return bool True on success, false if the entry doesn't have a key.
		 */
		public function add_entry( $entry ) {
			if ( is_array( $entry ) ) {
				$entry = new Translation_Entry( $entry );
			}
			$key = $entry->key();
			if ( false === $key ) {
				return false;
			}
			$this->entries[ $key ] = &$entry;
			return true;
		}

		/**
		 * Adds or merges an entry to the PO structure.
		 *
		 * @since 2.8.0
		 *
		 * @param array|Translation_Entry $entry
		 * @return bool True on success, false if the entry doesn't have a key.
		 */
		public function add_entry_or_merge( $entry ) {
			if ( is_array( $entry ) ) {
				$entry = new Translation_Entry( $entry );
			}
			$key = $entry->key();
			if ( false === $key ) {
				return false;
			}
			if ( isset( $this->entries[ $key ] ) ) {
				$this->entries[ $key ]->merge_with( $entry );
			} else {
				$this->entries[ $key ] = &$entry;
			}
			return true;
		}

		/**
		 * Sets $header PO header to $value
		 *
		 * If the header already exists, it will be overwritten
		 *
		 * TODO: this should be out of this class, it is gettext specific
		 *
		 * @since 2.8.0
		 *
		 * @param string $header header name, without trailing :
		 * @param string $value header value, without trailing \n
		 */
		public function set_header( $header, $value ) {
			$this->headers[ $header ] = $value;
		}

		/**
		 * Sets translation headers.
		 *
		 * @since 2.8.0
		 *
		 * @param array $headers Associative array of headers.
		 */
		public function set_headers( $headers ) {
			foreach ( $headers as $header => $value ) {
				$this->set_header( $header, $value );
			}
		}

		/**
		 * Returns a given translation header.
		 *
		 * @since 2.8.0
		 *
		 * @param string $header
		 * @return string|false Header if it exists, false otherwise.
		 */
		public function get_header( $header ) {
			return $this->headers[ $header ] ?? false;
		}

		/**
		 * Returns a given translation entry.
		 *
		 * @since 2.8.0
		 *
		 * @param Translation_Entry $entry Translation entry.
		 * @return Translation_Entry|false Translation entry if it exists, false otherwise.
		 */
		public function translate_entry( &$entry ) {
			$key = $entry->key();
			return $this->entries[ $key ] ?? false;
		}

		/**
		 * Translates a singular string.
		 *
		 * @since 2.8.0
		 *
		 * @param string $singular
		 * @param string $context
		 * @return string
		 */
		public function translate( $singular, $context = null ) {
			$entry      = new Translation_Entry(
				array(
					'singular' => $singular,
					'context'  => $context,
				)
			);
			$translated = $this->translate_entry( $entry );
			return ( $translated && ! empty( $translated->translations ) ) ? $translated->translations[0] : $singular;
		}

		/**
		 * Given the number of items, returns the 0-based index of the plural form to use
		 *
		 * Here, in the base Translations class, the common logic for English is implemented:
		 *  0 if there is one element, 1 otherwise
		 *
		 * This function should be overridden by the subclasses. For example MO/PO can derive the logic
		 * from their headers.
		 *
		 * @since 2.8.0
		 *
		 * @param int $count Number of items.
		 * @return int Plural form to use.
		 */
		public function select_plural_form( $count ) {
			return 1 === (int) $count ? 0 : 1;
		}

		/**
		 * Returns the plural forms count.
		 *
		 * @since 2.8.0
		 *
		 * @return int Plural forms count.
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
			$entry              = new Translation_Entry(
				array(
					'singular' => $singular,
					'plural'   => $plural,
					'context'  => $context,
				)
			);
			$translated         = $this->translate_entry( $entry );
			$index              = $this->select_plural_form( $count );
			$total_plural_forms = $this->get_plural_forms_count();
			if ( $translated && 0 <= $index && $index < $total_plural_forms &&
				is_array( $translated->translations ) &&
				isset( $translated->translations[ $index ] ) ) {
				return $translated->translations[ $index ];
			} else {
				return 1 === (int) $count ? $singular : $plural;
			}
		}

		/**
		 * Merges other translations into the current one.
		 *
		 * @since 2.8.0
		 *
		 * @param Translations $other Another Translation object, whose translations will be merged in this one (passed by reference).
		 */
		public function merge_with( &$other ) {
			foreach ( $other->entries as $entry ) {
				$this->entries[ $entry->key() ] = $entry;
			}
		}

		/**
		 * Merges originals with existing entries.
		 *
		 * @since 2.8.0
		 *
		 * @param Translations $other
		 */
		public function merge_originals_with( &$other ) {
			foreach ( $other->entries as $entry ) {
				if ( ! isset( $this->entries[ $entry->key() ] ) ) {
					$this->entries[ $entry->key() ] = $entry;
				} else {
					$this->entries[ $entry->key() ]->merge_with( $entry );
				}
			}
		}
	}
endif;
