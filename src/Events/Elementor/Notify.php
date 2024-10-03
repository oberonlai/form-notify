<?php
/**
 * Elementor Notify class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Elementor;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Events\AbstractNotify;

/**
 * Notify class
 */
class Notify extends AbstractNotify {

	/**
	 * Replace message content to form data.
	 *
	 * @param string $content Message content.
	 * @param array  $data    Form data.
	 *
	 * @return string $content Message content.
	 */
	public function replace_message_content( string $content, array $data ): string {
		if ( $data ) {
			foreach ( $data as $key => $value ) {
				$content = str_replace( '{{' . $key . '}}', $value, $content );
			}
		}

		return $content;
	}
}
