<?php
namespace app\vendor\pomo;

/**
 * Contains POMO_CachedFileReader class
 *
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( __NAMESPACE__ . '\POMO_CachedFileReader', false ) ) :
	/**
	 * Reads the contents of the file in the beginning.
	 */
	class POMO_CachedFileReader extends POMO_StringReader {
		/**
		 * PHP5 constructor.
		 */
		public function __construct( $filename ) {
			parent::__construct();
			$this->_str = file_get_contents( $filename );
			if ( false === $this->_str ) {
				return false;
			}
			$this->_pos = 0;
		}
	}
endif;
