<?php

/*
Plugin Name: Events
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Events Plugin for W4P.
Version: 1.0
Author: Vladyslav Kukil
*/

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
		'name' => 'Events',
		'singular_name' => 'Event',
		'add_new' => 'Add Event',
		'add_new_item' => 'Adding Event',
		'new_item' => 'New Event',
		'view_item' => 'View Event',
		'search_items' => 'Search Events',
		'not_found' => 'Events not found',
		'not_found_in_trash' => 'Events not fount in trash',
		'all_items' => 'All Events',
		'filter_items_list' => 'Filter Events',
		'items_list_navigation' => 'Events navigation',
		'items_list' => 'List of Events',
		'menu_name' => 'Events',
		'name_admin_bar' => 'Event',
		'archives' => 'Event archives',
		'attributes' => 'Event attributes',
		'parent_item_colon' => 'Parent Event',
		'view_items' => 'View Events',
		'item_updated' => 'Event was updated',
		'item_published' => 'Events was published',
		'item_published_privately' => 'Events was published privately',
		'item_reverted_to_draft' => 'Events not fount',
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
		'capability_type' => 'post',
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
		esc_html__( 'Status', 'eventlist' ),
		'events_callback',   // Callback function
		'events',         // Admin page (or post type)
		'side',         // Context
		'default'         // Priority
	);
}
function events_callback($post) {
    // generate a nonce field
    wp_nonce_field( 'events_meta_box', 'events_nonce' );
    // get previously saved meta values (if any)
    $event_date = get_post_meta( $post->ID, 'events-date', true );
    $event_status = get_post_meta( $post->ID, 'events-status', true );
    $event_date = ! empty( $event_date ) ? $event_date : time();
    ?>

    <p><label for="events-date"><?php _e( 'Event Date', 'eventlist' ); ?></label>

        <input class="widefat" id="events-date" type="date" name="events-date" required maxlength="30"
               placeholder="Event Date" value="<?php echo date('d.m.y',
            sanitize_text_field( $event_date )); ?>" /></p>

    <p><label for="events-status"><?php _e( 'Event Status', 'eventlist' ); ?></label>
        <input class="widefat" id="events-status" type="text" name="events-status" required maxlength="150"
               placeholder="Open/Invited" value="<?php echo sanitize_text_field( $event_status ); ?>" /></p>
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

function events_custom_columns( $defaults ) {
    unset( $defaults['date'] );
    $defaults['events_date'] = 'Event Date';
    $defaults['events_status'] ='Event Status';
    return $defaults;
}
add_filter( 'manage_edit_event_columns', 'events_custom_columns', 10 );

function events_custom_columns_content( $column_name, $post_id ) {
    if ( 'events_date' == $column_name ) {
        $date = get_post_meta( $post_id, 'events-date', true );
        echo date( 'd-m-Y', $date );
    }
    if ( 'events_status' == $column_name ) {
        $status = get_post_meta( $post_id, 'events-status', true );
        echo $status;
    }
}
add_action( 'manage_event_posts_custom_column', 'events_custom_columns_content', 10, 2 );


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
		    $return .=
                '<li>' . $event->post_title . '</li>' . '</br>' ;
                $return .='Event Date: ' . date( 'd.m.y', intval( get_post_meta( $event->ID, 'events-date', true  ))) . '</br>' ;
                $return .= 'Event Status: ' . get_post_meta( $event->ID, 'events-status', true );
		}


	/* Restore original Post Data */
	wp_reset_postdata();
	return $return;
}

add_shortcode('events_shortcode', 'add_events_shortcode');