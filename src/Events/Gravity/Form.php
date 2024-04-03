<?php
/**
 * Gravity form class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Gravity;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Sms\Every8d;
use FORMNOTIFY\Options\History;

/**
 * Gravity form class
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
		$this->notify = new Notify( 'gravity', __( 'Via Gravity Form', 'form-notify' ) );
		$this->notify->set_trigger_key( 'trigger_form' );
	}

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( in_array( 'gravityforms/gravityforms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$class = new self();
			add_filter( 'form_notify_trigger_event_options', array( $class, 'add_trigger_event_options' ) );
			add_action( 'form_notify_trigger', array( $class, 'add_form_select' ) );
			add_action( 'form_notify_param', array( $class, 'add_form_params' ) );
			add_action( 'gform_after_submission', array( $class, 'push' ), 20, 2 );
			add_action( 'e8d_check_status_gravity_form', array( $class, 'check_sms_status' ), 10, 3 );
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
		$options['by_gravity_form'] = __( 'After Gravity Form submitted', 'form-notify' );

		return $options;
	}

	/**
	 * Add form select
	 *
	 * @param object $trigger Trigger object.
	 */
	public function add_form_select( object $trigger ): void {
		$options = array();
		$forms   = \GFAPI::get_forms(); // phpcs:ignore
		if ( $forms ) {
			foreach ( $forms as $form ) {
				$options[ rgar( $form, 'id' ) ] = rgar( $form, 'title' );
			}
		}

		$trigger->add_select(
			array(
				'id'         => 'form_notify_trigger_form',
				'class'      => 'form-notify-trigger-form',
				'label'      => __( 'Form', 'form-notify' ),
				'desc'       => __( 'Select the form.', 'form-notify' ),
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_gravity_form',
			),
			$options
		);
	}

	/**
	 * Add form params
	 *
	 * @param object $param Options' param.
	 */
	public function add_form_params( object $param ) {
		$forms = \GFAPI::get_forms(); // phpcs:ignore

		if ( ! $forms ) {
			return false;
		}

		$html = '';

		foreach ( $forms as $form ) {
			$form_name = rgar( $form, 'title' );
			$form_id   = rgar( $form, 'id' );
			$fields    = rgar( $form, 'fields' );
			$html     .= '<div class="p:5|10 bg:white form-notify-params-toggle min-h:140">';
			$html     .= '
			<h3 class="rel f:14 border-bottom:1px|solid|#ccc f:#444 pb:10 px:3 cursor:pointer">' . $form_name . '
				<span class="toggle-indicator abs top:0 right:5 pointer-events:none" aria-hidden="true"></span>
			</h3>
			<div>
				<ul class="d:flex flex-wrap:wrap mb:0" data-show-by="form_notify_trigger_form" data-show-value="' . $form_id . '">';
			foreach ( $form['fields'] as $field ) {
				$html .= '
				<li class="mr:10">
					<button class="btn-copy cursor:pointer rel border:0 p:5|8 r:4 bg:#eee bg:#ddd:hover" data-clipboard-text="{{' . $field->label . '-' . $field->id . '}}">' . ucwords( $field->label ) . '
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
				'id'         => 'metabox_fluent_form_field',
				'html'       => '<div class="data-show">' . $html . '</div>',
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_gravity_form',
			),
		);
	}

	/**
	 * Push notify
	 *
	 * @param array $entry entry.
	 * @param array $form  form.
	 *
	 * @return void
	 */
	public function push( array $entry, array $form ): void {
		$notify_ids = $this->notify->get_notify_ids( rgar( $form, 'id' ) );
		$form_data  = array();
		foreach ( $form['fields'] as $field ) {
			$inputs = $field->get_entry_inputs();
			if ( is_array( $inputs ) ) {
				foreach ( $inputs as $input ) {
					if ( preg_match( '/\d+\.\d+$/', $input['id'] ) ) {
						$new_key = (string) explode( '.', $input['id'] )[0];
						if ( key_exists( '{{' . $new_key . '}}', $form_data ) ) {
							$form_data[ '{{' . $new_key . '}}' ] .= rgar( $entry, (string) $input['id'] ) . ' ';
						} else {
							$form_data[ '{{' . $new_key . '}}' ] = rgar( $entry, (string) $input['id'] ) . ' ';
						}
					} else {
						$form_data[ '{{' . $input['id'] . '}}' ] = rgar( $entry, (string) $input['id'] );
					}
				}
			} else {
				$form_data[ '{{' . $field->id . '}}' ] = rgar( $entry, (string) $field->id );
			}
		}
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


