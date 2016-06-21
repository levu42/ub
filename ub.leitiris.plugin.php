<?php

class LeitirIS implements IUBPlugin {
	protected $permalink = '';

	public static function doesBookMatch($key, $val, $matchagainst) {
		if ($key == 'permalink') {
			return ($val == $matchagainst);
		}
		return false;
	}

	public static function forme ($onlineidentifier) {
		return ((substr("".$onlineidentifier, 0, 17) == 'http://leitir.is/') || (substr("".$onlineidentifier, 0, 18) == 'https://leitir.is/'));
	}

	public function __construct($onlineidentifier) {
		if (self::forme($onlineidentifier)) {
			$this->permalink = $onlineidentifier;
		}
	}

	public function getBibTeX() {
		$RISurl = 'http://leitir.is/primo_library/libweb/action/PushToAction.do?pushToType=RISPushTo&fromEshelf=false&encode=UTF-8&docs=';
		$id = substr($this->permalink, strrpos($this->permalink, ':')+1);
		$ris = file_get_contents($RISurl . $id);
		require_once (__DIR__ . '/ub.leitiris.fixris.php');
		$ris = ub_leitir_is_fix_ris($ris);
		$bib = ub_ris2bib($ris);
		$bib = str_rreplace("\n}", ",\npermalink=\"" . $this->permalink . "\"\n}", $bib);
		return $bib;
	}

	public function __toString() {
		return 'https://leitir.is/' . $this->permalink;
	}

}

