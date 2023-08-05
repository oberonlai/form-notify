<?php

namespace FORMNOTIFY\APIs\E8d;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;

class Every8d {
	private $sms_host;
	private $send_sms_url;
	private $get_credit_url;
	private $get_token_url;
	private $batch_id;
	private $credit;
	private $process_msg;
	private $user_id;
	private $password;

	function __construct() {
		$this->user_id        = get_option( 'form_notify_e8d_user_id' );
		$this->password       = get_option( 'form_notify_e8d_password' );
		$this->sms_host       = 'api.e8d.tw';
		$this->send_sms_url   = 'https://' . $this->sms_host . '/API21/HTTP/sendSMS.ashx';
		$this->get_credit_url = 'https://' . $this->sms_host . '/API21/HTTP/GetCredit.ashx';
		$this->get_token_url  = 'https://' . $this->sms_host . '/API21/HTTP/ConnectionHandler.ashx';
		$this->get_status_url = 'https://' . $this->sms_host . '/API21/HTTP/GetDeliveryStatus.ashx';
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
	 * 取得授權憑證
	 */
	private function get_token() {
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
			update_option( 'form_notify_e8d_token', $resp->Msg );
		}
	}

	/**
	 * 檢查 Token
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
			$error_message = $response->get_error_message();
		} else {
			$resp = json_decode( wp_remote_retrieve_body( $response ) );

			return $resp->Result;
		}
	}

	/**
	 * SMS 簡訊傳送
	 */
	public function send_sms( $phone, $message ) {
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
			$error_message = $response->get_error_message();
		} else {
			$resp = explode( ',', wp_remote_retrieve_body( $response ) );
			if ( count( $resp ) > 2 ) {
				$credit   = $resp[0]; // 發送後剩餘點數.
				$sended   = $resp[1]; // 發送通數.
				$cost     = $resp[2]; // 本次發送扣除點數.
				$unsend   = $resp[3]; // 無額度時發送的通數.
				$batch_id = $resp[4]; // 批次識別代碼.
			}
		}

		return $batch_id;
	}

	/**
	 * 發送狀態查詢
	 */
	public function check_status( $batch_id ) {
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
				'Authorization' => 'Bearer ' . get_option( 'form_notify_e8d_token' ),
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
			$status = $resp->DATA[0]->STATUS ?? '';
			if ( $status === '' ) {
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
	 * @param string $type     通知類型.
	 * @param string $receiver 接收者.
	 * @param string $message  通知內容.
	 */
	public function send_notify_events( $type, $receiver, $message, $history_via, $cron_name ) {
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
	 * @hook add_action( 'rest_api_init', __CLASS__ . '::register_bot_api_route');
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
	 * API Callback
	 */
	public function get_points_api_body( $request ) {
		$points = $this->get_points();

		return rest_ensure_response( $points );
	}
}

new Every8d();
