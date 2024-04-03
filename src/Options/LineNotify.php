<?php
/**
 * LINE Notify Options
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

/**
 * LineNotify class
 */
class LineNotify {
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
				'id'    => 'line_notify_title',
				'label' => __( 'LINE Notify Setting - <a target="_blank" class="t:none:hover" href="https://oberonlai.blog/line-notify-setting/">Tutorial for LINE Notify</a>', 'form-notify' ),
				'class' => 'html-section-title',
			),
		);

		$config->addText(
			$tab_id,
			array(
				'id'           => 'line_notify_token',
				'label'        => __( 'Access token', 'form-notify' ),
				'desc'         => __( 'LINE Notify access token.', 'form-notify' ),
				'placeholder'  => '',
				'show_in_rest' => true,
				'class'        => '',
				'size'         => 'regular',
			),
		);
	}
}
