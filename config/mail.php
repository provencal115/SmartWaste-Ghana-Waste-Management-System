<?php
/**
 * SMTP mail configuration — Smart Waste Management Ghana
 *
 * For Gmail:
 * 1. Enable 2-Step Verification on your Google account.
 * 2. Create an App Password at https://myaccount.google.com/apppasswords
 * 3. Set smtp.username to your Gmail address and smtp.password to the app password.
 */
return [
    'enabled' => true,

    'from_email' => 'isaacprovencal7@gmail.com',
    'from_name'  => 'Smart Waste Management Ghana',

    'company' => [
        'name'    => 'Smart Waste Management Ghana',
        'email'   => 'isaacprovencal7@gmail.com',
        'phone'   => '0558478203',
        'phone_alt' => '0277690706',
        'address' => 'Accra, Greater Accra, Ghana',
        'website' => 'https://smartwaste.com',
    ],

    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        // Used automatically if port 587 is blocked (common on some Windows/XAMPP setups)
        'alternate'  => [
            'port'       => 465,
            'encryption' => 'ssl',
        ],
        'username'   => 'isaacprovencal7@gmail.com',
        'password'   => '',
    ],

    'welcome' => [
        'subject' => 'Welcome to Smart Waste Management Ghana!',
    ],

    'contact' => [
        'admin_notify_email' => 'isaacprovencal7@gmail.com',
        'admin_notify_subject' => 'New Contact Message — Smart Waste Management Ghana',
        'customer_confirm_subject' => 'We Have Received Your Message – Smart Waste Management Ghana',
        'reply_subject_prefix' => 'Re: ',
        'reply_subject_suffix' => ' – Smart Waste Management Ghana',
    ],
];
