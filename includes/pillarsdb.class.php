<?php

	define( 'DB_MYSQL', 1 );
	define( 'DB_MARIADB', 1 );
	define( 'DB_PGSQL', 2 );
	define( 'DB_SQLITE', 3 );


class pillarsDB {

	private $connection;
	private $connected;	
	private $dbconfig;
	private $result;

	public function __construct( $new_link = false ){
		global $config;
		$this->connected = false;
		$this->connection = false;
		$this->dbconfig = $config[ 'db_config' ];
		$this->result = false;
		$error = false;

		foreach( array( 'host', 'username', 'password', 'type', 'database' ) as $key ){
			if ( !array_key_exists( $key, $this->dbconfig ) || empty( $this->dbconfig[ $key ] ) ) {
				$error .= 'PillarsDB::__construct: "' . $key . '" is undefined, please set up includes/configuration.php properly' ."\n";
			}
		}
		if ( $error ) {
			throw new Exception( $error );
		} else {
			if ( !$this->connected ) {
				switch ( $this->dbconfig[ 'type' ] ) {
					case DB_MYSQL:
						$this->connect_mysql( $new_link );
						break;
					default:
						throw new Exception( 'Sorry, that DB type: ' . $this->dbconfig[ 'type' ] . ' is not implemented yet.' );
				}
			}
		}
	}

	private function connect_mysql( $new_link ) {
		$this->connection = mysqli_connect( $this->dbconfig[ 'host' ], $this->dbconfig[ 'username' ], $this->dbconfig[ 'password' ], $new_link );
		if ( $this->connection === false ) {
			throw new Exception( 'PillarsDB::connect_mysql: ' . mysqli_errno($this->connection) . ':' . mysqli_error($this->connection) );
		} else {
			$this->connected = true;
		}
		if( !mysqli_select_db( $this->connection, $this->dbconfig[ 'database' ] ) ) {
			throw new Exception( 'PillarsDB::connect_mysql: ' . mysqli_errno($this->connection) . ':' . mysqli_error($this->connection));
		}
	}

	public function query ( $query, $echo_query = false ){
		if ( $this->connected ) {
			switch( $this->dbconfig[ 'type' ] ){
				case DB_MYSQL:
					if ( $echo_query ) {
						echo '<pre>';
						echo $query;
						echo '</pre>';
					}
					return $this->query_mysql( $query );
					break;
				default:
					break;
			}
		} else {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
	}

	private function query_mysql ( $query ) {
		$this->result = mysqli_query( $this->connection, $query );
		if ( !$this->result  ) {
			throw new Exception( __CLASS__ . '::' . __FUNCTION__ . ': invalid query: ' . mysqli_error( $this->connection ) . ':<pre>' . $query . '</pre>' );
		}
	}

	public function num_results(){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
			case DB_MYSQL:
			return mysqli_num_rows( $this->result );
			break;
		}

	}

	public function fetch_row(){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
			case DB_MYSQL:
			return mysqli_fetch_assoc( $this->result );
			break;
		}
		
	}

	public function fetch_all(){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
		case DB_MYSQL:
			$result = array();
			while( $row = mysqli_fetch_assoc( $this->result ) ){
				$result[] = $row;
			}
			return $result;
			break;
		}
	}

	public function escape( $string ){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
			case DB_MYSQL:
			return mysqli_real_escape_string( $this->connection, $string );
			break;
		}
	}

	public function get_affected_rows(){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
			case DB_MYSQL:
			return mysqli_affected_rows( $this->connection );
			break;
		}
	}

	public function get_insert_id(){
		if ( !$this->connected ) {
			throw new Exception( 'PillarsDB::' . __FUNCTION__ . ': Not connected to DB' );
		}
		switch ( $this->dbconfig[ 'type' ] ) {
			case DB_MYSQL:
			return mysqli_insert_id( $this->connection );
			break;
		}
	}
}


