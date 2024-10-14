<?php
/**
 * Plugin Name: WooCommerce Mix and Match - Container Step
 * Plugin URI: https://github.com/kathyisawesome/wc-mnm-container-step
 * Version: 2.0.2
 * Description: Require container size to be in quantity mnultiples, ie: 12,16,20,etc. 
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com/
 * Developer: Kathy Darling
 * Developer URI: http://kathyisawesome.com/
 * Text Domain: wc-mnm-container-step
 * Domain Path: /languages
 * 
 * GitHub Plugin URI: kathyisawesome/wc-mnm-grouped
 * GitHub Plugin URI: https://github.com/kathyisawesome/wc-mnm-grouped
 * Release Asset: true
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
	const VERSION = '2.0.2';
	const REQ_MNM_VERSION = '2.6.0';

	/**
	 * WC_MNM_Container_Step Constructor
	 *
	 * @access 	public
     * @return 	WC_MNM_Container_Step
	 */
	public static function init() {

		// Quietly quit if MNM is not active.
		if ( ! function_exists( 'wc_mix_and_match' ) || version_compare( wc_mix_and_match()->version, self::REQ_MNM_VERSION ) < 0 ) {
			return false;
		}

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Add extra meta.
		add_action( 'wc_mnm_admin_product_options', array( __CLASS__, 'container_size_options') , 15, 2 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'process_meta' ), 20 );

		// Register Scripts.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_filter( 'wc_mnm_add_to_cart_script_parameters', array( __CLASS__, 'script_parameters' ) );
		add_filter( 'wc_mnm_container_data_attributes', array( __CLASS__, 'add_data_attributes' ), 10, 2 );

		// Display Scripts.
		add_action( 'woocommerce_mix-and-match_add_to_cart', array( __CLASS__, 'load_scripts' ) );

		// QuickView support.
		add_action( 'wc_quick_view_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );

		// Cart validation.
		add_filter( 'wc_mnm_add_to_cart_container_validation', array( __CLASS__, 'validation' ), 10, 3 );
		add_filter( 'wc_mnm_cart_container_validation', array( __CLASS__, 'validation' ), 10, 3 );
		add_filter( 'wc_mnm_add_to_order_container_validation', array( __CLASS__, 'validation' ), 10, 3 );

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
			'label'       => esc_html__( 'Container Size Step', 'wc-mnm-container-step' ),
			'desc_tip'    => true,
			'description' => esc_html__( 'Force customers to purchase quantities in multiples. Ignored if min and max sizes are the same.', 'wc-mnm-container-step' ),
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

			if ( ! empty( $_POST[ '_mnm_container_step' ] ) ) {
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

		$hook = current_filter();

		$hook = str_replace( 'wc_mnm_', '', $hook );
		$hook = str_replace( '_container_validation', '', $hook );
		$context = str_replace( '_', '-', $hook );

		if ( $product->get_meta( '_mnm_container_step', true ) && $product->get_min_container_size() !== $product->get_max_container_size() ) {		

			$total_qty = $mnm_stock->get_total_quantity();
			$step      = $product->get_meta( '_mnm_container_step', true );

			// Validate the step modulus.
			if ( 0 !== $total_qty % $step ) {

				$reason = sprintf( esc_html__( 'The total quantity of selected products must be a multiple of %d.', 'wc-mnm-container-step' ), $step );
				
				if ( 'add-to-cart' === $context ) {
					// translators: %1$s product title. %2$s Error reason.
					$error_message = sprintf( _x( '&quot;%1$s&quot; cannot be added to the cart as configured. %2$s', 'wc-mnm-container-step' ), $product->get_title(), $reason );
				} else {
					// translators: %1$s product title. %2$s Error reason.
					$error_message = sprintf( _x( '&quot;%1$s&quot; cannot be purchased as configured. %2$s', 'wc-mnm-container-step' ), $product->get_title(), $reason );
				}

				throw new Exception( $error_message );
			}

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
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_min_qty_error_singular'   => _x( '%v Please select at least %min item (in multiples of %step) to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_min_qty_error'            => _x( '%v Please select at least %min items (in multiples of %step) to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_max_qty_error_singular'   => _x( '%v Please select fewer than %max item (in multiples of %step) to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_max_qty_error'            => _x( '%v Please select fewer than %max items (in multiples of %step) to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_min_max_qty_error'        => esc_html_x( '%v Please select between %min and %max items (in multiples of %step) to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the script placeholder for min quantity. %max is script placeholder for max quantity.
			'i18n_step_min_or_max_error'         => esc_html_x( '%v Please choose either %min or %max items to continue&hellip;.', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_message'       => _x( '%v You may select any multiple of %step items or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_min_max_message'    => _x( '%v You may select between %min and %max items (in multiples of %step) or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_max_message'    => _x( '%v You may select fewer items (in multiples of %step) or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_max_no_min_message'    => _x( '%v You may select up to %max items (in multiples of %step) or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_min_message'    => _x( '%v You may select more items (in multiples of %step) or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_step_valid_min_or_max_message' => _x( '%v You may select either %min or %max items, or add to cart to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),

			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_message'       => _x( '%v You may select any multiple of %step items or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_min_max_message'    => _x( '%v You may select between %min and %max items (in multiples of %step) or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_max_message'    => _x( '%v You may select fewer items (in multiples of %step) or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_max_no_min_message'    => _x( '%v You may select up to %max items (in multiples of %step) or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_min_message'    => _x( '%v You may select more items (in multiples of %step) or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),
			// translators                       :  %v is the current quantity message. %min is the container minimum. %max is the container maximum.
			'i18n_edit_step_valid_min_or_max_message' => _x( '%v You may select either %min or %max items, or update to continue&hellip;', '[Frontend]', 'wc-mnm-container-step' ),

		);

		return array_merge( $params, $new_params );
 
	}

	/**
	 * Register scripts
	 *
	 * @return void
	 */
	public static function register_scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( 'wc-add-to-cart-mnm-step-validation', plugins_url( 'assets/js/frontend/wc-add-to-cart-mnm-step-validation' . $suffix . '.js', __FILE__ ), array( 'wc-add-to-cart-mnm' ), self::VERSION, true );
	}

	/**
	 * Product-specific data attributes.
	 *
	 * @param  array $params
	 * @param  obj WC_Mix_and_Match_Product
	 * @return array
	 */
	public static function add_data_attributes( $params, $product ) {

		if ( self::validate_by_step( $product ) ) {

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
