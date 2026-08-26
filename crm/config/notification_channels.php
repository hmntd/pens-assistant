<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending notifications to Telegram via Bot API.
    | Obtain a token from @BotFather on Telegram.
    |
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'PensAssistantBot'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Configuration (Commented out for future implementation)
    |--------------------------------------------------------------------------
    |
    | 'sms' => [
    |     'driver' => env('SMS_GATEWAY_DRIVER', 'log'),
    |     'api_key' => env('SMS_GATEWAY_API_KEY', ''),
    |     'api_url' => env('SMS_GATEWAY_URL', 'https://api.turbosms.ua/message/send.json'),
    |     'sender_id' => env('SMS_GATEWAY_SENDER_ID', 'PensAssist'),
    | ],
    */

];
