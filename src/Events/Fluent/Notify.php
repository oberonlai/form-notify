<?php

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
	 * @param array  $data Form data.
	 *
	 * @return string $content Message content.
	 */
	public function replace_message_content( $content, $data ) {
		if ( $data ) {
			$replace = array();
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = ( 'checkbox' === $key || 'multi_select' === $key ) ? implode( '、', $value ) : implode( '', $value );
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
