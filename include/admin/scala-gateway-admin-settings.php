<?php

defined( 'ABSPATH' ) || exit;

return array(
    'enabled' => array(
        'title' => __('Enable / Disable', 'scala_gateway'),
        'label' => __('Enable this payment gateway', 'scala_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'scala_gateway'),
        'type' => 'text',
        'desc_tip' => __('Payment title the customer will see during the checkout process.', 'scala_gateway'),
        'default' => __('Scala Gateway', 'scala_gateway')
    ),
    'description' => array(
        'title' => __('Description', 'scala_gateway'),
        'type' => 'textarea',
        'desc_tip' => __('Payment description the customer will see during the checkout process.', 'scala_gateway'),
        'default' => __('Pay securely using Scala. You will be provided payment details after checkout.', 'scala_gateway')
    ),
    'discount' => array(
        'title' => __('Discount for using Scala', 'scala_gateway'),
        'desc_tip' => __('Provide a discount to your customers for making a private payment with Scala', 'scala_gateway'),
        'description' => __('Enter a percentage discount (i.e. 5 for 5%) or leave this empty if you do not wish to provide a discount', 'scala_gateway'),
        'type' => __('number'),
        'default' => '0'
    ),
    'valid_time' => array(
        'title' => __('Order valid time', 'scala_gateway'),
        'desc_tip' => __('Amount of time order is valid before expiring', 'scala_gateway'),
        'description' => __('Enter the number of seconds that the funds must be received in after order is placed. 3600 seconds = 1 hour', 'scala_gateway'),
        'type' => __('number'),
        'default' => '3600'
    ),
    'confirms' => array(
        'title' => __('Number of confirmations', 'scala_gateway'),
        'desc_tip' => __('Number of confirms a transaction must have to be valid', 'scala_gateway'),
        'description' => __('Enter the number of confirms that transactions must have. Enter 0 to zero-confim. Each confirm will take approximately four minutes', 'scala_gateway'),
        'type' => __('number'),
        'default' => '5'
    ),
    'confirm_type' => array(
        'title' => __('Confirmation Type', 'scala_gateway'),
        'desc_tip' => __('Select the method for confirming transactions', 'scala_gateway'),
        'description' => __('Select the method for confirming transactions', 'scala_gateway'),
        'type' => 'select',
        'options' => array(
            'viewkey'        => __('viewkey', 'scala_gateway'),
            'scala-wallet-rpc' => __('scala-wallet-rpc', 'scala_gateway')
        ),
        'default' => 'viewkey'
    ),
    'scala_address' => array(
        'title' => __('Scala Address', 'scala_gateway'),
        'label' => __('Useful for people that have not a daemon online'),
        'type' => 'text',
        'desc_tip' => __('Scala Wallet Address (ScalaL)', 'scala_gateway')
    ),
    'viewkey' => array(
        'title' => __('Secret Viewkey', 'scala_gateway'),
        'label' => __('Secret Viewkey'),
        'type' => 'text',
        'desc_tip' => __('Your secret Viewkey', 'scala_gateway')
    ),
    'daemon_host' => array(
        'title' => __('Scala wallet RPC Host/IP', 'scala_gateway'),
        'type' => 'text',
        'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with', 'scala_gateway'),
        'default' => '127.0.0.1',
    ),
    'daemon_port' => array(
        'title' => __('Scala wallet RPC port', 'scala_gateway'),
        'type' => __('number'),
        'desc_tip' => __('This is the Wallet RPC port to authorize the payment with', 'scala_gateway'),
        'default' => '18080',
    ),
    'testnet' => array(
        'title' => __(' Testnet', 'scala_gateway'),
        'label' => __(' Check this if you are using testnet ', 'scala_gateway'),
        'type' => 'checkbox',
        'description' => __('Advanced usage only', 'scala_gateway'),
        'default' => 'no'
    ),
    'javascript' => array(
        'title' => __(' Javascript', 'scala_gateway'),
        'label' => __(' Check this to ENABLE Javascript in Checkout page ', 'scala_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
     ),
    'onion_service' => array(
        'title' => __(' SSL warnings ', 'scala_gateway'),
        'label' => __(' Check to Silence SSL warnings', 'scala_gateway'),
        'type' => 'checkbox',
        'description' => __('Check this box if you are running on an Onion Service (Suppress SSL errors)', 'scala_gateway'),
        'default' => 'no'
    ),
    'show_qr' => array(
        'title' => __('Show QR Code', 'scala_gateway'),
        'label' => __('Show QR Code', 'scala_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to show a QR code after checkout with payment details.'),
        'default' => 'no'
    ),
    'use_scala_price' => array(
        'title' => __('Show Prices in Scala', 'scala_gateway'),
        'label' => __('Show Prices in Scala', 'scala_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to convert ALL prices on the frontend to Scala (experimental)'),
        'default' => 'no'
    ),
    'use_scala_price_decimals' => array(
        'title' => __('Display Decimals', 'scala_gateway'),
        'type' => __('number'),
        'description' => __('Number of decimal places to display on frontend. Upon checkout exact price will be displayed.'),
        'default' => 12,
    ),
);
