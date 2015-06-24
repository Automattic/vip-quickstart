<?php
/**
 * A simplification of the settings API
 * @author Michael Pretty (prettyboymp)
 * @version 0.2
 * @package eventbrite-parent
 *
 * Note - Modified for use in the eventbrite themes
 */

if ( ! class_exists( 'Voce_Settings_API' ) ) {
class Voce_Settings_API {
	private static $instance;

	private $settings_pages;

	CONST VERSION = 0.2;

	/**
	 * Returns singleton instance of api
	 *
	 * @return Voce_Settings_API
	 */
	public static function GetInstance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new Voce_Settings_API();
		return self::$instance;
	}

	private function __construct() {
		$this->settings_pages = array();
	}

	public function get_setting( $setting_key, $group_key, $default = null ) {
		$setting_group = get_option( $group_key );
		$group = $this->get_group( $group_key );
		if ( is_array( $setting_group ) && isset( $setting_group[$setting_key] ) ) {
				return $setting_group[$setting_key];
		} elseif ( ( $group ) && isset( $group->settings[$setting_key] ) && ! empty( $group->settings[$setting_key]->default_value ) ) {
			return $group->settings[$setting_key]->default_value;
		}
		return $default;
	}

	public function set_setting($setting_key, $group_key, $value) {
		$new_values = get_option($group_key, array());
		$new_values[$setting_key] = $value;
		update_option($group_key, $new_values);
	}


	private function get_group( $group_key ) {
		foreach( $this->settings_pages as $settings_page ) {
			if ( isset( $settings_page->groups[$group_key] ) )
				return $settings_page->groups[$group_key];
		}
		return null;
	}

	/**
	 * Adds a new settings page if one doesn't already exist
	 *
	 * @param string $page_title
	 * @param string $menu_title
	 * @param string $page_key
	 * @param string $capability
	 * @param string $description
	 * @param string $parent_page slug for parent page, leave empty to create new menu
	 * @return Voce_Settings_Page
	 */
	public function add_page($page_title, $menu_title, $page_key, $capability = false, $description = '', $parent_page = '') {
		if ( ! $capability ) {
			$capability = 'manage_options';
		} else {
			add_filter( 'option_page_capability_' . $page_key . '-page', array( new Page_Cap_Filter( $capability ), 'set_cap' ) );
		}

		if ( ! $page_key ) {
			$page_key = 'vsp_' . sanitize_key( $this->title );
		}
		if ( ! isset($this->settings_pages[$page_key] ) ) {
			$page = new Voce_Settings_Page( $page_title, $menu_title, $page_key . '-page', $capability, $description, $parent_page );
			$this->settings_pages[$page_key] = $page;
		}
		return $this->settings_pages[$page_key];
	}
}
}

if ( ! class_exists( 'Voce_Settings_Page' ) ) {
class Voce_Settings_Page {

	public $title;
	public $menu_title;
	public $page_key;
	public $capability;
	public $description;

	/**
	 * Key of this page's parent page if it is a submenu item
	 *
	 * @var string
	 */
	public $parent_page;
	public $groups;


	public function __construct( $title, $menu_title, $page_key, $capability = 'manage_options', $description = '', $parent_page = '' ) {
		$this->title = $title;
		$this->page_key = $page_key;
		$this->menu_title = $menu_title;
		$this->capability = $capability;
		$this->description = $description;
		$this->parent_page = $parent_page;
		$this->groups = array();

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		if ( current_user_can( $this->capability ) ) {
			add_action( 'admin_init', array($this, 'admin_init' ) );
		}
	}

	public function add_error( $code, $message, $type = 'error' ) {
		add_settings_error( $this->page_key, $code, $message, $type );
	}

	/**
	 * Adds a new group to the page
	 *
	 * @param string $group_key
	 * @param string $title
	 * @param string $capability
	 * @param string $description
	 * @return Voce_Settings_Group
	 */
	public function add_group( $title, $group_key, $capability = '', $description = '' ) {
		if ( ! isset( $this->groups[$group_key] ) ) {
			$group = new Voce_Settings_Group( $this, $title, $group_key, $capability, $description );
			$this->groups[$group_key] = $group;
		}
		return $this->groups[$group_key];
	}

	public function admin_menu() {
		if ( current_user_can($this->capability ) ) {
			add_theme_page( $this->title, $this->menu_title, $this->capability, $this->page_key, array( $this, 'display' ) );
		}
	}

	public function admin_init() {
		register_setting( $this->page_key, $this->page_key, array( $this, 'sanitize_callback' ) );
	}

	public function sanitize_callback($new_values) {
		if ( current_user_can( $this->capability ) ) {
			$this->add_error('all', _x( 'Your changes have been saved.', 'voce settings api', 'eventbrite-parent' ), 'updated');

			foreach ( $this->groups as $group ) {
				$new_value = isset( $new_values[$group->group_key] ) ? $new_values[$group->group_key] : array();
				$group->sanitize_callback( $new_value );
			}
		}
		/**
		 * return false so a new option doesn't get added for this.
		 */
		return false;
	}

	public function display() {
		if ( current_user_can( $this->capability ) ) {
			?>
			<div class="wrap">
				<h2><?php echo esc_html( $this->title ); ?></h2>
				<?php settings_errors($this->page_key, false, true); ?>
				<?php if ( $this->description )
					echo '<p>' . esc_html( $this->description ) . '</p>'; ?>
				<form action="options.php" method="POST">
					<?php settings_fields( $this->page_key ); ?>
					<?php do_settings_sections( $this->page_key ); ?>
					<p class="submit">
						<input type="submit" value="<?php esc_attr_e( 'Save Changes', 'eventbrite-parent' ); ?>" class="button-primary" name="<?php echo esc_attr_x( 'Submit', 'submit-button', 'eventbrite-parent' ); ?>">
					</p>
				</form>
			</div>
			<?php
		}
	}
}
}

if ( ! class_exists( 'Voce_Settings_Group' ) ) {
class Voce_Settings_Group {
	/**
	 * Pointer to this Section's Page
	 *
	 * @var Voce_Settings_Page
	 */
	public $page;

	public $title;
	public $capability;
	public $group_key;
	public $description;
	public $settings;

	public function __construct( $page, $title, $group_key, $capability = '', $description = '' ) {
		$this->page = $page;
		$this->group_key = $group_key;
		$this->title = $title;
		$this->capability = $capability ? $capability : $this->page->capability;
		$this->description = $description;
		$this->settings = array();
		if ( current_user_can( $this->capability ) ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	public function add_error( $code, $message, $type = 'error' ) {
			$this->page->add_error( $code, $message, $type );
	}


	public function admin_init() {
			add_settings_section( $this->group_key, $this->title, array( $this, 'display' ), $this->page->page_key );
	}

	/**
	 * Adds a group to the group
	 *
	 * @param string $title
	 * @param string $group_key
	 * @param string $capability
	 * @param string $description
	 * @return Voce_Setting
	 */
	public function add_setting($title, $setting_key, $args = array()) {
		if ( ! isset( $this->settings[$setting_key] ) ) {
			$setting = new Voce_Setting($this, $title, $setting_key, $args);
			$this->settings[$setting_key] = $setting;
		}
		return $this->settings[$setting_key];
	}

	public function display() {
		if ( current_user_can( $this->capability ) ) {
			if ( $this->description )
				echo '<p>' . esc_html( $this->description ) . '</p>';
		}
	}

	public function sanitize_callback( $new_values ) {
		$old_values = get_option( $this->group_key, array() );
		if ( current_user_can( $this->capability ) ) {
			foreach( $this->settings as $setting ) {
				$new_value = isset( $new_values[$setting->setting_key] ) ? $new_values[$setting->setting_key] : null;
				$old_value = isset( $old_values[$setting->setting_key] ) ? $old_values[$setting->setting_key] : null;
				$old_values[$setting->setting_key] = $setting->sanitize( $new_value, $old_value );
			}
		}
		update_option( $this->group_key, $old_values );
	}
}
}

if ( ! class_exists( 'Voce_Setting' ) ) {
class Voce_Setting {

	/**
	 * Pointer to this settings group
	 *
	 * @var Voce_Settings_Group
	 */
	public $group;
	public $title;
	public $setting_key;
	public $capability;
	public $default_value;
	public $args;

	/**
	 * Constructor for Voce Setting
	 *
	 * @param Voce_Settings_Group $group
	 * @param string $title
	 * @param string $setting_key
	 * @param array $args
	 */
	public function __construct( $group, $title, $setting_key, $args = array() ) {
		$this->group = $group;
		$this->title = $title;
		$this->setting_key = $setting_key;

		$args = wp_parse_args( $args, $defaults = array(
			'capability' => $this->group->capability,
			'default_value' => '',
			'display_callback' => '',
			'sanitize_callbacks' => array(
				array( __CLASS__, 'vs_sanitize_text' )
			),
			'description' => ''
		) );

		$this->default_value = $args['default_value'];
		$this->capability = $args['capability'];
		$this->args = $args;
		if ( current_user_can( $this->capability ) ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	public function add_error( $message, $type = 'error' ) {
		$this->group->add_error( $this->setting_key, $message, $type );
	}

	public function admin_init() {
		$field_id = implode( '-', array( $this->group->page->page_key, $this->group->group_key, $this->setting_key ) );
		add_settings_field( $this->setting_key, sprintf( '<label for="%s">%s</label>', $field_id, $this->title ), array( $this, 'display' ), $this->group->page->page_key, $this->group->group_key );
	}

	public function get_field_name() {
		return $this->group->page->page_key . '[' . $this->group->group_key . '][' . $this->setting_key . ']';
	}

	public function get_field_id() {
		return $this->group->page->page_key . '-' . $this->group->group_key . '-' . $this->setting_key;
	}

	public function display() {
		$value = Voce_Settings_API::GetInstance()->get_setting( $this->setting_key, $this->group->group_key, $this->default_value );
		if ( ! empty( $this->args['display_callback'] ) ) {
			call_user_func_array($this->args['display_callback'], array( $value, $this, $this->args ) );
		} else {
			?>
			<input name="<?php echo esc_attr( $this->get_field_name() ); ?>" id="<?php echo esc_attr( $this->get_field_id() ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" type="text">
			<?php if( ! empty( $this->args['description'] ) ) : ?>
				<span class="description"><?php echo esc_html( $this->args['description'] ); ?></span>
			<?php endif; ?>
			<?php
		}
	}

	public function sanitize( $new_value, $old_value ) {
		if ( current_user_can( $this->capability ) ) {
			$old_value = $new_value;
			foreach( $this->args['sanitize_callbacks'] as $callback ) {
				$old_value = call_user_func_array( $callback, array( $old_value, $this, $this->args ) );
			}
		}
		return $old_value;
	}

	public function vs_sanitize_text( $value, $setting, $args ) {
		return trim( strip_tags( $value ) );
	}
}
}

if ( ! class_exists( 'Page_Cap_Filter' ) ) {
class Page_Cap_Filter {
	private $capability;

	function __construct( $capability ) {
		$this->capability = $capability;
	}

	function set_cap() {
		return $this->capability;
	}
}
}
