<?php

namespace FORMNOTIFY\APIs\Mitake;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;

class Mitake {
	private $sms_host;
	private $send_sms_url;
	private $get_credit_url;
	private $get_token_url;
	private $batch_id;
	private $credit;
	private $process_msg;
	private $username;
	private $password;

	function __construct() {
		$this->username       = get_option( 'form_notify_mitake_username' );
		$this->password       = get_option( 'form_notify_mitake_password' );
		$this->sms_host       = get_option( 'form_notify_mitake_api_url' );
		$this->send_sms_url   = 'https://' . $this->sms_host . '/api/mtk/SmSend';
		$this->get_credit_url = 'https://' . $this->sms_host . '/api/mtk/SmQuery';
		$this->batch_id       = '';
		$this->credit         = 0;
		$this->process_msg    = '';
		// if ( ! $this->check_token() ) {
		// $this->get_token();
		// }
		add_action( 'rest_api_init', array( $this, 'register_api_route' ) );
		add_action( 'form_notify_action_module_type_events', array( $this, 'send_notify_events' ), 10, 5 );
	}

	/**
	 * SMS 簡訊傳送
	 *
	 * @param string $phone   手機號碼.
	 * @param string $message 簡訊內容.
	 */
	public function send_sms( $phone, $message ) {
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
			$error_message = $response->get_error_message();
		} else {
			$result = explode( PHP_EOL, wp_remote_retrieve_body( $response ) );
			$msgid  = substr( $result[1], strpos( $result[1], '=' ) + 1 );
			// $code   = preg_replace( '/[^0-9]/', '', $result[2] );
			// $point  = preg_replace( '/[^0-9]/', '', $result[3] );
			return $msgid;
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
			return (int) preg_replace( '/[^0-9]/', '', wp_remote_retrieve_body( $response ) );
		}
	}

	/**
	 * Register api route
	 */
	public function register_api_route() {
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
	 */
	public function get_callback_body( $request ) {
		$msgid      = $request['msgid'];
		$phone      = $request['dstaddr'];
		$code       = $request['statuscode'];
		$statusstr  = $request['statusstr'];
		$statusflag = $request['StatusFlag'];
		$history_id = History::select( $msgid, 'notify_type' );

		switch ( $statusstr ) {
			case 'DELIVRD':
				$status = '已送達手機';
				break;
			case 'EXPIRED':
				$status = '逾時無法送達';
				break;
			case 'DELETED':
				$status = '預約已取消';
				break;
			case 'UNDELIV':
				$status = '無法送達(門號有錯誤/簡訊已停用)';
				break;
			case 'UNKNOWN':
				$status = '無效的簡訊狀態，系統有錯誤';
				break;
			case 'SYNTAXE':
				$status = '簡訊內容有錯誤';
				break;
			default:
				$status = 'null';
				break;
		}

		if ( $history_id ) {
			History::update( $history_id, $status );
		}

		echo 'magicid=sms_gateway_rpack[LF]msgid=' . $msgid . '[LF]';
	}

	/**
	 * API Callback
	 */
	public function get_points_api_body( $request ) {
		return rest_ensure_response( $this->get_points() );
	}

	/**
	 * Send notify for events
	 *
	 * @param string $type     通知類型.
	 * @param string $receiver 接收者.
	 * @param string $message  通知內容.
	 */
	public function send_notify_events( $type, $receiver, $message, $history_via, $cron_name ): void {
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
