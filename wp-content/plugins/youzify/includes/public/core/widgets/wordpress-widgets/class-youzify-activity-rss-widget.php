<?php

/**
 * Members Activity Rss Widget
 */

class Youzify_Activity_Rss_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'youzify_activity_rss',
			__( 'Youzify - Members Activity RSS', 'youzify' ),
			array(
				'description' => __( 'Members activity RSS widget ( works only on global activity page )', 'youzify' )
			)
		);

		// Print Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer-widgets.php', array( $this, 'print_scripts' ), 9999 );

	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts( $hook_suffix ) {

		if ( 'widgets.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'underscore' );

	}

	/**
	 * Print scripts.
	 *
	 * @since 1.0
	 */
	public function print_scripts() { ?>
		<script>
			( function( $ ){
				function initColorPicker( widget ) {
					widget.find( '.youzify-color-picker' ).wpColorPicker( {
						change: _.throttle( function() { // For Customizer
							$(this).trigger( 'change' );
						}, 3000 )
					});
				}

				function onFormUpdate( event, widget ) {
					initColorPicker( widget );
				}

				$( document ).on( 'widget-added widget-updated', onFormUpdate );

				$( document ).ready( function() {
					$( '#widgets-right .widget:has(.youzify-color-picker)' ).each( function () {
						initColorPicker( $( this ) );
					} );
				} );
			}( jQuery ) );
		</script>

		<?php
	}

	/**
	 * Back-end widget form.
	 */
	public function form( $instance ) {

		// Default Widget Settings
	    $defaults = array(
	        'left_color'  => '#FF5722',
	        'right_color' => '#F9D423',
	        'title' 	  => __( 'Members activity RSS', 'youzify' ),
	    );

	    // Get Widget Data.
	    $instance = wp_parse_args( (array) $instance, $defaults );

		?>

		<!-- Widget Title. -->
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'youzify' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>

        <p>
            <label for="<?php echo $this->get_field_id( 'left_color' ); ?>"><?php _e( 'Gradient Left Color', 'youzify' ); ?></label><br>
            <input class="youzify-color-picker" type="text" id="<?php echo $this->get_field_id( 'left_color' ); ?>" name="<?php echo $this->get_field_name( 'left_color' ); ?>" value="<?php echo esc_attr( $instance['left_color'] ); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'right_color' ); ?>"><?php _e( 'Gradient Right Color', 'youzify' ); ?></label><br>
            <input class="youzify-color-picker" type="text" id="<?php echo $this->get_field_id( 'right_color' ); ?>" name="<?php echo $this->get_field_name( 'right_color' ); ?>" value="<?php echo esc_attr( $instance['right_color'] ); ?>">
        </p>

	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = array();

		// Update Values.
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['left_color'] = $new_instance['left_color'];
        $instance['right_color'] = $new_instance['right_color'];

		return $instance;
	}
	/**
	 * Login Widget Content
	 */
	public function widget( $args, $instance ) {

		// Check if widget should be visible or not.
		if ( ! bp_is_active( 'activity' ) || ! bp_is_activity_component() ) {
			return false;
		}

		// Get Box Gradient Color.
		$left_color = $instance['left_color'];
		$right_color = $instance['right_color'];

		?>

		<?php if ( ! empty( $left_color ) && ! empty( $right_color ) ) : ?>
		<style>
			body .<?php echo $args['widget_id']; ?> a {
			    background: <?php echo $right_color; ?>;
			    background: url(<?php echo YOUZIFY_ASSETS; ?>images/dotted-bg.png),linear-gradient(to left, <?php echo $left_color; ?> , <?php echo $right_color; ?> );
			    background: url(<?php echo YOUZIFY_ASSETS; ?>images/dotted-bg.png),-webkit-linear-gradient(right, <?php echo $left_color; ?> , <?php echo $right_color; ?> );
			}
		</style>
		<?php endif; ?>

		<div class="youzify-wp-widget-box youzify-wp-activity-rss-box <?php echo $args['widget_id']; ?>">
			<a href="<?php bp_sitewide_activity_feed_link(); ?>">
				<i class="fas fa-rss"></i>
					<?php echo $instance['title']; ?>

			</a>
		</div>

		<?php
	}

}