<?php

if (!isset($_GET['barcode'])) die;

require_once __DIR__ . '/ub.func.inc.php';
require_once __DIR__ . '/ub.posttweet.php';

if (isset(ub_config()['web']['password_sha256'])) {
	if (!isset($_GET['hash'])) die;
	if (hash('sha256', $_GET['hash']) !== ub_config()['web']['password_sha256']) {
		die;
	}
}

ub_execute_add([$_GET['barcode'], 'barcode'], ['cli' => false]);
ub_execute_twitter_tweet([$_GET['barcode']], ['cli' => false]);

header('Location: ' . ub_get_val_from_bibtex(ub_execute_get([$_GET['barcode']], ['cli' => false]), ['hdsurl', 'url']));

