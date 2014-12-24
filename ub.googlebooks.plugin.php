<?php

class GoogleBooks implements IUBPlugin {

	public static function forme($onlineidentifier) {
		if (preg_match('=^([\dX]{10}|\d{13})$=i', $onlineidentifier)) return true;
		if (preg_match('=^https://books.google.com/', $onlineidentifier)) return true;
		return false;
	}

	public static function doesBookMatch($key, $val, $matchagainst) {
		if (strtolower($key) != 'isbn') return false;
		return ($val == $matchagainst);
	}

	private $isbn = null;
	private $googleBooksID = null;
	public function __construct($onlineidentifier) {
		if (preg_match('=^([\dX]{10}|\d{13})$=i', $onlineidentifier, $pat)) {
			if (strlen($onlineidentifier) == 10) {
				$onlineidentifier = '978' . $onlineidentifier;
			}
			if (strlen($onlineidentifier) == 13) {
				if (in_array(substr($onlineidentifier, 0, 3), ['978', '979'])) { //TODO: if 979, check if not 9790
					$data = json_decode(
						file_get_contents(
							'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $onlineidentifier
						), true
					);
					$this->isbn = $onlineidentifier;
					$this->googleBooksID = $data['items'][0]['id'];
				}
			}
		}
	}

	public function getBibTeX() {
		$googleBooksURL = 'http://books.google.de/books?id=' . urlencode($this->googleBooksID);
		$gbhtml = file_get_contents($googleBooksURL);
		preg_match('/href="([^"]+output=bibtex)"/msi', $gbhtml, $pat);
		return file_get_contents(htmlspecialchars_decode($pat[1]));
}

	public function __toString() {
		return "GoogleBooks:ISBN:" . $this->isbn;
	}

}
