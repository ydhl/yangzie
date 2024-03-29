<?php
/**
 * Class for working with MO files
 *
 * @version $Id: class.MO.php 259 2010-02-21 14:03:48Z liizii $
 * @package pomo
 * @subpackage mo
 */
class MO extends Gettext_Translations {

	public $_nplurals = 2;

	/**
	 * Fills up with the entries from MO file $filename
	 *
	 * @param string $filename MO file to load
	 */
	public function import_from_file($filename) {
		$reader = new POMO_CachedIntFileReader($filename);
		if (isset($reader->error)) {
			return false;
		}
		return $this->import_from_reader($reader);
	}

	public function export_to_file($filename) {
		$fh = fopen($filename, 'wb');
		if ( !$fh ) return false;
		$entries = array_filter($this->entries, create_function('$e', 'return !empty($e->translations);'));
		ksort($entries);
		$magic = 0x950412de;
		$revision = 0;
		$total = count($entries) + 1; // all the headers are one entry
		$originals_lenghts_addr = 28;
		$translations_lenghts_addr = $originals_lenghts_addr + 8 * $total;
		$size_of_hash = 0;
		$hash_addr = $translations_lenghts_addr + 8 * $total;
		$current_addr = $hash_addr;
		fwrite($fh, pack('V*', $magic, $revision, $total, $originals_lenghts_addr,
			$translations_lenghts_addr, $size_of_hash, $hash_addr));
		fseek($fh, $originals_lenghts_addr);

		// headers' msgid is an empty string
		fwrite($fh, pack('VV', 0, $current_addr));
		$current_addr++;
		$originals_table = chr(0);

		foreach($entries as $entry) {
			$originals_table .= $this->export_original($entry) . chr(0);
			$length = strlen($this->export_original($entry));
			fwrite($fh, pack('VV', $length, $current_addr));
			$current_addr += $length + 1; // account for the NULL byte after
		}

		$exported_headers = $this->export_headers();
		fwrite($fh, pack('VV', strlen($exported_headers), $current_addr));
		$current_addr += strlen($exported_headers) + 1;
		$translations_table = $exported_headers . chr(0);

		foreach($entries as $entry) {
			$translations_table .= $this->export_translations($entry) . chr(0);
			$length = strlen($this->export_translations($entry));
			fwrite($fh, pack('VV', $length, $current_addr));
			$current_addr += $length + 1;
		}

		fwrite($fh, $originals_table);
		fwrite($fh, $translations_table);
		fclose($fh);
	}

	public function export_original($entry) {
		//TODO: warnings for control characters
		$exported = $entry->singular;
		if ($entry->is_plural) $exported .= chr(0).$entry->plural;
		if (!is_null($entry->context)) $exported = $entry->context . chr(4) . $exported;
		return $exported;
	}

	public function export_translations($entry) {
		//TODO: warnings for control characters
		return implode(chr(0), $entry->translations);
	}

	public function export_headers() {
		$exported = '';
		foreach($this->headers as $header => $value) {
			$exported.= "$header: $value\n";
		}
		return $exported;
	}

	public function get_byteorder($magic) {

		// The magic is 0x950412de

		// bug in PHP 5.0.2, see https://savannah.nongnu.org/bugs/?func=detailitem&item_id=10565
		$magic_little = (int) - 1794895138;
		$magic_little_64 = (int) 2500072158;
		// 0xde120495
		$magic_big = ((int) - 569244523) && 0xFFFFFFFF;

		if ($magic_little == $magic || $magic_little_64 == $magic) {
			return 'little';
		} else if ($magic_big == $magic) {
			return 'big';
		} else {
			return false;
		}
	}

	public function import_from_reader($reader) {
		$reader->setEndian('little');
		$endian = MO::get_byteorder($reader->readint32());
		if (false === $endian) {
			return false;
		}
		$reader->setEndian($endian);

		$revision = $reader->readint32();
		$total = $reader->readint32();
		// get addresses of array of lenghts and offsets for original string and translations
		$originals_lenghts_addr = $reader->readint32();
		$translations_lenghts_addr = $reader->readint32();

		$reader->seekto($originals_lenghts_addr);
		$originals_lenghts = $reader->readint32array($total * 2); // each of
		$reader->seekto($translations_lenghts_addr);
		$translations_lenghts = $reader->readint32array($total * 2);

		$length = function($i) { return $i * 2 + 1;};
		$offset = function($i) { return $i * 2 + 2;};

		for ($i = 0; $i < $total; ++$i) {
			$reader->seekto($originals_lenghts[$offset($i)]);
			$original = $reader->read($originals_lenghts[$length($i)]);
			$reader->seekto($translations_lenghts[$offset($i)]);
			$translation = $reader->read($translations_lenghts[$length($i)]);
			if ('' == $original) {
				$header = $this->make_headers($translation);
				$this->set_headers($header);
			} else {
				$this->add_entry($this->make_entry($original, $translation));
			}
		}
		return true;
	}

	/**
	 * @static
	 */
	public function &make_entry($original, $translation) {
		$args = array();
		// look for context
		$parts = explode(chr(4), $original);
		if (isset($parts[1])) {
			$original = $parts[1];
			$args['context'] = $parts[0];
		}
		// look for plural original
		$parts = explode(chr(0), $original);
		$args['singular'] = $parts[0];
		if (isset($parts[1])) {
			$args['plural'] = $parts[1];
		}
		// plural translations are also separated by \0
		$args['translations'] = explode(chr(0), $translation);
		$entry =  new Translation_Entry($args);
		return $entry;
	}

	public function select_plural_form($count) {
		return $this->gettext_select_plural_form($count);
	}

	public function get_plural_forms_count() {
		return $this->_nplurals;
	}


}
?>
