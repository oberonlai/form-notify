<?php
/**
 * Action Metabox
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Posts\Notify\Metabox;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\APIs\Metabox\Metabox as FormMetabox;

/**
 * Notify Metabox
 */
class Action {
	/**
	 * Register
	 *
	 * @return void
	 */
	public static function register(): void {
		$class = new self();
		add_action( 'admin_init', array( $class, 'init' ), 99 );
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {

		/**
		 * Action
		 */

		$action = new FormMetabox(
			array(
				'id'       => 'form_notify_action',
				'title'    => __( 'Notify Action', 'form-notify' ),
				'screen'   => 'form-notify',
				'context'  => 'normal',
				'priority' => 'default',
			)
		);

		$options = array(
			''       => __( 'Choose', 'form-notify' ),
			'notify' => __( 'LINE Notify', 'form-notify' ),
			'bot'    => __( 'BOT', 'form-notify' ),
			'email'  => __( 'Email', 'form-notify' ),
		);

		( 'yes' === get_option( 'form_notify_e8d_enable' ) ) ? $options['e8d']       = __( 'Every8d SMS', 'form-notify' ) : '';
		( 'yes' === get_option( 'form_notify_mitake_enable' ) ) ? $options['mitake'] = __( 'Mitake SMS', 'form-notify' ) : '';
		( 'yes' === get_option( 'form_notify_easygo_enable' ) ) ? $options['easygo'] = __( 'easyGo SMS', 'form-notify' ) : '';

		$form_notify_action_module[] = $action->add_select(
			array(
				'id'    => 'form_notify_action_module_type',
				'class' => 'form_notify_action_module_type',
				'label' => __( 'Notify Type', 'form-notify' ),
			),
			$options,
			true
		);

		$form_notify_action_module[] = $action->add_text(
			array(
				'id'         => 'form_notify_action_module_subject',
				'label'      => __( 'Email Subject', 'form-notify' ),
				'show_by'    => 'form_notify_action_module_type',
				'show_value' => 'email',
			),
			true
		);

		$form_notify_action_module[] = $action->add_textarea(
			array(
				'id'    => 'form_notify_action_module_content',
				'label' => __( 'Notify content', 'form-notify' ),
			),
			true
		);

		$form_notify_action_module[] = $action->add_text(
			array(
				'id'          => 'form_notify_action_module_receiver',
				'class'       => 'receiver-field',
				'label'       => __( 'Custom Receiver Field', 'form-notify' ),
				'placeholder' => __( 'ex:{{email}}', 'form-notify' ),
				'desc'        => __( 'Copy the field parameters by notify type. For example:<br>1.Using the parameter {{phone}} if you want to notify via SMS.<br>2.Using the parameter {{email}} if you want to notify via email address.<br>3.Using the parameter {{email}} which is from LINE Login if you want to notify via LINE Messaging API.', 'form-notify' ),
			),
			true
		);

		// FIX: Hook not work.
		do_action( 'form_notify_action_module', $form_notify_action_module, $action );

		$action->add_repeater_block(
			array(
				'id'           => 'form_notify_action_module',
				'class'        => 'form_notify_action_module',
				'fields'       => $form_notify_action_module,
				'desc'         => __( 'Notify action.', 'form-notify' ),
				'single_label' => __( 'Action', 'form-notify' ),
				'button_label' => __( 'Add', 'form-notify' ),
			)
		);

	}
}

Action::register();

