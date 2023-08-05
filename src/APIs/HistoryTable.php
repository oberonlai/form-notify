<?php

namespace FORMNOTIFY\APIs;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


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
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_datas( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}form_notify_history";

		// 當週數據
		if ( ! empty( $_GET['date_week_start'] ) && ! empty( $_GET['date_week_end'] ) && empty( $_GET['date_start'] ) ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $_GET['date_week_start'] ) . '" AND notify_time <= "' . esc_sql( $_GET['date_week_end'] ) . '"';
		}

		// 當月數據
		if ( ! empty( $_REQUEST['date_month'] ) && empty( $_REQUEST['date_start'] ) ) {
			$sql .= ' WHERE notify_time LIKE "%' . esc_sql( $_REQUEST['date_month'] ) . '%"';
		}

		// 日期區間
		if ( ! empty( $_REQUEST['date_start'] ) && ! empty( $_REQUEST['date_end'] ) ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $_REQUEST['date_start'] ) . '" AND notify_time <= "' . esc_sql( $_REQUEST['date_end'] ) . '"';
		}

		// 排序
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
			$sql .= ' ORDER BY id DESC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * 取得資料筆數
	 *
	 * @return null|string
	 */
	public function get_total_items() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}form_notify_history";

		// 當週數據
		if ( ! empty( $_REQUEST['date_week_start'] ) && ! empty( $_REQUEST['date_week_end'] ) && empty( $_REQUEST['date_start'] ) ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $_REQUEST['date_week_start'] ) . '" AND notify_time <= "' . esc_sql( $_REQUEST['date_week_end'] ) . '"';
		}

		// 當月數據
		if ( ! empty( $_REQUEST['date_month'] ) && empty( $_REQUEST['date_start'] ) ) {
			$sql .= ' WHERE notify_time LIKE "%' . esc_sql( $_REQUEST['date_month'] ) . '%"';
		}

		// 日期區間
		if ( ! empty( $_REQUEST['date_start'] ) && ! empty( $_REQUEST['date_end'] ) ) {
			$sql .= ' WHERE notify_time >="' . esc_sql( $_REQUEST['date_start'] ) . '" AND notify_time <= "' . esc_sql( $_REQUEST['date_end'] ) . '"';
		}

		return $wpdb->get_var( $sql );
	}

	/**
	 * 沒有資料時的顯示文字
	 */
	public function no_items() {
		echo esc_html( __( 'No Data', 'form-notify' ) );
	}

	/**
	 * 篩選資料按鈕
	 */
	protected function get_views() {
		$date_week = add_query_arg(
			array(
				'date_week_start' => date( 'Y-m-d', time() + ( 1 - date( 'w' ) ) * 24 * 3600 ),
				'date_week_end'   => date( 'Y-m-d', time() + ( 1 - date( 'w' ) ) * 24 * 3600 + 6 * 86400 ),
			),
			'edit.php?post_type=form-notify&page=form-notify-history'
		);

		$date_month = add_query_arg(
			array(
				'date_month' => date( 'Y-m' ),
			),
			'edit.php?post_type=form-notify&page=form-notify-history'
		);

		$status_links = array(
			// Translators: %s: all.
			'all'   => sprintf( __( '<a href="%s">All</a>', 'form-notify' ), admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) ),
			// Translators: %s: week.
			'week'  => sprintf( __( '<a href="%s">Week</a>', 'form-notify' ), $date_week ),
			// Translators: %s: month.
			'month' => sprintf( __( '<a href="%s">Month</a>', 'form-notify' ), $date_month ),
		);

		return $status_links;
	}

	/**
	 * 篩選資料時間區間
	 */
	public function get_datepicker() {
		if ( $this->get_datas() ) { ?>
			<div id="datePicker">
				<input type="date" name="date_start" value="<?php echo ( isset( $_REQUEST['date_start'] ) ) ? $_REQUEST['date_start'] : ''; ?>" placeholder="<?php _e( 'Start Date', 'form-notify' ); ?>"/>
				<input type="date" name="date_end" value="<?php echo ( isset( $_REQUEST['date_end'] ) ) ? $_REQUEST['date_end'] : ''; ?>" placeholder="<?php _e( 'End Date', 'form-notify' ); ?>"/>
				<a href="#" class="button" id="datePickerBtn"><?php _e( 'Filter', 'form-notify' ); ?></a>
				<a href="<?php echo admin_url( 'admin.php?page=form_notify_history' ); ?>" class="button"><?php _e( 'Clear', 'form-notify' ); ?></a>
			</div>
			<?php
		}
	}


	/**
	 * 欄位值設定
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user_id':
				$delete_nonce = wp_create_nonce( 'history_delete' );
				$actions      = array(
					'delete' => sprintf( '<a href="edit.php?post_type=form-notify&page=%s&action=%s&data=%s&_wpnonce=%s">' . __( 'Delete History', 'form-notify' ) . '</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
				);

				return ( '0' != $item['user_id'] ) ? '<a href="' . admin_url( 'user-edit.php?user_id=' . $item['user_id'] ) . '">' . get_userdata( $item['user_id'] )->user_login . '</a>' . $this->row_actions( $actions ) : '<span>-</span>' . $this->row_actions( $actions );
			case 'user_info':
				return $item[ $column_name ];
			case 'order_id':
				return ( '0' != $item[ $column_name ] ) ? '<a href="' . admin_url( 'post.php?post=' . $item[ $column_name ] ) . '&action=edit">#' . $item[ $column_name ] . '</a>' : '-';
			default:
		}

		return $item[ $column_name ];
	}

	/**
	 * 欄位標頭
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'user_info'      => __( 'Trigger Event', 'form-notify' ),
			'notify_type'    => __( 'Notify Type', 'form-notify' ),
			'notify_content' => __( 'Notify Content', 'form-notify' ),
			'status'         => __( 'Status', 'form-notify' ),
			'notify_time'    => __( 'Notify Time', 'form-notify' ),
		);

		return $columns;
	}

	/**
	 * 欄位允許排序
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'notify_time' => array( 'notify_time', 'asc' ),
		);

		return $sortable_columns;
	}

	/**
	 * 批次操作 checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * 批次操作選單
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'form-notify' ),
		);

		return $actions;
	}


	/**
	 * 處理資料顯示
	 */
	public function prepare_items() {

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
	 * @param int $id customer ID
	 */
	public function delete_history( $id ) {
		global $wpdb;
		$wpdb->delete(
			"{$wpdb->prefix}form_notify_history",
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * 處理批次操作
	 */
	public function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			$nonce = esc_attr( $_GET['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'history_delete' ) ) {
				die( '發生錯誤！' );
			} else {
				$this->delete_history( absint( $_GET['data'] ) );
				wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
				exit;
			}
		}

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
			 || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );
			foreach ( $delete_ids as $id ) {
				$this->delete_history( $id );
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=form-notify&page=form-notify-history' ) );
			exit;
		}
	}
}

