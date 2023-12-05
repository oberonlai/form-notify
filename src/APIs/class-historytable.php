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
	public function get_datas( int $per_page = 5, int $page_number = 1 ): array {

		global $wpdb;

		$table_name = $wpdb->prefix . 'form_notify_history';

		$sql = 'SELECT * FROM ' . $table_name;

		$sql .= $this->sql_query();

		// orderby.
		if ( ! empty( form_notify_get_params( 'orderby' ) ) ) {
			$orderby = ' ORDER BY ' . esc_sql( form_notify_get_params( 'orderby' ) );
			$order   = ! empty( form_notify_get_params( 'order' ) ) ? ' ' . esc_sql( form_notify_get_params( 'order' ) ) : ' ASC';
			$sql    .= $wpdb->prepare( ' ORDER BY %s %s', $orderby, $order );
		} else {
			$sql .= ' ORDER BY id DESC';
		}

		$sql .= $wpdb->prepare( ' LIMIT %d', $per_page );
		$sql .= $wpdb->prepare( ' OFFSET %d', ( $page_number - 1 ) * $per_page );

		$cache = wp_cache_get( 'form_notify_history' );

		if ( ! $cache ) {
			// $sql   = "SELECT * FROM {$wpdb->prefix}form_notify_history ORDER BY id DESC LIMIT %d OFFSET 0";
			// $cache = $wpdb->get_results( $wpdb->prepare( $sql, $per_page ), 'ARRAY_A' );
			$cache = $wpdb->get_results( $sql, 'ARRAY_A' );
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

		$sql .= $this->sql_query();

		$cache = wp_cache_get( 'form_notify_history_total' );

		if ( ! $cache ) {
			$cache = $wpdb->get_var( $sql );
			wp_cache_set( 'form_notify_history_total', $cache );
		}

		return $cache;
	}

	/**
	 * SQL Query
	 *
	 * @return string
	 */
	private function sql_query(): string {
		$sql = '';

		// query for this week.
		$date_week_start = form_notify_get_params( 'date_week_start' );
		$date_week_end   = form_notify_get_params( 'date_week_end' );
		if ( $date_week_start && $date_week_end ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $date_week_start ) . '" AND notify_time <= "' . esc_sql( $date_week_end ) . '"';
		}

		// query for this month.
		$date_month = form_notify_get_params( 'date_month' );
		if ( $date_month ) {
			$sql .= ' WHERE notify_time LIKE "%' . esc_sql( $date_month ) . '%"';
		}

		// query for date range.
		$date_start = form_notify_get_params( 'date_start' );
		$date_end   = form_notify_get_params( 'date_end' );
		if ( $date_start && $date_end ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $date_start ) . '" AND notify_time <= "' . esc_sql( $date_end ) . '"';
		}

		return $sql;
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
		$date_start = form_notify_get_params( 'date_start' );
		$date_end   = form_notify_get_params( 'date_end' );
		if ( 'top' === $which ) :
			?>
			<div class="alignleft actions">
				<label class="filter-by-date"><?php echo esc_html( __( 'Notify Date', 'form-notify' ) ); ?></label>
				<input type="date" value="<?php echo esc_attr( $date_start ? $date_start : '' ); ?>" name="date_start">
				<label class="filter-by-date">～</label>
				<input type="date" value="<?php echo esc_attr( ( $date_end ) ? $date_end : '' ); ?>" name="date_end">
				<input type="submit" value="篩選" class="button">
				<input type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'form-notify' ) ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?edit.php?post_type=form-notify&page=form-notify-history' ) ); ?>" class="button">重設</a>
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
		switch ( $column_name ) {
			case 'user_id':
				$delete_nonce = wp_create_nonce( 'history_delete' );
				$actions      = array(
					'delete' => sprintf( '<a href="edit.php?post_type=form-notify&page=%s&action=%s&data=%s&_wpnonce=%s">' . __( 'Delete History', 'form-notify' ) . '</a>', esc_attr( form_notify_get_params( 'page' ) ), 'delete', absint( $item['id'] ), $delete_nonce ),
				);

				return ( '0' !== $item['user_id'] ) ? '<a href="' . admin_url( 'user-edit.php?user_id=' . $item['user_id'] ) . '">' . get_userdata( $item['user_id'] )->user_login . '</a>' . $this->row_actions( $actions ) : '<span>-</span>' . $this->row_actions( $actions );
			case 'user_info':
				return $item[ $column_name ];
			case 'order_id':
				return ( '0' !== $item[ $column_name ] ) ? '<a href="' . admin_url( 'post.php?post=' . $item[ $column_name ] ) . '&action=edit">#' . $item[ $column_name ] . '</a>' : '-';
			default:
		}

		return $item[ $column_name ];
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
		$wpdb->delete(
			"{$wpdb->prefix}form_notify_history",
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * Process bulk action
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'delete' === $this->current_action() ) {
			$nonce = form_notify_get_params( '_wpnonce' );
			$data  = form_notify_get_params( 'data' );

			if ( ! wp_verify_nonce( $nonce, 'history_delete' ) ) {
				die( '發生錯誤！' );
			} else {
				$this->delete_history( absint( $data ) );
				wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
				exit;
			}
		}

		if ( ( isset( $_POST['action'] ) && 'bulk-delete' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'bulk-delete' === $_POST['action2'] ) ) {

			if ( ! isset( $_POST['bulk-delete'] ) ) {
				exit;
			};

			$delete_ids = wp_unslash( $_POST['bulk-delete'] );
			foreach ( $delete_ids as $id ) {
				$this->delete_history( $id );
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
			exit;
		}
	}
}

