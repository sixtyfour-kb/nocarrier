<?php	
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: Overwrite, Destination, Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control');
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400');
	header('Access-Control-Allow-Headers: Content-Type');
	header("Content-Type:text/plain");
	header("Cache-Control: no-cache, must-revalidate");

	include('./functions.php');
		
	function talkback($say){
		global $LOG;		
		$flags = set_flag($say);
		$LOG->save();
		die(json_encode(array('talkback' => $flags)));
	}
	
	function http_forbidden(){
		header('X-PHP-Response-Code: 403', true, 403);
		die();
	}
	
//--------------------------------------------------------------------//
//	DOORSTOP
//--------------------------------------------------------------------//
	if(!isset($_REQUEST['user']) || !isset($_REQUEST['pass']) || !isset($_REQUEST['timestamp']))
		http_forbidden();
	
	$allow				= false;
	$update_available	= false;
	$db_prefix			= DB_PREFIX;
	$flags				= '1100';		
	$user 				= $_REQUEST['user'];
	$pass 				= $_REQUEST['pass'];
	$client_timestamp	= $_REQUEST['timestamp'];
	
	// LOG
	$LOG = new Log();
	$LOG->setip($_SERVER['REMOTE_ADDR']);
		
	connect();
		
	$query = "SELECT * FROM {$db_prefix}users WHERE username = '{$user}' AND pass = '{$pass}'";
		
	if(mysqli_num_rows($u = retrieve($query)) === 1){
		
		$user_data = mysqli_fetch_assoc($u);	

		if($user_data['id'] == UNKNOWN_USER_ID)
			talkback(SERVER_DONT_KNOW_YOU);
		
		$LOG->whois($user_data);		
		
		// UPADATES & TIMESTAMP
		//-----------------------------------------------------------------
		if($client_timestamp == -1){ 
			set_flag(SERVER_UPDATE_READY);
		}
		else {		
			// compare timestamps
			$query = "SELECT TIMESTAMPDIFF(SECOND, `last_update`, '{$client_timestamp}') FROM `meta`";
			if(mysqli_num_rows($r = retrieve($query)) !== 1) 
				talkback(SERVER_DATA_IS_WRONG);
			
			$server_timestamp = mysqli_fetch_array($r);
			if($server_timestamp[0] != 0)
				set_flag(SERVER_UPDATE_READY);
		}
		// UPDATE & TIMESTAMP. END OF BLOCK
		
		set_flag(SERVER_KNOW_YOU);
		
		$allow = true;	
	
	} else
		talkback(SERVER_DONT_KNOW_YOU);	
	
	if($flags[2] == '0'){
		talkback(SERVER_NO_UPDATE);
		die();
	}
		
	if(!$allow)
		http_forbidden();
		
//--------------------------------------------------------------------//
//	SYNC
//--------------------------------------------------------------------//
	error_reporting(E_ALL);

/*	DB -> CLIENT NAMES TRANSLATION
-----------------------------------------------------------------------*/
	
	$DRG_NAMES = array(
		'id'				=> 'id',
		'slug' 				=> 'slug',
		'name' 				=> 'name',
		'price'				=> 'price',
		'discount'			=> 'discount',
		'commercial_name'	=> 'commercial',
		'dossage_form'		=> 'dosageForm',
		'mg_per_unit'		=> 'mgPerUnit',
		'units_per_box'		=> 'unitsPerBox',
		'mg_per_kilo'		=> 'mgPerKilo',
		'mg_per_area'		=> 'mgPerArea',
		'days_per_cycle'	=> 'daysPerCycle',
		'cycles'			=> 'cycles',
		'mg_per_cycle'		=> 'mgPerCycle',
		'quantity'			=> 'quantity',
		'fixes'				=> 'fixes'
	);
		
	$COUNTRY = 'Argentina';
	
	$query_sch = "SELECT {$db_prefix}schemes.* 
		FROM ({$db_prefix}countries, {$db_prefix}schemes)
		JOIN {$db_prefix}relations ON
			{$db_prefix}relations.scheme_id = {$db_prefix}schemes.id AND
			{$db_prefix}relations.country_id = {$db_prefix}countries.id
		WHERE {$db_prefix}countries.name = '{$COUNTRY}'
		GROUP BY {$db_prefix}schemes.id";
	
	$query_rel = "SELECT {$db_prefix}relations.id AS id, {$db_prefix}schemes.slug AS scheme, {$db_prefix}drugs.slug AS drug
		FROM ({$db_prefix}schemes, {$db_prefix}drugs, {$db_prefix}countries)
		JOIN {$db_prefix}relations ON
			{$db_prefix}relations.scheme_id		= {$db_prefix}schemes.id AND
			{$db_prefix}relations.country_id	= {$db_prefix}countries.id AND
			{$db_prefix}relations.drug_id		= {$db_prefix}drugs.id
		WHERE
			{$db_prefix}countries.name			= '{$COUNTRY}'";
			
	$query_drg = "SELECT {$db_prefix}drugs.*, {$db_prefix}prices.price, {$db_prefix}prices.discount 
		FROM ({$db_prefix}drugs) 
		JOIN {$db_prefix}prices 
		ON {$db_prefix}prices.drug_id = {$db_prefix}drugs.id 
		JOIN {$db_prefix}countries ON {$db_prefix}prices.country_id = {$db_prefix}countries.id 
		AND {$db_prefix}countries.name = '{$COUNTRY}'";
		
	$query_def = "SELECT DISTINCT defaults.id,
			tml1.name AS tml1_slug, 
			tml2.name AS tml2_slug, 
			tml3.name AS tml3_slug, 
			notml1.name AS notml1_slug, 
			notml2.name AS notml2_slug,
			notml3.name AS notml3_slug,
			{$db_prefix}defaults.infusion,
			{$db_prefix}defaults.weight,
			{$db_prefix}defaults.height
		FROM
			({$db_prefix}defaults, {$db_prefix}schemes)
		JOIN {$db_prefix}countries ON
			{$db_prefix}countries.id = {$db_prefix}defaults.country
		JOIN {$db_prefix}schemes AS tml1 ON
			tml1.id = {$db_prefix}defaults.tml_1
		JOIN {$db_prefix}schemes AS tml2 ON
			tml2.id = {$db_prefix}defaults.tml_2
		JOIN {$db_prefix}schemes AS tml3 ON
			tml3.id = {$db_prefix}defaults.tml_3
		JOIN {$db_prefix}schemes AS notml1 ON
			notml1.id = {$db_prefix}defaults.notml_1
		JOIN {$db_prefix}schemes AS notml2 ON
			notml2.id = {$db_prefix}defaults.notml_2 
		JOIN {$db_prefix}schemes AS notml3 ON
			notml3.id = {$db_prefix}defaults.notml_3
		WHERE {$db_prefix}countries.name = '{$COUNTRY}'";
	
	$query_met = "SELECT * FROM meta";
		
	function db_fetch_to_array_rel($query){
		$table = retrieve($query);
			
		while($fields = mysqli_fetch_assoc($table)){
					
			foreach($fields as $f => $d)				
				$t1[$f] = $d;					

			$t2[$fields['id']] = $t1;
		}
		return $t2;
	}		
		
	function db_fetch_to_array_scheme($query){
		$table = retrieve($query);
			
		while($fields = mysqli_fetch_assoc($table)){
					
			foreach($fields as $f => $d)				
				$t1[$f] = $d;					

			$t2[$fields['id']] = $t1;
		}	

		return $t2;
	}
	
	function db_fetch_to_array_drugs($query){
		
		global $DRG_NAMES;
		
		$table = retrieve($query);
			
		while($fields = mysqli_fetch_assoc($table)){
					
			foreach($fields as $f => $d)
				$t1[$DRG_NAMES[$f]] = $d;			

			$t2[$fields['id']] = $t1;
		}	
		return $t2;
	}
	
	function db_fetch_to_array_defaults($query){
		$table = retrieve($query);
			
		while($fields = mysqli_fetch_assoc($table)){
					
			foreach($fields as $f => $d)				
				$t1[$f] = $d;					
			
			$t2[] = $t1;
		}	

		return $t2;
	}	

	function db_fetch_to_array_meta($query){
		$table = retrieve($query);
			
		while($fields = mysqli_fetch_assoc($table)){
					
			foreach($fields as $f => $d)				
				$t1[$f] = $d;					
			
		}	

		return $t1;
	}
	
	//	TALKBACK
	//
	//	bit		descript
	//	------------------------
	//	0		server is up
	//	1		server_data_is_ok
	//	2		know you
	//	3		update available
		
	$json = array(
		'drugs'		=> db_fetch_to_array_drugs($query_drg),
		'schem'		=> db_fetch_to_array_scheme($query_sch),
		'relat' 	=> db_fetch_to_array_rel($query_rel),
		'defaults'	=> db_fetch_to_array_defaults($query_def),
		'ticket'	=> db_fetch_to_array_meta($query_met)
	);
	
	$LOG->save();
	
	$json['talkback'] = $flags;
		
	header('Content-type: application/json');
	echo json_encode($json);	
?>