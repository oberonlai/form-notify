<?php
/**
 * LINE Login Button option
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

/**
 * LineButton class
 */
class LineButton {

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
				'id'    => 'line_btn_wp_form_title',
				'label' => __( 'WordPress Login / Register Form Settings', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);
		$config->addSelect(
			$tab_id,
			array(
				'id'           => 'line_btn_wp_form_display',
				'label'        => __( 'Display in WordPress login form', 'form-notify' ),
				'desc'         => __( 'Show LINE Login button in WordPress admin login / register form.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'size'         => 'regular',
				'default'      => 'no',
				'options'      => array(
					'yes' => __( 'Yes', 'form-notify' ),
					'no'  => __( 'No', 'form-notify' ),
				),
			),
		);

		$config->addHtml(
			$tab_id,
			array(
				'id'    => 'line_btn_other_title',
				'label' => __( 'Other Settings', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);

		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_btn_redirect',
				'label'        => __( 'Redirect Page URL', 'form-notify' ),
				'desc'         => __( 'The redirect page after LINE Login.', 'form-notify' ),
				'placeholder'  => 'https://example.com/login-success',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);

		$config->addSelect(
			$tab_id,
			array(
				'id'           => 'line_btn_user_role',
				'label'        => __( 'User Role', 'form-notify' ),
				'desc'         => __( 'The user role after LINE Login.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
				'default'      => 'customer',
				'options'      => self::get_roles(),
			),
		);

		$config->addSelect(
			$tab_id,
			array(
				'id'           => 'line_btn_user_email',
				'label'        => __( 'User Email Permission', 'form-notify' ),
				'desc'         => __( 'The solution when users do not allow email permission.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
				'default'      => 'popup',
				'options'      => array(
					'popup' => __( 'Force enter email address', 'form-notify' ),
					'auto'  => __( 'Generate fake email with LINE user id', 'form-notify' ),
				),
			),
		);

	}

	/**
	 * Get user roles
	 *
	 * @return array
	 */
	public static function get_roles(): array {
		global $wp_roles;
		$roles     = array();
		$all_roles = $wp_roles->roles;
		foreach ( $all_roles as $key => $value ) {
			$roles[ $key ] = translate_user_role( $value['name'] );
		}

		return $roles;
	}
}
