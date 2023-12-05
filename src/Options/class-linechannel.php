<?php
/**
 * LINE Channel Options
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

/**
 * LineChannel class
 */
class LineChannel {
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
				'id'    => 'line_login_channel_title',
				'label' => __( 'LINE Login Setting - <a target="_blank" class="t:none:hover" href="https://oberonlai.blog/line-login-setting/">Tutorial for LINE Login</a>', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);
		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_login_channel_id',
				'label'        => __( 'Channel ID for Login', 'form-notify' ),
				'desc'         => __( 'LINE Login Channel ID.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);

		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_login_channel_secret',
				'label'        => __( 'Channel Secret for Login', 'form-notify' ),
				'desc'         => __( 'LINE Login Channel secret.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);

		$config->addHtml(
			$tab_id,
			array(
				'id'           => 'line_login_callback_url',
				'label'        => __( 'Callback URL for Login', 'form-notify' ),
				'desc'         => '<p class="w:325 bg:fade-86 bg:fade-82:hover cursor:pointer rel btn-copy p:8|12 d:inline-block r:5" data-clipboard-text="' . get_rest_url() . 'form-notify/v1/callback">' . get_rest_url() . 'form-notify/v1/callback<span class="opacity:0 pointer-events:none abs bottom:-20 bg:#2271b1 f:white p:3|5 f:12 r:2 left:50% translate(-50%,0) z:10 white-space:nowrap">' . __( 'copied!', 'form-notify' ) . '<i class="abs bottom:22 left:50% translate(-50%,0) w:0 h:0 border-style:solid; border-width:0|8px|10px|8px border-color:transparent|transparent|#2271b1|transparent z:5"></i></span></p><p>' . __( 'Copy the callback url to paste in LINE developer console.', 'form-notify' ) . '</p>',
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);

		$config->addHtml(
			$tab_id,
			array(
				'id'           => 'line_login_permission',
				'label'        => __( 'Email Address Permission', 'form-notify' ),
				'desc'         => __( 'You have to access the email permission for LINE Login.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => false,
				'class'        => 'bb:1px|solid|#ddd',
				'size'         => 'regular',
			),
		);

		$config->addHtml(
			$tab_id,
			array(
				'id'    => 'line_message_token_title',
				'label' => __( 'LINE Messaging API Setting - <a target="_blank" class="t:none:hover" href="https://oberonlai.blog/line-messaging-api-setting/">Tutorial for LINE Messaging API</a>', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);

		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_message_token',
				'label'        => __( 'Channel access token', 'form-notify' ),
				'desc'         => __( 'LINE Messaging API access token.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);

		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_test_user_id',
				'label'        => __( 'Testing User ID', 'form-notify' ),
				'desc'         => __( 'The User ID of Messaging API for testing.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);
	}
}
