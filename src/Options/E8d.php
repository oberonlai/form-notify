<?php
/**
 * Every8d SMS Settings
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

/**
 * E8d class
 */
class E8d {

	/**
	 * Add field to option page
	 *
	 * @param object $config option config.
	 * @param string $tab_id option tab id.
	 *
	 * @return void
	 */
	public static function add_field( object $config, string $tab_id ): void {

		$config->addHtml(
			$tab_id,
			array(
				'id'    => 'e8d_setting_titles',
				'label' => __( 'Every8d SMS Settings', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);
		$config->addRadio(
			$tab_id,
			array(
				'id'           => 'e8d_enable',
				'label'        => __( 'Enable', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => 'd:flex>td>fieldset gap:5!>td>fieldset',
				'size'         => 'regular',
				'default'      => 'no',
				'options'      => array(
					'yes' => __( 'Yes', 'form-notify' ),
					'no'  => __( 'No', 'form-notify' ),
				),
			),
		);
		$config->addText(
			$tab_id,
			array(
				'id'           => 'e8d_user_id',
				'label'        => __( 'User Id', 'form-notify' ),
				'desc'         => __( 'The login user id of every8d', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => false,
				'class'        => '',
				'size'         => 'regular',
			),
		);
		$config->addText(
			$tab_id,
			array(
				'id'           => 'e8d_password',
				'label'        => __( 'Password', 'form-notify' ),
				'desc'         => __( 'The password of every8d', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => false,
				'class'        => 'input-password',
				'size'         => 'regular',
			),
		);
		$config->addText(
			$tab_id,
			array(
				'id'           => 'e8d_token',
				'label'        => __( 'Token', 'form-notify' ),
				'desc'         => __( 'The access token of every8d. It generates automatically.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => false,
				'class'        => 'readonly',
				'size'         => 'regular',
			),
		);
		if ( get_option( 'form_notify_e8d_user_id' ) && get_option( 'form_notify_e8d_password' ) ) {
			$config->addHtml(
				$tab_id,
				array(
					'id'    => 'e8d_points',
					'label' => __( 'Current Points', 'form-notify' ),
					'desc'  => '<div class="flex align-items:center" x-data="{points:\'' . __( 'Check Points', 'form-notify' ) . '\'}"><span class="cursor:pointer button button-primary" x-text="points" @click.prevent="points=\'' . __( 'Loading...', 'form-notify' ) . '\';fetch(`' . home_url() . '/wp-json/e8d/v1/get-points`).then(response => response.json()).then((data) => { points = data })">' . __( 'Check Points', 'form-notify' ) . '</span></div><p class="description">' . __( 'Make sure you have enough points to send SMS.', 'form-notify' ) . '</p>',
					'class' => '',
				),
			);
		}
	}
}
