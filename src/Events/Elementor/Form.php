<?php
/**
 * Elementor Form class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Elementor;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Sms\Every8d;
use FORMNOTIFY\Options\History;

/**
 * Form class
 */
class Form {

	/**
	 * Notify object
	 *
	 * @var object $notify Notify object.
	 */
	private object $notify;

	/**
	 * Construct
	 */
	public function __construct() {
		$this->notify = new Notify( 'elementor', __( 'Via Elementor Form', 'form-notify' ) );
		$this->notify->set_trigger_key( 'trigger_form_elementor' );
	}

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( in_array( 'elementor-pro/elementor-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$class = new self();
			add_filter( 'form_notify_trigger_event_options', array( $class, 'add_trigger_event_options' ) );
			add_action( 'form_notify_trigger', array( $class, 'add_form_select' ) );
			add_action( 'form_notify_param', array( $class, 'add_form_params' ) );
			add_action( 'elementor_pro/forms/new_record', array( $class, 'push' ), 20, 2 );
			add_action( 'e8d_check_status_fluent_form', array( $class, 'check_sms_status' ), 10, 3 );
		}
	}

	/**
	 * Add trigger event options
	 *
	 * @param array $options event options.
	 *
	 * @return array $options event options.
	 */
	public function add_trigger_event_options( array $options ): array {
		$options['by_elementor_form'] = __( 'After Elementor Form submitted', 'form-notify' );

		return $options;
	}

	/**
	 * Add form select
	 *
	 * @param object $trigger Trigger object.
	 */
	public function add_form_select( object $trigger ): void {
		$trigger->add_select(
			array(
				'id'         => 'form_notify_trigger_form_elementor',
				'class'      => 'form-notify-trigger-form',
				'label'      => __( 'Form', 'form-notify' ),
				'desc'       => __( 'Select the form.', 'form-notify' ),
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_elementor_form',
			),
			$this->get_forms( 'options' )
		);
	}

	/**
	 * Add form params
	 *
	 * @param object $param Options' param.
	 */
	public function add_form_params( object $param ) {
		$forms = $this->get_forms( 'params' );

		if ( ! $forms ) {
			return false;
		}

		$html = '';

		foreach ( $forms as $form ) {
			$form_name = $form['name'];
			$labels    = $form['fields'];
			$html     .= '<div class="p:5|10 bg:white form-notify-params-toggle min-h:140">';
			$html     .= '
			<h3 class="rel f:14 border-bottom:1px|solid|#ccc f:#444 pb:10 px:3 cursor:pointer">' . $form_name . '
				<span class="toggle-indicator abs top:0 right:5 pointer-events:none" aria-hidden="true"></span>
			</h3>
			<div>
				<ul class="d:flex flex-wrap:wrap mb:0" data-show-by="form_notify_trigger_form_elementor" data-show-value="' . $form_name . '">';
			foreach ( $labels as $label ) {
				$html .= '
				 <li class="mr:10">
				 <button class="btn-copy cursor:pointer rel border:0 p:5|8 r:4 bg:#eee bg:#ddd:hover" data-clipboard-text="{{' . $label['custom_id'] . '}}">' . ucwords( str_replace( '_', ' ', $label['label'] ) ) . '
				 <span class="opacity:0 pointer-events:none abs bottom:-20 bg:#2271b1 f:white p:3|5 f:12 r:2 left:50% translate(-50%,0) z:10 white-space:nowrap">' . __( 'Copied!', 'form-notify' ) . '
				 <i class="abs bottom:18 left:50% translate(-50%,0) w:0 h:0 border-style:solid; border-width:0|8px|10px|8px border-color:transparent|transparent|#2271b1|transparent z:5"></i>
				 </span>
				 </button>
				 </li>';
			}
			$html .= '</ul></div>';
			$html .= '</div>';
		}

		$param->add_html(
			array(
				'id'         => 'metabox_elementor_form_field',
				'html'       => '<div class="data-show">' . $html . '</div>',
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_elementor_form',
			),
		);
	}

	/**
	 * Get forms
	 *
	 * @param string $type options or params.
	 *
	 * @return array $options or $params.
	 */
	private function get_forms( string $type ): array {
		$options   = array();
		$params    = array();
		$args_post = array(
			'posts_per_page' => '100',
			'post_type'      => 'any',
			'meta_key'       => '_elementor_data', // phpcs:ignore.
			'meta_value'     => 'form_fields', // phpcs:ignore.
			'meta_compare'   => 'LIKE',
		);

		$query_post = new \WP_Query( $args_post );
		if ( $query_post->have_posts() ) {
			while ( $query_post->have_posts() ) {
				$query_post->the_post();
				$ele_datas = get_post_meta( get_the_ID(), '_elementor_data' );
				if ( is_array( $ele_datas ) ) {
					foreach ( $ele_datas as $data ) {
						foreach ( json_decode( $data ) as $i ) {
							foreach ( $i->elements as $j ) {
								foreach ( $j->elements as $k ) {
									if ( 'options' === $type && is_object( $k->settings ) && property_exists( $k->settings, 'form_name' ) ) {
										$options[ $k->settings->form_name ] = $k->settings->form_name;
									}

									if ( 'params' === $type && is_object( $k->settings ) && property_exists( $k->settings, 'form_fields' ) ) {
										$fields = $k->settings->form_fields;
										$data   = array(
											'name' => $k->settings->form_name,
										);
										foreach ( $fields as $field ) {
											$data['fields'][] = array(
												'custom_id' => $field->custom_id,
												'label' => ( property_exists( $field, 'field_label' ) ) ? $field->field_label : $field->custom_id,
											);
										}
										$params[] = $data;
									}

									// 內部段.
									if ( $k->elements ) {
										foreach ( $k->elements as $l ) {
											foreach ( $l->elements as $m ) {
												if ( 'options' === $type && is_object( $m->settings ) && property_exists( $m->settings, 'form_name' ) ) {
													$options[ $m->settings->form_name ] = $m->settings->form_name;
												}

												if ( 'params' === $type && is_object( $m->settings ) && property_exists( $m->settings, 'form_fields' ) ) {
													$fields = $m->settings->form_fields;
													$data   = array(
														'name' => $m->settings->form_name,
													);
													foreach ( $fields as $field ) {
														$data['fields'][] = array(
															'custom_id' => $field->custom_id,
															'label'     => ( property_exists( $field, 'field_label' ) ) ? $field->field_label : $field->custom_id,
														);
													}
													$params[] = $data;
												}
											}
										}
									}
								}
							}
						}
					}
				}
			};
			wp_reset_postdata();
		}

		return ( 'options' === $type ) ? $options : $params;
	}

	/**
	 * Push
	 *
	 * @param object $record  Record object.
	 * @param object $handler Handler object.
	 */
	public function push( object $record, object $handler ): void {
		$form_name  = $record->get_form_settings( 'form_name' );
		$raw_fields = $record->get( 'fields' );
		$form_data  = array();
		foreach ( $raw_fields as $id => $field ) {
			$form_data[ $id ] = $field['value'];
		}

		$notify_ids = $this->notify->get_notify_ids( $form_name );
		$this->notify->send_notify( $notify_ids, $form_data );
	}

	/**
	 * 發送狀態查詢
	 *
	 * @param string $phone      手機號碼.
	 * @param string $batch_id   批次編號.
	 * @param string $history_id 紀錄編號.
	 */
	public function check_sms_status( string $phone, string $batch_id, string $history_id ): void {
		$sdk    = new Every8d();
		$result = $sdk->check_status( $batch_id );

		if ( $result ) {
			History::update( $history_id, $result );
		}
	}
}

Form::init();


