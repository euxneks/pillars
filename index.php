<?php
session_set_cookie_params( 0 ); //until browser is closed.
session_start();
require_once( 'includes/configuration.php' );
require_once( 'includes/authentication.class.php' );
global $config;
try{

	$urlhandler = new UrlHandler( $config[ 'BASE_URL' ] );
	if ( !$urlhandler->is_secure() && $config[ 'require_https' ]) {
		throw new Exception( 'You are accessing this site over an unsecure network, please go to: <a href="https://' . $_SERVER[ 'SERVER_NAME' ] . $config[ 'BASE_URL' ] . '>Secure</a>' );
	}
	//handle authentication in core
	$core = new Core( $urlhandler );
	$core->display();
	//let's add some statistics usage in here maybe?
	//TODO:add usage statistics tracking
}
catch ( Exception $e ) {
	//this will show a standard error page when a Exception occurs.
	$data = $urlhandler->get_data();
	if ( !$data  ){
		//no interesting request was sent.
		$template = new Template( $config[ 'template_dir' ]  . 'private/header.xhtml' );
		$template->set_from_array( $config['template_defaults'] );
		$template->display();
		
		if ( $e->getCode() == 1 ) {
			//show an authentication dialog
			$template = new Template( $config[ 'template_dir' ] . 'private/login.xhtml' );
		} else if ( $e->getCode() == 2 ){
			$template = new Template( $config[ 'template_dir' ] . 'private/message.xhtml' );
		} else {
			$template = new Template( $config[ 'template_dir' ] . 'private/error.xhtml' );
		}
		$template->set( 'message', $e->getMessage() );
		$template->set_from_array( $config['template_defaults'] );
		$template->display();
		
		$template = new Template( $config[ 'template_dir' ]  . 'private/footer.xhtml' );
		$template->set_from_array( $config['template_defaults'] );
		$template->display();
	} else {
		echo json_encode( array( 'result' => 'failure', 'message'=>$e->getMessage() ) );
		exit;
	}

}


