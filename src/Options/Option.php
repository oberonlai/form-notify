<?php
/**
 * Option page wrapper
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

use ODS\Option as OdsOption;

/**
 * Option class
 */
class Option {
	/**
	 * Register option page
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'init', array( $class, 'add_option_menu' ) );
	}

	/**
	 * Add option menu
	 *
	 * @return void
	 */
	public function add_option_menu(): void {
		$config = new OdsOption( 'form_notify_' );
		$config->addMenu(
			array(
				'page_title' => __( 'Notify Settings', 'form-notify' ),
				'menu_title' => __( 'Settings', 'form-notify' ),
				'capability' => 'manage_options',
				'slug'       => 'form-notify-setting',
				'icon'       => 'dashicons-performance',
				'position'   => 10,
				'submenu'    => true,
				'parent'     => 'edit.php?post_type=form-notify',
			)
		);
		$config->addTab(
			array(
				array(
					'id'    => 'line_notify',
					'title' => __( 'LINE Notify', 'form-notify' ),
					'desc'  => __( 'LINE Notify setting', 'form-notify' ),
				),
				array(
					'id'    => 'line_channel',
					'title' => __( 'LINE Channel', 'form-notify' ),
					'desc'  => __( 'LINE Channel setting for Login and Messaging API', 'form-notify' ),
				),
				array(
					'id'    => 'line_login_button',
					'title' => __( 'LINE Login button', 'form-notify' ),
					'desc'  => __( 'Set LINE Login button position and appearence.', 'form-notify' ),
				),
				array(
					'id'    => 'e8d_setting',
					'title' => __( 'Every8d', 'form-notify' ),
				),
				array(
					'id'    => 'mitake_setting',
					'title' => __( 'Mitake', 'form-notify' ),
				),
				array(
					'id'    => 'easygo_setting',
					'title' => __( 'easyGo', 'form-notify' ),
				),
			)
		);

		LineNotify::add_field( $config, 'line_notify' );
		LineChannel::add_field( $config, 'line_channel' );
		LineButton::add_field( $config, 'line_login_button' );
		E8d::add_field( $config, 'e8d_setting' );
		Mitake::add_field( $config, 'mitake_setting' );
		Easygo::add_field( $config, 'easygo_setting' );

		$config->register();
	}
}

Option::register();
