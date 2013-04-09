<?php

if( !class_exists( 'expirepasswordpublic') ) {

	class expirepasswordpublic {

		function __construct() {

			//add_action( 'init', array( &$this, 'check_for_oncer' ), 1 );

		}

		function expirepasswordpublic() {
			$this->__construct();
		}



	}

}

$expirepasswordpublic = new expirepasswordpublic();