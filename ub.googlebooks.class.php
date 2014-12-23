<?php

class GoogleBooks implements IUBPlugin {

	public static function forme($onlineidentifier) {
		return false;
	}

	public static function doesBookMatch($key, $val, $matchagainst) {
		return false;
	}

	public function __construct($onlineidentifier) {
		;
	}

	public function saveBibTeXtoDatabase($dbpath) {
		;
	}

}
