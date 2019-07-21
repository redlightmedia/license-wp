<?php
if ( ! class_exists( 'License_WP_Expired_Notice_Email' ) ) {

    if( ! class_exists('WC_Email' ) ){
        include_once(WC_ABSPATH . '/includes/emails/class-wc-email.php');
    }
    class License_WP_Expired_Notice_Email extends WC_Email
    {

        /**
         * @var \Never5\LicenseWP\License\License
         */
        protected $license;

        public function __construct()
        {
            $this->id          = 'license_wp_expired_notice_email';
            $this->title       = __('License Expiration Notice', 'license-wp');
            $this->description = __('License Expiration Notice - This is sent via bulk action of licenses', 'license-wp');
            $this->heading     = __('Your license has expired', 'license-wp');
            $this->subject     = __('Your license has expired', 'license-wp');

            $this->template_base  = license_wp()->service( 'file' )->plugin_path() . '/views/email/';
            $this->template_html  = 'mail-expired-notice.php';
            $this->template_plain = 'mail-expired-notice-plain.php';
            $this->customer_email = true;            

            // Call parent constructor.
            parent::__construct();

            $this->manual = true;

        }


        /**
         * get_content_html function.
         *
         * @return string
         */
        public function get_content_html(){
            return$this->renderTemplate(
                $this->template_base . $this->template_html,
                array(
                    'license'       => $this->license,
                    'email_heading' => $this->get_heading(),
                    'email'         => $this,
                    'email_body'    =>  $this->format_string ( $this->get_option('body') ),
                ));
        }

        /**
         * Get content plain.
         *
         * @return string
         */
        public function get_content_plain() {

            return $this->renderTemplate( 
                $this->template_base . $this->template_plain,
                array(
                    'license'       => $this->license,
                    'email_heading' => $this->get_heading(),
                    'plain_text'    => true,
                    'email'         => $this,
                    'email_body'    => $this->format_string ( $this->get_option('body') ),
                ));
        }

        /**
         * Initialize Settings Form Fields
         *
         */
        public function init_form_fields(){

            $form_fields = [
                'enabled'    => [
                    'title'   => __('Enable/Disable', 'license-wp'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable this email notification', 'license-wp'),
                    'default' => 'yes',
                ],
                'subject'    => [
                    'title'       => __('Subject', 'license-wp'),
                    'type'        => 'text',
                    'desc_tip'    => true,
                    'description' => '',
                    'default'     => __('Your license is expiring', 'license-wp'),
                ],
                'heading'    => [
                    'title'       => __('Email Heading', 'license-wp'),
                    'type'        => 'text',
                    'placeholder' => '',
                    'desc_tip'    => true,
                    'description' => '',
                    'default'     => __('Your license is expiring', 'license-wp'),
                ],
                'body'    => [
                    'title'       => __('Email Body', 'license-wp'),
                    'type'        => 'textarea',
                    'placeholder' => '',
                    'description' => sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>{product}, {license_key}, {license_expiration_date}, {renewal_link}</code>' ),
                    'default'     => '',
                ],
                'email_type' => [
                    'title'       => __('Email type', 'license-wp'),
                    'type'        => 'select',
                    'description' => __('Choose which format of email to send.', 'license-wp'),
                    'default'     => 'html',
                    'class'       => 'email_type',
                    'options'     => [
                        'plain' => __('Plain text', 'license-wp'),
                        'html'  => __('HTML', 'license-wp'),
                    ],
                ],
            ];

            $this->form_fields =  $form_fields;
        }

        /**
         * Send email
         */
        public function trigger( $license_key_id ){
            $license = license_wp()->service( 'license_factory' )->make( $license_key_id );
            $this->send_to_license_customer($license);
        }

        /**
         * Send email to certain license customer
         *
         * @param \Never5\LicenseWP\License\License $license
         */
        public function send_to_license_customer( $license ){
            $this->license    = $license;
            $customer_email = $license->get_activation_email();
            $result         = false;

            if( $customer_email ){
                // get WooCommerce product object
                $wc_product = wc_get_product( $license->get_product_id() );
                
                // get parent product if the product has one
                if ( false != $wc_product && 0 != $wc_product->get_parent_id() ) {
                    $wc_product = wc_get_product( $wc_product->get_parent_id() );
                }

                $this->placeholders['{product}']                 = $wc_product->get_title();
                $this->placeholders['{license_key}']             = $license->get_key();
                $this->placeholders['{license_expiration_date}'] = $license->get_date_expires() ? $license->get_date_expires()->format( 'M d Y' ) : '';
                $this->placeholders['{renewal_link}']            = apply_filters( 'license_wp_license_renewal_url_email', $license->get_renewal_url(), $license );

                $result = $this->send( $customer_email, $this->get_subject(), $this->get_content(), $this->get_headers(), []);

            }

        }


        /**
         * @param string $template
         * @param array $variables
         */
        public function includeTemplate($template, array $variables = [])
        {
            extract($variables);
            include $template;
        }

        /**
         * @param string $template
         * @param array $variables
         *
         * @return string
         */
        public function renderTemplate($template, $variables){
            ob_start();
                $this->includeTemplate($template, $variables);
                $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }

    }

}