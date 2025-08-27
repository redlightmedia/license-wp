<?php

namespace Never5\LicenseWP\Api;

/**
 * Class Activation
 * @package Never5\LicenseWP\Api
 */
class Activation {

	/**
	 * Setup API Activation endpoint
	 */
	public function setup() {
		add_action( 'woocommerce_api_wp_plugin_licencing_activation_api', array( $this, 'handle' ) );
		add_action( 'woocommerce_api_license_wp_api_activation', array( $this, 'handle' ) );
	}

	/**
	 * Handle API request
	 */
	public function handle() {
		global $wpdb;

		// hide DB errors
		$wpdb->hide_errors();

		// send no-cache header
		nocache_headers();

		// set request
		$request = array_map( 'sanitize_text_field', apply_filters( 'license_wp_api_activation_request', $_GET ) );

		try {

			$purchase_url = get_permalink( wc_get_page_id( 'shop' ) );

			// check for request var
			if ( ! isset( $request['request'] ) || empty( $request['request'] ) ) {
				throw new ApiException( __( 'Invalid API Request.', 'license-wp' ), 100 );
			}

			// check for license var
			if ( ! isset( $request['license_key'] ) || empty( $request['license_key'] ) ) {
				throw new ApiException( __( '<strong>Activation error:</strong> The provided license is invalid.', 'license-wp' ), 101 );
			}

			// check for api product ID var
			if ( ! isset( $request['api_product_id'] ) || empty( $request['api_product_id'] ) ) {
				throw new ApiException( __( '<strong>Activation error:</strong> Invalid API Product ID.', 'license-wp' ), 102 );
			}

			// get license
			/** @var \Never5\LicenseWP\License\License $license */
			$license = license_wp()->service( 'license_factory' )->make( $request['license_key'] );

			// check if license exists
			if ( '' == $license->get_key() ) {
				throw new ApiException( sprintf( __( '<strong>Activation error:</strong> The provided license is invalid. <a href="%s" target="_blank">Purchase a valid license</a> to receive updates and support.', 'license-wp' ), $purchase_url ), 101 );
			}

			// check if license expired
			if ( $license->is_expired() ) {
				throw new ApiException( sprintf( __( '<strong>Activation error:</strong> Your license has expired. You must <a href="%s" target="_blank">renew your license</a> if you want to use it again.', 'license-wp' ), $license->get_renewal_url() ), 110 ); // @todo add renew link
			}

			// check if license is linked to order and if so, if the order is not refunded
			if ( ! $license->has_valid_order_status() ) {
				throw new ApiException( sprintf( __( '<strong>Update error:</strong> The order used to purchase this license has an invalid status. <a href="%s" target="_blank">Purchase a valid license</a> to receive updates and support.', 'license-wp' ), $purchase_url ), 111 );
			}

			// get api product by given api product id (slug)
			if( is_numeric( $request['api_product_id'] ) ){
				$api_product = license_wp()->service( 'api_product_factory' )->make( $request['api_product_id'] );
				// get api products linked to license
				$license_api_products = $license->get_api_products();
				$license_api_products_ids = array();
				if ( count( $license_api_products ) > 0 ) {
					foreach ( $license_api_products as $license_api_product ) {
						$license_api_products_ids[] = $license_api_product->get_id();
					}
				}
				
				if (in_array($request['api_product_id'], $license_api_products_ids) == false) {
					throw new ApiException( __( '<strong>Activation error:</strong> The numeric product ID doesnt match the license.', 'license-wp' ) , 111 );
				}else{
					$request['api_product_id'] = $api_product->get_slug();
				}
			}else{
				$api_product = $license->get_api_product_by_slug( $request['api_product_id'] );
			}

			// check if license grants access to request api product
			if ( null === $api_product ) {
				throw new ApiException( sprintf( __( '<strong>Activation error:</strong> This license does not allow access to the requested product. <a href="%s" target="_blank">Purchase a valid license</a> to receive updates and support.', 'license-wp' ), $purchase_url ), 104 );
			}

			switch ( $request['request'] ) {
				case 'activate' :
					$validate_email_on_activation = apply_filters( 'license_wp_validate_email_on_activation', true );
					if( $validate_email_on_activation ){
						// we do the email check here because email var is not passed for deactivations
						$email_err_message = __( '<strong>Activation error:</strong> The email provided (%s) is invalid. Please enter the correct email address or <a href="%s" target="_blank">purchase a valid license</a> to receive updates and support.', 'license-wp' );

						// check for email var
						if ( ! isset( $request['email'] ) || empty( $request['email'] ) ) {
							throw new ApiException( sprintf( $email_err_message, $request['email'], $purchase_url ), 103 );
						}

						// check if activation email is correct
						if ( ! is_email( $request['email'] ) || $request['email'] != $license->get_activation_email() ) {
							throw new ApiException( sprintf( $email_err_message, $request['email'], $purchase_url ), 103 );
						}
					}
					

					// activate the license
					$this->activate( $license, $api_product, $request );
					break;
				case 'deactivate' :
					$this->deactivate( $license, $api_product, $request );
					break;
				default :
					throw new ApiException( __( 'Invalid API Request.', 'license-wp' ), 100 );
					break;
			}


		} catch ( ApiException $e ) {
			header( 'Content-Type: application/json' );
			echo $e->__toString();
			exit;
		}

		// bye
		exit;

	}

	/**
	 * Activate an instance of a license
	 *
	 * @param \Never5\LicenseWP\License\License $license
	 * @param \Never5\LicenseWP\ApiProduct\ApiProduct $api_product
	 * @param array $request
	 *
	 * @throws ApiException
	 */
	private function activate( $license, $api_product, $request ) {

		// Format the instance
		$request['instance'] = str_replace( array( 'http://', 'https://', 'www.' ), '', untrailingslashit ( trim( $request['instance'] ) ) );

		// get all activation, including deactivated activations
		$existing_activations = license_wp()->service( 'activation_manager' )->get_activations( $license, $api_product, false );

		// existing active activation instances
		$existing_active_activation_instances = array();

		// check & loop
		if ( count( $existing_activations ) > 0 ) {
			foreach ( $existing_activations as $existing_activation ) {

				// check if activation is active
				if ( $existing_activation->is_active() ) {

					// add instance to array
					$existing_active_activation_instances[] = $existing_activation->get_instance();

				}

			}
		}
		$activations = $license->get_activations( $api_product );
		$active_non_staging = 0;
		foreach ( $activations as $a ) {
			if ( ! $this->is_staging_site( $a->get_instance() ) ) {
				$active_non_staging++;
			}
		}
		$is_staging_request = $this->is_staging_site( $request['instance'] );
		// check if activation limit is reached and the requested instance isn't already activated
		if ( $license->get_activation_limit() > 0 && $active_non_staging >= $license->get_activation_limit() && ! in_array( $request['instance'], $existing_active_activation_instances ) && ! $is_staging_request 	 ) {
			throw new ApiException( sprintf( __( '<strong>Activation error:</strong> Activation limit reached. Please deactivate another website or upgrade your license at your <a href="%s" target="_blank">My Account page</a>.', 'license-wp' ), get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ), 105 );
		}

		// the activation
		$activation = null;

		// check if request instance already exists in an activation
		if ( count( $existing_activations ) > 0 ) {
			foreach ( $existing_activations as $existing_activation ) {

				// check if request instance equals activation instance
				if ( $request['instance'] === $existing_activation->get_instance() ) {
					$activation = $existing_activation;
					break;
				}

			}
		}

		// check if we got an activation for requested instance
		if ( null === $activation ) {

			// make new activation
			/** @var \Never5\LicenseWP\Activation\Activation $activation */
			$activation = license_wp()->service( 'activation_factory' )->make();

			// set props
			$activation->set_license_key( $license->get_key() );
			$activation->set_api_product_id( $request['api_product_id'] );
			$activation->set_instance( $request['instance'] );
			$activation->set_activation_date( new \DateTime() );
			$activation->set_activation_active( 1 );

		} else {
			$activation->set_activation_date( new \DateTime() );
			$activation->set_activation_active( 1 );
		}


		// persist activation
		$activation = license_wp()->service( 'activation_repository' )->persist( $activation );

		// check if activation was saved
		if ( $activation->get_id() == 0 ) {
			throw new ApiException( __( '<strong>Activation error:</strong> Could not activate license key. Please contact support.', 'license-wp' ), 107 );
		}

		// calculate activations left
		$activations = $license->get_activations( $api_product );
		$active_non_staging = 0;
		foreach ( $activations as $a ) {
			if ( ! $this->is_staging_site( $a->get_instance() ) ) {
				$active_non_staging++;
			}
		}
		$activations_left = ( ( $license->get_activation_limit() > 0 ) ? $license->get_activation_limit() - $active_non_staging : - 1 );

		// response
		$response = apply_filters( 'license_wp_api_activation_response', array(
			'success'   => true,
			'activated' => true,
			'remaining' => $activations_left
		) );

		// send JSON the WP way
		wp_send_json( $response );
		exit;

	}

	/**
	 * Deactivates an instance of a license
	 *
	 * @param \Never5\LicenseWP\License\License $license
	 * @param \Never5\LicenseWP\ApiProduct\ApiProduct $api_product
	 * @param array $request
	 *
	 * @throws ApiException
	 */
	private function deactivate( $license, $api_product, $request ) {

		// get activations
		$activations = $license->get_activations( $api_product );

		// check & loop
		if ( count( $activations ) > 0 ) {

			/** @var \Never5\LicenseWP\Activation\Activation $activation */
			foreach ( $activations as $activation ) {


				// check if given instance equals activation instance
				if ( $activation->format_instance( $request['instance'] ) === $activation->get_instance() ) {

					// set activation to not active
					$activation->set_activation_active( 0 );

					// set activation date to now
					$activation->set_activation_date( new \DateTime() );

					// persist activation
					$activation = license_wp()->service( 'activation_repository' )->persist( $activation );

					// check if deactivation was successful
					if ( $activation->is_active() ) {
						throw new ApiException( __( 'Deactivation error: Could not deactivate license key. Please contact support.', 'license-wp' ), 108 );
					}

					// response
					$response = apply_filters( 'license_wp_api_activation_response', array(
						'success' => true,
					) );

					// send JSON the WP way
					wp_send_json( $response );
					exit;
				}
			}
		}

		throw new ApiException( __( 'Deactivation error: instance not found.', 'license-wp' ), 109 );

	}

	/**
	 * Determine if an instance should be treated as a staging site.
	 *
	 * @param string $instance
	 * @return bool
	 */
	private function is_staging_site( $instance ) {
		$instance = strtolower( trim( $instance ) );
		$instance = str_replace( array( 'http://', 'https://', 'www.' ), '', $instance );
		if ( false !== strpos( $instance, '/' ) ) {
			$host = substr( $instance, 0, strpos( $instance, '/' ) );
		} else {
			$host = $instance;
		}

		if ( ! apply_filters( 'license_wp_detect_staging', true, $host, $instance ) ) {
			return false;
		}

		$is_staging = false;

		if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			$is_staging = true;
		}

		if ( ! $is_staging && filter_var( $host, FILTER_VALIDATE_IP ) && ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			$is_staging = true;
		}

		$tlds = apply_filters( 'license_wp_staging_tlds', array( '.local', '.localhost', '.test', '.example', '.invalid' ) );
		if ( ! $is_staging ) {
			foreach ( $tlds as $tld ) {
				if ( substr( $host, - strlen( $tld ) ) === $tld ) {
					$is_staging = true;
					break;
				}
			}
		}

		$hosting_patterns = apply_filters( 'license_wp_staging_hosting_patterns', array(
			'.wpengine.com',
			'.wpenginepowered.com',
			'.instawp.xyz',
			'.cloudwaysapps.com',
			'.kinsta.cloud',
			'.mwp.dev',
			'.mwpstage.com',
			'.godaddysites.com',
			'.myftpupload.com',
			'.flywheelsites.com',
			'.getflywheel.com',
			'.flywheelstaging.com',
			'.bluehostwp.com',
			'.previewdns.com',
			'.sg-host.com',
			'.siteground.biz',
			'.hemsida.eu',
			'.templweb.com',
		) );
		if ( ! $is_staging ) {
			foreach ( $hosting_patterns as $pattern ) {
				if ( substr( $host, - strlen( $pattern ) ) === $pattern ) {
					$is_staging = true;
					break;
				}
			}
		}
		// Example: beeweb-green.io, beeweb-anything.io but not beeweb.io
		$regex_patterns = apply_filters( 'license_wp_staging_regex_patterns', array( '/beeweb-[^.]+\.io$/' ) );
		if ( ! $is_staging ) {
			foreach ( $regex_patterns as $regex ) {
				if ( preg_match( $regex, $host ) ) {
					$is_staging = true;
					break;
				}
			}
		}

		if ( ! $is_staging ) {
			$indicators = apply_filters( 'license_wp_staging_indicators', array( 'dev', 'development', 'test', 'testing', 'staging', 'stage', 'local' ) );
			$parts	    = explode( '.', $host );
			foreach ( $parts as $part ) {
				if ( in_array( $part, $indicators, true ) ) {
					$is_staging = true;
					break;
				}
				foreach ( $indicators as $indicator ) {
					if ( strpos( $part, $indicator . '-' ) === 0 ) {
						$is_staging = true;
						break 2;
					}
				}
			}
		}

		return (bool) apply_filters( 'license_wp_is_staging_site', $is_staging, $host, $instance );
	}
}
