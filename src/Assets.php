<?php

namespace FORMNOTIFY;

defined( 'ABSPATH' ) || exit;


class Assets {
	public static function register() {
		$class = new self();
		add_action( 'admin_enqueue_scripts', array( $class, 'enqueue_admin_script' ) );
	}
	public function __construct() {

	}
	/**
	 * SelectWoo
	 */
	public function enqueue_admin_script() {
		wp_enqueue_script( 'wc-enhanced-select' );
	}

}

Assets::register();
