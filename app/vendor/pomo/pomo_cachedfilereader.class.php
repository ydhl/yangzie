<?php 
/**
 * Reads the contents of the file in the beginning.
 */
class POMO_CachedFileReader extends POMO_StringReader {
	function POMO_CachedFileReader($filename) {
		parent::POMO_StringReader();
		$this->_str = file_get_contents($filename);
		if (false === $this->_str)
			return false;
		$this->_pos = 0;
	}
}
?>