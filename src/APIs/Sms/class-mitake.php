<?php
/**
 * MITAKE SMS API
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Sms;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;
use WP_REST_Request;
use WP_REST_Response;

/**
 * MITAKE SMS API class
 */
class Mitake {
	/**
	 * SMS API host
	 *
	 * @var string
	 */
	private string $sms_host;
	/**
	 * Send SMS API URL
	 *
	 * @var string
	 */
	private string $send_sms_url;
	/**
	 * Get credit API URL
	 *
	 * @var string
	 */
	private string $get_credit_url;
	/**
	 * Mitake Username
	 *
	 * @var string
	 */
	private string $username;
	/**
	 * Mitake Password
	 *
	 * @var string
	 */
	private string $password;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->username       = get_option( 'form_notify_mitake_username' );
		$this->password       = get_option( 'form_notify_mitake_password' );
		$this->sms_host       = get_option( 'form_notify_mitake_api_url' );
		$this->send_sms_url   = 'https://' . $this->sms_host . '/api/mtk/SmSend';
		$this->get_credit_url = 'https://' . $this->sms_host . '/api/mtk/SmQuery';
		add_action( 'rest_api_init', array( $this, 'register_api_route' ) );
		add_action( 'form_notify_action_module_type_events', array( $this, 'send_notify_events' ), 10, 5 );
	}

	/**
	 * SMS 簡訊傳送
	 *
	 * @param string $phone   手機號碼.
	 * @param string $message 簡訊內容.
	 */
	public function send_sms( string $phone, string $message ): string {
		$body = array(
			'username' => $this->username,
			'password' => $this->password,
			'dstaddr'  => $phone,
			'smbody'   => $message,
			'clientid' => time(),
			'response' => rest_url() . 'form-notify/v1/sms/callback',
		);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => http_build_query( $body ),
		);

		$response = wp_remote_request( $this->send_sms_url . '?CharsetURL=UTF-8', $options );
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		} else {
			$result = explode( PHP_EOL, wp_remote_retrieve_body( $response ) );

			return substr( $result[1], strpos( $result[1], '=' ) + 1 );
		}
	}

	/**
	 * Get sms left points
	 *
	 * @return string|int
	 */
	public function get_points(): string|int {
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
			return $response->get_error_message();
		} else {
			return (int) preg_replace( '/[^0-9]/', '', wp_remote_retrieve_body( $response ) );
		}
	}

	/**
	 * Register api route
	 */
	public function register_api_route(): void {
		register_rest_route(
			'form-notify/v1',
			'/sms/callback',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_callback_body' ),
				'headers'             => array(
					'Content-Type' => 'Text/Plain',
				),
				'permission_callback' => function () {
					return true;
				},
			)
		);
		register_rest_route(
			'mitake/v1',
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
	 * API Callback
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return string
	 */
	public function get_callback_body( WP_REST_Request $request ): string {
		$msgid      = $request['msgid'];
		$phone      = $request['dstaddr'];
		$code       = $request['statuscode'];
		$statusstr  = $request['statusstr'];
		$statusflag = $request['StatusFlag'];
		$history_id = History::select( $msgid, 'notify_type' );

		$status = match ( $statusstr ) {
			'DELIVRD' => '已送達手機',
			'EXPIRED' => '逾時無法送達',
			'DELETED' => '預約已取消',
			'UNDELIV' => '無法送達(門號有錯誤/簡訊已停用)',
			'UNKNOWN' => '無效的簡訊狀態，系統有錯誤',
			'SYNTAXE' => '簡訊內容有錯誤',
			default => 'null',
		};

		if ( $history_id ) {
			History::update( $history_id, $status );
		}

		return 'magicid=sms_gateway_rpack[LF]msgid=' . $msgid . '[LF]';
	}

	/**
	 * API Callback
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_points_api_body( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( $this->get_points() );
	}

	/**
	 * Send notify for events
	 *
	 * @param string $type        type.
	 * @param string $receiver    receiver.
	 * @param string $message     message.
	 * @param string $history_via history method.
	 * @param string $cron_name   cron name.
	 */
	public function send_notify_events( string $type, string $receiver, string $message, string $history_via, string $cron_name ): void {
		if ( 'mitake' === $type ) {
			if ( $this->get_points() > 0 ) {
				$msgid = $this->send_sms( $receiver, $message );
				$type  = __( 'Mitake SMS', 'form-notify' ) . ',' . $msgid;
				History::insert( 0, $receiver . ' - ' . $history_via, 0, $type, $message, __( 'Processing', 'form-notify' ) );
			} else {
				History::insert( 0, $receiver . ' - ' . $history_via, 0, __( 'Mitake SMS', 'form-notify' ), $message, __( 'Form Notify Mitake Sending Result: Points are not enough.', 'form-notify' ) );
			}
		}
	}
}

new Mitake();
