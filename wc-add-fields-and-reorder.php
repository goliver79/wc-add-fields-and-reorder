<?php
	if ( !class_exists( 'GoAddCustomFields' ) ) {
		class GoAddCustomFields {
			function activate() {
				add_filter( 'woocommerce_default_address_fields', array( $this, 'go_add_woo_fields' ) );
				add_filter( 'woocommerce_order_formatted_billing_address', array(
					$this,
					'lgdp_add_woo_billing_fields'
				), 1, 2 );
				add_filter( 'woocommerce_order_formatted_shipping_address', array(
					$this,
					'lgdp_add_woo_shipping_fields'
				), 1, 2 );
				add_filter( 'woocommerce_formatted_address_replacements', array(
					$this,
					'go_format_billing_address'
				), 1, 2 );
				add_filter( 'woocommerce_localisation_address_formats', array(
					$this,
					'go_format_localized_address'
				) );
				add_filter( 'woocommerce_customer_meta_fields', array( $this, 'go_add_woo_user_fields' ) );
				add_filter( 'woocommerce_user_column_billing_address', array(
					$this,
					'go_add_fields_billing_address'
				), 1, 2 );
				add_filter( 'woocommerce_user_column_shipping_address', array(
					$this,
					'go_add_fields_shipping_address'
				), 1, 2 );
				add_filter( 'woocommerce_my_account_my_address_formatted_address', array(
					$this,
					'go_add_fields_edit_my_address'
				), 10, 3 );
				add_filter( 'woocommerce_admin_billing_fields', array( $this, 'go_add_fields_edit_order_address' ) );
				add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'go_add_fields_edit_order_address' ) );
				add_filter( 'woocommerce_my_account_my_address_formatted_address', array(
					$this,
					'go_add_fields_address_edit'
				), 10, 3 );

				// WC > 3.0
				add_filter( 'woocommerce_ajax_get_customer_details', array(
					$this,
					'go_custom_found_customer_details'
				), 10, 3 );
			}

			function go_add_woo_fields( $fields ) {
				$fields[ 'nif' ] = array(
					'label'    => __( 'NIF/CIF', 'goaddcustomfields' ),
					'required' => TRUE
				);

				return $fields;
			}

			function lgdp_add_woo_billing_fields( $fields, $order ) {
				$fields[ 'nif' ] = ( isset( $order->order_custom_fields[ '_billing_nif' ][ 0 ] ) ? $order->order_custom_fields[ '_billing_nif' ][ 0 ] : '' );

				return $fields;
			}

			function lgdp_add_woo_shipping_fields( $fields, $order ) {
				$fields = (array)$fields;
				$fields[ 'nif' ] = ( isset( $order->order_custom_fields[ '_shipping_nif' ][ 0 ] ) ? $order->order_custom_fields[ '_shipping_nif' ][ 0 ] : '' );

				return $fields;
			}

			function go_format_billing_address( $fields, $args ) {
				$fields[ '{nif}' ]       = ( isset( $args[ 'nif' ] ) ? $args[ 'nif' ] : '' );
				$fields[ '{nif_upper}' ] = ( isset( $fields[ 'nif' ] ) ? strtoupper( $fields[ 'nif' ] ) : '' );

				return $fields;
			}

			//Reordenamos los campos de la dirección predeterminada
			function go_format_localized_address( $fields ) {
				$fields[ 'default' ] = "{name}\n{company}\n{nif}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}";
				$fields[ 'ES' ]      = "{name}\n{company}\n{nif}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}";
				$fields[ 'CA' ]      = "{name}\n{company}\n{nif}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}";
				$fields[ 'EN' ]      = "{name}\n{company}\n{nif}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}";
				$fields[ 'IT' ]      = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{state_upper}\n{country}";

				return $fields;
			}

			//Añade el campo CIF/NIF a usuarios
			function go_add_woo_user_fields( $fields ) {
				$fields[ 'billing' ][ 'fields' ][ 'billing_nif' ]   = array(
					'label'       => __( 'NIF/CIF', 'goaddcustomfields' ),
					'description' => ''
				);
				$fields[ 'shipping' ][ 'fields' ][ 'shipping_nif' ] = array(
					'label'       => __( 'NIF/CIF', 'goaddcustomfields' ),
					'description' => ''
				);
				$new_fields                                         = apply_filters( 'wcbcf_customer_meta_fields', $fields );

				return $new_fields;
			}

			//Añadimos el NIF a la dirección de facturación y envío
			function go_add_fields_billing_address( $fields, $user ) {
				$fields[ 'nif' ] = get_user_meta( $user, 'billing_nif', TRUE );

				return $fields;
			}

			function go_add_fields_shipping_address( $fields, $user ) {
				$fields[ 'nif' ] = get_user_meta( $user, 'shipping_nif', TRUE );

				return $fields;
			}

			//Añade el campo NIF a Editar mi dirección
			function go_add_fields_edit_my_address( $fields, $user, $name ) {
				$fields[ 'nif' ] = get_user_meta( $user, $name . '_nif', TRUE );

				return $fields;
			}

			//Añade el campo NIF a Detalles del pedido
			function go_add_fields_edit_order_address( $fields ) {
				$fields[ 'nif' ] = array(
					'label' => __( 'NIF/CIF', 'goaddcustomfields' ),
					'show'  => FALSE
				);

				return $fields;
			}

			//Añade el campo NIF a Editar mi dirección
			function go_add_fields_address_edit( $fields, $user, $name ) {
				$fields[ 'nif' ] = get_user_meta( $user, $name . '_nif', TRUE );

				return $fields;
			}

			// populate nif data in manual admin orders
			function go_custom_found_customer_details( $data, $customer, $user_id ) {
				$data[ 'shipping' ][ 'nif' ] = get_user_meta( $user_id, 'shipping_nif', TRUE );
				$data[ 'billing' ][ 'nif' ]  = get_user_meta( $user_id, 'billing_nif', TRUE );

				return $data;
			}
		}

		$go_add_custom_fields = new GoAddCustomFields();
		$go_add_custom_fields->activate();
	}
