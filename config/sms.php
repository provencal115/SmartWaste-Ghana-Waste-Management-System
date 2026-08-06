<?php
/**
 * SMS provider configuration — Smart Waste Management Ghana
 *
 * Supported providers: hubtel, arkesel, mnotify, smsgh, simulate
 * Copy settings to sms.local.php to override (keeps API keys out of version control).
 */
return [
    'enabled' => true,

    /** hubtel | arkesel | mnotify | smsgh | simulate */
    'provider' => 'simulate',

    'sender_id' => 'SmartWaste',

    'hubtel' => [
        'client_id'     => '',
        'client_secret' => '',
        'endpoint'      => 'https://sms.hubtel.com/v1/messages/send',
    ],

    'arkesel' => [
        'api_key'  => '',
        'sender'   => 'SmartWaste',
        'endpoint' => 'https://sms.arkesel.com/sms/api',
    ],

    'mnotify' => [
        'api_key'  => '',
        'sender'   => 'SmartWaste',
        'endpoint' => 'https://api.mnotify.com/api/sms/quick',
    ],

    'smsgh' => [
        'api_key'    => '',
        'api_secret' => '',
        'sender'     => 'SmartWaste',
        'endpoint'   => 'https://api.smsgh.com/v3/messages/send',
    ],

    /** Message templates — placeholders: {name}, {amount}, {date}, {time}, {receipt}, {link} */
    'templates' => [
        'registration_welcome' => 'Welcome to Smart Waste Management Ghana, {name}! Your account is ready. Log in to schedule collections and manage your subscription.',
        'payment_confirmation' => 'SmartWaste: Payment of {amount} received. Receipt: {receipt}. Thank you for your payment.',
        'pickup_reminder'        => 'SmartWaste: Your waste collection is scheduled for {date}{time}. Please have your bin accessible.',
        'collection_complete'    => 'SmartWaste: Your waste was collected successfully on {date}. Thank you for keeping Ghana clean!',
        'password_reset'         => 'SmartWaste: Use this link to reset your password (valid 1 hour): {link}',
    ],
];
