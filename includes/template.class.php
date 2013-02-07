<?php

/**
 * class Template
 *
 * This is a basic templating class which I use pretty much everywhere. It's lightweight if you use it properly.
 *
 * @author Chris Tooley <euxneks@gmail.com>
 */

class Template {
	private $template;
	private $template_file;
	private $values;

	private $debug;

	/**
	 * Base constructor, supply the template file in your initialization
	 */

	public function __construct( $template_file, $dbg = false ){
		$this->set_template( $template_file );
		$this->debug( $dbg );
	}

	/*
	 * function debug 
	 *
	 * self explanatory 
	 *
	 */

	public function debug( $dbg = true ){
		$this->debug = $dbg;
	}

	/**
	 * public function get_special_values
	 *
	 * Gets values as defined in the template. This is a way for the template to define something for the controller or core class to do.
	 * Example: {pillars:make_me_a_sandwich}" is in the template,
	 *
	 * Calling this function ala get_special_values( 'pillars' );
	 * will return an array with only one element, 'make_me_a_sandwich'
	 * 
	 * @param metaname the name you wish to use for your special values tag
	 * @param separator an optional separator value, defaults to ','
	 *
	 * @return the special values in the current template file.
	 */
	public function get_special_values( $metaname, $separator = ',' ){
		if ( empty( $this->template ) ) {
			throw new Exception( 'template.class.php::empty template or no template defined' );
		}
		//search through for this element and it's corresponding values:
		
		$open_token = preg_quote( '{' . $metaname . ':' );
		$close_token = preg_quote( '}' );
		$pattern = '|' . $open_token . '(.*)' . $close_token . '|msU';
		$matches = array();

		preg_match_all( $pattern, $this->template, $matches );
		if ( empty( $matches[1] ) ) {
			return false;
		} else {
			//anything else we don't care about.
			$results = $matches[ 1 ][ 0 ];
			foreach ( $matches[1] as $foundit ) {
				$tag = '{' . $metaname . ':' . $foundit . '}';
				$this->template = str_replace( array( "$tag\n", $tag ), '', $this->template );
			}
			$results = preg_replace( '/\s+/', '', $results );
			return explode( $separator, $results );
		}
	}

	/*
	 * function set_template 
	 *
	 * sets a new template file to use as the base template - keeps the old values that were set()
	 *
	 * @param $template_file string The new template to use
	 *
	 */
	public function set_template ( $template_file ) {
		$this->template = file_get_contents( $template_file );
		$this->template_file = $template_file;
	}

	/*
	 * function set 
	 * sets a varname to be replaced by a value.
	 *
	 * @param $var_name string The name of the var
	 * @param $value mixed 	The value, can be an associative array or plain string.
	 *
	 */
	public function set( $var_name, $value ){
		$this->values[ $var_name ] = $value;
	}

	/**
	 * function set_from_array 
	 *
	 * sets varnames from key value pairs in an array.
	 *
	 * @param $values array An array of key=>value parameters
	 * 	 
	 */

	public function set_from_array( $values ){
		if ( is_array( $values ) && !empty( $values ) ) {
			foreach ( $values as $key => $value ) {
				$this->set( $key, $value );
			}
		}
	}


	/*
	 * function render
	 * returns the templated file with the template vars replaced.. if you haven't specified the template vars then they will still show up.
	 */

	public function render(){
		//go through each of the values and find/replace their corresponding keys in the template file.
		if ( empty( $this->values ) ){
			//foreach glitches on empty?
			return $this->template;
		}
		foreach ( $this->values as $name => $value ) {
			$this->template = $this->recursively_replace( $name, $value, $this->template );
		}
		$this->template = $this->recursively_replace( 'YEAR', date( 'Y' ), $this->template );
		$this->template = $this->recursively_replace( 'MONTH', date( 'm' ), $this->template );
		$this->template = $this->recursively_replace( 'DAY', date( 'd' ), $this->template );
		//replace all constants we've defined in this template class.
		return $this->template;
	}

	/*
	 * function recursively_replace()
	 * used internally by the class to recursively replace a name with a value in the template.
	 * @param $name string The name of the value
	 * @param $value string The value to be placed into the template.
	 * @param $template string the template string in which we want to put the value.
	 * @return string The template with all $name tokens replaced with $value
	 */
	private function recursively_replace( $name, $value, $template ){
		if($this->debug)echo "<pre> Value:\n";
		if($this->debug)var_dump( $value );
		if ( !is_array( $value ) ) {
			//simply replace
			//base case.
			$value = utf8_encode( $value );
			if($this->debug)echo "base case \n";
			if($this->debug)echo "{$name}=$value";
			$template = str_replace( '{' . $name . '}', $value, $template );
		} else if ( is_array( $value ) ) {
			//this is an array, check for a foreach, and failing that, just name:value keypairs
			if($this->debug)echo "array, checking for a foreach:$name\n";
			
			$open_token = '{foreach:' . $name . '}';
			$close_token = '{/foreach:' . $name . '}';
			$pattern = '|' . preg_quote( $open_token ) . '(.*)' . preg_quote( $close_token ) . '|msU';
			if( $this->debug )echo "$pattern to match";
			$matches = array();
			$has_simple_part = false;
			foreach( $value as $key => $val  ){
				if ( !is_numeric( $key ) ) {
					$has_simple_part = true;
					break;
				}
			}
			if( preg_match_all( $pattern, $template, $matches ) ) {
				//we've got a shortcut - process it.
				//usually going to be something like a row.
				if($this->debug)echo "foreach found, processing..\n";
				if($this->debug)echo count( $matches[0] ) . " matches..\n";
				foreach ( $matches[ 1 ] as $key => $match ) {
					$tpl = '';
					foreach ( $value as $valname => $val ) {
						if ( $val != 0 && ( empty( $val ) || $val == 'NULL' ) ) {
							continue;
						}
						if ( is_numeric( $valname ) ) {
							$tpl .= $this->recursively_replace( $name, $val, $match );
						} else {
							if ( $tpl == '' ) {
								//haven't done a match yet, but if we do, make sure we keep the replacement.
								$tpl = $match;
							}
							$tpl = $this->recursively_replace( $valname, $val, $tpl );
						}
					}
					if ( $tpl == $match ) {
						//no replacement made, get rid of this tag.
						$tpl = '';
					}
					$template = str_replace( $matches[ 0 ][ $key ], $tpl, $template );
				}
			}
			if( $has_simple_part ) {
				if($this->debug)echo "has simple part\n";
			//array of values, recursively call until we get through the array.
				foreach ( $value as $key => $val ) {
					if ( is_numeric( $key ) ) {
						continue;
					}
					if ( is_array( $val ) ) {
						if($this->debug)echo "array, recurse $name:$key\n";
						$template = $this->recursively_replace( $name . ':' . $key, $val, $template );
					} else {
						if($this->debug)echo "Base: $name:$key = $val\n";
						$template = str_replace( '{' . $name . ':' . $key . '}', utf8_encode( $val ), $template );
					}
				}
			}
		}
		if($this->debug)echo "\nresult: " . htmlspecialchars( $template );
		return $template;
	}

	/*
	 * function display
	 * display only echoes out the rendering of the template.
	 *
	 */
	public function display(){
		echo $this->render();
	}

}


?>
