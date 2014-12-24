<?php

interface IUBPlugin {

	public static function doesBookMatch($key, $val, $matchagainst);

	public static function forme ($onlineidentifier);
		
	public function __construct($onlineidentifier);

	public function getBibTeX();

	public function __toString();
}
