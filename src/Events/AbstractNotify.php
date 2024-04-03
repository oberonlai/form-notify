<?php // phpcs:ignore

/**
 * Abstract Notify class
 *
 * @package FORMNOTIFY
 */

namespace FORMNOTIFY\Events;

defined( 'ABSPATH' ) || exit;

use FORMNOTIFY\Options\History;
use FORMNOTIFY\APIs\Line\Message;

/**
 * Notify abstract class
 */
abstract class AbstractNotify {

	/**
	 * Plugin slug
	 *
	 * @var string $slug Plugin slug.
	 */
	private string $slug;

	/**
	 * String show in history
	 *
	 * @var string $history_text String show in history.
	 */
	private string $history_text;

	/**
	 * Field Key
	 *
	 * @var string $trigger_key Field Key.
	 */
	private string $trigger_key;

	/**
	 * Event Value for non form plugin
	 *
	 * @var string $trigger_event_value Event Value.
	 */
	private string $trigger_event_value;

	/**
	 * Trigger form key
	 *
	 * @var string $trigger_form_key Trigger form key.
	 */
	private string $trigger_form_key;

	/**
	 * Construct
	 *
	 * @param string $slug   Plugin slug.
	 * @param string $string String show in history.
	 */
	public function __construct( string $slug, string $string ) {
		$this->slug             = $slug;
		$this->history_text     = $string;
		$this->trigger_form_key = ( 'gravity' === $slug ) ? 'form_notify_trigger_form' : 'form_notify_trigger_form_' . $slug;
	}

	/**
	 * Set event value
	 *
	 * @param string $value Event value.
	 */
	public function set_trigger_key( string $value ): void {
		$this->trigger_key = 'form_notify_' . $value;
	}

	/**
	 * Set event value
	 *
	 * @param string $value Event value.
	 */
	public function set_event_value( $value ) {
		$this->trigger_event_value = $value;
	}

	/**
	 * Get Notify post ids
	 *
	 * @param int|string $value notify meta value.
	 *
	 * @return array $notify_ids Notify Post IDs.
	 */
	public function get_notify_ids( int|string $value ): array {
		$notify_ids = array();
		// @codingStandardsIgnoreStart
		$args_post = array(
			'posts_per_page' => '100',
			'post_type'      => 'form-notify',
			'meta_query'     => array(
				array(
					'key'     => 'form_notify_trigger_event',
					'value'   => ( ! empty( $this->trigger_event_value ) ) ? $this->trigger_event_value : 'by_' . $this->slug . '_form',
					'compare' => '=',
				),
				array(
					'key'     => $this->trigger_key,
					'value'   => $value,
					'compare' => '=',
				),
			),
		);
		// @codingStandardsIgnoreEnd

		$query_post = new \WP_Query( $args_post );
		if ( $query_post->have_posts() ) {
			while ( $query_post->have_posts() ) {
				$query_post->the_post();
				$notify_ids[] = get_the_ID();
			}
		}

		return $notify_ids;
	}

	/**
	 * Replace message content to form data
	 *
	 * @param string $content Message content.
	 * @param array  $data    Form data.
	 */
	abstract public function replace_message_content( string $content, array $data );

	/**
	 * Send notify
	 *
	 * @param array $notify_ids Notify IDs.
	 * @param array $form_data  Form data.
	 */
	public function send_notify( array $notify_ids, array $form_data ): void {
		if ( $notify_ids ) {
			foreach ( $notify_ids as $notify_id ) {
				$actions = get_post_meta( $notify_id, 'form_notify_action_module', true );
				if ( $actions ) {
					foreach ( $actions as $action ) {
						$method   = $action['form_notify_action_module_type'];
						$receiver = $this->replace_message_content( $action['form_notify_action_module_receiver'], $form_data );

						switch ( $action['form_notify_action_module_type'] ) {
							case 'notify':
								$line_notify = new \FORMNOTIFY\APIs\Line\Notify();
								$result      = $line_notify->push( $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ) );

								History::insert( '-', $this->history_text, 0, __( 'LINE Notify', 'form-notify' ), $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ), $result );

								break;
							case 'bot':
								if ( $receiver && email_exists( $receiver ) ) {
									$line_user_id = $this->get_line_user_id( get_user_by( 'email', $receiver )->ID );
									if ( $line_user_id ) {
										$line   = new Message();
										$result = $line->push(
											$line_user_id,
											$this->replace_message_content( $action['form_notify_action_module_content'], $form_data )
										);
									} else {
										$result = __( 'Not found LINE user id', 'form-notify' );
									}
								} else {
									$result = __( 'Not found LINE user id', 'form-notify' );
								}

								History::insert( 0, $receiver . ' - ' . $this->history_text, 0, __( 'LINE Messaging API', 'form-notify' ), $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ), $result );

								break;
							case 'email':
								$subject = $this->replace_message_content( $action['form_notify_action_module_subject'], $form_data );
								$headers = array( 'Content-Type: text/html; charset=UTF-8' );
								$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/><meta http-equiv="X-UA-Compatible" content="IE=edge"/><meta name="viewport" content="width=device-width, initial-scale=1.0"><title></title><!--[if (gte mso 9)|(IE)]><style type="text/css">table{border-collapse: collapse;}</style><![endif]--><style type="text/css"> body{margin: 0 !important; padding: 0; background-color: #ffffff; font-family: "HanHei TC", "PingFang TC", "Helvetica Neue", "Helvetica", "STHeitiTC-Light", "Arial", sans-serif;}table{width:100%;border-spacing: 0; color: #4A4A4A;}td{padding: 0;}img{border: 0;}ul{margin: 0; padding: 0;}li{list-style: none; font-size: 14px; color: #4A4A4A; margin-bottom: 20px;}div[style*="margin: 16px 0"]{margin:0 !important;}.wrapper{width: 100%; table-layout: fixed; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;}.webkit{max-width: 680px; width: 90%;}.inner{padding: 37px;}p{Margin: 0;}a{color: DodgerBlue; text-decoration: none;}.one-column .contents{text-align: left;}.one-column p{font-size: 14px; margin-bottom: 10px;}.content{background: #efefef;}.color{color: #FA666C;}.btn{position: relative; width: 100%; margin: 10px 0 0 0; display: inline-block; padding: 1rem 0; text-align: center; font-size: 1.2rem; white-space: nowrap;-webkit-border-radius: 100px;-moz-border-radius: 100px;-ms-border-radius: 100px;-o-border-radius: 100px;border-radius: 100px;-webkit-transition: all .2s ease;-o-transition: all .2s ease;transition: all .2s ease;}.btnorage{background: #ED6B00; color: #fff;}.btnorage:hover,.btnred:active{background: #F09F54;}.hr{display: block; width: 100%; height: 1px; margin: 20px 0; background: #ccc;}</style></head><body> <div class="wrapper"> <div class="webkit"><!--[if (gte mso 9)|(IE)]> <table width="375" align="center"> <tr> <td><![endif]--> <table class="outer" align="center"> <tbody> <tr class="psingle"> <td class="one-column"> <table width="100%"> <tbody> <tr> </tr></tbody> </table> </td></tr><tr class="psingle content"> <td class="one-column"> <table width="100%"> <tbody> <tr> <td class="inner contents"> <h3 style="text-align:center">' . $this->replace_message_content( $action['form_notify_action_module_subject'], $form_data ) . '</h3><div>' . nl2br( $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ) ) . '</div><br><hr> <br><p style="text-align: center;">' . get_bloginfo( 'name' ) . '</p><p style="text-align: center;"><a href="' . home_url() . '">' . home_url() . '</a></p></td></tr></tbody> </table> </td></tr></tbody> </table><!--[if (gte mso 9)|(IE)]> </td></tr></table><![endif]--> </div></div> </body></html>';
								wp_mail( $receiver, $subject, $message, $headers );

								History::insert( 0, $receiver . ' - ' . $this->history_text, 0, __( 'Email', 'form-notify' ), $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ), __( 'Success', 'form-notify' ) );
								break;
							default:
								// code...
								break;
						}

						do_action( 'form_notify_action_module_type_events', $action['form_notify_action_module_type'], $receiver, $this->replace_message_content( $action['form_notify_action_module_content'], $form_data ), $this->history_text, $this->slug );
					}
				}
			}
		}
	}

	/**
	 * Get LINE User ID
	 *
	 * @param int $user_id User ID.
	 */
	private function get_line_user_id( int $user_id ) {

		if ( ! $user_id ) {
			return false;
		}

		$user_email = get_userdata( $user_id )->user_email;

		// Form Notify.
		if ( get_user_meta( $user_id, 'form_notify_line_user_id', true ) ) {
			return get_user_meta( $user_id, 'form_notify_line_user_id', true );
		}

		// LINE for WordPress.
		if ( get_user_meta( $user_id, 'line_user_id', true ) ) {
			return get_user_meta( $user_id, 'line_user_id', true );
		}

		// Super Socializer.
		if ( str_contains( $user_email, '@line.com' ) ) {
			return explode( '@', $user_email )[0];
		}

		// HB LINE Login.
		if ( get_user_meta( $user_id, '_heiblack_social_line_id', true ) ) {
			return get_user_meta( $user_id, '_heiblack_social_line_id', true );
		}

		// Nextend Social Login.
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT identifier FROM `{$wpdb->prefix}social_users` WHERE `ID` = %d ", $user_id );

		$cache = wp_cache_get( 'form_notify_identifier' );

		if ( ! $cache ) {
			// @codingStandardsIgnoreStart
			$result = $wpdb->get_results( $sql );
			$cache  = $result[0]->identifier;
			wp_cache_set( 'form_notify_identifier', $cache );
			// @codingStandardsIgnoreEnd
		}

		return $cache;
	}
}
