<?php

namespace FORMNOTIFY\APIs\Easygo;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;

class Easygo {
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
		$this->username       = get_option( 'form_notify_easygo_username' );
		$this->password       = get_option( 'form_notify_easygo_password' );
		$this->sms_host       = 'https://www.easysms.com.tw/easygohttpapi/httpapiservice.asmx';
		$this->send_sms_url   = $this->sms_host . '/CallSendSMS';
		$this->get_credit_url = $this->sms_host . '/CallQueryPoints';
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
	 * @param string $phone 手機號碼.
	 * @param string $message 簡訊內容.
	 */
	public function send_sms( $phone, $message ) {
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
			$error_message = $response->get_error_message();
		} else {
			$body = wp_remote_retrieve_body( $response );
			$xml  = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );
			if ( $xml->ErrorMsg ) {
				return (string) $xml->ErrorMsg;
			} else {
				return __( 'Success', 'form-notify' );
			}
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
	 */
	public function register_api_route() {
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
	 */
	public function get_points_api_body( $request ) {
		return rest_ensure_response( $this->get_points() );
	}

	/**
	 * Send notify for events
	 *
	 * @param string $type 通知類型.
	 * @param string $receiver 接收者.
	 * @param string $message 通知內容.
	 */
	public function send_notify_events( $type, $receiver, $message, $history_via ) {
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

	/**
	 * Send notify for order
	 */
	public function send_notify_order( $type, $order, $message ) {
		if ( 'easygo' === $type ) {
			if ( $this->get_points() > 0 ) {
				$phone   = ( $order->get_billing_phone() ) ? $order->get_billing_phone() : $order->get_shipping_phone();
				$result = $this->send_sms( $phone, $message );
				$type   = __( 'easyGo SMS', 'form-notify' );

				$history_id = History::insert( $order->get_user_id(), $order->get_billing_phone(), $order->get_id(), $type, $message, $result );

				$order->add_order_note( __( 'Form Notify easyGo Sending Result: Success', 'form-notify' ) );

			} else {
				$order->add_order_note( __( 'Form Notify easyGo Sending Result: Points are not enough.', 'form-notify' ) );

				$history_id = History::insert( $order->get_user_id(), $phone, $order->get_id(), __( 'easyGo SMS', 'form-notify' ), $message, __( 'Form Notify easyGo Sending Result: Points are not enough.', 'form-notify' ) );
			}
		}
	}


}

new Easygo();
