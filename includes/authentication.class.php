<?php

define( "AUTH_LDAP", 1 );
define( "AUTH_MYSQL", 2 );
define( "AUTH_MARIADB", 2 );
define( "AUTH_LOCAL", 4 );

/**
	*		Authentication class.
	*		This is a class used for user authentication.  You call this with username and password peramaters and this class will determine whether 
	*		or not this user is authenticated. It will also typically get the user information for further use by other classes..
	*
 */

class Authentication {
	private $auth_type;
	private $server_key;
	/**
	 *  function __construct
	 *  Simple constructor for authentication. Takes as its parameter a bitmask of defined values.
	 *
	 *  @param type_of_auth bitmask of AUTH_LDAP, AUTH_MYSQL, AUTH_MARIADB, or AUTH_LOCAL
	 *
	 */
	public function __construct( $type_of_auth = AUTH_MARIADB ){
		$this->auth_type = $type_of_auth;
		$this->server_key = 'sxJoi51uaPnMyzWADFTr'; //choose a random string like this.
	}

	/**
	 * public function authenticate
	 *
	 * This function is a simple wrapper for auth_user that checks to see if we should log out first, before user authentication
	 *
	 * @param &$database An instance of pillarsDB that is currently connected to the DB
	 * @param &$urlhandler An instance of urlhandler which we are currently using in the rest of the scripts
	 *
	 * @throws Exception when you have been successfully logged out.
	 *
	 */
	public function authenticate( &$database, &$urlhandler ){
		
		if ( $urlhandler->count_parts() > 0 && $urlhandler->get( 0 ) == 'logout' ) {
			//logout, remove the session information.
			$_SESSION[ 'user_info' ][ 'uid' ] = $database->escape( $_SESSION[ 'user_info' ][ 'uid' ] );
			$query = 'DELETE FROM mutex WHERE uid=\'' . $_SESSION[ 'user_info' ][ 'uid' ] . '\'';
			$database->query( $query );
			$_SESSION[ 'authentication' ] = NULL;
			$_SESSION[ 'user_info' ] = NULL;
			//remove all mutexes from the mutex table
			throw new Exception( 'You have been successfully logged out', 2 );
		} else {
			$this->auth_user( $database, $urlhandler );
		}
	}

	/**
	 * public function get_user_info()
	 *
	 * This is another basic function which simply returns user information which was retreived and stored in the session when we first authenticated the user.
	 *
	 * @returns An array consisting of the currently logged in user.
	 */
	public function get_user_info(){
		if ( array_key_exists( 'user_info', $_SESSION ) && !empty( $_SESSION[ 'user_info' ] ) ) {
			return $_SESSION[ 'user_info' ];
		}
	}

	/**
	 * public function auth_user
	 *
	 * Meat and bones of the authentication class. This function will take as it's params, a database and urlhandler instance and check for a username and password value in the POST information.
	 * It will also sanity check the cookie information.
	 * The steps this function takes is to
	 * 		1) Check to see if there is any user information currently in the session. If so, this means the user has possibly already authenticated.
	 * 			1.a) If there is user information, check to make sure that this session is actually valid and just return the user info if it is. If it isn't, remove the authentication data
	 * 		2) check to see if there is any values in POST for authentication checking, if there is:
	 * 			2.a) Check the authentication method given in the constructor against a bitmask operation of the defined variables.
	 *			This system is slightly unique in that it will get the user information to see what type of user this person is. If the user is defined as "local" then, if we have AUTH_LOCAL set,
	 *			verify user infromation.
	 *			Do the same with LDAP or other methods.
	 *		3) Otherwise, just throw an exception, with any messages if needed.
	 *
	 *	@param $database a pillarsDB instance
	 *	@param $urlhandler a urlhandler instance
	 *
	 *	@return an array of the successfully authenticated user's information.
	 */

	public function auth_user( &$database, &$urlhandler ) {
		global $config;
		//authenticate, show a login window, unless you've already got a SESSION variable for login, in which case check authentication and save a session value.
		if ( !empty( $_SESSION[ 'user_info' ] ) ) {
			//has already authenticated
			if ( array_key_exists( 'authentication', $_SESSION ) && $_SESSION[ 'authentication' ] !== crypt( $_SERVER[ 'REMOTE_ADDR' ] . $this->server_key, $_SESSION[ 'authentication' ]  ) ) {
				$_SESSION[ 'authentication' ] = NULL;
				$_SESSION[ 'user_info' ] = NULL;
				throw new Exception( 'Error with Authentication', 1 );
			} else {
				return $_SESSION[ 'user_info' ];
			}
		} else if ( !empty( $_POST[ 'username' ] ) && !empty( $_POST[ 'password' ] ) ) {
			//authenticate user information
			$auth = false;
			$auth_message = '';
			//first, check with the db to see if this person is in the DB.
			$crypt = $this->check_db( $database, $_POST[ 'username' ], $_POST[ 'password' ] );
			if ( $crypt !== false && ($this->auth_type & AUTH_LDAP)  && $_SESSION[ 'user_info' ][ 'auth_type' ] == 'ldap' ) {
				$ldap_connection = ldap_connect( $config['ldap']['uri'] );
				if ( $ldap_connection ) {
					$ldapbind = ldap_bind( $ldap_connection, $config['ldap']['auth_agent'], $config['ldap']['agent_password'] );
					if ( !$ldapbind ) {
						throw new Exception( 'LDAP failed to bind', 1 );
					}
					if ( !ldap_bind( $ldap_connection, 'uid=' . $_POST[ 'username' ] . ',' . $config[ 'ldap' ][ 'netlink_ou' ], $_POST[ 'password' ] ) ) {
						$auth = false;
						$auth_message = 'Invalid username and/or password';
					} else {
						//we have a valid user and they are a lab user.
						$auth = true;
						if ( $_SESSION[ 'user_info' ][ 'status' ] !== 'enabled' ) {
							$_SESSION[ 'user_info' ] = NULL;
							$_SESSION[ 'authentication' ] = NULL; 
						} else {
							$_SESSION[ 'authentication' ] = crypt( $_SERVER[ 'REMOTE_ADDR' ] . $this->server_key );
						}
					}
				} else {
					throw new Exception( 'LDAP Connection auth:' . $ds, 1 );
				}
			}
			if ( $_SESSION[ 'user_info' ][ 'auth_type' ] == 'local' && !empty( $crypt ) ) {
				if ( $this->auth_type & AUTH_MARIADB ) {
					//check for successful database auth.
					//verify the user password with crypt().
					if ( $crypt !== crypt( $_POST[ 'password' ], $crypt ) ) {
						$_SESSION[ 'user_info' ] = NULL;
						$_SESSION[ 'authentication' ] = NULL; 
						throw new Exception( 'Invalid username and/or password', 1 );
					} else {
						$auth = true;
						if ( $_SESSION[ 'user_info' ][ 'status' ] !== 'enabled' ) {
							$_SESSION[ 'user_info' ] = NULL;
							$_SESSION[ 'authentication' ] = NULL; 
							$auth = false;
						} else {
							$_SESSION[ 'authentication' ] = crypt( $_SERVER[ 'REMOTE_ADDR' ] . $this->server_key );
						}
					}
				} else {
					throw new Exception( 'Database authentication has not been selected for authentication.', 1 );
				}
			}
			if ( !$auth ) {
				throw new Exception( 'Username and/or password is incorrect' . print_r( $auth, true ), 1 );
			}
			return $_SESSION[ 'user_info' ];
		}
		if ( $config['auth']['needs_login'] ) {
			throw new Exception( 'Not Authenticated', 1 );
		}
	}

	/**
	 *  private function check_db
	 *
	 *  Checks the database for information on this user, if the user is not enabled, or the user doesn't exist, then this function throws an exception.
	 *
	 * @param $database a pillarsDB instance
	 * @param $username the uid of the user attempting to authenticate.
	 * @param $password the user's password.
	 *
	 * @return the crypt value of the user. This may be empty for LDAP users, as we do not store their password information.
	 */

	private function check_db( &$database, $username, $password ){
		global $config;
		$database->query( 'SELECT `uid`, `id_group`, `auth_type`, `status`, `fname`, `lname`, `crypt`, `email` 
			FROM `user` WHERE ( `uid` = \'' . $database->escape( $username ) . '\' OR `email`=\'' . $database->escape( $username ) . '\') AND `status` = \'enabled\'' );
		$row = $database->fetch_row();
		if ( empty( $row ) ) {
			//no user with this username
			return false;
		}
		$valid_keys = array( 'uid', 'id_group', 'status', 'auth_type', 'fname', 'lname', 'email' );
		foreach ( $valid_keys as $key ) {
			$_SESSION[ 'user_info' ][$key] = $row[ $key ];
		}
		return $row[ 'crypt' ];
	}
}

?>
