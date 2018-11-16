<?php

/**
 * Integration with Event Tickets Plus by Tribe
 *
 * @see  https://theeventscalendar.com/product/wordpress-event-tickets-plus/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if tribe plugin is activated
if ( !class_exists('Tribe__Tickets_Plus__Commerce__WooCommerce__Main') ) return;


class WCBA_Tribe_Tickets_Plus_Integration {

public $tribe_instance;

public $order_id = null;


public function __construct( $order_id ) {
	$this->tribe_instance = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
	
	$this->order_id = $order_id;
}

/**
 * Get the event related to order ID
 *
 * @since  1.0
 */

public function get_event_by_order_id() {
	
	if ( !$this->order_id ) return;

	$event_id = $this->tribe_instance->get_event_id_from_order_id( $this->order_id );
	
	return $event_id;

}

// /**
//  * Check if there are tickets for an event
//  *
//  * @since  1.0
//  * @return  boolean
//  */

// public function check_if_any_tickets( $post_id ) {
// 	if ( ! class_exists('Tribe__Tickets_Plus__Commerce__WooCommerce__Main') ) return;

// 	$tickets_instance = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
// 	$tickets = $tickets_instance->get_tickets_ids( $post_id );

// 	if ( $tickets && !empty($tickets) ) {
// 		return true;
// 	}

// 	return false;

// }

// /**
//  * Get all events by user id
//  *
//  * @since  1.0
//  */

// public function get_events_by_user_id( $user_id ) {
	
// 	if ( !$user_id ) return;

// 	// Get orders id in array
// 	$query = new WC_Order_Query( array(
// 	    'limit' => -1,
// 	    'customer_id' => $user_id,
// 	    'return' => 'ids',
// 	) );

// 	$orders = $query->get_orders();
// 	$events = [];

// 	if ( !class_exists('Tribe__Tickets_Plus__Commerce__WooCommerce__Main') ) return $orders;

// 	// Loop through orders to get events
// 	foreach ( $orders as $order ) {
// 		$event_id = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance()->get_event_id_from_order_id( $order );
// 		$events[] = $event_id;
// 	}

// 	return array_filter( $events );
// }

}
