<?php
/**
 * Every8d SMS API
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Sms;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Every8d SMS API class
 */
class Every8d {
	/**
	 * Sms host
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
	 * Get token URL
	 *
	 * @var string $get_token_url
	 */
	private string $get_token_url;
	/**
	 * Batch id
	 *
	 * @var string $batch_id
	 */
	private string $batch_id;
	/**
	 * Credit
	 *
	 * @var float $credit
	 */
	private float $credit;
	/**
	 * Process message
	 *
	 * @var string $process_msg
	 */
	private string $process_msg;
	/**
	 * Every8d username
	 *
	 * @var string $user_id
	 */
	private string $user_id;
	/**
	 * Every8d password
	 *
	 * @var string $password
	 */
	private string $password;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->user_id        = get_option( 'form_notify_e8d_user_id' );
		$this->password       = get_option( 'form_notify_e8d_password' );
		$this->sms_host       = 'api.e8d.tw';
		$this->send_sms_url   = 'https://' . $this->sms_host . '/API21/HTTP/sendSMS.ashx';
		$this->get_credit_url = 'https://' . $this->sms_host . '/API21/HTTP/GetCredit.ashx';
		$this->get_token_url  = 'https://' . $this->sms_host . '/API21/HTTP/ConnectionHandler.ashx';
		$this->batch_id       = '';
		$this->credit         = 0.0;
		$this->process_msg    = '';
		if ( ! $this->check_token() ) {
			$this->get_token();
		}
		add_action( 'form_notify_action_module_type_events', array( $this, 'send_notify_events' ), 10, 5 );
		add_action( 'rest_api_init', array( $this, 'register_points_api_route' ) );
	}

	/**
	 * Get token
	 */
	private function get_token(): void {
		$body    = array(
			'HandlerType' => 3,
			'VerifyType'  => 1,
			'UID'         => $this->user_id,
			'PWD'         => $this->password,
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		);

		$response = wp_remote_request( $this->get_token_url, $options );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
		} else {
			$resp = json_decode( wp_remote_retrieve_body( $response ) );
			// @codingStandardsIgnoreStart
			update_option( 'form_notify_e8d_token', $resp->Msg );
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Check token
	 */
	private function check_token() {
		$body    = array(
			'HandlerType' => 3,
			'VerifyType'  => 2,
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . get_option( 'form_notify_e8d_token' ),
			),
			'body'    => wp_json_encode( $body ),
		);

		$response = wp_remote_request( $this->get_token_url, $options );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		} else {
			$resp = json_decode( wp_remote_retrieve_body( $response ) );

			// @codingStandardsIgnoreStart
			return $resp->Result;
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Send sms
	 *
	 * @param string $phone   Phone.
	 * @param string $message Message.
	 *
	 * @return string $batch_id
	 */
	public function send_sms( string $phone, string $message ): string {
		$body    = array(
			'DEST' => $phone,
			'MSG'  => $message,
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . get_option( 'form_notify_e8d_token' ),
			),
			'body'    => http_build_query( $body ),
		);

		$response = wp_remote_request( $this->send_sms_url, $options );
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		} else {
			$resp = explode( ',', wp_remote_retrieve_body( $response ) );
			if ( count( $resp ) > 2 ) {
				$this->batch_id = $resp[4]; // batch id.
			}
		}

		return $this->batch_id;
	}

	/**
	 * Get Points
	 */
	public function get_points() {
		if ( ! get_option( 'form_notify_e8d_token' ) ) {
			return __( 'Enter the username and password first', 'form-notify' );
		}
		$options = array(
			'method'  => 'GET',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . get_option( 'form_notify_e8d_token' ),
			),
		);

		$response = wp_remote_request( $this->get_credit_url, $options );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
		} else {
			return wp_remote_retrieve_body( $response );
		}
	}

	/**
	 * Send notify for events
	 *
	 * @param string $type        type.
	 * @param string $receiver    receiver.
	 * @param string $message     message.
	 * @param string $history_via history via method.
	 * @param string $cron_name   cron name.
	 *
	 * @return void
	 */
	public function send_notify_events( string $type, string $receiver, string $message, string $history_via, string $cron_name ): void {
		if ( 'e8d' === $type ) {
			if ( $this->get_points() > 0 ) {
				$batch_id   = $this->send_sms( $receiver, $message );
				$history_id = History::insert( 0, $receiver . ' - ' . $history_via, 0, __( 'Every8d SMS', 'form-notify' ), $message, __( 'Processing', 'form-notify' ) );

				as_schedule_single_action(
					strtotime( current_time( 'Y-m-d H:i:s' ) . '-8 hour + 30 minute' ),
					'e8d_check_status_' . $cron_name . '_form',
					array(
						$receiver,
						$batch_id,
						$history_id,
					)
				);
			} else {
				History::insert( 0, $receiver . ' - ' . $history_via, 0, __( 'Every8d SMS', 'form-notify' ), $message, __( 'Form Notify Every8d Sending Result: Points are not enough.', 'form-notify' ) );
			}
		}
	}

	/**
	 * Register api route
	 *
	 * @return void
	 */
	public function register_points_api_route(): void {
		register_rest_route(
			'e8d/v1',
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
		$points = $this->get_points();

		return rest_ensure_response( $points );
	}
}

new Every8d();
