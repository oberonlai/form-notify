<?php

use ODS\Updater;

/**
 * WordPress Form Notify
 *
 * @link              https://oberonlai.blog
 * @since             0.0.0
 * @package           Form-Notify
 *
 * @wordpress-plugin
 * Plugin Name:       FormNotify
 * Plugin URI:        https://oberonlai.blog/form-notify
 * Description:       Notification for WordPress form plugins.
 * Version:           1.1.02
 * Author:            Daily WPdev.
 * Author URI:        https://oberonlai.blog
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       form-notify
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'FORMNOTIFY_VERSION', '1.1.02' );
define( 'FORMNOTIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FORMNOTIFY_PLUGIN_FILE', __FILE__ );

require_once FORMNOTIFY_PLUGIN_DIR . 'vendor/autoload.php';
\A7\autoload( FORMNOTIFY_PLUGIN_DIR . 'src' );


/**
 * I18n
 */
function formnotify_load_plugin_i18n(): void {
	load_plugin_textdomain( 'form-notify', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugin_loaded', 'formnotify_load_plugin_i18n' );

/**
 * Get params from url
 *
 * @param string $key key name.
 *
 * @return string|null
 */
function formnotify_get_params( string $key ): string|null {
	$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
	parse_str( $query_string, $params );

	return isset( $params[ $key ] ) ? $params[ $key ] : '';
}

/**
 * Wpack Enqueue
 */
add_action(
	'admin_init',
	function () {
		global $pagenow;
		$current_post = formnotify_get_params( 'post' );
		$current_page = formnotify_get_params( 'page' );
		$post_type    = formnotify_get_params( 'post_type' );

		if ( ( $current_post && 'post.php' === $pagenow ) || ( $current_post && 'shop_order' === get_post_type( $current_post ) ) || ( $current_post && 'form-notify' === get_post_type( $current_post ) ) || ( $post_type && 'form-notify' === $post_type && 'post-new.php' === $pagenow ) || ( $current_page && 'form-notify-setting' === $current_page ) ) {
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
		wp_enqueue_script( 'alpine', FORMNOTIFY_PLUGIN_URL . 'assets/src/alpine.js', array( 'jquery' ), '1.0.0', true );
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


add_action(
	'init',
	function () {
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
);
