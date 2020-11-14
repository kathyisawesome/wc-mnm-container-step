( function( $ ) {

	/**
	 * Main container object.
	 */
	function WC_MNM_Container_Step( container ) {

		var self       = this;
		this.container = container;
		this.$form     = container.$mnm_form;

		/**
		 * Init.
		 */

		this.initialize = function() {
			if( 'undefined' !== typeof container.$mnm_cart.data( 'container_step' ) && parseInt( container.$mnm_cart.data( 'container_step' ) ) > 0 ) {
				this.bind_event_handlers();		
			}

		};

		/**
		 * Container-Level Event Handlers.
		 */
		this.bind_event_handlers = function() {
			this.$form.on( 'wc-mnm-validation',     this.validate );
		};


		/**
		 * Validate.
		 */
		this.validate = function( event, container ) {

			var step = container.$mnm_cart.data( 'container_step' );
			if( typeof step === 'undefined' ){
				step = 1;
			}

			step = parseInt( step );

			if( step > 1 ) {

				if( container.passes_validation() && 0 !== ( container.api.get_container_size() % step ) ) {

					// "Selected X total".
					var selected_qty_message = container.selected_quantity_message( container.api.get_container_size() );

					// Add error message, replacing placeholders with current values.
					var message = wc_mnm_params.i18n_step_error.replace( '%step', step );
					message = message.replace( '%v', selected_qty_message );

					container.add_message( message, 'error' );


				} else {
					
					var messages = container.api.get_validation_messages();

					// Add step notice to existing error.
					messages.forEach(function(message, i) {
						var step_message = wc_mnm_params.i18n_step_error.replace( '%step', step );
						messages[i] = step_message.replace( '%v', message );
					});

				}
					

			}

		};

	} // End WC_MNM_Container_Step.

	/*-----------------------------------------------------------------*/
	/*  Initialization.                                                */
	/*-----------------------------------------------------------------*/

	$( 'body' ).on( 'wc-mnm-initializing', function( e, container ) {
		var weight = new WC_MNM_Container_Step( container );
		weight.initialize();
	});

} ) ( jQuery );