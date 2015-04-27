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
		preg_match('/PPN=([\dX]+)[^\dX]/msi', $html, $pat);
		if (!count($pat)) {
			$opacURL = 'https://lbsopac.rz.uni-frankfurt.de/DB=30/SET=6/TTL=1/CMD?ACT=SRCHA&IKT=8520&DB=30&SRT=YOP&TRM=' . urlencode($barcode) . '*';
			$html = file_get_contents($opacURL);
			preg_match('/BASE HREF="([^"]+)"/msi', $html, $pat);
			$eintragURL = $pat[1] . 'SHW?FRST=1';
			$html = file_get_contents($eintragURL);
			preg_match('/PPN=([\dX]+)[^\dX]/msi', $html, $pat);
		}
		if (is_array($pat) && count($pat)) {
			$ppn = $pat[1];
		} else {
			$ppn = false;
		}

		$GLOBALS['ub_config']['hebis']['barcode_cache'][$barcode] = $ppn;
		ub_config_save();
		return $ppn;
	}

	public static function doesBookMatch($key, $val, $matchagainst) {
		if (strtolower($key) != 'uniqueid') return false;
		$val = strtolower($val);
		if (substr($val, 0, 3) != 'heb') $val = 'heb' . $val;
		$matchagainst = strtolower($matchagainst);
		$m = $matchagainst;
		if (substr($m, 0, 3) != 'heb') $m = 'heb' . $m;
		if ($val === $m) return true;
		$m = self::getPPNfromBarcode($matchagainst);
		if ($m === false) return false;
		if (substr($m, 0, 3) != 'heb') $m = 'heb' . strtolower($m);
		return ($val === $m);
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
		if ($this->ppn === false) return '';
		$url = 'https://hds.hebis.de/ubffm/Puma/Export?id=HEB' . $this->ppn . '&exportType=bib';	
		return file_get_contents($url);
	}

	public function __toString() {
		if ($this->ppn === false) return '';
		return 'HEB' . $this->ppn;
	}
}
