<?php
namespace app\vendor\pomo;

/**
 * Contains Gettext_Translations class
 *
 * @version $Id: translations.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage translations
 */

if ( ! class_exists( __NAMESPACE__ . '\Gettext_Translations', false ) ) :
	/**
	 * Gettext_Translations class.
	 *
	 * @since 2.8.0
	 */
	class Gettext_Translations extends Translations {

		/**
		 * Number of plural forms.
		 *
		 * @var int
		 *
		 * @since 2.8.0
		 */
		public $_nplurals;

		/**
		 * Callback to retrieve the plural form.
		 *
		 * @var callable
		 *
		 * @since 2.8.0
		 */
		public $_gettext_select_plural_form;

		/**
		 * The gettext implementation of select_plural_form.
		 *
		 * It lives in this class, because there are more than one descendant, which will use it and
		 * they can't share it effectively.
		 *
		 * @since 2.8.0
		 *
		 * @param int $count Plural forms count.
		 * @return int Plural form to use.
		 */
		public function gettext_select_plural_form( $count ) {
			if ( ! isset( $this->_gettext_select_plural_form ) || is_null( $this->_gettext_select_plural_form ) ) {
				list( $nplurals, $expression )     = $this->nplurals_and_expression_from_header( $this->get_header( 'Plural-Forms' ) );
				$this->_nplurals                   = $nplurals;
				$this->_gettext_select_plural_form = $this->make_plural_form_function( $nplurals, $expression );
			}
			return call_user_func( $this->_gettext_select_plural_form, $count );
		}

		/**
		 * Returns the nplurals and plural forms expression from the Plural-Forms header.
		 *
		 * @since 2.8.0
		 *
		 * @param string $header
		 * @return array{0: int, 1: string}
		 */
		public function nplurals_and_expression_from_header( $header ) {
			if ( preg_match( '/^\s*nplurals\s*=\s*(\d+)\s*;\s+plural\s*=\s*(.+)$/', $header, $matches ) ) {
				$nplurals   = (int) $matches[1];
				$expression = trim( $matches[2] );
				return array( $nplurals, $expression );
			} else {
				return array( 2, 'n != 1' );
			}
		}

		/**
		 * Makes a function, which will return the right translation index, according to the
		 * plural forms header.
		 *
		 * @since 2.8.0
		 *
		 * @param int    $nplurals
		 * @param string $expression
		 * @return callable
		 */
		public function make_plural_form_function( $nplurals, $expression ) {
			try {
				$handler = new Plural_Forms( rtrim( $expression, ';' ) );
				return array( $handler, 'get' );
			} catch ( \Exception $e ) {
				// Fall back to default plural-form function.
				return $this->make_plural_form_function( 2, 'n != 1' );
			}
		}

		/**
		 * Adds parentheses to the inner parts of ternary operators in
		 * plural expressions, because PHP evaluates ternary operators from left to right
		 *
		 * @since 2.8.0
		 * @deprecated 6.5.0 Use the Plural_Forms class instead.
		 *
		 * @see Plural_Forms
		 *
		 * @param string $expression the expression without parentheses
		 * @return string the expression with parentheses added
		 */
		public function parenthesize_plural_exression( $expression ) {
			$expression .= ';';
			$res         = '';
			$depth       = 0;
			for ( $i = 0; $i < strlen( $expression ); ++$i ) {
				$char = $expression[ $i ];
				switch ( $char ) {
					case '?':
						$res .= ' ? (';
						++$depth;
						break;
					case ':':
						$res .= ') : (';
						break;
					case ';':
						$res  .= str_repeat( ')', $depth ) . ';';
						$depth = 0;
						break;
					default:
						$res .= $char;
				}
			}
			return rtrim( $res, ';' );
		}

		/**
		 * Prepare translation headers.
		 *
		 * @since 2.8.0
		 *
		 * @param string $translation
		 * @return array<string, string> Translation headers
		 */
		public function make_headers( $translation ) {
			$headers = array();
			// Sometimes \n's are used instead of real new lines.
			$translation = str_replace( '\n', "\n", $translation );
			$lines       = explode( "\n", $translation );
			foreach ( $lines as $line ) {
				$parts = explode( ':', $line, 2 );
				if ( ! isset( $parts[1] ) ) {
					continue;
				}
				$headers[ trim( $parts[0] ) ] = trim( $parts[1] );
			}
			return $headers;
		}

		/**
		 * Sets translation headers.
		 *
		 * @since 2.8.0
		 *
		 * @param string $header
		 * @param string $value
		 */
		public function set_header( $header, $value ) {
			parent::set_header( $header, $value );
			if ( 'Plural-Forms' === $header ) {
				list( $nplurals, $expression )     = $this->nplurals_and_expression_from_header( $this->get_header( 'Plural-Forms' ) );
				$this->_nplurals                   = $nplurals;
				$this->_gettext_select_plural_form = $this->make_plural_form_function( $nplurals, $expression );
			}
		}
	}
endif;
