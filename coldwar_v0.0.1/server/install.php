<?php
	error_reporting(E_ALL);	
		
	include_once('./functions.php');
	
/*----------------------------------------------------------------------
	INSTALL
----------------------------------------------------------------------*/
		
	function install(){
		debug('INSTALL ------ drop & create<br/>');
		
		debug('connecting to database ...... ');
		@$link = new mysqli(DB_HOST, DB_USER, DB_PASS);
		if($err =  mysqli_connect_errno()){
			debug(DEBUG_FAIL);
			db_err_handle($err);
		}
		debug(DEBUG_OK);			
		
		// drop if exists & create database		
		$query = 'DROP DATABASE IF EXISTS `'.DB_NAME.'`;';
		$query.= 'CREATE DATABASE `'.DB_NAME.'`;';
		
		debug('opening \''.DB_IMPORT.'\' import file ...... ');	
		$query.= file_get_contents(DB_IMPORT);						
		
		debug(DEBUG_OK.'creating database and importing ...... ');		
		
		if(!mysqli_multi_query($link, $query)){
			debug(DEBUG_FAIL);
			db_err_handle(mysqli_error($link));
		} 
		
		debug(DEBUG_DONE);		
		return true;
	}
	
	if($params['init'] && $params[APP_PASS])
		install();
	else
		is_installed();
	
		
	debug();	
?>