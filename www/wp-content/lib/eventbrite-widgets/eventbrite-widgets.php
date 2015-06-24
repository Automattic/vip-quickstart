<?php
/**
 * Eventbrite theme widgets
 *
 * @package eventbrite-parent
 * @author  Voce Communications
 */

/**
 * Widget that displays a freeform text box with a large button
 */
if ( !class_exists( 'Eventbrite_Introduction_Widget' ) ) {
class Eventbrite_Introduction_Widget extends WP_Widget {

	/**
	 * Create the widget
	 */
	function __construct() {
		$widget_ops = array( 'classname' => 'widget_introduction', 'description' => __( 'Display an Introduction widget, with text and a link', 'eventbrite-parent' ) );
		parent::__construct( 'introduction', __( 'Eventbrite: Introduction', 'eventbrite-parent' ), $widget_ops );
		$this->alt_option_name = 'widget_introduction';
	}

	/**
	 * Update function for widget
	 * @param type $new_instance
	 * @param type $old_instance
	 * @return type
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		$instance['filter'] = isset($new_instance['filter']);
		$instance['link-label'] = strip_tags($new_instance['link-label']);
		$instance['link-url'] = esc_url_raw( $new_instance['link-url'] );
		return $instance;
	}

	/**
	 * Form used with the admin
	 * @param type $instance
	 */
	function form( $instance ) {
		$instance   = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'link-label' => '', 'link-url' => '' ) );
		$title      = strip_tags( $instance['title'] );
		$text       = esc_textarea( $instance['text'] );
		$link_label = strip_tags( $instance['link-label'] );
		$link_url   = esc_url( $instance['link-url'] );
		?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'eventbrite-parent' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo esc_attr( $this->get_field_id('text') ); ?>" name="<?php echo esc_attr( $this->get_field_name('text') ); ?>"><?php echo esc_html( $text ); ?></textarea>

		<p><input id="<?php echo esc_attr( $this->get_field_id( 'filter' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'filter' ) ); ?>" type="checkbox" <?php checked( isset( $instance['filter'] ) ? $instance['filter'] : 0 ); ?> />&nbsp;<label for="<?php echo esc_attr( $this->get_field_id( 'filter' ) ); ?>"><?php _e( 'Automatically add paragraphs', 'eventbrite-parent' ); ?></label></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'link-label' ) ); ?>"><?php _e( 'Link Label:', 'eventbrite-parent' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link-label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link-label' ) ); ?>" type="text" value="<?php echo esc_html( $link_label ); ?>" /></p>

		<p><label for="<?php echo esc_attr( $this->get_field_id( 'link-url' ) ); ?>"><?php _e( 'Link URL:', 'eventbrite-parent' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link-url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link-url' ) ); ?>" type="text" value="<?php echo esc_url( $link_url ); ?>" /></p>
		<?php
	}

	/**
	 * Display function for widget
	 * @param type $args
	 * @param type $instance
	 * @return type
	 */
	function widget( $args, $instance ) {
		extract($args);
		$title       = empty( $instance['title'] ) ? '' : $instance['title'];
		$title       = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$text        = empty( $instance['text'] ) ? '' : $instance['text'];
		$link_label  = empty( $instance['link-label'] ) ? '' : $instance['link-label'];
		$link_url    = empty( $instance['link-url'] ) ? '' : $instance['link-url'];

		echo $before_widget;

		if ( !empty( $title ) )
			echo $before_title . $title . $after_title;

		?>
			<div class="textwidget">
				<?php echo !empty( $instance['filter'] ) ? wpautop( $text ) : $text; ?>
				<?php if ( $link_url && $link_label ) : ?>
				<p class="eventbrite-intro-widget-button"><a href="<?php echo esc_url( $link_url ); ?>" class="btn btn-warning"><?php echo esc_html( $link_label ); ?></a></p>
				<?php endif; ?>
			</div>
		<?php
		echo $after_widget;
	}
}
}
