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
	 * @var \WP_User|false $user User.
	 */
	private $user = false;

	/**
	 * Roles
	 *
	 * @var array $roles Roles.
	 */
	private array $roles = array();

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
	 * Look up the WordPress user by the LINE sub stored in user_meta.
	 * Never trust client-supplied email for identity.
	 *
	 * @param string $user_raw_id LINE user sub.
	 *
	 * @return bool
	 */
	public function is_member( string $user_raw_id ): bool {
		if ( empty( $user_raw_id ) ) {
			return false;
		}

		$query = new \WP_User_Query(
			array(
				'meta_key'   => 'form_notify_line_user_id',
				'meta_value' => $user_raw_id,
				'number'     => 1,
				'fields'     => 'all',
			)
		);

		$results = $query->get_results();
		if ( empty( $results ) ) {
			return false;
		}

		$this->user  = $results[0];
		$this->roles = (array) $this->user->roles;

		return true;
	}

	/**
	 * Login
	 *
	 * @param string $user_raw_id    User raw id.
	 * @param string $user_email     User email.
	 * @param string $user_display   User display.
	 * @param string $user_avatar    User avatar.
	 * @param bool   $has_real_email Whether LINE provided a real email.
	 *
	 * @return void
	 */
	public function login( string $user_raw_id, string $user_email, string $user_display, string $user_avatar, bool $has_real_email = false ): void {
		if ( is_user_logged_in() || ! $this->user ) {
			return;
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $this->user->ID );
		wp_set_auth_cookie( $this->user->ID, true );

		update_user_meta( $this->user->ID, 'form_notify_line_user_avatar', $user_avatar );
		if ( ! get_user_meta( $this->user->ID, 'nickname', true ) ) {
			update_user_meta( $this->user->ID, 'nickname', $user_display );
		}
		if ( $has_real_email && ! get_user_meta( $this->user->ID, 'billing_email', true ) ) {
			update_user_meta( $this->user->ID, 'billing_email', $user_email );
		}

		$this->set_logged_redirect( 'login' );
	}

	/**
	 * Sign up
	 *
	 * @param string $user_raw_id    User raw id.
	 * @param string $user_email     User email.
	 * @param string $user_display   User display.
	 * @param string $user_avatar    User avatar.
	 * @param bool   $has_real_email Whether LINE provided a real email.
	 *
	 * @return void
	 */
	public function sign_up( string $user_raw_id, string $user_email, string $user_display, string $user_avatar, bool $has_real_email = false ): void {

		if ( is_user_logged_in() ) {
			return;
		}

		// If LINE provided a real email and that email already belongs to a WP account,
		// do not auto-link or auto-create — abort to prevent account hijack.
		if ( $has_real_email && email_exists( $user_email ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$base_login = sanitize_user( strstr( $user_email, '@', true ), true );
		if ( empty( $base_login ) ) {
			$base_login = 'line_' . substr( md5( $user_raw_id ), 0, 8 );
		}
		$user_login = $base_login;
		$suffix     = 1;
		while ( username_exists( $user_login ) ) {
			$user_login = $base_login . '-' . $suffix;
			++$suffix;
		}

		$userdata = array(
			'user_login'   => $user_login,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'user_email'   => $user_email,
			'display_name' => $user_display,
			'nickname'     => $user_display,
			'role'         => $this->role_check(),
		);

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

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
		wp_set_auth_cookie( $user_id, true );

		$this->user  = get_user_by( 'id', $user_id );
		$this->roles = $this->user ? (array) $this->user->roles : array();

		$this->set_logged_redirect( 'signup' );
	}

	/**
	 * Role check
	 *
	 * @return string
	 */
	private function role_check(): string {
		$configured = get_option( 'form_notify_line_btn_user_role' );
		if ( $configured ) {
			return (string) $configured;
		}
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return 'customer';
		}
		return 'subscriber';
	}

	/**
	 * Set LINE login redirect action
	 */
	public function set_login_redirect_url(): void {
		$lgmode = formnotify_get_params( 'lgmode' );

		if ( ! $lgmode ) {
			return;
		}

		$line  = new SDK();
		$state = wp_generate_password( 32, false );

		$redirect_url = '';

		if ( 'true' === $lgmode ) {
			if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				$http_host    = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
				$request_uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$scheme       = is_ssl() ? 'https://' : 'http://';
				$redirect_url = preg_replace( '~(\?|&)lgmode=[^&]*~', '$1', $scheme . $http_host . $request_uri );
				$redirect_url = wp_validate_redirect( $redirect_url, home_url() );
			}
		} elseif ( str_contains( $lgmode, 'http' ) ) {
			// Only allow on-site redirects.
			$redirect_url = wp_validate_redirect( $lgmode, home_url() );
		}

		if ( $redirect_url ) {
			setcookie(
				'form_notify_login_redirect',
				esc_url_raw( $redirect_url ),
				array(
					'expires'  => time() + 3600,
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		set_transient( 'form_notify_line_state_' . $state, 1, 60 * 10 );

		wp_redirect( esc_url_raw( $line->get_login_url( $state ) ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- redirect to LINE OAuth host.
		exit;
	}

	/**
	 * Set Logged Redirect
	 *
	 * @param string $type login or signup.
	 */
	public function set_logged_redirect( string $type ): void {

		$cookie_redirect = isset( $_COOKIE['form_notify_login_redirect'] )
			? esc_url_raw( wp_unslash( $_COOKIE['form_notify_login_redirect'] ) )
			: '';
		// Validate cookie value is on-site only.
		$login_redirect_url = $cookie_redirect ? wp_validate_redirect( $cookie_redirect, '' ) : '';

		$option_redirect = get_option( 'form_notify_line_btn_redirect' );
		// Validate option value is on-site (admin-set, but defensive).
		$option_redirect = $option_redirect ? wp_validate_redirect( $option_redirect, '' ) : '';

		$admin_roles = array( 'administrator', 'shop_manager' );
		$is_admin    = ( count( array_intersect( $admin_roles, $this->roles ) ) > 0 );
		$is_wp_login = $login_redirect_url && str_contains( $login_redirect_url, 'wp-login.php' );

		// Clear the redirect cookie after consumption.
		if ( $cookie_redirect ) {
			setcookie(
				'form_notify_login_redirect',
				'',
				array(
					'expires'  => time() - 3600,
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		if ( $login_redirect_url && ! $option_redirect ) {
			wp_safe_redirect( $login_redirect_url );
			exit;
		}

		if ( $option_redirect ) {
			wp_safe_redirect( $option_redirect );
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

		$user_id = 0;

		if ( $id_or_email instanceof \WP_Comment ) {
			$user_id = (int) $id_or_email->user_id;
		} elseif ( $id_or_email instanceof \WP_User ) {
			$user_id = (int) $id_or_email->ID;
		} elseif ( is_int( $id_or_email ) ) {
			$user_id = $id_or_email;
		} elseif ( is_string( $id_or_email ) && strpos( $id_or_email, '@' ) !== false ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$user_id = (int) $user->ID;
			}
		}

		if ( $user_id ) {
			$avatar = get_user_meta( $user_id, 'form_notify_line_user_avatar', true );
			if ( $avatar ) {
				$args['url'] = esc_url_raw( $avatar );
			}
		}

		return $args;
	}
}

User::register();
