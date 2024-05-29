<?php
/**
 * Fluent form Notify class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Fluent;

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
	public function replace_message_content( $content, $data ) {
		if ( $data ) {
			$replace = array();
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( str_contains( $key, 'fcal_booking' ) ) {
						foreach ( $value as $k => $v ) {
							$replace[ '{{' . $k . '}}' ] = $v;
						}
					} else {
						$value = ( 'checkbox' === $key || 'multi_select' === $key ) ? implode( '、', $value ) : implode( '', $value );
					}
				}
				if ( 'fcal_booking' === $key ) {
					continue;
				}
				$replace[ '{{' . $key . '}}' ] = $value;
			}

			foreach ( $replace as $key => $value ) {
				if ( ! empty( $value ) ) {
					$content = str_replace( $key, $value, $content );
				}
			}
			$content = preg_replace( '/\{\{.*?\}\}/', '', $content );
		}
		return $content;
	}
}
