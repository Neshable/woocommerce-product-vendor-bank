<?php
/*
Plugin Name: WooCommerce Product Vendor Bank Accounts
Description: Select different bank accounts/PayPals per admin vendor.
Plugin URI:  https://github.com/Neshable/woocommerce-product-vendor-bank
Version:     1.0
Author:      Nesho Sabakov
Author URI:  https://neshable.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: wpvba
GitHub Plugin URI: https://github.com/Neshable/woocommerce-product-vendor-bank
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Vendor_Bank_Accounts {

	private $id;

	private $selected_user;

	public function __construct() {
		$this->id = 'wpvba';

		if ( is_admin() ) {
			add_action( 'woocommerce_loaded', array( $this, 'load_settings' ) );
			add_action( 'update_option_woocommerce_bacs_accounts', array( $this, 'bank_accounts_changed' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
			$this->add_authors_to_products();

		} else {
			add_action( 'woocommerce_thankyou_bacs', array( $this, 'set_selected_user' ), 1 );
			add_action( 'woocommerce_email_before_order_table', array( $this, 'set_selected_user' ), 1 );
			// Hook available bank accounts
			add_filter( 'woocommerce_bacs_accounts', array( $this, 'available_bank_accounts' ) );
			// Hook paypal emails
			add_filter( 'woocommerce_paypal_args', array( $this, 'choose_paypal_account' ), 10, 2 );
		}

	}

	/**
	 * Load main plugin settings
	 */
	public function load_settings() {
		require_once( 'class-wc-product-vendor-bank-settings.php' );

		new WC_Product_Vendor_Bank_Settings();
	}
	/**
	 * Add support for product authors
	 */
	public function add_authors_to_products() {
		if ( post_type_exists( 'product' ) ) {
			add_post_type_support( 'product', 'author' );
		}
	}

	/**
	 * Bank accounts settings changed hook
	 */
	public function bank_accounts_changed() { ?>

		<div class="notice notice-warning">
			<p><?php _e( 'Your bank accounts details have been updated. You should now connect the appropriate users ', 'wpvba' ); ?>
				<a href="<?php echo network_admin_url( 'admin.php?page=wc-settings&tab=' . $this->id ); ?>"><?php echo esc_html__( 'here.', 'wpvba' ); ?></a>
			</p>
		</div>

	<?php
	}

	/**
	 * Get the event or product author from order or order_id
	 */
	public function set_selected_user( $order ) {
		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $this->check_if_event_tickets_is_checked() ) {
			require_once( 'inc/class-tribe-tickets-plus-integration.php' );

			$event = new WCBA_Tribe_Tickets_Plus_Integration( $order->get_id() );

			$event_id = $event->get_event_by_order_id();

			$this->selected_user = get_post_field( 'post_author', $event_id );
		} else {
			$items      = $order->get_items();
			$product_id = $item[0]->get_product_id();
			// Get author for the Woo product
			$this->selected_user = get_post_field( 'post_author', $product_id );
		}

	}

	/**
	 * See if the checkbox for Ticket PLus is checked
	 *
	 * @return bool
	 */
	private function check_if_event_tickets_is_checked() {

		$ticket_plus_checkbox = get_option( $this->id . '_enable_event_tickets', true );

		if ( $ticket_plus_checkbox ) {
			return true;
		}

		return false;
	}

	/**
	 * Switch dinamically between additional bank accounts based on the author of the event/product
	 *
	 * @return array with updated PayPal args
	 */
	public function choose_paypal_account( $paypal_args, $order ) {
		$this->set_selected_user( $order );
		
		if ( isset( $this->selected_user ) ) {

			for ( $i = 1; $i < 7; $i++ ) {
				// Get connected user role
				$paypal_user_user = get_option( $this->id . '_paypal_account_' . $i, true );

				// If the current author and the options match then get the PayPal email
				if ( $paypal_user_user && ( $this->check_user_and_role( $this->selected_user, $paypal_user_user ) ) ) {
					$paypal_email = get_option( $this->id . '_paypal_' . $i, true );
					if ( $paypal_email ) {
						$paypal_args['business'] = $paypal_email;
					}
				}
			}
		}

		return $paypal_args;

	}

	/**
	 * See if the user is a part of a role
	 */
	public function check_user_and_role( $user_id, $role_name ) {
		// Get user meta details
		$user_meta = get_userdata( $user_id );

		$user_roles = $user_meta->roles; //array of roles the user is part of.

		if ( $user_roles && !empty( $user_roles ) ) {
			foreach ( $user_roles as $role ) {
				if ( $role == $role_name ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * List through available bank accounts,
	 * check if certain bank account is enabled for country,
	 * if no, unset it from $bacs_accounts array
	 *
	 * @return array with updated list of available bank accounts
	 */

	public function available_bank_accounts( $bacs_accounts ) {
		// Cache the initial bank accounts in case there are none left
		$init_bacs_accounts = array(
			'original' => $bacs_accounts,
			'modified' => $bacs_accounts,
		);

		if ( isset( $this->selected_user ) ) {

			foreach ( $init_bacs_accounts['modified'] as $i => $account ) {

				// $account_user = get_option( $this->id . '_' . md5( serialize( $account ) ), true );
				$account_user_role = get_option( $this->id . '_' . md5( serialize( $account ) ), true );

				if ( $account_user_role && ( !$this->check_user_and_role( $this->selected_user, $account_user_role ) ) ) {
					unset( $init_bacs_accounts['modified'][ $i ] );
				}
			}
			// If it's empty, then return all bank accounts
			if ( empty( $init_bacs_accounts['modified'] ) ) {
				return $init_bacs_accounts['original'];
			}
		}

		return $init_bacs_accounts['modified'];
	}

	/**
	 * Show action links on the plugin screen
	 */
	public function add_action_links( $links ) {
		// Donate link
		//array_unshift( $links, '<a href="http://softsab.com" title="' . esc_attr__( 'Go Premium', 'wpvba' ) . '" target="_blank">' . esc_html__( 'Premium', 'wpvba' ) . '</a>' );
		// Settings link
		array_unshift( $links, '<a href="' . network_admin_url( 'admin.php?page=wc-settings&tab=' . $this->id ) . '" title="' . esc_attr__( 'Settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>' );

		return $links;
	}
}

new WC_Product_Vendor_Bank_Accounts();
