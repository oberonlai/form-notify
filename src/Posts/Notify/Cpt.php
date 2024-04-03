<?php
/**
 * Register Notify post type
 *
 * @package FormNotify
 */

namespace FORMNOTIFY\Posts\Notify;

defined( 'ABSPATH' ) || exit;

use PostTypes\PostType;
use PostTypes\Taxonomy;

/**
 * Register Notify post type
 */
class Cpt {
	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'plugins_loaded', array( $class, 'init' ) );
	}

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public function init(): void {

		$name = array(
			'name'   => 'form-notify',
			'plural' => __( 'Form Notify', 'form-notify' ),
			'slug'   => 'form-notify',
		);

		$options = array(
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-format-chat',
			'supports'      => array( 'title', 'revisions' ),
			'menu_position' => 56,
		);

		$notify = new PostType( $name, $options );
		$notify->register();

		$labels = array(
			'all_items'          => __( 'All items', 'form-notify' ),
			'add_new'            => __( 'Add new', 'form-notify' ),
			'add_new_item'       => __( 'Add new item', 'form-notify' ),
			'edit_item'          => __( 'Edit item', 'form-notify' ),
			'new_item'           => __( 'New item', 'form-notify' ),
			'view_item'          => __( 'View item', 'form-notify' ),
			'view_items'         => __( 'View items', 'form-notify' ),
			'search_items'       => __( 'Search items', 'form-notify' ),
			'not_found'          => __( 'Not found', 'form-notify' ),
			'not_found_in_trash' => __( 'Not found in trash', 'form-notify' ),
		);

		$notify->labels( $labels );

		$name = array(
			'name'   => 'form-notify-genre',
			'plural' => __( 'Notify Genre', 'form-notify' ),
			'slug'   => 'form-notify-genre',
		);

		$options = array(
			'hierarchical' => true,
		);

		$genres = new Taxonomy( $name, $options );
		$genres->posttype( 'form-notify' );
		$genres->labels( $labels );
		$genres->register();

	}

}

Cpt::register();
