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

		$state = wp_generate_password( 32, false );

		set_transient( 'form_notify_line_state_' . $state, 1, 60 * 10 );

		$line = new SDK();
		$url  = $line->get_login_url( $state );

		wp_redirect( esc_url_raw( $url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- redirect to LINE OAuth host.
		exit;
	}

	/**
	 * Get api callback
	 *
	 * @param object $request Request.
	 */
	public function get_api_callback( object $request ): void {

		$code  = formnotify_get_params( 'code' );
		$state = formnotify_get_params( 'state' );

		if ( empty( $code ) || empty( $state ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$transient_key = 'form_notify_line_state_' . $state;
		$session_state = get_transient( $transient_key );

		if ( empty( $session_state ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		// Single-use state token.
		delete_transient( $transient_key );

		$line  = new SDK();
		$token = $line->get_access_token( $code );

		if ( empty( $token['access_token'] ) || empty( $token['id_token'] ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$user = $line->get_line_profile( $token['access_token'], $token['id_token'] );

		if ( ! $user || empty( $user->sub ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$user_raw_id    = $user->sub;
		$user_display   = isset( $user->name ) ? $user->name : '';
		$user_avatar    = isset( $user->picture ) ? $user->picture : '';
		$has_real_email = ! empty( $user->email );
		$user_email     = $has_real_email ? $user->email : $user_raw_id . '@line.local';

		$user_obj = new User();
		if ( $user_obj->is_member( $user_raw_id ) ) {
			$user_obj->login( $user_raw_id, $user_email, $user_display, $user_avatar, $has_real_email );
		} else {
			$user_obj->sign_up( $user_raw_id, $user_email, $user_display, $user_avatar, $has_real_email );
		}
	}
}

Route::register();
