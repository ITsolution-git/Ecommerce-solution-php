<?php
/**
 * Curl class
 */

class curl {
	/**
	 * Creates a continuous instance of curl
	 */
	public function __construct() {
		$this->ch = curl_init();
	}
	
	/**
	 * Executes a curl statement and returns the page that is returned
	 *
	 * @access public
	 *
	 * @param string $url the url of the page being called
	 * @return str/bool
	 */
	public static function get( $url ) {
		// Whether we should close
		$close = true;
		
		if ( isset( $this ) ) {
			$ch = &$this->ch;
			$close = false;
		} else {
			$ch = curl_init();
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_COOKIE, 1 );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, COOKIE_PATH );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, COOKIE_PATH );
		$page = curl_exec( $ch );
		
		if ( $close )
			curl_close( $ch );
		
		return $page;
	}
	
	/**
	 * Posts variables to a url and returns the page that is returned
	 *
	 * @access public
	 *
	 * @param string $url the url of the page being called
	 * @param array $post_fields an array of fields to be sent
	 * @return str/bool
	 */
	public static function post( $url, $post_fields ) {
		// Whether we should close
		$close = true;
		
		if ( isset( $this ) ) {
			$ch = &$this->ch;
			$close = false;
		} else {
			$ch = curl_init();
		}
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_COOKIE, 1 );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, COOKIE_PATH );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, COOKIE_PATH );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, ( is_array( $post_fields ) ) ? http_build_query( $post_fields, '', '&' ) : $post_fields );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		$page = curl_exec( $ch );
		
		if ( $close )
			curl_close( $ch );
		
		return $page;
	}
	
	/**
	 * Checks if a file exists
	 *
	 * @param string $url the url of the page being called
     * @param int $timeout
	 * @return bool
	 */
	public static function check_file( $url, $timeout = 60 ) {
		// Whether we should close
		$close = true;
		
		if ( isset( $this ) ) {
			$ch = &$this->ch;
			$close = false;
		} else {
			$ch = curl_init();
		}
		
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		
		// Don't download content
		curl_setopt( $ch, CURLOPT_NOBODY, 1 );
		curl_setopt( $ch, CURLOPT_FAILONERROR, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $timeout );
        curl_setopt($ch, CURLOPT_TIMEOUT , $timeout );

        $result = ( false !== curl_exec( $ch ) ) ? true : false;
		
		if ( $close )
			curl_close( $ch );
		
		return $result;
	}
	
	/**
	 * Saves a file
	 *
	 * @param string $url the url of the page being called
	 * @param resource $fp file pointer
	 * @return bool
	 */
	public static function save_file( $url, $fp ) {
		if ( isset( $this ) ) {
			$ch = &$this->ch;
			$close = false;
		} else {
			$ch = curl_init();
		}
		
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, TRUE );
		curl_exec( $ch );
		
		if ( $close )
			curl_close( $ch );
		
		return true;
	}

    /**
   	 * Returns a range of data from a file
     *
     * @param string $url
   	 * @return string
   	 */
   	public static function ranger( $url ){
   		// Whether we should close
   		$close = true;

   		if ( isset( $this ) ) {
   			$ch = &$this->ch;
   			$close = false;
   		} else {
   			$ch = curl_init();
   		}

           $headers = array(
   			"Range: bytes=0-32768"
           );

           $ch = curl_init( $url );
           curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
           curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
           $data = curl_exec( $ch );

   		if ( $close )
   			curl_close( $ch );

           return $data;
       }
	
	/**
	 * Creates a continuous instance of curl
	 */
	public function __destruct() {
		curl_close( $this->ch );
	}
}