<?php
/**
 * Assets
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY;

defined( 'ABSPATH' ) || exit;

/**
 * Assets class
 */
class Assets {
	/**
	 * Register
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'admin_enqueue_scripts', array( $class, 'enqueue_admin_script' ) );
	}

	/**
	 * SelectWoo
	 */
	public function enqueue_admin_script(): void {
		wp_enqueue_script( 'wc-enhanced-select' );
	}

}

Assets::register();
