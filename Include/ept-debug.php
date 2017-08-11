<?php

function EPT_DEBUG( $str ) {
	global $ept_debug;
	$ept_enable_log = get_option('ept_enable_log');
	if($ept_enable_log)
	{
		$ept_debug->enable(true);
	}
        
	$ept_debug->add_to_log( $str );
}

function ept_is_debug_enabled() {
	global $ept_debug;
	
	return $ept_debug->is_enabled();
}

class eptDebug {
	var $debug_file;
	var $log_messages;

	function eptDebug() {
		$this->debug_file = false;
	}
	
	function is_enabled() {
		return ( $this->debug_file );	
	}

	function enable( $enable_or_disable ) {
		if ( $enable_or_disable ) {
			$this->debug_file = fopen( WP_CONTENT_DIR . '/plugins/evergreen-post-tweeter/log.txt', 'a+t' );
			$this->log_messages = 0;
		} else if ( $this->debug_file ) {
			fclose( $this->debug_file );
			$this->debug_file = false;		
		}
	}

	function add_to_log( $str ) {
		if ( $this->debug_file ) {
			
			$log_string = $str;
			$log_string .='The last tweet in : '.date("F j, Y, g:i a");
			// Write the data to the log file
			fwrite( $this->debug_file, sprintf( "%12s %s\n", time(), $log_string ) );
			fflush( $this->debug_file );
			
			$this->log_messages++;
		}
	}
}

global $ept_debug;
$ept_debug = &new eptDebug;


?>
