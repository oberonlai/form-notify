<?php
/**
 * Line Login User
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Line\Login;

defined( 'ABSPATH' ) || exit;

/**
 * Line Login User class
 */
class User {

	/**
	 * User
	 *
	 * @var object $user User.
	 */
	private object|bool $user;

	/**
	 * Roles
	 *
	 * @var array $roles Roles.
	 */
	private array $roles;

	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'init', array( $class, 'set_login_redirect_url' ) );
		add_filter( 'pre_get_avatar_data', array( $class, 'replace_avatar_url' ), 1, 2 );
	}

	/**
	 * Check is member
	 *
	 * @param string $user_email  User email.
	 * @param string $user_avatar User avatar.
	 *
	 * @return bool
	 */
	public function is_member( string $user_email, string $user_avatar ): bool {
		$this->user    = get_user_by( 'email', $user_email );
		$this->roles[] = $this->user->roles;
		if ( ! is_wp_error( $this->user ) && $this->user ) {
			return true;
		}

		return false;
	}

	/**
	 * Login
	 *
	 * @param string $user_raw_id  User raw id.
	 * @param string $user_email   User email.
	 * @param string $user_display User display.
	 * @param string $user_avatar    User avatar.
	 * @param bool   $has_real_email Whether LINE provided a real email.
	 *
	 * @return void
	 */
	public function login( string $user_raw_id, string $user_email, string $user_display, string $user_avatar, bool $has_real_email = false ): void {
		if ( ! is_user_logged_in() ) {

			wp_clear_auth_cookie();
			wp_set_current_user( $this->user->ID );
			wp_set_auth_cookie( $this->user->ID, true, is_ssl() );

			if ( ! get_user_meta( $this->user->ID, 'form_notify_line_user_id', true ) ) {
				update_user_meta( $this->user->ID, 'form_notify_line_user_id', $user_raw_id );
				update_user_meta( $this->user->ID, 'form_notify_line_user_avatar', $user_avatar );
				update_user_meta( $this->user->ID, 'nickname', $user_display );
				if ( $has_real_email ) {
					update_user_meta( $this->user->ID, 'billing_email', $user_email );
				}
			}

			$this->roles = $this->user->roles;
			$this->set_logged_redirect( 'login' );

		}
	}

	/**
	 * Sign up
	 *
	 * @param string $user_raw_id  User raw id.
	 * @param string $user_email   User email.
	 * @param string $user_display User display.
	 * @param string $user_avatar    User avatar.
	 * @param bool   $has_real_email Whether LINE provided a real email.
	 *
	 * @return void
	 */
	public function sign_up( string $user_raw_id, string $user_email, string $user_display, string $user_avatar, bool $has_real_email = false ): void {

		if ( ! is_user_logged_in() ) {

			if ( username_exists( strstr( $user_email, '@', true ) ) ) {
				$user_login = strstr( $user_email, '@', true ) . '-' . wp_rand( 1, 10 );
			} else {
				$user_login = strstr( $user_email, '@', true );
			}

			$userdata = array(
				'user_login'   => $user_login,
				'user_pass'    => $user_email,
				'user_email'   => $user_email,
				'display_name' => $user_display,
				'nickname'     => $user_display,
				'role'         => $this->role_check(),
			);

			$user_id = wp_insert_user( $userdata );

			update_user_meta( $user_id, 'form_notify_line_user_id', $user_raw_id );
			update_user_meta( $user_id, 'form_notify_line_user_avatar', $user_avatar );
			if ( $has_real_email ) {
				update_user_meta( $user_id, 'billing_email', $user_email );
			}

			if ( function_exists( 'add_user_to_blog' ) ) {
				add_user_to_blog( get_current_blog_id(), $user_id, $this->role_check() );
			}

			wp_clear_auth_cookie();
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );

			$this->set_logged_redirect( 'signup' );
		}

	}

	/**
	 * Role check
	 *
	 * @return string
	 */
	private function role_check(): string {
		if ( get_option( 'form_notify_line_btn_user_role' ) ) {
			return get_option( 'form_notify_line_btn_user_role' );
		} else {
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
				return 'customer';
			} else {
				return 'subscriber';
			}
		}
	}

	/**
	 * Set LINE login redirect action
	 */
	public function set_login_redirect_url(): void {
		$lgmode = formnotify_get_params( 'lgmode' );

		if ( $lgmode ) {
			session_start();

			$line  = new SDK();
			$state = md5( time() );

			$redirect_url = '';

			if ( 'true' === $lgmode ) {
				if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
					$http_post    = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
					$request_uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
					$redirect_url = preg_replace( '~(\?|&)lgmode=[^&]*~', '$1', 'https://' . $http_post . $request_uri );
				}
			} elseif ( str_contains( $lgmode, 'http' ) ) {
				$redirect_url = wp_unslash( $lgmode );
			}

			setcookie( 'login_redirect_url', $redirect_url, time() + 3600, '/' );
			$_SESSION[ 'form_notify_line_state_' . $state ] = $state;
			set_transient( 'form_notify_line_state_' . $state, $state, 60 * 60 );

			header( 'Location:' . $line->get_login_url( $state ) );
			exit;
		}
	}

	/**
	 * Set Logged Redirect
	 *
	 * @param string $type login or signup.
	 */
	public function set_logged_redirect( string $type ): void {

		$login_redirect_url = isset( $_COOKIE['login_redirect_url'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['login_redirect_url'] ) ) : '';
		$admin_roles        = array( 'administrator', 'shop_manager' );
		$is_admin           = ( count( array_intersect( $admin_roles, $this->roles ) ) > 0 ) ? true : false;
		$is_wp_login        = str_contains( $login_redirect_url, 'wp-login.php' );

		if ( $login_redirect_url && ! get_option( 'form_notify_line_btn_redirect' ) ) {
			header( 'Location:' . $login_redirect_url );
			exit;
		}

		if ( get_option( 'form_notify_line_btn_redirect' ) ) {
			header( 'Location:' . get_option( 'form_notify_line_btn_redirect' ) );
			exit;
		}

		if ( 'login' === $type && $is_admin && in_array( 'bbpress/bbpress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			wp_safe_redirect( home_url() . '/activity' );
			exit;
		}

		if ( 'login' === $type && $is_admin ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		if ( 'login' === $type && $is_wp_login && $is_admin ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		if ( 'login' === $type ) {
			if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
				wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
			} else {
				wp_safe_redirect( home_url() );
			}
			exit;
		}

		if ( 'signup' === $type && $is_wp_login ) {
			if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
				wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
			} else {
				wp_safe_redirect( home_url() );
			}
			exit;
		}
	}

	/**
	 * Replace avatar url
	 *
	 * @param array $args        Avatar args.
	 * @param mixed $id_or_email User id or email.
	 *
	 * @return array
	 */
	public function replace_avatar_url( array $args, mixed $id_or_email ): array {

		$user_id = '';

		if ( 'object' === gettype( $id_or_email ) && 'WP_Comment' === get_class( $id_or_email ) ) {
			$user_id = $id_or_email->user_id;
		}

		if ( 'object' === gettype( $id_or_email ) && 'WP_User' === get_class( $id_or_email ) ) {
			$user_id = $id_or_email->ID;
		}

		if ( 'integer' === gettype( $id_or_email ) ) {
			$user_id = $id_or_email;
		}

		if ( 'string' === gettype( $id_or_email ) && strpos( $id_or_email, '@' ) !== false ) {
			$user_id = get_user_by( 'email', $id_or_email )->ID;
		}

		if ( $user_id ) {
			if ( get_user_meta( $user_id, 'form_notify_line_user_avatar' ) ) {
				$args['url'] = get_user_meta( $user_id, 'form_notify_line_user_avatar', true );
			}
		}

		return $args;

	}

}

User::register();
