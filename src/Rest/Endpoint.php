<?php

namespace Never5\LicenseWP\Rest;

class Endpoint {

	/**
	 * Setup hooks and filters
	 */
	public function setup() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ));
    }
    
    public function register_routes() {
        
        $namespace = "license-wp";

        register_rest_route($namespace, 'license/status/(?P<license>[[a-zA-Z0-9_.-]+)', array(
            array(
                'methods'         => \WP_REST_Server::READABLE,
                'callback'        => array( $this, 'get_license_status' ),
                'permission_callback' => array( $this, 'get_permission' ),
                'args'            => array(
                    'license' => array(
                        'required'      => true,
                    ),
                ),
            )
        ));

    }

    public function get_permission() {
        return true;
    }

    static function get_license_status( \WP_REST_Request $request ){
        
        $parameters = $request->get_params();
        if( !isset( $parameters['license'] ) || empty($parameters['license']) ){
            $data = array( 
                'error' => 'no_license_given',
                'message' => 'License is required'
            );
            return new \WP_REST_Response($data,400);
        }
        $license_key = sanitize_text_field($parameters['license']);

        $license = license_wp()->service( 'license_factory' )->make( $license_key );
        // check if license exists
		if ( '' == $license->get_key() ) {
            $data = array( 
                'error' => true,
                'errorCode' => 'NOTFOUND',
                'errorMessage' => 'Licensekey was not found'
            );
            return new \WP_REST_Response($data,404);
        }
		
		$wc_product = \Never5\LicenseWP\WooCommerce\Product::get_product( $license->get_product_id() );
        
        $data = array( 
            'licensekey' => $license->get_key(),
            'expired' => $license->is_expired(),
			'activation_limit' => $license->get_activation_limit(),
            'date_created' => $license->get_date_created(),
			'date_expires' => $license->get_date_expires(),
            'products' => [],
        );

        $api_products = $license->get_api_products();
        if ( isset( $api_products ) && count( $api_products ) > 0 ) {
            foreach ( $api_products as $api_product ) {
                $product_data = array(
					'id' => $api_product->get_id(),
                    'name' => $api_product->get_name(),
					'activations' => [],
                );
                $activations = $license->get_activations( $api_product );
                if ( isset( $activations ) && count( $activations ) > 0 ) {
					foreach ( $activations as $activation ) {
						$product_data['activations'][] = array(
							'instance' => $activation->get_instance()
						);
					}
                }
				$data['products'][] = $product_data;
				
            }
        }
        return new \WP_REST_Response($data,200);

    }

}