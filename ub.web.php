<?php

if (!isset($_GET['barcode'])) die;
require_once __FILE__ . '/ub.func.inc.php';

if (isset(ub_config()['web']['password_sha256'])) {
	if (!isset($_GET['hash'])) die;
	if (hash('sha256', $_GET['hash']) !== ub_config()['web']['password_sha256']) {
		die;
	}
}

ub_execute_add([$_GET['barcode'], 'barcode'], ['cli' => false]);

$book = ub_execute_get([$_GET['barcode']], ['cli' => false, 'only_from_db' => 'barcode']);

$title = ub_get_val_from_bibtex($book, 'title');
$author = ub_get_val_from_bibtex($book, 'author');
$URL = ub_get_val_from_bibtex($book, ['hdsurl', 'url']);

$strtr = [
	'ä' => 'ae',
	'Ä' => 'Ae',
	'ö' => 'oe',
	'Ö' => 'Oe',
	'ü' => 'ue',
	'Ü' => 'Ue',
	'ß' => 'ss',
	'í' => 'i',
	'Í' => 'I',
	'á' => 'a',
	'Á' => 'A',
	'é' => 'e',
	'É' => 'E',
	'ó' => 'o',
	'Ó' => 'O',
	'ú' => 'u',
	'Ú' => 'U',
	'ð' => 'dh',
	'Ð' => 'Dh',
	'þ' => 'th',
	'Þ' => 'Th',
	'æ' => 'ae',
	'Æ' => 'Ae',
	'…' => '...',
];
$title = strtr($title, $strtr);
$author = strtr($author, $strtr);
if (strlen($title) > 50) {
	$title = substr($title, 0, 48) . '...';
}
system('HOME=' . UB_HOME_DIR . ' twidge update ' . escapeshellarg("Ich halte gerade in der Hand: \"$title\" von $author $URL") . ' 2>&1');

header('Location: ' . $URL);

