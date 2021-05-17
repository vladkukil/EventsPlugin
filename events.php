<?php

/*
Plugin Name: Events
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Events Plugin for W4P.
Version: 1.0
Author: Vladyslav Kukil
*/

require_once 'class-events-widget.php';

function create_taxonomy_corporate(){
    $labels = array(
	    'name'              => 'Categories',
	    'singular_name'     => 'Category',
	    'search_items'      => 'Search Categories',
	    'all_items'         => 'All Categories',
	    'view_item '        => 'View Category',
	    'parent_item'       => 'Parent Category',
	    'parent_item_colon' => 'Parent Category:',
	    'edit_item'         => 'Edit Category',
	    'update_item'       => 'Update Category',
	    'add_new_item'      => 'Add New Category',
	    'new_item_name'     => 'New Category Name',
	    'menu_name'         => 'Categories',
    );
	register_taxonomy( 'events_taxonomy', [ 'events' ], [
		'label'                 => '',
		'labels'                => $labels,
		'description'           => '',
		'public'                => true,
		'hierarchical'          => false,
		'rewrite'               => true,
		'capabilities'          => array(),
		'meta_box_cb'           => null,
		'show_admin_column'     => false,
		'show_in_rest'          => null,
		'rest_base'             => null,
	] );
}


function events_post_type(){
	$labels = array(
		'name' => __('Events'),
		'singular_name' => __('Event'),
		'add_new' => __('Add Event'),
		'add_new_item' => __('Adding Event'),
		'new_item' => __('New Event'),
		'view_item' => __('View Event'),
		'search_items' => __('Search Events'),
		'not_found' => __('Events not found'),
		'not_found_in_trash' => __('Events not fount in trash'),
		'all_items' => __('All Events'),
		'filter_items_list' => __('Filter Events'),
		'items_list_navigation' => __('Events navigation'),
		'items_list' => __('List of Events'),
		'menu_name' => __('Events'),
		'name_admin_bar' => __('Event'),
		'archives' => __('Event archives'),
		'attributes' => __('Event attributes'),
		'parent_item_colon' => __('Parent Event'),
		'view_items' => __('View Events'),
		'item_updated' => __('Event was updated'),
		'item_published' => __('Events was published'),
		'item_published_privately' => __('Events was published privately'),
		'item_reverted_to_draft' => __('Events not fount'),
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'menu_position' => 5,
		'has_archive' => true,
		'supports' => array( 'title', 'excerpt', 'author', 'revisions', 'comments', 'thumbnail'),
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_admin_bar' => true,
		'show_in_nav_menus' => true,
		'can_export' => true,
		'hierarchical' => false,
		'exclude_from_search' => false,
		'show_in_rest' => true,
		'capability_type' => __('post'),
		'rewrite' => array('slug' => 'events'),
	);
	register_post_type('events', $args);
}
add_action( 'init', 'create_taxonomy_corporate' );

add_action('init', 'events_post_type', 0);

function rewrite_events_flush() {
	events_post_type();
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'rewrite_events_flush');

add_action('load-post.php', 'events_post_meta_boxes_setup');
add_action('load-post-new.php', 'events_post_meta_boxes_setup');

function events_post_meta_boxes_setup() {
	add_action( 'add_meta_boxes', 'events_meta_box' );
}

function events_meta_box() {
	add_meta_box(
		'smashing-post-class',      // Unique ID
		esc_html__( 'Status'),
		'events_callback',   // Callback function
		'events',         // Admin page (or post type)
		'side',         // Context
		'default'         // Priority
	);
}

function events_callback() {
	$events = get_posts( array(
		'post_status' => 'publish',
		'post_type' => 'events',
	) );
    // generate a nonce field
    wp_nonce_field( 'events_meta_box', 'events_nonce' );
    // get previously saved meta values (if any)
    foreach ($events as $event) {
        $event_date = get_post_meta( $event->ID, 'events-date', true );
        //$event_status = get_post_meta( $event->ID, 'events-status', true );
        $event_date = ! empty( $event_date ) ? $event_date : time();
    }
    ?>

    <p><label for="events-date"><?php _e( 'Event Date'); ?>
        <input class="widefat" id="events-date" type="date" name="events-date" required maxlength="30"
               placeholder="Event Date" value="<?php echo date(' YYYY-MM-DD.',
            sanitize_text_field( $event_date )); ?>" /></label></p>

    <label for="events-date"> <?php _e( 'Event Status'); ?>
        <select class="widefat" id="events-status" required name="events-status">
            <option>Open</option>
            <option>Invited</option>
    </select> </label>
	<?php
}

function events_save($post_id) {
    // check if nonce is set
    if ( ! isset( $_POST['events_nonce'] ) ) {
        return;
    }
    // verify that nonce is valid
    if ( ! wp_verify_nonce( $_POST['events_nonce'], 'events_meta_box' ) ) {
        return;
    }
    // if this is an autosave, our form has not been submitted, so do nothing
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // check user permissions
    if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }
    // checking for the values and save fields
    if ( isset( $_POST['events-date'] ) ) {
        update_post_meta( $post_id, 'events-date', strtotime( $_POST['events-date'] ) );
    }
    if ( isset( $_POST['events-status'] ) ) {
        update_post_meta( $post_id, 'events-status', sanitize_text_field( $_POST['events-status'] ) );
    }
}

add_action( 'save_post', 'events_save');

function add_events_shortcode($atts){
	$return = '';
	extract(shortcode_atts(array(
		"records_number" => '3',
		"status" => 'invited'
	), $atts, 'events_shortcode'));

	$events = get_posts( array(
		'post_status' => 'publish',
		'post_type' => 'events',
		'numberposts' => $records_number,
        'meta_key' => 'events-status',
        'meta_value' => $status,
	) );
		foreach($events as $event){
			$event_date = date( ' Y/m/d.', intval( get_post_meta( $event->ID, 'events-date', true ) ) );
			$current_date = date('Y/m/d');
			if( $event_date >= $current_date ) {
				$return .=
					'<li>' . $event->post_title . '</li>' . '</br>';
				$return .= 'Event Date: ' . $event_date . '</br>';
				$return .= 'Event Status: ' . get_post_meta( $event->ID, 'events-status', true );
			}
		}
	/* Restore original Post Data */
	wp_reset_postdata();
	return $return;
}

add_shortcode('events_shortcode', 'add_events_shortcode');