<?php

function ub_leitir_is_fix_ris($file) {
	$lines = explode("\n", $file);
	$hasTY = false;
	$buffer = [];
	$ret = '';
	foreach ($lines as $line) {
		$line = trim($line, "\r");
		if (trim($line) == '') continue;
		if ($hasTY) {
			$ret .= $line . "\r\n";
		} else {
			if (substr($line, 0, 2) == "TY") {
				$hasTY = true;
				$ret .= $line . "\r\n";
				foreach ($buffer as $b) $ret .= $b . "\r\n";
			} else {
				$buffer[] = $line;
			}
		}
	}
	return $ret;
}

if (count(get_included_files()) == 1) {
	$file = file_get_contents("php://stdin");
	echo ub_leitir_is_fix_ris($file);
}

