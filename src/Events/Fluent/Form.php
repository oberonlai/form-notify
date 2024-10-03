<?php
/**
 * Fluent Form class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events\Fluent;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Sms\Every8d;
use FORMNOTIFY\Options\History;

/**
 * Fluent Form class
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
		$this->notify = new Notify( 'fluent', __( 'Via Fluent Form', 'form-notify' ) );
		$this->notify->set_trigger_key( 'trigger_form_fluent' );
	}

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		if ( in_array( 'fluentform/fluentform.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			$class = new self();
			add_filter( 'form_notify_trigger_event_options', array( $class, 'add_trigger_event_options' ) );
			add_action( 'form_notify_trigger', array( $class, 'add_form_select' ) );
			add_action( 'form_notify_param', array( $class, 'add_form_params' ) );
			add_filter( 'form_notify_action_module', array( $class, 'add_form_action_module' ), 99, 2 );
			add_action( 'fluentform_submission_inserted', array( $class, 'push' ), 20, 3 );
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
	public function add_trigger_event_options( $options ) {
		$options['by_fluent_form'] = __( 'After Fluent Form submitted', 'form-notify' );

		return $options;
	}

	/**
	 * Add form select
	 *
	 * @param object $trigger Trigger object.
	 */
	public function add_form_select( object $trigger ): void {
		$options = array();
		$forms   = $this->get_forms();
		if ( $forms ) {
			foreach ( $forms as $form ) {
				$options[ $form->id ] = $form->title;
			}
		}

		$trigger->add_select(
			array(
				'id'         => 'form_notify_trigger_form_fluent',
				'class'      => 'form-notify-trigger-form',
				'label'      => __( 'Form', 'form-notify' ),
				'desc'       => __( 'Select the form.', 'form-notify' ),
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_fluent_form',
			),
			$options
		);
	}

	/**
	 * Add form params
	 *
	 * @param object $param Options param.
	 */
	public function add_form_params( object $param ) {
		$forms = $this->get_forms();

		if ( ! $forms ) {
			return false;
		}

		$has_booking  = false;
		$booking_args = array(
			'timezone'   => __( 'Booking timezone', 'form-notify' ),
			'duration'   => __( 'Booking duration', 'form-notify' ),
			'start_time' => __( 'Booking start time', 'form-notify' ),
			'address'    => __( 'Booking address', 'form-notify' ),
			'end_time'   => __( 'Booking end time', 'form-notify' ),
		);

		$html = '';

		foreach ( $forms as $form ) {
			$form_api  = fluentFormApi( 'forms' )->form( $formId = $form->id ); // phpcs:ignore.
			$form_name = $form->title;
			$labels    = $form_api->labels();
			$html     .= '<div class="p:5|10 bg:white form-notify-params-toggle min-h:140">';
			$html     .= '
			<h3 class="rel f:14 border-bottom:1px|solid|#ccc f:#444 pb:10 px:3 cursor:pointer">' . $form_name . '
				<span class="toggle-indicator abs top:0 right:5 pointer-events:none" aria-hidden="true"></span>
			</h3>
			<div>
				<ul class="d:flex flex-wrap:wrap mb:0" data-show-by="form_notify_trigger_form_fluent" data-show-value="' . $form->id . '">';
			foreach ( $labels as $input => $label ) {
				if ( 'fcal_booking' === $input ) {
					$has_booking = true;
					continue;
				}
				$html .= '
				<li class="mr:10">
					<button class="btn-copy cursor:pointer rel border:0 p:5|8 r:4 bg:#eee bg:#ddd:hover" data-clipboard-text="{{' . $input . '}}">' . ucwords( str_replace( '_', ' ', $label ) ) . '
						<span class="opacity:0 pointer-events:none abs bottom:-20 bg:#2271b1 f:white p:3|5 f:12 r:2 left:50% translate(-50%,0) z:10 white-space:nowrap">' . __( 'Copied!', 'form-notify' ) . '
							<i class="abs bottom:18 left:50% translate(-50%,0) w:0 h:0 border-style:solid; border-width:0|8px|10px|8px border-color:transparent|transparent|#2271b1|transparent z:5"></i>
						</span>
					</button>
				</li>';
			}

			if ( $has_booking ) {
				foreach ( $booking_args as $input => $label ) {
					$html .= '<li class="mr:10">
					<button class="btn-copy cursor:pointer rel border:0 p:5|8 r:4 bg:#eee bg:#ddd:hover" data-clipboard-text="{{' . $input . '}}">' . ucwords( str_replace( '_', ' ', $label ) ) . '
						<span class="opacity:0 pointer-events:none abs bottom:-20 bg:#2271b1 f:white p:3|5 f:12 r:2 left:50% translate(-50%,0) z:10 white-space:nowrap">' . __( 'Copied!', 'form-notify' ) . '
							<i class="abs bottom:18 left:50% translate(-50%,0) w:0 h:0 border-style:solid; border-width:0|8px|10px|8px border-color:transparent|transparent|#2271b1|transparent z:5"></i>
						</span>
					</button>
				</li>';
				}
			}

			$html .= '</ul></div>';
			$html .= '</div>';
		}

		$param->add_html(
			array(
				'id'         => 'metabox_fluent_form_field',
				'html'       => '<div class="data-show">' . $html . '</div>',
				'show_by'    => 'form_notify_trigger_event',
				'show_value' => 'by_fluent_form',
			),
		);
	}

	/**
	 * Add form action module
	 *
	 * @param array  $form_notify_action_module Action module.
	 * @param object $action                    Action Object.
	 */
	public function add_form_action_module( array $form_notify_action_module, object $action ): void {
		$form_notify_action_module[] = $action->add_text(
			array(
				'id'          => 'form_notify_action_module_receiver',
				'class'       => 'by_fluent_form',
				'label'       => __( 'Fluent Form Receiver Field', 'form-notify' ),
				'placeholder' => __( 'ex:{{email}}', 'form-notify' ),
				'desc'        => __( 'Copy the field parameters by notify type. Use case:<br>1.Using the parameter {{phone}} if you want to notify via SMS.<br>2.Using the parameter {{email}} which is from LINE Login if you want to notify via LINE Messaging API.', 'form-notify' ),
			),
			true
		);
	}

	/**
	 * Get forms
	 *
	 * @return array $forms Forms.
	 */
	private function get_forms(): array {
		$form_api = fluentFormApi( 'forms' ); // phpcs:ignore
		$atts     = array(
			'status'      => 'all',
			'sort_column' => 'id',
			'sort_by'     => 'DESC',
			'per_page'    => 9999,
		);

		return $form_api->forms( $atts, $withFields = false )['data']; // phpcs:ignore
	}

	/**
	 * Push
	 *
	 * @param int    $insert_id inset.
	 * @param array  $form_data form data.
	 * @param object $form      form object.
	 *
	 * @return void
	 */
	public function push( int $insert_id, array $form_data, object $form ): void {
		$notify_ids = $this->notify->get_notify_ids( $form->id );
		$this->notify->send_notify( $notify_ids, $form_data );
	}

	/**
	 * Sms status
	 *
	 * @param string $phone      phone.
	 * @param string $batch_id   batch_id.
	 * @param string $history_id history_id.
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
