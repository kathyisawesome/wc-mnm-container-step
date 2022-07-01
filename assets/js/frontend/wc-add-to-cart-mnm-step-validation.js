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
				container.step = parseInt( container.$mnm_cart.data( 'container_step' ) );
				container.either_or = 1 === ( container.api.get_max_container_size() - container.api.get_min_container_size() ) / container.step;
				
				if ( container.either_or ) {
					wc_mnm_params.i18n_min_max_qty_error = '';
				}
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
		 * Get step error message.
		 */
		this.get_step_message = function( container ) {

			var message = '';

			var min = container.api.get_min_container_size();
			var max = container.api.get_max_container_size();

			if ( container.either_or ) {
				message = wc_mnm_params.i18n_min_or_max_error.replace( '%min', container.api.get_min_container_size() ).replace( '%max', container.api.get_max_container_size() );
			} else {
				message = wc_mnm_params.i18n_step_error.replace( '%step', container.step );
			}

			return message;

		}

		/**
		 * Validate.
		 */
		this.validate = function( event, container ) {

			var min = container.api.get_min_container_size();
			var max = container.api.get_max_container_size();

			if( container.step > 1 ) {

				if( container.passes_validation() && 0 !== ( container.api.get_container_size() % container.step ) ) {

					// "Selected X total".
					var selected_qty_message = container.selected_quantity_message( container.api.get_container_size() );

					// Add error message, replacing placeholders with current values.
					var message = self.get_step_message( container ).replace( '%v', selected_qty_message );

					container.add_message( message, 'error' );

				} else {
					
					var messages = container.api.get_validation_messages();

					// Add step notice to existing error.
					messages.forEach(function(message, i) {
						var step_message = self.get_step_message( container );
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
		var step = new WC_MNM_Container_Step( container );
		step.initialize();
	});

} ) ( jQuery );