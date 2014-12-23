<?php

define ('CLI_OK', cli_bold() . "[" . cli_green() . "✓" . cli_unescape() . cli_bold() . "]" . cli_unescape());
define ('CLI_NOK', cli_bold() . "[" . cli_red() . "✘" . cli_unescape() . cli_bold() . "]" . cli_unescape());
define ('CLI_WARNING', cli_bold() . "[" . cli_red() . "⚠" . cli_unescape() . cli_bold() . "]" . cli_unescape());

function ub_link_names () { // since there are no array constants possible…
	return ['main', 'barcode', 'from', 'to'];
}

function cli_unescape() {
	if (posix_isatty(STDOUT)) {
		return "\033[0m";
	} else {
		return '';
	}
}

function cli_bold() {
	if (posix_isatty(STDOUT)) {
		return "\033[1m";
	} else {
		return '';
	}
}

function cli_red() {
	if (posix_isatty(STDOUT)) {
		return "\033[31m";
	} else {
		return '';
	}
}

function cli_green() {
	if (posix_isatty(STDOUT)) {
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
	require_once __DIR__ . '/ub.' . strtolower($class) . '.class.php';
});


//// ub_ functions

function ub_cli_text ($name) {
	$fn = __DIR__ . '/ub.' . str_replace('/', '_', $name) . '.txt';
	if (file_exists($fn)) {
		echo str_replace("\t", "  ", file_get_contents($fn));
	}
}

function ub_db_get_path($name = null) {
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
				return $dbs[$link]['path'];
			}
		}
	} else {
		if (isset($dbs[$name])) {
			return $dbs[$name]['path'];
		}
	}
}

function ub_config () {
	if (!isset($GLOBALS['ub_config'])) {
		if (!defined('UB_CONFIG_FILE')) {
			define('UB_CONFIG_FILE', posix_getpwuid(posix_getuid())['dir'] . '/.ubconfig.json');
		}
		if (!file_exists(UB_CONFIG_FILE)) {
			file_put_contents(UB_CONFIG_FILE, "{}\n");
		}
		$c = json_decode(file_get_contents(UB_CONFIG_FILE), true);
		if (!isset($c['db'])) {
			$c['db'] = [];
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
		}
		$GLOBALS['ub_config'] = $c;
		register_shutdown_function('ub_config_save');
	}
	return $GLOBALS['ub_config'];
}

function ub_config_save () {
	file_put_contents(UB_CONFIG_FILE, json_encode($GLOBALS['ub_config']));
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
	}
}

function ub_execute_add (array $command, array $options) {
	if (count($command) > 0) {
		if (count($command) == 1) {
			$command[1] = 'main';
		}
		$dbpath = ub_db_get_path($command[1]);
		foreach (ub_config()['plugins'] as $plugin) {
			if ($plugin::forme($command[0])) {
				$p = new $plugin($command[0]);
				var_dump($p); die;
				if ($options['cli']) {
					echo CLI_OK . " entry »{$command[0]}« successfully saved\n";
				}
				return true;
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

}

function ub_execute_get (array $command, array $options) {

}
