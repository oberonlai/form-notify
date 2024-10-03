<?php
/**
 * History Table
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\APIs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * History Table class
 */
class HistoryTable extends \WP_List_Table {

	/**
	 * 建構式
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Notify History', 'form-notify' ),
				'plural'   => __( 'Notify History', 'form-notify' ),
				'ajax'     => true,
			)
		);
	}

	/**
	 * 資料 Query
	 *
	 * @param int $per_page    per page.
	 * @param int $page_number page number.
	 *
	 * @return array
	 */
	public function get_datas( $per_page = 5, $page_number = 1 ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . 'form_notify_history';

		$sql = "SELECT * FROM {$table_name}";

		list( $sql_conditions, $query_params ) = $this->sql_query();

		$sql .= $sql_conditions;

		$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, ( $page_number - 1 ) * $per_page );

		$cache = wp_cache_get( 'form_notify_history' );

		if ( ! $cache ) {
			// @codingStandardsIgnoreStart
			$cache = $wpdb->get_results( $wpdb->prepare( $sql, ...$query_params ), 'ARRAY_A' );
			// @codingStandardsIgnoreEnd
			wp_cache_set( 'form_notify_history', $cache );
		}

		return $cache;
	}

	/**
	 * Get total items
	 *
	 * @return int
	 */
	public function get_total_items(): int {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}form_notify_history";

		list( $sql_conditions, $query_params ) = $this->sql_query();

		$sql .= $sql_conditions;

		$cache = wp_cache_get( 'form_notify_history_total' );

		if ( ! $cache ) {
			// @codingStandardsIgnoreStart
			$cache = $wpdb->get_var( $wpdb->prepare( $sql, ...$query_params ) );
			// @codingStandardsIgnoreEnd
			wp_cache_set( 'form_notify_history_total', $cache );
		}

		return $cache;
	}

	/**
	 * SQL Query
	 *
	 * @return array
	 */
	private function sql_query(): array {
		global $wpdb;

		$sql    = '';
		$params = array();

		// Query for this week.
		$date_week_start = formnotify_get_params( 'date_week_start' );
		$date_week_end   = formnotify_get_params( 'date_week_end' );
		if ( $date_week_start && $date_week_end ) {
			$sql     .= ' WHERE notify_time >= %s AND notify_time <= %s';
			$params[] = $date_week_start;
			$params[] = $date_week_end;
		}

		// Query for this month.
		$date_month = formnotify_get_params( 'date_month' );
		if ( $date_month ) {
			// If there's already a WHERE clause, use AND instead of WHERE.
			$sql     .= ( empty( $sql ) ? ' WHERE ' : ' AND ' );
			$sql     .= ' notify_time LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $date_month ) . '%';
		}

		// Query for date range.
		$date_start = formnotify_get_params( 'date_start' );
		$date_end   = formnotify_get_params( 'date_end' );
		if ( $date_start && $date_end ) {
			// If there's already a WHERE clause, use AND instead of WHERE.
			$sql     .= ( empty( $sql ) ? ' WHERE ' : ' AND ' );
			$sql     .= ' notify_time >= %s AND notify_time <= %s';
			$params[] = $date_start . ' 00:00:00';
			$params[] = $date_end . ' 23:59:59';
		}

		// Return the SQL condition and the parameters to bind.
		return array( $sql, $params );
	}

	/**
	 * 沒有資料時的顯示文字
	 */
	public function no_items(): void {
		echo esc_html( __( 'No Data', 'form-notify' ) );
	}

	/**
	 * 篩選資料按鈕
	 */
	protected function get_views(): array {
		$date_week = add_query_arg(
			array(
				'date_week_start' => gmdate( 'Y-m-d', time() + ( 1 - gmdate( 'w' ) ) * 24 * 3600 ),
				'date_week_end'   => gmdate( 'Y-m-d', time() + ( 1 - gmdate( 'w' ) ) * 24 * 3600 + 6 * 86400 ),
			),
			'edit.php?post_type=form-notify&page=form-notify-history'
		);

		$date_month = add_query_arg(
			array(
				'date_month' => gmdate( 'Y-m' ),
			),
			'edit.php?post_type=form-notify&page=form-notify-history'
		);

		return array(
			// Translators: %s: all.
			'all'   => sprintf( __( '<a href="%s">All</a>', 'form-notify' ), admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) ),
			// Translators: %s: week.
			'week'  => sprintf( __( '<a href="%s">Week</a>', 'form-notify' ), $date_week ),
			// Translators: %s: month.
			'month' => sprintf( __( '<a href="%s">Month</a>', 'form-notify' ), $date_month ),
		);
	}

	/**
	 * 篩選資料時間區間
	 */
	/**
	 * 新增額外搜尋列
	 *
	 * @param string $which 搜尋列位置.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ): void {
		$date_start = formnotify_get_params( 'date_start' );
		$date_end   = formnotify_get_params( 'date_end' );
		if ( 'top' === $which ) :
			?>
			<div class="alignleft actions">
				<label class="filter-by-date"><?php echo esc_html( __( 'Notify Date', 'form-notify' ) ); ?></label>
				<input type="date" value="<?php echo esc_attr( $date_start ? $date_start : '' ); ?>" name="date_start">
				<label class="filter-by-date">～</label>
				<input type="date" value="<?php echo esc_attr( ( $date_end ) ? $date_end : '' ); ?>" name="date_end">
				<input type="submit" value="篩選" class="button">
				<input type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'form-notify' ) ); ?>">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) ); ?>" class="button">重設</a>
			</div>
			<?php
		endif;
	}

	/**
	 * Column output
	 *
	 * @param array  $item        item.
	 * @param string $column_name column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ): mixed {
		return match ( $column_name ) {
			'user_info' => $this->get_column_name( (array) $item ),
			default => $item[ $column_name ],
		};
	}

	/**
	 * Get column name value
	 *
	 * @param array $item item data.
	 */
	private function get_column_name( array $item ): string {
		$delete_nonce = wp_create_nonce( 'history_delete' );
		$actions      = $this->get_column_action( (array) $item, $delete_nonce );

		return $item['user_info'] . $this->row_actions( $actions );
	}

	/**
	 * Get column action
	 *
	 * @param array  $item         item data.
	 * @param string $delete_nonce delete nonce.
	 */
	private function get_column_action( array $item, string $delete_nonce ): array {
		$action['delete'] = sprintf(
			'<a href="' . admin_url() . 'edit.php?post_type=form-notify&page=%1$s&action=%2$s&id=%3$s&_wpnonce=%4$s">' . __( 'delete', 'form-notify' ) . '</a>',
			'form-notify-history',
			'delete',
			$item['id'],
			$delete_nonce
		);

		return $action;
	}

	/**
	 * Column title
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'             => '<input type="checkbox" />',
			'user_info'      => __( 'Trigger Event', 'form-notify' ),
			'notify_type'    => __( 'Notify Type', 'form-notify' ),
			'notify_content' => __( 'Notify Content', 'form-notify' ),
			'status'         => __( 'Status', 'form-notify' ),
			'notify_time'    => __( 'Notify Time', 'form-notify' ),
		);
	}

	/**
	 * Column sort
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'notify_time' => array( 'notify_time', 'asc' ),
		);
	}

	/**
	 * Column checkbox
	 *
	 * @param array $item item.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'bulk-delete' => __( 'Delete', 'form-notify' ),
		);
	}


	/**
	 * Prepare items
	 *
	 * @return void
	 */
	public function prepare_items(): void {

		$this->_column_headers = $this->get_column_info();
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'items_per_page', 20 );
		$current_page = $this->get_pagenum();

		$this->set_pagination_args(
			array(
				'total_items' => $this->get_total_items(),
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->get_datas( $per_page, $current_page );
	}

	/**
	 * Delete a record.
	 *
	 * @param int $id customer ID.
	 *
	 * @return void
	 */
	public function delete_history( int $id ): void {
		global $wpdb;
		// @codingStandardsIgnoreStart
		$wpdb->delete(
			"{$wpdb->prefix}form_notify_history",
			array( 'id' => $id ),
			array( '%d' )
		);
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Process bulk action
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'delete' === $this->current_action() ) {
			$nonce = formnotify_get_params( '_wpnonce' );
			$data  = formnotify_get_params( 'id' );

			if ( ! wp_verify_nonce( $nonce, 'history_delete' ) ) {
				die( '發生錯誤！' );
			} else {
				$this->delete_history( absint( $data ) );
				wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
				exit;
			}
		}

		$action  = formnotify_get_params( 'action' );
		$action2 = formnotify_get_params( 'action2' );

		$bulk = isset( $_GET['bulk-delete'] ) ? sanitize_text_field( wp_unslash( $_GET['bulk-delete'] ) ) : array();

		$sanitized_bulk = array_map( 'sanitize_text_field', $bulk );

		if ( 'bulk-delete' === $action || 'bulk-delete' === $action2 ) {
			$delete_ids = esc_sql( $sanitized_bulk );
			foreach ( $delete_ids as $id ) {
				$this->delete_history( $id );
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
			exit;
		}
	}
}

