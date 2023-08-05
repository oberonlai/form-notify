<?php

/**
 * WordPress Form Notify
 *
 * @link              https://oberonlai.blog
 * @since             0.0.0
 * @package           Form-Notify
 *
 * @wordpress-plugin
 * Plugin Name:       Form Notify
 * Plugin URI:        https://oberonlai.blog
 * Description:       Notification for WordPress form plugins.
 * Version:           1.0.0
 * Author:            Oberon Lai
 * Author URI:        https://oberonlai.blog
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       form-notify
 * Domain Path:       /languages
 *
 * WC requires at least: 5.0
 * WC tested up to: 5.7.1
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMNOTIFY_VERSION', '1.0.0' );
define( 'FORMNOTIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_FILE', __FILE__ );

require_once FORMNOTIFY_PLUGIN_DIR . 'vendor/autoload.php';
\A7\autoload( FORMNOTIFY_PLUGIN_DIR . 'src' );


/**
 * I18n
 */
function form_notify_load_plugin_i18n() {
	load_plugin_textdomain( 'form-notify', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugin_loaded', 'form_notify_load_plugin_i18n' );

/**
 * Wpack Enqueue
 */
add_action(
	'admin_init',
	function () {
		global $pagenow;
		if ( ( isset( $_GET['post'] ) && 'post.php' === $pagenow ) ||
			 ( isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] ) ) ||
			 ( isset( $_GET['post'] ) && 'form-notify' === get_post_type( $_GET['post'] ) ) ||
			 ( isset( $_GET['post_type'] ) && 'form-notify' === $_GET['post_type'] && 'post-new.php' === $pagenow ) ||
			 ( isset( $_GET['page'] ) && 'form-notify-setting' === $_GET['page'] ) ) {
			$enqueue = new \WPackio\Enqueue( 'formNotify', 'assets/dist', '1.0.0', 'plugin', __FILE__ );
			$enqueue->enqueue( 'app', 'admin', array() );
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$enqueue = new \WPackio\Enqueue( 'formNotify', 'assets/dist', '1.0.0', 'plugin', __FILE__ );
		$enqueue->enqueue( 'app', 'public', array( 'in_footer' => false ) );
	}
);

add_action(
	'admin_enqueue_scripts',
	function () {
		wp_enqueue_script( 'alpine', 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js', array(), '3.1.0', true );
	},
);

add_filter(
	'script_loader_tag',
	function ( $tag, $handle ) {
		if ( strpos( $handle, 'alpine' ) === false ) {
			return $tag;
		}

		return str_replace( ' src', ' defer src', $tag );
	},
	10,
	2
);


function form_notify_line_button_enqueue_scripts() {
	$enqueue = new \WPackio\Enqueue( 'formNotify', 'assets/dist', '1.0.0', 'plugin', __FILE__ );

	$assets = $enqueue->enqueue(
		'blocks',
		'lineLoginButton',
		array(
			'js_dep' => array( 'wp-blocks', 'wp-components', 'wp-editor', 'wp-i18n', 'wp-data' ),
		)
	);

	wp_localize_script(
		array_pop( $assets['js'] )['handle'],
		'lineLoginButtonParams',
		array(
			'buttonIconUrl' => FORMNOTIFY_PLUGIN_URL . 'assets/img/dashicon.png',
		)
	);

}

add_action( 'init', 'form_notify_line_button_enqueue_scripts' );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
