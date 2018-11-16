<?php

/**
 * Admin settings in WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_Vendor_Bank_Settings {

	private $id;


	public function __construct() {
		$this->id = 'wpvba';

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 40 );

		add_action( 'woocommerce_settings_tabs_' . $this->id, array( $this, 'add_section_to_tab' ) );

		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'update_options' ) );

		// Add more paypal fields to checkout tab
		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'paypal_add_section' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'paypal_all_settings' ), 10, 2 );
	}


	/**
	 * Create settings tab for WooCommerce settings
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ $this->id ] = __( 'Product Vendor Bank', 'wpvba' );

		return $tabs;
	}

	/**
	 * Create the section beneath the checkout tab
	 */
	public function paypal_add_section( $sections ) {

		$sections[ $this->id . '_paypal' ] = __( 'PayPal Emails', 'wpvba' );
		return $sections;

	}

	/**
	 * Add more fields for additional PayPal emails
	 *
	 * @param  $settings    initial WooCommerce settings for this tab
	 * @param  string                                                $current_section Current selected section
	 */
	public function paypal_all_settings( $settings, $current_section ) {
		if ( $current_section == $this->id . '_paypal' ) {

			$settings_paypal = array();

			// Add Title to the Settings
			$settings_paypal[] = array(
				'name' => __( 'Product Vendor PayPal Emails', 'wpvba' ),
				'type' => 'title',
				'desc' => __( 'Please add up to 6 additional PayPal Emails', 'wpvba' ),
				'id'   => 'wpvba_paypal_title',
			);

			// Add additional paypal fields
			for ( $i = 1; $i < 7; $i++ ) {
				$settings_paypal[] = array(
					'name'     => __( 'PayPal Email #' . $i, 'wpvba' ),
					'desc_tip' => __( 'Be careful when typing since this field accepts any characters', 'wpvba' ),
					'id'       => $this->id . '_paypal_' . $i,
					'type'     => 'text',
					'desc'     => __( 'Add active PayPal email address.', 'wpvba' ),
				);
			}

			$settings_paypal[] = array(
				'type' => 'sectionend',
				'id'   => 'wpvba_paypal_title',
			);
			return $settings_paypal;

		} else {
			return $settings;
		}
	}

	/**
	 * Get all available users
	 *
	 * @since  1.0
	 * @return  $users array
	 */
	public function get_wp_users() : array {

		$all_users = get_users(
			array(
				'role__in'     => array(),
				'role__not_in' => array(),
				'fields'       => 'all',
				'who'          => '',
			)
		);

		$all_users_options['all'] = __( 'Select user', 'wpvba' );

		foreach ( $all_users as $user ) {
			$all_users_options[ $user->ID ] = esc_html( $user->display_name . ' ' . $user->user_email );
		}

		return $all_users_options;
	}

	/**
	 * Create input field for every available bank account
	 *
	 * @return $fields array
	 */
	public function create_bank_fields() {
		$gateways = WC()->payment_gateways->payment_gateways();
		$bacs     = $gateways['bacs'];

		$fields = array();

		$fields[] = array(
			'title' => __( 'Event Tickets Plus Support', 'wpvba' ),
			'label' => __( 'Enable?', 'wpvba' ),
			'id'    => $this->id . '_enable_event_tickets',
			'type'  => 'checkbox',
			'desc'  => __( 'Enable support for Event Tickets to filter bank accounts based on Event post author.', 'wpvba' ),
		);

		if ( ! empty( $bacs->account_details ) ) {

			foreach ( $bacs->account_details as $account ) {
				$fields[] = array(
					'title'   => implode( ', ', array_filter( $account ) ),
					// 'type'    => 'multi_select_countries',
					// TODO no IDs on bank accounts, it's neccessary to use all fields to create a key
					'id'      => $this->id . '_' . md5( serialize( $account ) ),
					'type'    => 'select',
					'label'   => __( 'Choose Users', 'wpvba' ),
					'options' => $this->get_wp_users(),
				);
			}
		} else {
			$fields[] = array(
				'title' => __( 'No bank accounts found', 'wpvba' ),
				'desc'  => __( 'Please, first set up bank account details', 'wpvba' ) . ' <a href="' . network_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bacs' ) . '">' . esc_html__( 'here', 'wpvba' ) . '</a>',
				'type'  => 'title',
				'id'    => $this->id . '_no_accounts_notice',
			);
		}

		return $fields;
	}

	/**
	 * Create input field for every available PayPal address
	 *
	 * @return $fields array
	 */
	public function create_paypal_fields() {
		
		$fields = array();

		for ( $i = 1; $i < 7; $i++ ) {
			$paypal = get_option( $this->id . '_paypal_' . $i, true );

			if ( $paypal ) {

				$fields[] = array(
					'title'   => $paypal,
					'id'      => $this->id . '_paypal_account_' . $i,
					'type'    => 'select',
					'label'   => __( 'Choose Users', 'wpvba' ),
					'options' => $this->get_wp_users(),
				);

			}
			
		}

		return $fields;
	}

	/**
	 * Create section and include input fields in section
	 *
	 * @return array
	 */
	public function create_tab_section() {
		$section = array();

		$section[] = array(
			'title' => __( 'Product Vendor Bank Accounts', 'wpvba' ),
			'desc'  => __( 'Please choose which bank account wil be available for specific product vendor.', 'wpvba' ),
			'type'  => 'title',
			'id'    => $this->id,
		);

		$section = array_merge( $section, $this->create_bank_fields() );

		$section[] = array(
			'type' => 'sectionend',
			'id'   => $this->id,
		);

		$section[] = array(
			'title' => __( 'Product Vendor PayPal', 'wpvba' ),
			'desc'  => __( 'Please, connect users to the additional paypal emails configured ', 'wpvba' ) . ' <a href="' . network_admin_url( 'admin.php?page=wc-settings&tab=advanced&section=wpvba_paypal' ) . '">' . esc_html__( 'here', 'wpvba' ) . '</a>',
			'type'  => 'title',
			'id'    => $this->id . '_second',
		);

		$section_cache = $this->create_paypal_fields();

		if ( !empty($section_cache) ) {
			foreach ( $section_cache as $key => $value ) {
				$section[] = $value;
			}
		}

		$section[] = array(
			'type' => 'sectionend',
			'id'   => $this->id . '_second',
		);

		return $section;
	}

	/**
	 * Add section to tab
	 */
	public function add_section_to_tab() {
		woocommerce_admin_fields( $this->create_tab_section() );
	}

	/**
	 *  Update setting fields
	 */
	public function update_options() {
		woocommerce_update_options( $this->create_bank_fields() );
		woocommerce_update_options( $this->create_paypal_fields() );
	}
}
