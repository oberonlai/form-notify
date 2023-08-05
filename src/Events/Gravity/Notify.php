<?php

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
	 * @param array  $data Form data.
	 *
	 * @return string $content Message content.
	 */
	public function replace_message_content( $content, $data ) {
		$content = preg_replace( '/({{\D+)/', '{{', $content );
		if ( $data ) {
			foreach ( $data as $key => $value ) {

				// multi select field.
				if ( strpos( $value, '["' ) !== false ) {
					$arr     = implode( ',', json_decode( $value ) );
					$content = str_replace( $key, $arr, $content );
				}

				// list field.
				if ( strpos( $value, 'a:' ) !== false ) {
					$arr     = implode( ',', unserialize( $value ) );
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
