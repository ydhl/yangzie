<?php
namespace app\vendor\pomo;

/**
 * Contains POMO_FileReader class
 *
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

if ( ! class_exists( __NAMESPACE__ . '\POMO_FileReader', false ) ) :
	class POMO_FileReader extends POMO_Reader {

		/**
		 * File pointer resource.
		 *
		 * @var resource|false
		 */
		public $_f;

		/**
		 * @param string $filename
		 */
		public function __construct( $filename ) {
			parent::__construct();
			$this->_f = fopen( $filename, 'rb' );
		}

		/**
		 * @param int $bytes
		 * @return string|false Returns read string, otherwise false.
		 */
		public function read( $bytes ) {
			return fread( $this->_f, $bytes );
		}

		/**
		 * @param int $pos
		 * @return bool
		 */
		public function seekto( $pos ) {
			if ( -1 === fseek( $this->_f, $pos, SEEK_SET ) ) {
				return false;
			}
			$this->_pos = $pos;
			return true;
		}

		/**
		 * @return bool
		 */
		public function is_resource() {
			return is_resource( $this->_f );
		}

		/**
		 * @return bool
		 */
		public function feof() {
			return feof( $this->_f );
		}

		/**
		 * @return bool
		 */
		public function close() {
			return fclose( $this->_f );
		}

		/**
		 * @return string
		 */
		public function read_all() {
			return stream_get_contents( $this->_f );
		}
	}
endif;
