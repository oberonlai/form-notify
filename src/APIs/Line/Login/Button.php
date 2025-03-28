<?php
/**
 * LINE login button
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs\Line\Login;

defined( 'ABSPATH' ) || exit;

/**
 * Button class
 */
class Button {

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		$class = new self();
		add_shortcode( 'linelogin', array( $class, 'register_login_button' ) );
		add_action( 'wp_enqueue_scripts', array( $class, 'custom_login_style' ) );
		add_action(
			'plugins_loaded',
			function () {
				$class = new self();
				if ( 'yes' === get_option( 'form_notify_line_btn_form_display' ) ) {
					$position = get_option( 'form_notify_line_btn_form_display_position' );
					add_action( 'woocommerce_login_form_' . $position, array( $class, 'line_login_button' ) );
					add_action( 'woocommerce_register_form_' . $position, array( $class, 'line_login_button' ) );
				}

				if ( 'yes' === get_option( 'form_notify_line_btn_checkout_display' ) ) {
					$position_checkout = get_option( 'form_notify_line_btn_checkout_display_position' );
					add_action( $position_checkout, array( $class, 'line_login_button_checkout' ) );
				}

				if ( 'yes' === get_option( 'form_notify_line_btn_wp_form_display' ) ) {
					add_action( 'login_form', array( $class, 'line_login_button_wp_form' ) );
					add_action( 'register_form', array( $class, 'line_login_button_wp_form' ) );
					add_action( 'login_enqueue_scripts', array( $class, 'custom_login_style' ) );
				}
			}
		);
	}

	/**
	 * Enqueue custom login style
	 *
	 * @return void
	 */
	public function custom_login_style(): void {
		wp_enqueue_style( 'form-notify-login', FORMNOTIFY_PLUGIN_URL . 'assets/src/login.css', array(), '1.0.0' );
	}

	/**
	 * Set LINE login button in login form
	 */
	public function line_login_button(): void {
		$size  = get_option( 'form_notify_line_btn_form_display_size' ) ? get_option( 'form_notify_line_btn_form_display_size' ) : 'f';
		$align = get_option( 'form_notify_line_btn_form_display_align' );
		$text  = ( 'woocommerce_login_form_' . get_option( 'form_notify_line_btn_form_display_position' ) === current_action() ? __( 'Login', 'form-notify' ) : __( 'Register', 'form-notify' ) );
		$this->render_button( $size, $text, $align );
	}

	/**
	 * Set LINE login button in checkout
	 */
	public function line_login_button_checkout(): void {
		$size  = get_option( 'form_notify_line_btn_checkout_display_size' ) ? get_option( 'form_notify_line_btn_checkout_display_size' ) : 'f';
		$align = get_option( 'form_notify_line_btn_checkout_display_align' );
		$this->render_button( $size, get_option( 'form_notify_line_btn_checkout_text' ) ? get_option( 'form_notify_line_btn_checkout_text' ) : __( 'Login', 'form-notify' ), $align );
	}

	/**
	 * Set LINE login button in wp-login.php
	 */
	public function line_login_button_wp_form(): void {
		$html = '';
		if ( 'register_form' === current_action() ) {
			$html = $this->render_button( 'f', __( 'Register', 'form-notify' ), 'center' );
		} else {
			$html = $this->render_button( 'f', __( 'Login', 'form-notify' ), 'center' );
		}
		echo wp_kses_post( $html );
	}

	/**
	 * Render button
	 *
	 * @param string      $size   button size.
	 * @param string      $text   button text.
	 * @param string      $align  button align.
	 * @param string|null $show   ignore user logged status.
	 * @param string      $lgmode lgmode.
	 */
	public function render_button( string $size, string $text, string $align, string $show = null, string $lgmode = 'true' ): string {
		if ( ! is_user_logged_in() || 'show' === $show ) {
			// Translators: %s: text.
			return '<div class="form-notify-line-wrap ' . esc_attr( $align ) . '"><a class="size-' . esc_attr( $size ) . '" href="' . esc_attr( get_the_permalink() ) . '?lgmode=' . $lgmode . '"><img src="' . esc_attr( FORMNOTIFY_PLUGIN_URL ) . 'assets/img/icon-line.svg" />' . esc_html( $text ) . '</a></div>';
		}

		return '';
	}


	/**
	 * LINE login button shortcode
	 *
	 * @param array $attrs shortocde attribute.
	 *
	 * @return string
	 */
	public function register_login_button( array $attrs ): string {

		if ( is_user_logged_in() ) {
			return '';
		}

		$param = shortcode_atts(
			array(
				'text'   => __( 'Button Text', 'form-notify' ),
				'size'   => 's',
				'lgmode' => 'true',
				'align'  => 'center',
			),
			$attrs
		);

		$r = '<div class="form-notify-line-wrap ' . $param['align'] . '"><a class="size-' . $param['size'] . '" href="' . get_the_permalink() . '?lgmode=' . $param['lgmode'] . '"><img src="' . FORMNOTIFY_PLUGIN_URL . 'assets/img/icon-line.svg">' . esc_html( $param['text'] ) . '</a></div>';

		return $r;
	}

}

Button::init();
