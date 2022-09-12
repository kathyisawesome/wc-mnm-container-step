( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Container_Step( container ) {

		this.container = container;
		this.$form     = container.$mnm_form;

		/**
		 * Init.
		 */
		this.initialize = function() {
			if ( 'undefined' !== typeof container.$mnm_cart.data( 'container_step' ) && parseInt( container.$mnm_cart.data( 'container_step' ), 10 ) > 0 ) {
				container.step = parseInt( container.$mnm_cart.data( 'container_step' ), 10 );
				container.min_or_max = 1 === ( container.api.get_max_container_size() - container.api.get_min_container_size() ) / container.step;
				this.bind_event_handlers();
			}
		};

		/**
		 * Container-Level Event Handlers.
		 */
		this.bind_event_handlers = function() {
			this.$form.on( 'wc-mnm-validation', this.validate );
		};

		/**
		 * Get step error message.
		 */
		this.get_step_message = function( container ) {

			var message = '';

			if ( container.min_or_max ) {
				message = wc_mnm_params.i18n_step_min_or_max_error.replace( '%min', container.api.get_min_container_size() ).replace( '%max', container.api.get_max_container_size() );
			} else {
				message = wc_mnm_params.i18n_step_error.replace( '%step', container.step );
			}

			return message;

		};

		/**
		 * Validate.
		 */
		this.validate = function( event, container ) {

			var min_container_size = container.api.get_min_container_size();
			var max_container_size = container.api.get_max_container_size();

			if ( container.step > 1 && min_container_size !== max_container_size ) {

				var error_message      = '';
				var valid_message      = '';
				var total_qty          = container.api.get_container_size();
				var qty_message        = container.selected_quantity_message( total_qty ); // "Selected X total".
				var passes_validation  = 0 == ( total_qty % container.step );

				console.debug("passes_validation", passes_validation);

				// Validation.
				switch ( true ) {

					// Validate a min OR max container.
					case container.min_or_max:
	
						valid_message = 'undefined' !== typeof wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_min_or_max_message'  ] ?  wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_min_or_max_message'  ] : wc_mnm_params.i18n_step_valid_min_or_max_message;

						if ( total_qty !== min_container_size && total_qty !== max_container_size ) {
							error_message = wc_mnm_params.i18n_step_min_or_max_error;
						}

						break;

					// Validate that a container has fewer than the maximum number of items.
					case max_container_size > 0 && min_container_size === 0:

						if ( min_container_size === 0 ) {
							valid_message = 'undefined' !== typeof wc_mnm_params[ 'i18n_' + container.validation_context + 'valid_max_no_min_message'  ] ?  wc_mnm_params[ 'i18n_' + container.validation_context + 'valid_max_no_min_message'  ] : wc_mnm_params.i18n_step_valid_max_no_min_message;
						} else {
							valid_message = 'undefined' !== typeof wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_max_message'  ] ?  wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_max_message'  ] : wc_mnm_params.i18n_step_valid_max_message;
						}

						if ( total_qty > max_container_size || ! passes_validation ) {
							error_message = wc_mnm_params.i18n_step_max_qty_error;
						}

						break;

					// Validate a range.
					case max_container_size > 0 && min_container_size > 0:

						valid_message = 'undefined' !== typeof wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_min_max_message'  ] ?  wc_mnm_params[ 'i18n_' + container.validation_context + '_step_valid_min_max_message'  ] : wc_mnm_params.i18n_step_valid_min_max_message;

						if ( total_qty < min_container_size || total_qty > max_container_size || ! passes_validation ) {
							error_message = wc_mnm_params.i18n_step_min_max_qty_error;
						}
					break;

					// Validate that a container has minimum number of items.
					case min_container_size >= 0:

						valid_message = 'undefined' !== typeof wc_mnm_params[ 'i18n_' + container.validation_context + 'i18n_step_valid_min_message'  ] ?  wc_mnm_params[ 'i18n_' + container.validation_context + 'i18n_step_valid_min_message'  ] : wc_mnm_params.i18n_step_valid_min_message;

						if ( total_qty < min_container_size || ! passes_validation ) {
							error_message = min_container_size > 1 ? wc_mnm_params.i18n_step_min_qty_error : wc_mnm_params.i18n_step_min_qty_error_singular;
						}

						break;

				}

				if ( error_message !== '' ) {

					// Clear existing messages.
					container.reset_messages();
					
					error_message = error_message.replace( '%max', max_container_size ).replace( '%min', min_container_size ).replace( '%step', container.step );
					container.add_message( error_message.replace( '%v', qty_message ), 'error' );

					// Add selected qty status message if there are no error messages.
				} else if ( valid_message !== '' ) {

					// Clear existing messages.
					container.reset_messages();

					valid_message = valid_message.replace( '%max', max_container_size ).replace( '%min', min_container_size ).replace( '%step', container.step );
					container.add_message( valid_message.replace( '%v', qty_message ) );
				}

			}

		};

	} // End WC_MNM_Container_Step.

	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/

	$( 'body' ).on( 'wc-mnm-initializing', function( e, container ) {
		var step = new WC_MNM_Container_Step( container );
		step.initialize();
	});

} ) ( jQuery );