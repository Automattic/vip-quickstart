<?php

final class PMC_Debug {
	private static $_instance = false;
	private $_theme_directory = false;
	private $_host_prefix = false;

	public static function get_instance() {
		if ( empty( self::$_instance ) ) {
			$class = get_called_class();
			self::$_instance = new $class();
		}
		return self::$_instance;
	}
	
	public function __construct() {
		$this->_init();
	}

	private function _init() {
		add_action( 'init', array( $this, 'action_init' ) );
	}
	
	function action_init() {
		if ( !isset( $_GET['debug'] ) ) {
			return;
		}

		add_filter( 'pmc_pre_render_ads', array( $this, 'filter_pmc_pre_render_ads' ), 10, 3 );

	}
	
	public function filter_pmc_pre_render_ads( $html, $ad_location, $ad_title ) {
		$ad_slot = $ad_location;
		if ( !empty( PMC_Ads::get_instance()->locations[$ad_slot] ) ) {
			$ad_slot = PMC_Ads::get_instance()->locations[$ad_slot];
		}
		$html .= sprintf( '<div class="pmc-adm-debug" style="display:block;background:yellow;color:red;padding:10px;text-align:center;font-weight:bold;font;text-transform:none;">[ad-slot=%s]</div>', $ad_slot );
		return $html;
	}
}

PMC_Debug::get_instance();
