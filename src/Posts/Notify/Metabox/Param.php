<?php

namespace FORMNOTIFY\Posts\Notify\Metabox\Param;

defined( 'ABSPATH' ) || exit;

use ODSFormNotify\Metabox as FormMetabox;

/**
 * Notify Metabox
 */
class Param {

	private $customer_params;
	private $order_params;
	private $shop_params;

	public static function register() {
		$class = new self();
		add_action( 'admin_init', array( $class, 'init' ), 99 );
	}

	public function init() {

		$param = new FormMetabox(
			array(
				'id'       => 'form_notify_param',
				'title'    => __( 'Notify Params', 'form-notify' ),
				'screen'   => 'form-notify',
				'context'  => 'side',
				'priority' => 'default',
			)
		);

		do_action( 'form_notify_param', $param );
	}

	private function get_params( $params ) {

		if ( ! $params ) {
			return;
		}

		$html  = '<div class="p:5|10 bg:white form-notify-params-toggle">';
		$html .= '
		<h3 class="rel f:14 border-bottom:1px|solid|#ccc f:#444 pb:10 px:3 cursor:pointer">' . $params['title'] . '
			<span class="toggle-indicator abs top:0 right:5 pointer-events:none" aria-hidden="true"></span>
		</h3>
		<div>
			<ul class="d:flex flex-wrap:wrap mb:0">';

		foreach ( $params as $p => $label ) {
			if ( 'title' !== $p ) {
				$html .= '
				<li class="mr:10">
					<button class="btn-copy cursor:pointer rel border:0 p:5|8 r:4 bg:#eee bg:#ddd:hover" data-clipboard-text="{{' . $p . '}}">' . ucwords( str_replace( '_', ' ', $label ) ) . '
						<span class="opacity:0 pointer-events:none abs bottom:-20 bg:#2271b1 f:white p:3|5 f:12 r:2 left:50% translate(-50%,0) z:10 white-space:nowrap">' . __( 'Copied!', 'form-notify' ) . '
							<i class="abs bottom:18 left:50% translate(-50%,0) w:0 h:0 border-style:solid; border-width:0|8px|10px|8px border-color:transparent|transparent|#2271b1|transparent z:5"></i>
						</span>
					</button>
				</li>';
			}
		}
		$html .= '</ul></div>';
		$html .= '</div>';

		return $html;
	}

	private function is_active( $plugin ) {
		if ( in_array( $plugin . '/' . $plugin . '.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			return true;
		} else {
			return false;
		}
	}
}

Param::register();
