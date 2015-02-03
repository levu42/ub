<?php

setlocale(LC_CTYPE, "en_US.UTF-8");

require_once(__DIR__ . '/ub.posttweet.php');

define ('CLI_OK', cli_bold() . "[" . cli_green() . "✓" . cli_unescape() . cli_bold() . "]" . cli_unescape());
define ('CLI_NOK', cli_bold() . "[" . cli_red() . "✘" . cli_unescape() . cli_bold() . "]" . cli_unescape());
define ('CLI_WARNING', cli_bold() . "[" . cli_red() . "⚠" . cli_unescape() . cli_bold() . "]" . cli_unescape());

function ub_link_names () { // since there are no array constants possible…
	return ['main', 'barcode', 'from', 'to', 'import', 'autoadd'];
}

function is_tty() {
	return (defined('STDOUT') && posix_isatty(STDOUT));
}

function cli_unescape() {
	if (is_tty()) {
		return "\033[0m";
	} else {
		return '';
	}
}

function cli_bold() {
	if (is_tty()) {
		return "\033[1m";
	} else {
		return '';
	}
}

function cli_red() {
	if (is_tty()) {
		return "\033[31m";
	} else {
		return '';
	}
}

function cli_green() {
	if (is_tty()) {
		return "\033[32m";
	} else {
		return '';
	}
}

function array_shifted ($a) {
	array_shift($a);
	return $a;
}

//// autoloader

spl_autoload_register(function($class) {
	if (file_exists(__DIR__ . '/ub.' . strtolower($class) . '.plugin.php')) {
		require_once __DIR__ . '/ub.' . strtolower($class) . '.plugin.php';
	} else {
		require_once __DIR__ . '/ub.' . strtolower($class) . '.class.php';
	}
});


//// ub_ functions

function ub_cli_text ($name) {
	$fn = __DIR__ . '/ub.' . str_replace('/', '_', $name) . '.txt';
	if (file_exists($fn)) {
		echo str_replace("\t", "  ", file_get_contents($fn));
	}
}

function ub_db_get($name = null) {
	if (is_null($name)) {
		$name = 'main';
	}
	$dbs = ub_config()['db'];
	if (in_array($name, ub_link_names())) {
		if (!isset($dbs[$name])) {
			$name = 'main';
		}
		if (!isset($dbs[$name])) {
			return false;
		} else {
			$link = $dbs[$name]['link'];
			if (!isset($dbs[$link])) {
				return false;
			} else {
				return $dbs[$link];
			}
		}
	} else {
		if (isset($dbs[$name])) {
			return $dbs[$name];
		}
	}
}

function ub_db_get_path($name = null) {
	$db = ub_db_get($name);
	if ($db !== false) {
		return $db['path'];
	} else {
		return false;
	}
}

function ub_config () {
	if (!isset($GLOBALS['ub_config'])) {
		if (!defined('UB_CONFIG_FILE')) {
			define('UB_HOME_DIR', posix_getpwuid(posix_getuid())['dir']);
			define('UB_CONFIG_FILE', UB_HOME_DIR . '/.ubconfig.json');
		}
		if (!file_exists(UB_CONFIG_FILE)) {
			file_put_contents(UB_CONFIG_FILE, "{}\n");
		}
		$c = json_decode(file_get_contents(UB_CONFIG_FILE), true);
		if (!isset($c['db'])) {
			$c['db'] = [];
		}
		if (!isset($c['web'])) {
			$c['web'] = [];
		}
		if (!isset($c['bibsort_path'])) {
			$p = exec('which bibsort');
			if (file_exists($p)) {
				$c['bibsort_path'] = $p;	
			} else {
				echo cli_nok() . " bibsort not found. Please try again when bibsort is – at least for one run – in PATH\n";
				die;
			}
		}
		if (!isset($c['plugins'])) {
			$c['plugins'] = ['HeBIS', 'GoogleBooks'];
		} else {
			$c['plugins'] = array_diff($c['plugins'], ['GermanLaws']);
		}
		$GLOBALS['ub_config'] = $c;
		register_shutdown_function('ub_config_save');
	}
	return $GLOBALS['ub_config'];
}

function ub_config_save () {
	file_put_contents(UB_CONFIG_FILE, json_encode($GLOBALS['ub_config']));
}

function ub_save_bibtex_to_db($dbname, $bibtex, $commitmessage = "", $olddbcontent = null) {
	$dbpath = ub_db_get_path($dbname);
	if ($dbpath === false) return false;
	$git = ub_db_get($dbname)['git'];
	if ($git) {
		$olddir = getcwd();
		chdir (dirname($dbpath));
		exec("git stash 2>&1");
	}
	exec('cp ' . escapeshellarg($dbpath) . ' ' . escapeshellarg($dbpath . '.ub-add-tmp'));
	if (!is_null($olddbcontent)) {
		file_put_contents($dbpath . '.ub-add-tmp', $olddbcontent . "\n" . $bibtex);
	} else {
		file_put_contents($dbpath . '.ub-add-tmp', $bibtex, FILE_APPEND);
	}
	exec('LC_ALL=C ' . ub_config()['bibsort_path'] . ' -f -u < ' . escapeshellarg($dbpath . '.ub-add-tmp') . ' > ' . escapeshellarg($dbpath));
	clearstatcache();
	unlink($dbpath . '.ub-add-tmp');
	if ($git) {
		exec("git commit -m " . escapeshellarg('bibtex saved' . ($commitmessage ? ': ' . $commitmessage : '')) . " " . escapeshellarg($dbpath));
		exec("git push 2>&1");
		exec("git stash apply 2>&1");
		chdir($olddir);
	}
	return true;
}

function ub_get_db_without_book($dbname, $bookname) {
	$dbp = ub_db_get_path($dbname);
	if ($dbp === false) return false;
	$db = file($dbp);
	$inbook = false;
	$skipbook = false;
	$ret = [];
	foreach($db as $l) {
		$l = trim($l, "\r\n");
		if (preg_match('/^\s*@(\w+)\{(\w+),\s*$/i', $l, $pat)) {
			if ($pat[2] == $bookname) {
				$skipbook = true;
			}
		}
		if (!$skipbook) {
			$ret[] = $l;
		}
		if (trim($l) == '}') {
			$inbook = false;
			$skipbook = false;
		}
	}

	return implode("\n", $ret) . "\n";
}

function ub_get_val_from_bibtex($bibtex, $getkey) {
	if (is_array($getkey)) {
		$ret = false;
		foreach ($getkey as $k) {
			$ret = ub_get_val_from_bibtex($bibtex, $k);
			if ($ret !== false) {
				break;
			}
		}
		return $ret;
	}
	foreach(explode("\n", $bibtex) as $line) {
		$p = preg_match('/^\s*(\w+)\s*=\s*({\s*(.+)\s*}|(\d+))\s*,?\s*$/i', $line, $pat);
		if (count($pat) < 4) continue;
		$key = $pat[1];
		$val = $pat[3];
		if (!strlen($val)) $val = $pat[4]; //number
		$replacements = [
			"{\\\"O}" => "Ó",
			"{\\\"o}" => "ó",
			"{\\\"U}" => "Ú",
			"{\\\"u}" => "ú",
			"{\\\"E}" => "É",
			"{\\\"e}" => "é",
			"{\\\"A}" => "Á",
			"{\\\"a}" => "á",
			"{\\\"I}" => "Í",
			"{\\\"i}" => "í",
			"{\\'O}" => "Ó",
			"{\\'o}" => "ó",
			"{\\'U}" => "Ú",
			"{\\'u}" => "ú",
			"{\\'E}" => "É",
			"{\\'e}" => "é",
			"{\\'A}" => "Á",
			"{\\'a}" => "á",
			"{\\'I}" => "Í",
			"{\\'i}" => "í",
			"{\\dh}" => "ð",
			"{\\Dh}" => "Ð",
			"{\\DH}" => "Ð",
			"{\\th}" => "þ",
			"{\\Th}" => "Þ",
			"{\\TH}" => "Þ",
			"\\_" => "_",
		];
		$val = strtr($val, $replacements);
		if ($key == $getkey) {
			return $val;
		}
	}
	return false;
}

function ub_get_book($identifier, $options) {
	$curbook = '';
	$yupthisisit = false;
	$inbook = false;
	$name = '';
	$dbs = ub_config()['db'];
	if (isset($options['only_from_db'])) {
		$dbs = [$options['only_from_db'] => ub_db_get($options['only_from_db'])];
	}
	$formeplugins = [];
	foreach (ub_config()['plugins'] as $plugin) {
		if ($plugin::forme($identifier)) {
			$formeplugins[] = $plugin;
		}
	}
	foreach ($dbs as $dbname => $db) if ($db === false) continue; else if (!isset($db['path'])) continue; else {
		if (file_exists($db['path'])) foreach(file($db['path']) as $line) {
			if (!trim($line)) continue;
			if ($inbook) {
				$curbook .= $line;
			}
			if ($inbook && (trim($line) == '}')) {
				$inbook = false;
				if ($yupthisisit) {
					return [
						'bibtex' => $curbook,
						'dbname' => $dbname,
						'name' => $name,
					];
				}
			} else if (trim($line)[0] == '@') {
				$name = trim(substr($line, strpos($line, '{')+1), "\n,");
				$yupthisisit = ($name == $identifier);
				$inbook = true;
				$curbook = $line;
			} else if ($inbook && !$yupthisisit) {
				$p = preg_match('/^\s*(\w+)\s*=\s*({\s*([^}]+)\s*}|(\d+))\s*,?\s*$/i', $line, $pat);
				if (count($pat) < 4) continue;
				$key = $pat[1];
				$val = $pat[3];
				if (!strlen($val)) $val = $pat[4]; //number
				foreach($formeplugins as $plugin) {
					if ($plugin::doesBookMatch($key, $val, $identifier)) {
						$yupthisisit = true;
						break;
					}
				}
			}
		}
	}
	return false;
}

function ub_execute (array $command, array $options = []) {
	$options = array_merge([
		'cli' => false
	], $options);

	if (count($command) == 0) {
		if ($options['cli']) {
			ub_cli_text('usage');
		}
		return null;
	}
	switch($command[0]) {
		case 'add':
			return ub_execute_add(array_shifted($command), $options);
		case 'list':
			return ub_execute_list(array_shifted($command), $options);
		case 'db':
			return ub_execute_db(array_shifted($command), $options);
		case 'copy':
			return ub_execute_copy(array_shifted($command), $options);
		case 'get':
			return ub_execute_get(array_shifted($command), $options);
		case 'edit':
			return ub_execute_edit(array_shifted($command), $options);
		case 'import':
			return ub_execute_import(array_shifted($command), $options);
		case 'twitter':
			return ub_execute_twitter(array_shifted($command), $options);
	}
}

function ub_execute_import (array $command, array $options) {
	if (count($command) == 0) {
		$command = ['-'];
	}
	if ($command[0] == '-') {
		$command[0] = 'php://stdin';
	} else {
		$testurl = strtolower($command[0]);
		if (!(file_exists($command[0])) || (substr($testurl, 0, 5) == 'http:') || (substr($testurl, 0, 6) == 'https:')) {
			if ($options['cli']) {
				echo CLI_NOK . " import file not found\n";
			}
			return false;
		}
	}
	$book = file_get_contents($command[0]);
	$dbname = isset($command[1]) ? $command[1] : 'import';
	$ret = ub_save_bibtex_to_db($dbname, $book, 'imported from ' . $command[0]);
	if ($options['cli']) {
		if ($ret === false) {
			echo CLI_NOK . " error on saving to »{$dbname}« database\n";
		} else {
			echo CLI_OK . " successfully imported to »{$dbname}«\n";
		}
	} else {
		return $ret;
	}
}

function ub_execute_add (array $command, array $options) {
	if (count($command) > 0) {
		if (count($command) == 1) {
			$command[1] = 'main';
		}
		foreach (ub_config()['plugins'] as $plugin) {
			if ($plugin::forme($command[0])) {
				$p = new $plugin($command[0]);
				$bibtex = $p->getBibTeX();
				$ret = ub_save_bibtex_to_db($command[1], $bibtex, (isset($options['commitmessage']) ? $options['commitmessage'] : '') . $p . " added");
				if ($options['cli']) {
					if ($ret) {
						echo CLI_OK . " entry »{$command[0]}« successfully saved\n";
					} else {
						echo CLI_NOK . " error on saving »{$command[0]}« to database\n";
					}
				}
				return $ret;
			}
		}
		if ($options['cli']) {
			echo CLI_NOK . " could find no plugin for entry »{$command[0]}«\n";
		}
		return false;
	} else {
		if ($options['cli']) {
		  echo "Usage: add onlineidentifier[, dbname]\n";
		}	
	}
}

function ub_execute_edit (array $command, array $options) {
	if (!$options['cli']) return false; //interactive edit command, can only be called via cli
	if (!is_tty()) return false; // see above
	if (count($command) == 0) {
		if ($options['cli']) {
			echo "usage: edit identifier[, dbname]\n";
		}
		return;
	}
	$o = [];
	if (count($command) > 1) {
		$o['only_from_db'] = $command[1];
	}
	$book = ub_get_book($command[0], $o);
	$tmpf = tempnam('/tmp', 'ub.edit.');
	file_put_contents($tmpf, $book['bibtex']);
	$editor = (getenv('VISUAL') === false) ? 'vim' : getenv('VISUAL');
	exec(escapeshellcmd($editor) . ' ' . escapeshellarg($tmpf) . ' > `tty`');
	$newentry = file_get_contents($tmpf);
	$dbwithout = ub_get_db_without_book($book['dbname'], $book['name']);
	$ret = ub_save_bibtex_to_db($book['dbname'], $newentry, 'edited by user with ub edit', $dbwithout);
	if ($options['cli']) {
		if ($ret !== false) {
			echo CLI_OK . " entry successfully saved\n";
		} else {
			echo CLI_NOK . " could not save entry\n";
		}
	}
	return $ret;
}

function ub_execute_list (array $command, array $options) {
	if (count($command) == 0) {
		foreach(ub_config()['db'] as $key => $db) {
			if (isset($db['path'])) { // don't iterate over links as the linked database would be listed twice here
				ub_execute_list([$key], $options);
			} 
		}
		return;
	}
	$bibtexfile = ub_db_get_path($command[0]);
	if (file_exists($bibtexfile)) {
		$lines = file($bibtexfile);
		foreach ($lines as $l) {
			if (preg_match('/^\s*@(\w+)\{(\w+),\s*$/i', trim($l), $pat)) {
				echo "$pat[2] ($pat[1])\n";
			}	
		}
	} else {
		if ($options['cli']) {
			echo CLI_NOK . " database »{$command[0]}« not found. Use db list to get a list of all databses\n";
		} else {
			return false;
		}
	}
}

function ub_execute_twitter (array $command, array $options) {
	if (count($command) == 0) {
		$command = ['setup'];
	}
	switch($command[0]) {
	 case 'tweet':
		 return ub_execute_twitter_tweet(array_shifted($command), $options);
	 case 'setup':
		 return ub_execute_twitter_setup(array_shifted($command), $options);
	}
}

function ub_execute_twitter_tweet (array $command, array $options) {
	if (count($command) == 0) {
		if ($options['cli']) {
			echo "Usage: twitter tweet identifier\n";
		}
		return;
	}
	$book = ub_execute_get($command, ['cli' => false]);
	$title = ub_get_val_from_bibtex($book, 'title');
	$author = ub_get_val_from_bibtex($book, ['author', 'editor']);
	$URL = ub_get_val_from_bibtex($book, ['hdsurl', 'url']);
	if (strlen($title) > 50) {
		$title = substr($title, 0, 50) . '…';
	}
	if (($title != '') && ($author != '')) {
		tweet("Ich halte gerade in der Hand: »{$title}« von $author $URL");
	}
}

function ub_execute_db (array $command, array $options) {
	if (count($command) == 0) {
		$command = ['list'];
	}
	switch($command[0]) {
	 case 'add':
		 return ub_execute_db_add(array_shifted($command), $options);
	 case 'list':
		 return ub_execute_db_list(array_shifted($command), $options);
	 case 'remove': case 'rm':
		 return ub_execute_db_remove(array_shifted($command), $options);
	 case 'link': case 'def':
		 return ub_execute_db_link(array_shifted($command), $options);
	}
}

function ub_execute_db_add (array $command, array $options) {
	if (count($command) > 1) {
		if (!isset(ub_config()['db'][$command[0]])) {
			if (!in_array($command[0], ub_link_names())) {
				if (!file_exists($command[1]) && $options['cli']) {
					echo CLI_WARNING . " database file »{$command[1]}« does not exist, adding anyway. Remove with db remove {$command[0]} if necessary\n";
				} else if (isset($command[2]) && $options['cli']) {
					$od = getcwd();
					chdir(dirname($command[1]));
					exec('git rev-parse --show-toplevel 2>&1', $output, $execretval);
					if ($execretval != 0) {
						   echo CLI_WARNING . " database file »{$command[1]}« is not in a git repository right now, trying to use git in the future anyway. Remove with db remove {$command[0]} if necessary\n";
					}
					chdir($od);
				}
				$GLOBALS['ub_config']['db'][$command[0]] = [
					'path' => realpath($command[1]),
					'git' => isset($command[2]),
				];
				ub_config_save();
				if ($options['cli']) {
					echo CLI_OK . " database »{$command[0]}« added\n";
				} else {
					return true;
				}
			} else {
				if ($options['cli']) {
					echo CLI_NOK . " »{$command[0]}« is no valid database name\n"; 
				} else {
					return false;
				}
			}
		} else {
			if ($options['cli']) {
				echo CLI_NOK . " database »{$command[0]}« does already exist\n"; 
			} else {
				return false;
			}
		}
	} else {
		if ($options['cli']) {
			echo "Usage: db add dbname path[, usegit]\n";
		}
	}
}

function ub_execute_db_link (array $command, array $options) {
	if (count($command) == 1) {
		$command[1] = 'main';
	}
	if (count($command) > 1) {
		if (in_array($command[1], ub_link_names())) {
			if (isset(ub_config()['db'][$command[0]])) {
				$GLOBALS['ub_config']['db'][$command[1]] = [
					'link' => $command[0]
				];
				ub_config_save();
				if ($options['cli']) {
					echo CLI_OK . " link " . cli_bold() . $command[1] . cli_unescape() . " set to »{$command[0]}«\n";
				} else {
					return true;
				}
			} else {
				if ($options['cli']) {
					echo CLI_NOK . " database »{$command[0]}« does not exist\n";
				} else {
					return false;
				}
			}
		} else {
			if ($options['cli']) {
				echo CLI_NOK . " »{$command[1]}« is no valid link name\n"; 
			} else {
				return false;
			}
		}
	} else {
		return ub_execute_db_list([], array_merge($options, ['only' => 'links']));
	}
}

function ub_execute_db_list (array $command, array $options) {
	if (!$options['cli']) {
		return ub_config()['db'];
	}
	if (!isset($options['only'])) {
		$options['only'] = false;
	}
	$printed = false;
	foreach(ub_config()['db'] as $name => $db) {
		$printed = true;
		if (in_array($name, ub_link_names())) {
			if ($options['only'] !== 'files') {
				echo cli_bold() . $name . cli_unescape() . " is linked to $db[link] database\n";
			}
		} else {
			if ($options['only'] !== 'links') {
				echo cli_bold() . $name . cli_unescape() . " is stored in $db[path]";
				if ($db['git']) {
					$od = getcwd();
					chdir(dirname($db['path']));
					exec('git rev-parse --show-toplevel 2>&1', $output, $execretval);
					$color = $execretval ? cli_red() : cli_green();
					echo " using {$color}git" . cli_unescape();
				}
				echo "\n";
			}
		}
	}
	if (!$printed) {
		echo "No databases configured. Use ›db add dbname, path[, usegit]‹ to add one\n";
	}
}

function ub_execute_db_remove (array $command, array $options) {
	if (count($command) > 0) {
		if (isset(ub_config()['db'][$command[0]])) {
			unset($GLOBALS['ub_config']['db'][$command[0]]);
			ub_config_save();
			if ($options['cli']) {
				echo CLI_OK . " database »{$command[0]}« removed\n";
			} else {
				return true;
			}
		} else {
			if ($options['cli']) {
				echo CLI_NOK . " database »{$command[0]}« could not be found and was therefore not removed\n"; 
			} else {
				return false;
			}
		}
	} else {
		if ($options['cli']) {
			echo "Usage: db remove dbname\n";
		}
	}
}

function ub_execute_copy (array $command, array $options) {
	if (count($command) == 0) {
		echo "Usage: copy identifier[, from[, to]]\n";
		return;
	}
	if (count($command) > 1) {
		$from = $command[1];
		if (count($command) > 2) {
			$to = $command[2];
		} else {
			$to = 'to';
		}
	} else {
		$from = 'from';
	}
	$fromp = ub_db_get_path($from);
	$top = ub_db_get_path($to);
	if (realpath($top) == realpath($fromp)) {
		if ($options['cli']) {
			echo CLI_NOK . " from and to have to be different databases\n";
		}
		return false;
	}
	$book = ub_execute_get([$command[0]], ['cli' => false, 'only_from_db' => $fromp]);
	return ub_save_bibtex_to_db($to, $book);
}

function ub_execute_get (array $command, array $options) {
	if (count($command) == 0) {
		if ($options['cli']) {
			echo "usage: get identifier[, fieldname]\n";
		}
		return;
	}
	if (count($command) > 1) {
		$book = ub_execute_get ([$command[0]], ['cli' => false]);
		$val = ub_get_val_from_bibtex($book, $command[1]);
		if ($options['cli']) {
			if ($val === false) {
				echo CLI_NOK . " entry »{$command[1]}« in book »{$command[0]}« not found\n";
			} else {
				echo "$val\n";
			}
			return true;
		} else {
			return $val;
		}
	}
	$book = ub_get_book($command[0], $options);
	if ($book === false) {
		if (isset(ub_config()['auto_add_on_failed_get']) && !isset($options['is_already_auto_get']) && ub_config()['auto_add_on_failed_get']) {
			ub_execute_add([$command[0], 'autoadd'], $options);
			return ub_execute_get($command, array_merge($options, ['is_already_auto_get' => true]));
		}
		if ($options['cli']) {
			echo CLI_NOK . " book »{$command[0]}« not found\n";
		}
		return false;
	}
	$book = $book['bibtex'];
	if ($options['cli']) {
		echo $book;
		return true;
	} else {
		return $book;
	}
}
