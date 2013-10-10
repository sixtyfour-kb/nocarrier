<?php
	error_reporting(E_ALL);
	
	// password expected for database reset
	define('APP_PASS',	'pass');
		
	// get script params
	// init:		drop & reinitializes database
	// APP_PASS:	password required for reinitializing
	// verbose:		echoes functions debug messages
	$params['init']		= isset($_GET['init'])		? true : false;
	$params['verbose']	= isset($_GET['verbose'])	? true : false;
	$params[APP_PASS]	= isset($_GET[APP_PASS])	? true : false;	
	
	// reinitialising requires password
	if($params['init'] && !$params[APP_PASS])
		die('AVASTIN::access denied');	
	
	// database
	define('DB_HOST',	'68.178.216.48');
	define('DB_USER',	'mipsdb');
	define('DB_PASS',	'Cocotero77!');
	define('DB_NAME',	'mipsdb');
	define('DB_PREFIX',	'');
	define('DB_IMPORT',	'avastin.sql');
	
	// err codes	
	define('SERVER_IS_DOWN',		'0---');
	define('SERVER_DATA_IS_WRONG',	'-0--');
	define('SERVER_DONT_KNOW_YOU',	'--0-');
	define('SERVER_NO_UPDATE',		'---0');
	// ok codes
	define('SERVER_IS_UP',			'1---');
	define('SERVER_DATA_IS_OK',		'-1--');
	define('SERVER_KNOW_YOU',		'--1-');
	define('SERVER_UPDATE_READY',	'---1');
		
	// debug
	define('DEBUG_VERBOSE',	(isset($params['verbose'])) ? $params['verbose'] : false);
	define('DEBUG_FAIL',	"<span class='debug-fail'>FAIL</span><br/>");
	define('DEBUG_OK',		"<span class='debug-ok'>OK</span><br/>");
	define('DEBUG_DONE',	"<span class='debug-ok'>DONE</span><br/>");
	
	// used in database integrity check
	$AVASTIN_TABLES = array(
		DB_PREFIX.'countries',
		DB_PREFIX.'drugs',
		DB_PREFIX.'i18n_in',
		DB_PREFIX.'i18n_out',
		DB_PREFIX.'prices',
		DB_PREFIX.'relations',
		DB_PREFIX.'schemes',		
		DB_PREFIX.'users'		
	);
	
	define('UNKNOWN_USER_ID', 2);
	
	if(DEBUG_VERBOSE)
		echo '<style>.debug-fail{color:red;}.debug-ok{color:green;}</style>';
	
	// mysqli extension is required
	if(!class_exists('mysqli'))
		throw new Exception('AVASTIN::mysqli extension not found');
		
	$link	= false;	// global database connection
	$debug	= array();	// debug message buffer
		
	function debug($entry = false){
		if(DEBUG_VERBOSE){
			global $debug;
			
			if($entry)
				array_push($debug, $entry);
			else {
				foreach($debug as $d)
					echo $d;
					
				$debug = array();
			}				
		}
	}
	
	function connect($halt = false){
		global $link;
		
		debug('CONNECT ...... connecting ...... ');		
		@$link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		
		if($err =  mysqli_connect_errno()){
			debug(DEBUG_FAIL);
			if($halt)
				db_err_handle($err);
			else
				return $err;
		}
		debug(DEBUG_OK);
		return true; 	
	}
	
	function disconnect(){
		global $link;
		mysqli_close($link);
	}
	
	function retrieve($query){
		global $link;		
		debug('RETRIEVE ...... retrieving data ...... ');
		if(!$data = mysqli_query($link, $query)){
			debug(DEBUG_FAIL);
			db_err_handle(mysqli_error($link));
		}	
		debug(DEBUG_OK);		
		return $data;
	}
	
	function write($query, $expected_count = 1){
		global $link;
		
		if(!$data = mysqli_query($link, $query)){
			debug(DEBUG_FAIL);
			db_err_handle(mysqli_error($link));
		}

		if(mysqli_affected_rows($link) < $expected_count)
			return false;
		
		return mysqli_insert_id($link);
	}
	
	function is_installed(){
		
		debug('CHECK INSTALLATION ------ checking database integrity<br/>');
	
		global $link;
		global $AVASTIN_TABLES;
			
		// Can connect to MySQL server?
		debug('server connection ...... ');
		@$link = new mysqli(DB_HOST, DB_USER, DB_PASS);
		if($err =  mysqli_connect_errno()){
			debug(DEBUG_FAIL);	
			return false;
		}
		
		// Avastin database exists?
		debug(DEBUG_OK.'database exists ...... ');
		if(($status = connect()) !== true)
			return false;			
		
		// Get tables tables
		debug('getting tables ...... ');
		$r = mysqli_query($link, 'SHOW TABLES');			
		// Is there any table missing?
		debug(DEBUG_OK.'missing tables ...... ');
		if(mysqli_num_rows($r) < count($AVASTIN_TABLES)){
			debug(DEBUG_FAIL);	
			return false;
		}
		
		// Is there more tables?
		debug(DEBUG_OK.'alien tables ...... ');
		if(mysqli_num_rows($r) < count($AVASTIN_TABLES)){
			debug(DEBUG_FAIL);	
			return false;
		}
		
		// Is there any mismatch in table names?	
		debug(DEBUG_OK.'checking mismatch tables:<br/>');		
		while(list($t) = mysqli_fetch_row($r)){
			debug($t.' ...... ');
			if(!in_array($t, $AVASTIN_TABLES)){
				debug(DEBUG_FAIL);	
				return false;
			}
			debug(DEBUG_OK);
		}
		
		debug('CHECK INSTALLATION ------ '.DEBUG_DONE);
		return true;
	}
	
	function db_err_handle($errcode){
		global $link;
		debug();
		switch($errcode){
			case('400'):
				header(' ', true, 400);die();
				break;
			case('2005'): // 2005 Unknown MySQL server host
				$err = 'AVASTIN::Can´t reach server host -> '.E_USER_ERROR;
				break;
			case('1045'): // 1045 Access denied wrong user/pass
				$err = 'AVASTIN::Access denied -> '.E_USER_ERROR;
				break;
			case('1049'): // 1049 Unknown database
				$err = 'AVASTIN::Unknown database -> '.E_USER_ERROR;
				break;
			default:
				$err = mysqli_error($link).' -> AVASTIN::Unrecoverable error -> '.E_USER_ERROR;
		}
		if(function_exists('talkback'))
			talkback(SERVER_IS_DOWN);
		die('...died');
	}
	
	function set_flag($bits){
		global $flags;
	
		if(strlen($bits) != 4)
			throw new InvalidArgumentException('set_flags: 4 bits expected. Read: ' + $bits);
			
		for($i = 0; $i < 4; $i++){
			if($bits[$i] == '-') continue;
			$flags[$i] = $bits[$i];	
		}
		return $flags;
	}
		
	class Log{
		private $uid	= UNKNOWN_USER_ID;
		private $ip		= 'unknown';
		
		public function whois($userdata){
			$this->uid = $userdata['id'];
		}
		
		public function setip($ip){
			$this->ip = $ip;
		}
			
		public function save(){
			global $link;
			global $flags;
			
			write("INSERT INTO log (user_id, ip, response) VALUES ('{$this->uid}', '{$this->ip}', {$flags})");
			
		}
	}
?>