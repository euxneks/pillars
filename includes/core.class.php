<?php
/**
 * class Core
 *
 * The is the core functionality of pillars. It will take information from the urlhandler class and determine if we have a function. Feel free to muck
 * about with this class.
 *
 */
class Core {
	
	private $templater;
	private $urlhandler;
	private $authentication;
	private $database;
	private $html;

	/**
	 * public function __construct
	 *
	 * Constructor class, get all information about the current URL, using the urlhandler class.  It will then check for proper authentication
	 * credentials and then figure out if we have a function defined in our extention class for the current URL - if so, call it and render 
	 * it using the appropriate template.
	 *
	 * @param urlhandler $urlhandy 	Instance of urlhandler.
	 *
	 * @see urlhandler.class.php
	 *
	 *
	 */
	public function __construct( &$urlhandy ){
		global $config;
		//default is to render HTML - this means we use header and footer templates.
		$this->html = true;
		$this->urlhandler = $urlhandy;
		//init a new DB
		$this->database = new pillarsDB();
		//check for authentication
		$this->authentication = new Authentication( AUTH_MARIADB );
		$this->authentication->authenticate( $this->database, $this->urlhandler );
		//if we are at a URL that is not considered the main start page
		if ( $this->urlhandler->count_parts() > 0 ){
			//is a template file defined? if so, start the templating function.
			if( file_exists( $config[ 'template_dir' ] . $this->urlhandler->get( 0 ) . '.xhtml') ) {
				$this->initiate_template( $this->urlhandler->get( 0 ) );
			}else {
				//perhaps this exists as a function in the extension class? i.e. uploading data or something?
				$function_name = 'single_' . $this->urlhandler->get(0);
				//woa! dynamic loading of the class file defined in our configuration!
				if ( is_array( $config[ 'extension' ] ) ) {
					//not implemented yet - still trying to figure out the best way to do so
					throw new Exception( 'Multiple extensions not implemented yet!' );
				} else {
					require_once( 'extensions/' . $config[ 'extension' ] . '.core.extension.php' );
				}
				$class = ucfirst( $config[ 'extension' ] );
				$class = new $class( $this->authentication, $this->database, $this->urlhandler );

				if ( method_exists( $class, $function_name ) ) {
					//method exists! show it!
					$this->templater = $class->$function_name();
					$this->html = false;
				} else {
					//Ain't nuthin in here but us chikkens... (Show index or throw an exception.)
					if ( $this->urlhandler->get(0) == '' ){
						$this->initiate_template( 'index' );
					} else {
						throw new Exception( 'There is no resulting handler for the url part: ' . htmlspecialchars( $this->urlhandler->get(0) ) );
					}
				}
			}
		} else {
			//just show the special "main index" template.
			$this->initiate_template( 'index' );
		}
	}
	/**
	 * private function initiate_template
	 *
	 * Initiate a template for a specific string, using the templater class.
	 *
	 * @param string $template_name
	 *
	 */
	private function initiate_template( $template_name ){
		global $config;
		$this->templater = new Template( $config[ 'template_dir' ] . $template_name . '.xhtml' );
		$values_to_get = $this->templater->get_special_values( 'pillars', ',' );
		if ( is_array( $config[ 'extension' ] ) ) {
			//not implemented yet - still trying to figure out the best way to do so
			throw new Exception( 'Multiple extensions not implemented yet!' );
		} else {
			require_once( 'extensions/' . $config[ 'extension' ] . '.core.extension.php' );
		}
		$theme_class = ucfirst( $config[ 'extension' ] );
		$extension = new $theme_class( $this->authentication, $this->database, $this->urlhandler );
		if ( !empty( $values_to_get ) ) {
			foreach ( $values_to_get as $value ) {
				if ( empty( $value ) ) {
					//ignore the empty value, it's a silly thing
					//shouldn't be getting these anyway, but something could have buggered up - might as well cover all bases.
					continue;
				}
				//find functions in our extension class, and get the result from calling them with a database and urlhandler parameter.
				$function_name = 'get_' . $value;
				if ( method_exists( $extension, $function_name ) ) {
					$this->templater->set( $value, $extension->$function_name() );
				} else if ( method_exists( $this, $function_name ) ) {
					//if the function name is a standard function which is included in the core.
					$this->templater->set( $value, $this->$function_name() );
				} else {
					//don't know what the heck y'all are looking for.
					throw new Exception( __CLASS__ . '::' . __FUNCTION__ .':No function by the name of ' . $function_name );
				}
			}
		}
		$this->templater->set_from_array( $config[ 'template_defaults' ] );
	}

	/**
	 * public function display
	 *
	 * Displays the rendered template, using some default values as defined in a configuration array.
	 *
	 */
	public function display(){
		global $config;
		//outputting as HTML? show a common header and footer defined in the private directory.
		if ( $this->html ) {
			$header = new Template(  $config[ 'template_dir' ] . 'private/header.xhtml'  );
			$header->set_from_array( $config[ 'template_defaults' ] );
			$header->display();
			$theme_class = ucfirst( $config[ 'extension' ] );
			$extension = new $theme_class( $this->authentication, $this->database, $this->urlhandler );
			$this->templater->display();
			$footer = new Template(  $config[ 'template_dir' ] . 'private/footer.xhtml'  );
			$footer->set_from_array( $config[ 'template_defaults' ] );
			$footer->display();
		} else {
			//else just show the results from whatever function we called. In this case, $this->templater is *not* a templater class instance.
			//TODO: don't use templater for non templater instance objects/values.
			header('content-type: application/json; charset=UTF-8');
			echo $this->templater;
		}
	}

	/**
	 * public static function obfuscate_email
	 *
	 * Obfuscate an email address so it's still usable by people but not usable by bots - computing power required for bots to parse out javascript makes it's prohibitive - and a PITA.
	 *
	 * @param string $email The email to obfuscate
	 * @param boolean $mailto toggle to switch between just displaying an email and showing a link to click on for emailing.
	 *
	 * @returns An HTML string containing script elements.
	 *
	 */
	public static function obfuscate_email( $email, $mailto = '' ){
		//randomly insert some underscores
		$email = str_replace( '_', '$', $email );
		$email = preg_replace( '//', '!', $email );
		$email = explode( '!', $email );
		for ( $i = 0; $i <= 4; $i++ ) {
			$email = array_chunk( $email, rand( 1, count( $email ) ) );
			$val = array();
			foreach ( $email as $elem ) {
				$elem[] = '_';
				$val = array_merge( $val, $elem );
			}
			$email = $val;
		}
		$email = implode( $email );
		$email = str_replace( array( '@', '.' ), array( '&#x40;', '&#46;'), $email );
		$ret = '';
		if ( empty( $mailto ) ) {
			$ret .= '<script type="text/javascript"> ';
			$ret .= "\n /* <![CDATA[  */ \n";
			$ret .= "document.write( '$email'.replace(/_/g, '').replace(/\\$/g, '_') );\n";
			$ret .= " /* ]]> */\n </script> \n";
		} else if ( !empty( $mailto ) ) {
			$email = 'mailto:' . $email;
			$ret .= '<script type="text/javascript"> ';
			$ret .= " /* <![CDATA[  */ \n";
			$ret .= "document.write( '<a href=\"' );\n";
			$ret .= "document.write( '$email'.replace(/_/g, '').replace(/\\$/g, '_') );\n";
			$ret .= "document.write( '\">$mailto</a>' );\n";
			$ret .= " /* ]]> */ \n</script> \n";
		}
		return $ret;
	}

	/**
	 * public static function make_slug
	 *
	 * Given a string of values, replace all non-alphanumeric values with dashes, this is known as a "slug". Commonly used for URLs.
	 *
	 * @param string $title  The original title from which to make a "slug"
	 * @returns A string "slug"
	 */
	public static function make_slug( $title ){
		$str = mb_strtolower(trim($title));
		$str = preg_replace('/[^a-z0-9-]/', '-', $str);
		$str = preg_replace('/-+/', "-", $str);
		return $str;
		
	}

}
