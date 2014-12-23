<?php

if (!isset(ub_config()['hebis'])) {
	$GLOBALS['ub_config']['hebis'] = ['barcode_cache' => []];
}

class HeBIS {
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

	public static forme ($onlineidentifier) {
		if (preg_match('=^https?://hds.hebis.de/ubffm/Record/HEB(\d+)$=i', $onlineidentifier)) return true;
		if (preg_match('=^heb\d{10}$=i', $onlineidentifier)) return true;
		if (preg_match('=^\d{10}$=i', $onlineidentifier)) return true;
		return false;
	}
		
	private $origid = null;
	public function __construct($onlineidentifier) {
		$this->origid = $onlineidentifier;
		if (preg_match('=^https?://hds.hebis.de/ubffm/Record/HEB(\d+)$=i', $onlineidentifier, $pat) {
			var_dump($pat); die;
		} else {
			if (strtolower(substr($onlineidentifier, 0, 3)) == 'heb') {
				$this->ppn = $this->$onlineidentifier;
			} else {
				$this->ppn = self::getPPNfromBarcode($onlineidentifier);
			}
		}
	}

	public function saveBibTeXtoDatabase($dbpath) {
		$url = 'https://hds.hebis.de/ubffm/Puma/Export?id=' . $this->ppn . '&exportType=bib';	
		$bibtex = file_get_contents($url);
		if ($bibtex === false) {
			return false;
		}
		exec('cp ' . escapeshellarg($dbpath) . ' ' . escapeshellarg($dbpath . '.ub-add-tmp'));
		file_put_contents($dbpath . '.ub-add-tmp', $bibtex, FILE_APPEND);
		exec('LC_ALL=C ' . ub_config()['bibsort_path'] . ' -f -u < ' . escapeshellarg($dbpath . '.ub-add-tmp') . ' > ' . escapeshellarg($dbpath));
		clearstatcache();
		if (filesize($dbpath) >= filesize ($dbpath . '.ub-add-tmp')) {
			unlink($dbpath . '.ub-add-tmp');
		}
		return true;
	}
}
