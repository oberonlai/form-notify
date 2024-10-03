<?php
/**
 * Trigger Metabox
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Posts\Notify\Metabox;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Metabox\Metabox as FormMetabox;

/**
 * Notify Metabox class
 */
class Trigger {
	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'admin_init', array( $class, 'init' ), 90 );
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		$trigger = new FormMetabox(
			array(
				'id'       => 'form_notify_trigger',
				'title'    => __( 'Notify Trigger', 'form-notify' ),
				'screen'   => 'form-notify',
				'context'  => 'normal',
				'priority' => 'default',
			)
		);

		$trigger_event_options = array(
			'' => __( 'Choose', 'form-notify' ),
		);

		$trigger_event_options = apply_filters( 'form_notify_trigger_event_options', $trigger_event_options );

		$trigger->add_select(
			array(
				'id'    => 'form_notify_trigger_event',
				'class' => 'form-notify-trigger-event',
				'label' => __( 'Event', 'form-notify' ),
				'desc'  => __( 'Select the trigger event of sending notifications.', 'form-notify' ),
			),
			$trigger_event_options
		);

		do_action( 'form_notify_trigger', $trigger );

	}
}

Trigger::register();


