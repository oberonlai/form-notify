<?php

namespace FORMNOTIFY\APIs\Line\Login;

defined( 'ABSPATH' ) || exit;

class User {

	private $user;
	private $roles;

	public static function register() {
		$class = new self();
		add_action( 'init', array( $class, 'set_login_redirect_url' ) );
		add_action( 'wp_footer', array( $class, 'add_email_form' ) );
		add_action( 'init', array( $class, 'add_email' ) );
		add_filter( 'pre_get_avatar_data', array( $class, 'replace_avatar_url' ), 1, 2 );
	}

	/**
	 * 判斷是否為網站會員
	 */
	public function is_member( $user_email, $user_avatar ) {
		$this->user    = get_user_by( 'email', $user_email );
		$this->roles[] = $this->user->roles;
		if ( ! is_wp_error( $this->user ) && $this->user ) {
			return true;
		}

		return false;
	}

	/**
	 * 登入網站會員
	 */
	public function login( $user_raw_id, $user_email, $user_display, $user_avatar ) {
		if ( ! is_user_logged_in() ) {

			wp_clear_auth_cookie();
			wp_set_current_user( $this->user->ID );
			wp_set_auth_cookie( $this->user->ID, true, is_ssl() );

			if ( ! get_user_meta( $this->user->ID, 'form_notify_line_user_id', true ) ) {
				update_user_meta( $this->user->ID, 'form_notify_line_user_id', $user_raw_id );
				update_user_meta( $this->user->ID, 'form_notify_line_user_avatar', $user_avatar );
				update_user_meta( $this->user->ID, 'nickname', $user_display );
				update_user_meta( $this->user->ID, 'billing_email', $user_email );
			}

			$this->roles = $this->user->roles;
			$this->set_logged_redirect( 'login' );

		}
	}

	/**
	 * 註冊網站會員
	 */
	public function sign_up( $user_raw_id, $user_email, $user_display, $user_avatar ) {

		if ( ! is_user_logged_in() ) {

			function role_check() {
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
				'role'         => role_check(),
			);

			$user_id = wp_insert_user( $userdata );

			update_user_meta( $user_id, 'form_notify_line_user_id', $user_raw_id );
			update_user_meta( $user_id, 'form_notify_line_user_avatar', $user_avatar );
			update_user_meta( $user_id, 'billing_email', $user_email );

			if ( function_exists( 'add_user_to_blog' ) ) {
				add_user_to_blog( get_current_blog_id(), $user_id, role_check() );
			}

			wp_clear_auth_cookie();
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true, is_ssl() );

			$this->set_logged_redirect( 'signup' );
		}

	}

	/**
	 * Set LINE login redirect action
	 */
	public function set_login_redirect_url() {
		if ( isset( $_GET['lgmode'] ) ) {

			if ( 'check-email' === $_GET['lgmode'] ) {
				//wc_add_notice( __( 'Please enter your emaill address to login with LINE.', 'form-notify' ), 'error' );
			} else {
				session_start();

				$line  = new SDK();
				$state = md5( time() );

				if ( 'true' === $_GET['lgmode'] ) {
					$redirect_url = preg_replace( '~(\?|&)lgmode=[^&]*~', '$1', 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				} elseif ( strpos( $_GET['lgmode'], 'http' ) !== false ) {
					$redirect_url = wp_unslash( $_GET['lgmode'] );
				}

				setcookie( 'login_redirect_url', $redirect_url, time() + 3600, '/' );
				$_SESSION[ 'form_notify_line_state_' . $state ] = $state;
				set_transient( 'form_notify_line_state_' . $state, $state, 60 * 60 );

				header( 'Location:' . $line->get_login_url( $state ) );
				exit;
			}
		}
	}

	/**
	 * Set Logged Redirect
	 *
	 * @param string $type login or signup.
	 */
	public function set_logged_redirect( $type ) {

		$login_redirect_url = isset( $_COOKIE['login_redirect_url'] ) ? $_COOKIE['login_redirect_url'] : '';
		$admin_roles        = array( 'administrator', 'shop_manager' );
		$is_admin           = ( count( array_intersect( $admin_roles, $this->roles ) ) > 0 ) ? true : false;
		$is_wp_login        = ( strpos( $login_redirect_url, 'wp-login.php' ) !== false ) ? true : false;

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
	 * 替換頭像網址
	 */
	public function replace_avatar_url( $args, $id_or_email ) {

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

	/**
	 * 補填 Email 表單
	 */
	public function add_email_form() {
		if ( isset( $_GET['lgmode'] ) && 'check-email' === $_GET['lgmode'] ) {
			?>
			<div class="fixed top:0 left:0 w:100% h:100vh bg:rgba(0,0,0,.5) z:1234"></div>
			<div class="fixed w:400@sm w:90% top:50% left:50% translate(-50%,-50%) bg:#fff r:5 z:5678">
				<h3 class="t:center pt:60 mb:0 f:red f:24">
					<span class=" d:block mx:auto scale(2.5,2.5) mb:10">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path
								d="M12 6C12.5523 6 13 6.44772 13 7V13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13V7C11 6.44772 11.4477 6 12 6Z"
								fill="currentColor" />
							<path
								d="M12 16C11.4477 16 11 16.4477 11 17C11 17.5523 11.4477 18 12 18C12.5523 18 13 17.5523 13 17C13 16.4477 12.5523 16 12 16Z"
								fill="currentColor" />
							<path fill-rule="evenodd" clip-rule="evenodd"
								d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM4 12C4 16.4183 7.58172 20 12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12Z"
								fill="currentColor" />
						</svg>
					</span>
					<?php echo esc_html( __( 'Register Failed.', 'form-notify' ) ); ?>
				</h3>
				<p class="m:0 f:14 f:#666 t:center"><?php echo esc_html( __( 'You have to enter your emaill address.', 'form-notify' ) ); ?></p>
				<form action="<?php echo esc_attr( get_the_permalink() ); ?>" method="post" class="p:20|30|20">
					<div>
						<input class="w:100% f:16 b:1px|solid|#ccc r:5 p:10|15 box:border appearance:none bg:#f2f2f2 f:#222" type="email" name="form_notify_user_email" placeholder="<?php echo esc_attr( __( 'your@email.com' ) ); ?>" required>
					</div>
					<div class="mt:10 rel d:flex">
						<button class="w:100% r:5 appearance:none border:0 outline:0 p:7|0|12! f:16 bg:#02d534! f:white! cursor:pointer ~background|.2s|ease bg:#12b83a:hover!" type="submit">
							<img class="rel top:8 d:inline" src="<?php echo esc_attr( WCNOTIFY_PLUGIN_URL ); ?>assets/img/icon-line.svg" />&nbsp;
							<?php echo esc_attr( __( 'Register', 'form-notify' ) ); ?>
						</button>
					</div>
					<a href="<?php echo esc_html( home_url() ); ?>" class="d:block mt:5 t:center w:100% r:5 appearance:none border:0 outline:0 p:8|0 t:none f:16 bg:#333! f:white! cursor:pointer ~background|.2s|ease bg:#555:hover!" type="submit">
						<?php echo esc_attr( __( 'Close', 'form-notify' ) ); ?>
					</a>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * 補填 Email 表單
	 */
	public function add_email() {
		if ( isset( $_POST['form_notify_user_email'] ) && ! empty( $_POST['form_notify_user_email'] ) ) {
			setcookie( 'form_notfify_line_email', sanitize_email( $_POST['form_notify_user_email'] ), time() + 3600, '/' );
			wp_safe_redirect( home_url() . '?lgmode=true' );
		}
	}
}

User::register();
