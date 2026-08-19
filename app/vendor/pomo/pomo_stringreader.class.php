<?php
namespace app\vendor\pomo;

/**
 * Contains POMO_StringReader class
 *
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( __NAMESPACE__ . '\POMO_StringReader', false ) ) :
	/**
	 * Provides file-like methods for manipulating a string instead
	 * of a physical file.
	 */
	class POMO_StringReader extends POMO_Reader {

		public $_str = '';

		/**
		 * PHP5 constructor.
		 */
		public function __construct( $str = '' ) {
			parent::__construct();
			$this->_str = $str;
			$this->_pos = 0;
		}

		/**
		 * @param string $bytes
		 * @return string
		 */
		public function read( $bytes ) {
			$data        = $this->substr( $this->_str, $this->_pos, $bytes );
			$this->_pos += $bytes;
			if ( $this->strlen( $this->_str ) < $this->_pos ) {
				$this->_pos = $this->strlen( $this->_str );
			}
			return $data;
		}

		/**
		 * @param int $pos
		 * @return int
		 */
		public function seekto( $pos ) {
			$this->_pos = $pos;
			if ( $this->strlen( $this->_str ) < $this->_pos ) {
				$this->_pos = $this->strlen( $this->_str );
			}
			return $this->_pos;
		}

		/**
		 * @return int
		 */
		public function length() {
			return $this->strlen( $this->_str );
		}

		/**
		 * @return string
		 */
		public function read_all() {
			return $this->substr( $this->_str, $this->_pos, $this->strlen( $this->_str ) );
		}
	}
endif;
