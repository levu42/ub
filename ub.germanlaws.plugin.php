<?php

class GermanLaws {

	public static function doesBookMatch($key, $val, $matchagainst) {
		;
	}

	public static function forme ($onlineidentifier) {
		return LawReference::isValid($onlineidentifier);
	}

	private $origcitation;
	private $lr;

	public function __construct($onlineidentifier) {
		$this->lr = new LawReference($onlineidentifier);
		$this->origcitation = $onlineidentifier;
	}

	public function getBibTeX() {
		;
	}

	public function __toString() {
		;
	}
}
