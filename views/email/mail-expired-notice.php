<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<?php
    do_action( 'woocommerce_email_header', $email_heading, $email );

    echo str_replace("\n", "<br>", $email_body );

    do_action( 'woocommerce_email_footer', $email );

?>