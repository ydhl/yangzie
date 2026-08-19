<?php
namespace app\vendor\pomo;

/**
 * Contains POMO_CachedIntFileReader class
 *
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( __NAMESPACE__ . '\POMO_CachedIntFileReader', false ) ) :
	/**
	 * Reads the contents of the file in the beginning.
	 */
	class POMO_CachedIntFileReader extends POMO_CachedFileReader {
		/**
		 * PHP5 constructor.
		 */
		public function __construct( $filename ) {
			parent::__construct( $filename );
		}
	}
endif;
