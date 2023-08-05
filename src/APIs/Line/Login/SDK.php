<?php

namespace FORMNOTIFY\APIs\Line\Login;

defined( 'ABSPATH' ) || exit;

class SDK {

	public function get_login_url( $state ) {

		$parameter = array(
			'response_type' => 'code',
			'client_id'     => get_option( 'form_notify_line_login_channel_id' ),
			'state'         => $state,
		);

		$host = 'https://access.line.me/oauth2/v2.1/authorize';

		$url = $host . '?' . http_build_query( $parameter ) . '&scope=email%20openid%20profile&redirect_uri=' . home_url() . '/wp-json/form-notify/v1/callback&bot_prompt=aggressive';

		return $url;
	}

	/**
	 * 取得 access_token 跟 id_token
	 */
	public function get_access_token( $code ) {
		$body    = array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => home_url() . '/wp-json/form-notify/v1/callback',
			'client_id'     => get_option( 'form_notify_line_login_channel_id' ),
			'client_secret' => get_option( 'form_notify_line_login_channel_secret' ),
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => $body,
		);

		$response = wp_remote_request( 'https://api.line.me/oauth2/v2.1/token', $options );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
		} else {
			$resp  = json_decode( wp_remote_retrieve_body( $response ) );
			$token = array(
				'access_token' => $resp->access_token,
				'id_token'     => $resp->id_token,
			);
			return $token;
		}
	}

	/**
	 * 取得使用者資訊
	 */
	public function get_line_profile( $access_token, $id_token ) {
		$body    = array(
			'id_token'  => $id_token,
			'client_id' => get_option( 'form_notify_line_login_channel_id' ),
		);
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $body,
		);

		$response = wp_remote_request( 'https://api.line.me/oauth2/v2.1/verify', $options );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
		} else {
			$resp = json_decode( wp_remote_retrieve_body( $response ) );
			return $resp;
		}
	}
}
