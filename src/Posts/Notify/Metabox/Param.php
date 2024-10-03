<?php
/**
 * Param Metabox
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Posts\Notify\Metabox;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Metabox\Metabox as FormMetabox;

/**
 * Notify Metabox
 */
class Param {
	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'admin_init', array( $class, 'init' ), 99 );
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {

		$param = new FormMetabox(
			array(
				'id'       => 'form_notify_param',
				'title'    => __( 'Notify Params', 'form-notify' ),
				'screen'   => 'form-notify',
				'context'  => 'side',
				'priority' => 'default',
			)
		);

		do_action( 'form_notify_param', $param );
	}
}

Param::register();
