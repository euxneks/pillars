<?php
/**
 * Template file for the configuration.php file. You should copy this file to configuration.php and fill in the config variables with the correct values.
 */
	require_once( 'includes/pillarsdb.class.php' );
	require_once( 'includes/core.class.php' );
	require_once( 'includes/template.class.php' );
	require_once( 'includes/urlhandler.class.php' );
	
	global $config;

	$config[ 'db_config' ][ 'username' ] 					= '';
	$config[ 'db_config' ][ 'password' ] 					= '';
	$config[ 'db_config' ][ 'host' ] 						= '';
	$config[ 'db_config' ][ 'database' ] 					= '';
	$config[ 'db_config' ][ 'type' ] 						= DB_MYSQL; //DB_MARIADB, DB_MYSQL, DB_PGSQL

	$config[ 'template_dir' ] 								= 'templates/';
	$config[ 'extension' ]									= 'blog';
	$config[ 'BASE_URL' ] 									= '/homepage/';
	$config[ 'require_https' ] 								= false;

	$config[ 'template_defaults' ][ 'email_contact' ] 		= Core::obfuscate_email( 'example@example.com', 'contact the webmaster' );
	$config[ 'template_defaults' ][ 'copywriter' ] 			= '';
	$config[ 'template_defaults' ][ 'title' ] 				= '';
	$config[ 'template_defaults' ][ 'BASE_URL' ] 			= $config[ 'BASE_URL' ];

	$config['ldap']['uri'] 									= '';
	$config['ldap']['auth_agent'] 							= '';
	$config['ldap']['agent_password'] 						= '';
	$config['ldap']['lab_group_cn'] 						= '';
	$config['ldap']['netlink_ou'] 							= '';
	$config['auth'][ 'needs_login' ] 						=false;
