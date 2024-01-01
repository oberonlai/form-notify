<?php
/**
 * LINE Messaging API
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Line;

defined( 'ABSPATH' ) || exit;

/**
 * LINE Messaging API class
 */
class Message {

	/**
	 * LINE messaging api channel access token
	 *
	 * @var string
	 */
	private string $token;
	/**
	 * LINE messaging api url
	 *
	 * @var string
	 */
	private string $endpoint;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->token    = ( get_option( 'form_notify_line_message_token' ) ) ? get_option( 'form_notify_line_message_token' ) : 'xrTVdDn+qvmS/vl1wicjOt9zsonq1fquP78yb/EOAlXIR+BwmxQd11a5kJLPN3vE4eN0KYgbXook7qAreUVWm9JFBSulgU1UKpvvQgaHNjqMYoHSi1UCVvWzGWGkXSbAIl/o2M+mlibm9xpW4nW32AdB04t89/1O/w1cDnyilFU=';
		$this->endpoint = 'https://api.line.me/v2/bot/message/push';
	}

	/**
	 * Remote api request
	 *
	 * @param string $to       LINE user id.
	 * @param object $messages LINE message object.
	 *
	 * @return object $resp request result.
	 */
	private function request( string $to, object $messages ): object {
		$body = array(
			'to'       => $to,
			'messages' => array(
				array(
					'type' => 'text',
					'text' => $messages,
				),
			),
		);

		$body    = wp_json_encode( $body );
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->token,
			),
			'body'    => $body,
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
	 * @param string $uid      LINE user id.
	 * @param object $messages LINE message object.
	 *
	 * @return string $result request result.
	 */
	public function push( string $uid, object $messages ): string {
		$result = $this->request( $uid, $messages );
		if ( is_object( $result ) && count( (array) $result ) > 0 ) {
			return $result->message;
		}

		return __( 'Success', 'form-notify' );
	}
}
