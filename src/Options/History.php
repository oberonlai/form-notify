<?php

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\HistoryTable;

class History {

	public $list_obj;

	public static function init() {
		$class = new self();
		add_action( 'admin_menu', array( $class, 'plugin_menu' ), 10 );
		add_filter( 'set-screen-option', array( $class, 'set_screen' ), 10, 3 );
		register_activation_hook( FORMNOTIFY_PLUGIN_FILE, array( $class, 'create_table' ) );
	}

	public function plugin_menu() {
		$hook = add_submenu_page(
			'edit.php?post_type=form-notify',
			__( 'History', 'form-notify' ),
			__( 'History', 'form-notify' ),
			'manage_options',
			'form-notify-history',
			function () { ?>
				<div class="wrap">
					<h2><?php echo esc_html( __( 'Notify History', 'form-notify' ) ); ?></h2>
					<div id="poststuff">
						<div id="post-body" class="metabox-holder">
							<div id="post-body-content">
								<div class="meta-box-sortables ui-sortable">
									<form method="post">
										<?php
										$this->list_obj->views();
										$this->list_obj->prepare_items();
										// $this->list_obj->get_datepicker();
										$this->list_obj->display();
										?>
									</form>
								</div>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>
				<?php
			},
			10
		);
		add_action( "load-$hook", array( $this, 'screen_option' ) );
	}

	/**
	 * 顯示項目設定
	 */
	public function screen_option() {
		$args = array(
			'label'   => __( 'Posts Per Page', 'form-notify' ),
			'default' => 20,
			'option'  => 'items_per_page',
		);
		add_screen_option( 'per_page', $args );
		$this->list_obj = new HistoryTable();
	}

	/**
	 * 啟用外掛時新增資料表
	 */
	public function create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'form_notify_history';

		$sql = "CREATE TABLE $table_name (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`user_id` INT,
			`user_info` VARCHAR(255) CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate},
			`order_id` INT,
			`notify_type` VARCHAR(255) CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate},
			`notify_content` LONGTEXT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate},
			`notify_time` DATETIME,
			`status` VARCHAR(255) CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}
		);
		DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		update_option( 'form_notify_db_version', FORMNOTIFY_VERSION );
	}

	/**
	 * 顯示項目設定值套用
	 */
	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	public static function insert( $user_id, $user_info, $order_id, $notify_type, $notify_content, $status ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'form_notify_history',
			array(
				'user_id'        => $user_id,
				'user_info'      => $user_info,
				'order_id'       => $order_id,
				'notify_type'    => $notify_type,
				'notify_content' => $notify_content,
				'notify_time'    => current_time( 'Y-m-d H:i:s' ),
				'status'         => $status,
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;

	}

	public static function update( $history_id, $status ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'form_notify_history',
			array(
				'status' => $status,
			),
			array(
				'id' => $history_id,
			)
		);
	}

	/**
	 * Select data
	 *
	 * @param string $keyword search keyword.
	 * @param string $field   db field.
	 *
	 * @return int $post_id Post id.
	 */
	public static function select( $keyword, $field ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'form_notify_history';
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$table_name} WHERE {$field} LIKE %s",
				'%' . $wpdb->esc_like( $keyword ) . '%'
			)
		);
		if ( $results ) {
			foreach ( $results as $result ) {
				return $result->ID;
			}
		}
	}
}

History::init();
