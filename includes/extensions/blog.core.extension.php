<?php
/*
 * Blog extension for pillars.
 *
 */
class Blog {

	private $authentication;
	private $database;
	private $urlhandler;

	public function Blog( &$auth, &$database, &$urlhandler ){
		$this->authentication = $auth;
		$this->database = $database;
		$this->urlhandler = $urlhandler;
	}

	public function get_admin(){
		global $config;
		//get an admin interface to allow for blog editing, etc.
		$user = $this->authentication->get_user_info();
		if ( empty( $user ) ) {
			throw new Exception( 'You are not an authorized user.', 1 );
		}
		$template = new Template( $config[ 'template_dir' ] . '/private/page.xhtml' );
		$directive = $this->urlhandler->count_parts();
		$valid_actions = array( 'edit', 'create' );
		$version = 1;
		$action = 'create';
		if ( $directive >= 2 ) {
			$action = $this->urlhandler->get(1);
			if ( !in_array( $action, $valid_actions ) ) {
				throw new Exception( 'Action: ' . htmlspecialchars( $action ) . ' not recognized.' );
			}
			if ( $directive >= 3 ) {
				$id = $this->urlhandler->get(2);
				$id = $this->database->escape( $id );
			}
		}
		$template->set( 'action', $action );
		if ( empty( $id ) ) {
			$template->set( 'title', '' );
			$template->set( 'content', '' );
			$template->set( 'uid', '' );
			$template->set( 'id', '' );
			$template->set( 'version', '' );
		}else{
			//get the details of this specific page.
			$this->database->query( 'SELECT p.* FROM `page` p, (SELECT MAX(`version`) as `version` FROM `page` WHERE `id`=\'' . $id . '\') p2 WHERE p.`id` = \'' . $id . '\' AND p.`version` = p2.`version`');
			$page = $this->database->fetch_row();
			$template->set_from_array( $page );
			$template->set( 'uid', $user['uid'] );
		}
		$template->set( 'action', ucfirst( $action ) );
		return $template->render();
	}

	public function get_archive(){
		$this->database->query( '
			SELECT p1.title, p1.id FROM page p1 
			RIGHT JOIN 
				(SELECT MAX(version) as version, id
					FROM page 
					GROUP BY id ) p2 
			ON p2.version = p1.version 
			AND p2.id = p1.id 
			ORDER BY p1.id DESC' );
		$results = $this->database->fetch_all();
		return $results;
	}

	public function get_links(){
		$this->database->query( 'SELECT * FROM link ORDER BY `link_order` ASC, title ASC;' );
		return $this->database->fetch_all();
	}

	public function get_page(){
		//show the latest 5 blurbs by default.
		$limit = 5;
		$id = $this->urlhandler->get(1);
		$this->database->query( 'SELECT * FROM page WHERE id=' . $id . ' LIMIT 1;' );
		$result = $this->database->fetch_row();
		if ( preg_match( '|\<p\>|', $result[ 'content' ]) != 1 ) {
			$result[ 'content' ] = '<p>' . str_replace( "\n", '</p><p>', $result[ 'content' ] ) . '</p>';
		}
		$previous_id = 0;

		$this->database->query( 'SELECT id, title FROM page WHERE id<' . $id . ' ORDER BY id DESC LIMIT 1;' );
		$previous_id = $this->database->fetch_row();
		$result[ 'prev_nav' ] = '';
		if ( !empty( $previous_id ) ) {
			$result[ 'prev_nav' ] = '<a href="{BASE_URL}page/' . $previous_id[ 'id' ] . '">' . $previous_id[ 'title' ] . '</a>';
		}
		$this->database->query( 'SELECT id, title FROM page WHERE id>' . $id . ' ORDER BY id ASC LIMIT 1;' );
		$next_id = $this->database->fetch_row();
		$result[ 'next_nav' ] = '';
		if ( !empty( $next_id ) ) {
			$result[ 'next_nav' ] = '<a href="{BASE_URL}page/' . $next_id[ 'id' ] . '">' . $next_id[ 'title' ] . '</a>';
		}
		return $result;
	}

	public function get_page_nav(){
		$query=' 
				SELECT p1.* 
				FROM page p1 
				RIGHT JOIN 
					(SELECT MAX(version) as version, id FROM page GROUP BY id ) p2 
					ON 
					p2.version = p1.version 
					AND p2.id = p1.id
				ORDER BY p1.id;';
	}

	public function get_pages(){
		//show the latest 5 pages by default.
		$limit = 5;
		$this->database->query( '
			SELECT p1.* FROM page p1 
			RIGHT JOIN 
				(SELECT MAX(version) as version, id
					FROM page 
					GROUP BY id ) p2 
			ON p2.version = p1.version 
			AND p2.id = p1.id 
			ORDER BY p1.id DESC
			LIMIT ' . $limit . ';' );
		$results = $this->database->fetch_all();
		foreach ( $results as &$result ) {
			$result[ 'content' ] = '<p>' . str_replace( "\n", '</p><p>', $result[ 'content' ] ) . '</p>';
		}
		return $results;
	}


}
