<?php
/**
 * EasyGo SMS API
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Sms;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;
use WP_REST_Request;
use WP_REST_Response;

/**
 * EasyGo class
 */
class Easygo {
	/**
	 * SMS host
	 *
	 * @var string $sms_host
	 */
	private string $sms_host;
	/**
	 * Send SMS URL
	 *
	 * @var string $send_sms_url
	 */
	private string $send_sms_url;
	/**
	 * Get credit URL
	 *
	 * @var string $get_credit_url
	 */
	private string $get_credit_url;
	/**
	 * Credit
	 *
	 * @var int $credit
	 */
	private int $credit;
	/**
	 * Process message
	 *
	 * @var string $process_msg
	 */
	private string $process_msg;
	/**
	 * EasyGo Username
	 *
	 * @var string $username
	 */
	private string $username;
	/**
	 * EasyGo Password
	 *
	 * @var string $password
	 */
	private string $password;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->username       = get_option( 'form_notify_easygo_username' );
		$this->password       = get_option( 'form_notify_easygo_password' );
		$this->sms_host       = 'https://www.easysms.com.tw/easygohttpapi/httpapiservice.asmx';
		$this->send_sms_url   = $this->sms_host . '/CallSendSMS';
		$this->get_credit_url = $this->sms_host . '/CallQueryPoints';
		$this->credit         = 0;
		$this->process_msg    = '';
		add_action( 'rest_api_init', array( $this, 'register_api_route' ) );
		add_action( 'form_notify_action_module_type_events', array( $this, 'send_notify_events' ), 10, 5 );
	}

	/**
	 * SMS 簡訊傳送
	 *
	 * @param string $phone   手機號碼.
	 * @param string $message 簡訊內容.
	 *
	 * @return string
	 */
	public function send_sms( string $phone, string $message ): string {
		$body = array(
			'UserName' => $this->username,
			'PassWord' => $this->password,
			'Cellno'   => $phone,
			'MsgBody'  => $message,
			'Mode'     => 'Immediate',
		);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => http_build_query( $body ),
		);

		$response = wp_remote_request( $this->send_sms_url, $options );
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		} else {
			$body = wp_remote_retrieve_body( $response );
			$xml  = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );
			// @codingStandardsIgnoreStart
			if ( $xml->ErrorMsg ) {
				return (string) $xml->ErrorMsg;
			} else {
				return __( 'Success', 'form-notify' );
			}
			// @codingStandardsIgnoreENd
		}
	}

	/**
	 * 餘額查詢
	 */
	public function get_points() {
		if ( ! $this->username || ! $this->password ) {
			return __( 'Enter the username and password first', 'form-notify' );
		}
		$body    = array(
			'username' => $this->username,
			'password' => $this->password,
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => http_build_query( $body ),
		);

		$response = wp_remote_request( $this->get_credit_url, $options );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
		} else {
			$body = wp_remote_retrieve_body( $response );
			$xml  = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );

			return (string) $xml->ReturnDouble;
		}
	}

	/**
	 * Register api route
	 *
	 * @return void
	 */
	public function register_api_route(): void {
		register_rest_route(
			'easygo/v1',
			'/get-points',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_points_api_body' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}

	/**
	 * Get points api body
	 *
	 * @param WP_REST_Request $request request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_points_api_body( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->get_points() );
	}

	/**
	 * Send notify for events
	 *
	 * @param string $type     message type.
	 * @param string $receiver message receiver.
	 * @param string $message  message content.
	 */
	public function send_notify_events( string $type, string $receiver, string $message, $history_via ): void {
		if ( 'easygo' === $type ) {
			if ( $this->get_points() > 0 ) {
				$result     = $this->send_sms( $receiver, $message );
				$type       = __( 'easyGo SMS', 'form-notify' );
				$history_id = History::insert( 0, $receiver . ' - ' . $history_via, 0, $type, $message, $result );
			} else {
				History::insert( 0, $receiver . ' - ' . $history_via, 0, __( 'easyGo SMS', 'form-notify' ), $message, __( 'Form Notify easyGo Sending Result: Points are not enough.', 'form-notify' ) );
			}
		}
	}
}

new Easygo();
