<?php
/**
 * class UrlHandler
 *
 * This is a class used to help out with the URL. This would typically be used in conjunction with
 * a mod_rewrite to a single php file (usually index.php).  Anyway, it will take nicely formatted
 * URLs and split them apart by "/". You can then proceed to get whichever part of the url you want
 *
 * @author: Chris Tooley <euxneks@gmail.com>
 * @
 */

class UrlHandler {

	private $url_parts;
	private $request;
	private $is_https;
	private $data;
	private $base_url;

	/**
	 * public function __construct
	 *
	 * Constructor function for UrlHandler. Takes as a value the base url we want to ignore and
	 * processes the rest of the REQUEST.  Also processes the data (if present in POST).
	 *
	 * @param $base_url		The base url we want to ignore in the parts.
	 *
	 */

	public function __construct( $base_url ){
		$this->base_url = $base_url;
		
		$this->is_https = false;
		if( !empty( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] === 'on' ){
			$this->is_https = true;
		}
        $this->request = $_SERVER[ 'REQUEST_URI' ];
		if ( $base_url != '/' ) {
			$url = str_replace( $base_url, '', $_SERVER[ 'REQUEST_URI' ] );
		} else {
			$url = $_SERVER[ 'REQUEST_URI' ];
		}
		$url = explode( '?', $url );
		$url = $url[0];
		$this->url_parts = explode( '/', $url );
		foreach( $this->url_parts as $key => $part ){
			if ( $part == '' ) {
				unset( $this->url_parts[ $key ] );
			}
		}
		if ( !empty( $_POST[ 'data' ] ) ) {
			$this->data = json_decode( $_POST[ 'data' ], true );
		} else {
			$this->data = false;
		}
		$this->url_parts = array_values( $this->url_parts );
	}

	/**
	 * public function is_secure
	 *
	 *	This is used to determine whether the connection we're on is secure or not - very basic
	 * it only checks to see if the URL is https or not.
	 *
	 * @return true If the protocol used is https, false otherwise.
	 *
	 */

	public function is_secure(){
		return $this->is_https;
	}

	/**
	 * function dump_parts
	 *
	 *	Var dumps the url parts of this current instance
	 */

	public function dump_parts(){
		var_dump( $this->get_all_parts() );
	}

	/**
	 * public function get_all_parts
	 *
	 *	returns all the url parts in an array
	 *
	 * @return The url parts, split by "/"
	 *
	 */

	public function get_all_parts(){
		return $this->url_parts;
	}

	/**
	 * public function has_part
	 *
	 * This is used to determine if a specific value is in the url
	 *
	 * @param $value	The value we wish to search for
	 *
	 * @return true if this url contains $value
	 *
	 */

	public function has_part( $value ){
		return in_array( $value, $this->url_parts );
	}

	/**
	 * public function get_url
	 *
	 * gets the full request URI for this current request.
	 *
	 * @return The fully requested URL for this current instance
	 *
	 */

	public function get_url() {
        return $this->request;
    }

	public function get_server_val( $VAL ){
		if ( array_key_exists( $VAL, $_SERVER ) ) {
			return $_SERVER[ $VAL ];
		}
		return false;
	}

	/**
	 * public function get_data
	 *
	 * gets all data
	 *
	 * @return The data specified in POST to this url.
	 */

	public function get_data(){
		return $this->data;
	}

	/**
	 * public function get
	 * 
	 * gets a value from the url specified by the offset.
	 *
	 * @param $offset		The offset, where 0 is the first value.
	 *
	 * @return The part of the url specified by the offset.
	 *
	 * @throws Exception If the offset is outside of the range of values.
	 *
	 */

	public function get( $offset ){
		if ( !is_numeric( $offset ) ) {
			//throw an error
			throw new Exception( 'offset is non-numeric' );
		}
		if ( $offset >= count( $this->url_parts ) ) {
			//throw an error.
			throw new Exception( 'Offset (' . $offset . ') outside of range (' . count( $this->url_parts ) . ') for urlhandler' );
		}
		return $this->url_parts[ $offset ];
	}
	
	/**
	 * counts the number of parts.
	 */

	public function count_parts(){
		return count( $this->url_parts );
	}

	/**
	 * public function set_data
	 *
	 * Sets this specific data
	 *
	 * @param	$name		Self explanatory
	 * @param 	$value		"
	 *
	 */

	public function set_data( $name, $value ){
		$this->data[ $name ] = $value;

	}
	
	/**
	 * public function get_base_url
	 *
	 * gets the base url with which this class was called.
	 *
	 * @return The base url with which this class was called.
	 *
	 *
	 */

	public function get_base_url(){
		return $this->base_url;
	}
}

?>
