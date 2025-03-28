<?php
/**
 * History
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Options;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\HistoryTable;

/**
 * History class
 */
class History {
	/**
	 * List Object
	 *
	 * @var object $list_obj
	 */
	public object $list_obj;

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		$class = new self();
		add_action( 'admin_menu', array( $class, 'plugin_menu' ), 10 );
		add_filter( 'set-screen-option', array( $class, 'set_screen' ), 10, 3 );
		register_activation_hook( FORMNOTIFY_PLUGIN_FILE, array( $class, 'create_table' ) );
	}

	/**
	 * Create history table menu
	 *
	 * @return void
	 */
	public function plugin_menu(): void {
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
									<form method="get" action="<?php echo esc_html( admin_url() ); ?>admin.php?edit_php?post_type=form-notify&page=form-notify-history">
										<input type="hidden" name="page" value="form-notify-history"/>
										<?php
										$this->list_obj->views();
										$this->list_obj->prepare_items();
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
	 * Screen option
	 *
	 * @return void
	 */
	public function screen_option(): void {
		$args = array(
			'label'   => __( 'Posts Per Page', 'form-notify' ),
			'default' => 20,
			'option'  => 'items_per_page',
		);
		add_screen_option( 'per_page', $args );
		$this->list_obj = new HistoryTable();
	}

	/**
	 * Create history table
	 */
	public function create_table(): void {
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
	 * Set Screen
	 *
	 * @param string $status status.
	 * @param string $option option.
	 * @param string $value  value.
	 *
	 * @return string $value
	 */
	public function set_screen( string $status, string $option, string $value ): string {
		return $value;
	}

	/**
	 * Insert data
	 *
	 * @param int|string $user_id        user id.
	 * @param string     $user_info      user info.
	 * @param int        $order_id       order id.
	 * @param string     $notify_type    notify type.
	 * @param string     $notify_content notify content.
	 * @param string     $status         status.
	 *
	 * @return int insert_id
	 */
	public static function insert( int|string $user_id, string $user_info, int $order_id, string $notify_type, string $notify_content, $status ): int {
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

	/**
	 * Update data
	 *
	 * @param int    $history_id history id.
	 * @param string $status     status.
	 *
	 * @return void
	 */
	public static function update( int $history_id, string $status ): void {
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
	public static function select( string $keyword, string $field ): int {
		global $wpdb;
		$table_name = $wpdb->prefix . 'form_notify_history';
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT ID FROM %s WHERE %s LIKE %s',
				$table_name,
				$field,
				'%' . $wpdb->esc_like( $keyword ) . '%'
			)
		);
		if ( $results ) {
			foreach ( $results as $result ) {
				return $result->ID;
			}
		}

		return 0;
	}
}

History::init();
