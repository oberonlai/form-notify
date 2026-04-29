<?php
/**
 * Line Login Route
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Line\Login;

defined( 'ABSPATH' ) || exit;

/**
 * Line Login Route class
 */
class Route {

	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'rest_api_init', array( $class, 'register_api_route' ) );
	}

	/**
	 * Register API Route
	 *
	 * @return void
	 */
	public function register_api_route(): void {

		register_rest_route(
			'form-notify/v1',
			'/login',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_api_login' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);

		register_rest_route(
			'form-notify/v1',
			'/callback',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_api_callback' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}

	/**
	 * Get api login
	 */
	public function get_api_login() {

		$ts    = time();
		$state = md5( $ts );

		set_transient( 'form_notify_line_state_' . $state, $state, 60 * 60 );

		$line = new SDK();
		$url  = $line->get_login_url( $state );

		header( 'Location:' . $url );
		exit;
	}

	/**
	 * Get api callback
	 *
	 * @param object $request Request.
	 */
	public function get_api_callback( object $request ): void {

		$line = new SDK();

		$code          = formnotify_get_params( 'code' );
		$state         = formnotify_get_params( 'state' );
		$session_state = get_transient( 'form_notify_line_state_' . $state );

		if ( empty( $session_state ) ) {
			$session_state = sanitize_text_field( wp_unslash( $_SESSION[ 'form_notify_line_state_' . $state ] ) );
			set_transient( 'form_notify_line_state_' . $state, $state, 60 * 60 );
		}

		if ( $session_state !== $state ) {
			$ts    = time();
			$state = md5( $ts );

			set_transient( 'form_notify_line_state_' . $state, $state, 60 * 60 );
			wp_safe_redirect( $line->get_login_url( $state ) );
			exit;

		}

		$token = $line->get_access_token( $code );

		setcookie( 'access_token', $token['access_token'], time() + 3600 * 24 * 14 );

		$user = $line->get_line_profile( $token['access_token'], $token['id_token'] );

		if ( $user ) {

			$user_raw_id    = $user->sub;
			$user_display   = $user->name;
			$user_avatar    = $user->picture;
			$has_real_email = ! empty( $user->email );
			$user_email     = $has_real_email ? $user->email : $user_raw_id . '@line.com';

			$user_obj = new User();
			if ( $user_obj->is_member( $user_email, $user_avatar ) ) {
				$user_obj->login( $user_raw_id, $user_email, $user_display, $user_avatar, $has_real_email );
			} else {
				$user_obj->sign_up( $user_raw_id, $user_email, $user_display, $user_avatar, $has_real_email );
			}
		}

	}
}

Route::register();
