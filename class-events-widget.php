<?php

class Events_Widget extends WP_Widget {
	public function __construct() {
		$widget_options = array(
			'classname'   => 'event_widget',
			'description' => __( 'Events Widget' )
		);
		parent::__construct( 'event_widget', __( 'Events Widget' ), $widget_options );
	}

	public function widget( $args, $instance ) {
		$current_date = date(' Y/m/d.');
		$status = empty( $instance['status'] ) ? '' : $instance['status'];
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 1;
		if ( ! $number ) {
			$number = 1;
		}

		$query_args = array(
			'post_type'  => 'events',
			'meta_query' => array(
				array(
					'key'   => 'events-status',
					'value' => $status ?? 'open',
				),
			)
		);
		$the_query  = new WP_Query( $query_args );
		if ( $the_query->have_posts() ) {
			global $post;
			$total = $the_query->post_count;
			if ( $number >= $total ) {
				$total_records = $total;
			} else {
				$total_records = $number;
			}
			
			for ( $i = 0; $i < $total_records; $i++ ) {
				$the_query->the_post();
				$event_date = date( ' Y/m/d.', intval( get_post_meta( $post->ID, 'events-date', true ) ) );
				if( $event_date >= $current_date ) {
					echo '<li>' . get_the_title() . '</li>' . '</br>';
					echo 'Event Date: ' . date( ' Y/m/d.', intval( get_post_meta( $post->ID, 'events-date', true ) ) ) . '</br>';
					echo 'Event Status: ' . get_post_meta( $post->ID, 'events-status', true );
				}
			}
		}
		else {
		    echo 'Events not found';
		}
		/* Restore original Post Data */
		wp_reset_postdata();
	}

	public function form( $instance ) {
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>"><?php _e( 'Status:' ); ?></label>
            <input type="text" value="<?php echo esc_attr( $instance['status'] ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'status' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'status' ) ); ?>" class="widefat" />
            <br />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of events to show:' ); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" />
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['status'] = sanitize_text_field( $new_instance['status'] );
		$instance['number'] = absint( $new_instance['number'] );
		return $instance;
	}
}

function events_register_widget() {
	register_widget( 'Events_Widget' );
}
add_action( 'widgets_init', 'events_register_widget' );