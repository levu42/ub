<?php

if (!isset(ub_config()['hebis'])) {
	$GLOBALS['ub_config']['hebis'] = ['barcode_cache' => []];
}

class HeBIS implements IUBPlugin {
	public static function getPPNfromBarcode($barcode) {
		if (isset(ub_config()['hebis']['barcode_cache'][$barcode])) {
			return ub_config()['hebis']['barcode_cache'][$barcode];
		}
		$opacURL = 'https://lbsopac.rz.uni-frankfurt.de/DB=30/SET=6/TTL=1/CMD?ACT=SRCHA&IKT=8520&DB=30&SRT=YOP&TRM=' . urlencode($barcode);

		$html = file_get_contents($opacURL);
		preg_match('/BASE HREF="([^"]+)"/msi', $html, $pat);
		$eintragURL = $pat[1] . 'SHW?FRST=1';
		$html = file_get_contents($eintragURL);
		preg_match('/PPN=(\d+)\D/msi', $html, $pat);
		$ppn = $pat[1];

		$GLOBALS['ub_config']['hebis']['barcode_cache'][$barcode] = $ppn;
		ub_config_save();
		return $ppn;
	}

	public static function doesBookMatch($key, $val, $matchagainst) {
		if (strtolower($key) != 'uniqueid') return false;
		$val = strtolower($val);
		if (substr($val, 0, 3) != 'heb') $val = 'heb' . $val;
		$matchagainst = strtolower($matchagainst);
		if (substr($matchagainst, 0, 3) != 'heb') $matchagainst = 'heb' . $matchagainst;
		return ($val === $matchagainst);
	}

	public static function forme ($onlineidentifier) {
		if (preg_match('=^https?://hds.hebis.de/ubffm/Record/HEB(\d+)$=i', $onlineidentifier)) return true;
		if (preg_match('=^heb[\d\w]{9}$=i', $onlineidentifier)) return true;
		if (preg_match('=^\d{8,10}$=i', $onlineidentifier)) return true;
		return false;
	}
		
	private $origid = null;
	public function __construct($onlineidentifier) {
		$this->origid = $onlineidentifier;
		if (preg_match('=^https?://hds.hebis.de/ubffm/Record/HEB([\d\w]+)$=i', $onlineidentifier, $pat)) {
			$this->ppn = $pat[1];
		} else {
			if (strtolower(substr($onlineidentifier, 0, 3)) == 'heb') {
				$this->ppn = substr($onlineidentifier, 3);
			} else {
				$this->ppn = self::getPPNfromBarcode($onlineidentifier);
			}
		}
	}

	public function getBibTeX() {
		$url = 'https://hds.hebis.de/ubffm/Puma/Export?id=HEB' . $this->ppn . '&exportType=bib';	
		return  file_get_contents($url);
	}
}
