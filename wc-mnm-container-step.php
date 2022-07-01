<?php
/**
 * Plugin Name: WooCommerce Mix and Match - Container Step
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 * Version: 1.0.0-beta-3
 * Description: Require container size to be in quantity mnultiples, ie: 12,16,20,etc. 
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-container-step
 * Domain Path: /languages
 *
 * Copyright: © 2020 Kathy Darling
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * The Main WC_MNM_Container_Step class
 **/
if ( ! class_exists( 'WC_MNM_Container_Step' ) ) :

class WC_MNM_Container_Step {

	/**
	 * constants
	 */
	CONST VERSION = '1.0.0-beta-3';

	/**
	 * WC_MNM_Container_Step Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Container_Step
	 */
	public static function init() {

		// Quietly quit if MNM is not active.
		if ( ! function_exists( 'wc_mix_and_match' ) ) {
			return false;
		}

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Add extra meta.
		add_action( 'woocommerce_mnm_product_options', array( __CLASS__, 'container_size_options') , 15, 2 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_meta' ), 20 );

		// Register Scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'woocommerce_mnm_add_to_cart_parameters', array( __CLASS__, 'script_parameters' ) );
		add_filter( 'woocommerce_mix_and_match_data_attributes', array( __CLASS__, 'add_data_attributes' ), 10, 2 );

		// Display Scripts.
		add_action( 'woocommerce_mix-and-match_add_to_cart', array( __CLASS__, 'load_scripts' ) );

		// QuickView support.
		add_action( 'wc_quick_view_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );

		// Add to cart validation.
		add_filter( 'woocommerce_mnm_add_to_cart_container_validation', array( __CLASS__, 'validation' ), 10, 3 );

    }


	/*-----------------------------------------------------------------------------------*/
	/* Localization */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Make the plugin translation ready
	 *
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-mnm-container-step' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
	}

	/*-----------------------------------------------------------------------------------*/
	/* Admin */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Adds the container max weight option writepanel options.
	 *
	 * @param int $post_id
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function container_size_options( $post_id, $mnm_product_object ) {

		woocommerce_wp_text_input( array(
			'id'            => '_mnm_container_step',
			'label'       => __( 'Container Size Step', 'wc-mnm-min-weight' ),
			'desc_tip'    => true,
			'description' => __( 'Force customers to purchase quantities in multiples. Ignored if min and max sizes are the same.', 'woocommerce' ),
			'type'        => 'number',
			'data_type'   => 'decimal',
			'value'			=> $mnm_product_object->get_meta( '_mnm_container_step', true, 'edit' ),
			'desc_tip'      => true,
		) );

	}

	/**
	 * Saves the new meta field.
	 *
	 * @param  WC_Product_Mix_and_Match  $mnm_product_object
	 */
	public static function process_meta( $product ) {

		if ( $product->is_type( 'mix-and-match' ) ) {

			if( ! empty( $_POST[ '_mnm_container_step' ] ) ) {
				$product->update_meta_data( '_mnm_container_step', intval( wc_clean( wp_unslash( $_POST[ '_mnm_container_step' ] ) ) ) );
			} else {
				$product->delete_meta_data( '_mnm_container_step' );
			}

		}

	}


	/*-----------------------------------------------------------------------------------*/
	/* Cart Functions */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Server-side validation
	 * 
	 * @param bool $is_valid
	 * @param obj WC_Product_Mix_and_Match $product
	 * @param obj WC_Mix_and_Match_Stock_Manager $mnm_stock
	 * @return  bool 
	 */
	public static function validation( $valid, $product, $mnm_stock ) {

		if( $product->get_meta( '_mnm_container_step', true ) && $product->get_min_container_size() !== $product->get_max_container_size() ) {		

			$total_qty = $mnm_stock->get_total_quantity();
			$step      = $product->get_meta( '_mnm_container_step', true );

			// Validate the step modulus.
			if ( 0 !== $total_qty % $step ) {
				$error_message = sprintf( 
					__( '&quot;%1$s&quot; is incorrectly configured. The total quantity of selected products must be a multiple of %2$d.', 'wc-mnm-container-step' ),
					$product->get_title(),
					$step
				);
				wc_add_notice( $error_message, 'error' );
				$valid = false;
			}

			$valid = true;

		}

		return $valid;
	}

	/*-----------------------------------------------------------------------------------*/
	/* Scripts and Styles */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Script parameters
	 *
	 * @param  array $params
	 * @return array
	 */
	public static function script_parameters( $params ) {

		$new_params = array(
			'i18n_qty_error'              => __( '%vPlease select %s items to continue. ', 'wc-mnm-container-step' ),
			'i18n_qty_error_single'       => __( '%vPlease select %s item to continue. ', 'wc-mnm-container-step' ),
			'i18n_empty_error'   		  => __( 'Please select at least 1 item to continue. ', 'wc-mnm-container-step' ),
			'i18n_min_max_qty_error'      => __( '%vPlease choose between %min and %max items to continue. ', 'wc-mnm-container-step' ),
			'i18n_min_qty_error_singular' => __( '%vPlease choose at least %min item to continue. ', 'wc-mnm-container-step' ),
			'i18n_min_qty_error'          => __( '%vPlease choose at least %min items to continue. ', 'wc-mnm-container-step' ),
			'i18n_max_qty_error_singular' => __( '%vPlease choose fewer than %max item to continue. ', 'wc-mnm-container-step' ),
			'i18n_max_qty_error'          => __( '%vPlease choose fewer than %max items to continue. ', 'wc-mnm-container-step' ),
			'i18n_step_error'             => __( '%vYour total quantity of items must be a multiple of %step.', 'wc-mnm-container-step' ),
			'i18n_min_or_max_error'       => __( '%vPlease choose either %min or %max items to continue&hellip;.', 'wc-mnm-container-step' ),
		);

		return array_merge( $params, $new_params );
 
	}

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public static function register_scripts() {

		wp_register_script( 'wc-add-to-cart-mnm-step-validation', plugins_url( 'js/wc-add-to-cart-mnm-step-validation.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), self::VERSION, true );

	}

	/**
	 * Product-specific data attributes.
	 *
	 * @param  array $params
	 * @param  obj WC_Mix_and_Match_Product
	 * @return array
	 */
	public static function add_data_attributes( $params, $product ) {

		if( self::validate_by_step( $product ) ) {

			$new_params = array(
			    'container_step' => $product->get_meta( '_mnm_container_step', true )
			);

			$params = array_merge( $params, $new_params );

		}

		return $params;

	}


	/**
	 * Load the script anywhere the MNN add to cart button is displayed
	 * @return void
	 */
	public static function load_scripts() {
		global $product;
		if ( self::validate_by_step( $product ) ) { 
			wp_enqueue_script( 'wc-add-to-cart-mnm-step-validation' );
		}
	}

	/*-----------------------------------------------------------------------------------*/
	/* Helpers                                                                           */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Does this product validate by weight.
	 * @param  WC_Product
	 * @return bool
	 */
	public static function validate_by_step( $product ) {
		return $product instanceOf WC_Product_Mix_and_Match && intval( $product->get_meta( '_mnm_container_step', true ) ) > 0 && $product->get_min_container_size() !== $product->get_max_container_size();
	}


} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'plugins_loaded', array( 'WC_MNM_Container_Step', 'init' ), 20 );
