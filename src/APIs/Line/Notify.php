<?php
/**
 * LINE Notify API
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Line;

defined( 'ABSPATH' ) || exit;

/**
 * LINE Notify API class
 */
class Notify {

	/**
	 * LINE Notify api channel access token
	 *
	 * @var string
	 */
	private string $token;
	/**
	 * LINE Notify api url
	 *
	 * @var string
	 */
	private string $endpoint;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->token    = ( get_option( 'form_notify_line_notify_token' ) ) ? get_option( 'form_notify_line_notify_token' ) : '';
		$this->endpoint = 'https://notify-api.line.me/api/notify';
	}

	/**
	 * Remote api request
	 *
	 * @param string $messages LINE message text.
	 *
	 * @return object $resp LINE api response.
	 */
	private function request( string $messages ): object {
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . $this->token,
			),
			'body'    => array( 'message' => $messages ),
		);

		$response = wp_remote_request( $this->endpoint, $options );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			return (object) array( 'message' => $error_message );
		} else {
			$resp = json_decode( wp_remote_retrieve_body( $response ) );
		}

		return $resp;
	}

	/**
	 * Execute push api
	 *
	 * @param string $messages LINE message object.
	 *
	 * @return string $result request result.
	 */
	public function push( string $messages ): string {
		if ( ! $this->token ) {
			return __( 'Please set LINE Notify access token first.', 'form-notify' );
		}
		$result = $this->request( $messages );
		if ( is_object( $result ) && count( (array) $result ) > 0 ) {
			return $result->message;
		}

		return __( 'Success', 'form-notify' );
	}
}
