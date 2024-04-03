<?php
/**
 * Gravity form Notify class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Gravity;

defined( 'ABSPATH' ) || exit;

/**
 * Notify class
 */
class Notify extends \FORMNOTIFY\Events\AbstractNotify {

	/**
	 * Replace message content to form data.
	 *
	 * @param string $content Message content.
	 * @param array  $data    Form data.
	 *
	 * @return string $content Message content.
	 */
	public function replace_message_content( string $content, array $data ): string {
		$content = preg_replace( '/({{\D+)/', '{{', $content );
		if ( $data ) {
			foreach ( $data as $key => $value ) {

				// multi select field.
				if ( str_contains( $value, '["' ) ) {
					$arr     = implode( ',', json_decode( $value ) );
					$content = str_replace( $key, $arr, $content );
				}

				// list field.
				if ( str_contains( $value, 'a:' ) ) {
					$arr     = implode( ',', unserialize( $value ) ); // phpcs:ignore
					$content = str_replace( $key, $arr, $content );
				}

				if ( $value ) {
					$content = str_replace( $key, $value, $content );
				} else {
					$content = str_replace( $key, '-', $content );
				}
			}
		}

		return $content;
	}
}
