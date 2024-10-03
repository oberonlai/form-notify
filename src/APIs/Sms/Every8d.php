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
	private $enable;
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
		$this->enable         = 'yes' === get_option( 'form_notify_e8d_enable' );
		$this->user_id        = get_option( 'form_notify_e8d_user_id' );
		$this->password       = get_option( 'form_notify_e8d_password' );
		$this->sms_host       = 'api.e8d.tw';
		$this->send_sms_url   = 'https://' . $this->sms_host . '/API21/HTTP/sendSMS.ashx';
		$this->get_credit_url = 'https://' . $this->sms_host . '/API21/HTTP/GetCredit.ashx';
		$this->get_token_url  = 'https://' . $this->sms_host . '/API21/HTTP/ConnectionHandler.ashx';
		$this->batch_id       = '';
		$this->credit         = 0.0;
		$this->process_msg    = '';

		if ( ! $this->enable ) {
			return;
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
		if ( ! $this->check_token() ) {
			$this->get_token();
		}
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
	 * Check sent status
	 *
	 * @param string $batch_id batch id.
	 *
	 * @return string $message
	 */
	public function check_status( string $batch_id ): string {
		$body    = array(
			'BID'        => $batch_id,
			'RESPFORMAT' => 1,
			'PNO'        => 1,
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . get_option( 'wc_notify_e8d_token' ),
			),
			'body'    => http_build_query( $body ),
		);

		$response = wp_remote_request( $this->get_status_url, $options );
		$message  = __( 'Success', 'form-notify' );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			return __( 'Occur error when connecting API. Please try later.', 'form-notify' );
		} else {
			$resp   = json_decode( wp_remote_retrieve_body( $response ) );
			$status = $resp->DATA[0]->STATUS ?? ''; // phpcs:ignore.
			if ( '' === $status ) {
				return '找不到批次識別碼';
			} elseif ( '100' !== $status ) {
				switch ( $status ) {
					case '-5':
						$message = 'Short Message 內容長度超過限制，該訊息傳送失敗。';
						break;
					case '-4':
						$message = '訊息預計發送時間已逾期 24 小時以上，該訊息傳送失敗。';
						break;
					case '-3':
						$message = '受話方手機號碼為無效號碼或黑名單，該訊息傳送失敗。';
						break;
					case '-2':
						$message = 'API 帳號或密碼錯誤，該訊息傳送失敗。';
						break;
					case '-1':
						$message = '參數錯誤，該訊息傳送失敗。';
						break;
					case '0':
						$message = '訊息已成功傳送給電信端，電信基地台與受話方手機溝通超過五分鐘，該訊息仍未送達受話方手機。';
						break;
					case '101':
						$message = '電信端回覆因受話方手機關機/訊號不良/簡訊功能異常等原因，該訊息無法送達受話方手機。';
						break;
					case '102':
						$message = '電信端回覆因網路系統/基地台設備異常等原因，該訊息無法送達受話方手機。';
						break;
					case '103':
						$message = '電信端回覆因受話方手機門號為空號或停用中，該訊息無法送達受話方手機。';
						break;
					case '104':
						$message = '電信端回覆因受話方手機門號為電信黑名單，該訊息無法送達受話方手機。';
						break;
					case '105':
						$message = '電信端回覆因受話方手機設備問題/手機出現未預期錯誤等原因，該訊息無法送達受話方手機。';
						break;
					case '106':
						$message = '電信端回覆因系統傳送時發生非預期錯誤，該訊息無法送達受話方手機。';
						break;
					case '107':
						$message = '電信端回覆因受話方手機關機/訊號不良/簡訊功能異常等原因，該息無法送達受話方手機。';
						break;
					case '301':
						$message = '無額度(或額度不足)無法發送。';
						break;
					case '500':
						$message = '表該門號為國際門號，請至帳號設定開啟國際簡訊發送功能。';
						break;
					default:
						// code...
						break;
				}
			}

			return $message;
		}
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
