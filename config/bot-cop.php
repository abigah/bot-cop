<?php

return [

    /*
     * Turn the forge blocking on and off here or in your env file.
     */
    'forge_block_enabled' => env('BOT_COP_FORGE_BLOCK_ENABLED', true),

    /*
     * Turn the cloudflare IP list adding on and off here or in your env file.
     */
    'cloudflare_ip_list_enabled' => env('BOT_COP_CLOUDFLARE_IP_LIST_ENABLED', true),

    /*
     * The name to use for the forge firewall rule, will default to 'bot-cop'.
     */
    'firewall-rule-name' => env('BOT_COP_FIREWALL_RULE_NAME', 'bot-cop'),

    /*
     * Turn the logging on and off her or in your env file.
     */
    'log_enabled' => env('BOT_COP_LOG_ENABLED', true),

    /*
     * You can automatically delete log files after a certain amount of days.
     * Setting this to 0 will prevent any deletions.
     */
    'delete-after' => 7,

    /*
    * The number of minutes to keep the IP banned in forge.
    * Setting this to 0 will prevent any removals.
    */
    'remove-after' => env('BOT_COP_REMOVE_AFTER', 60),


    /*
     * Option to change the name of the log files to avoid any conflicts.
     */
    'log-name' => env('BOT_COP_LOG_NAME', 'botcop'),

    /*
     * The Cloudflare API Token.
     */
    'cloudflare-api-token' => env('BOT_COP_CLOUDFLARE_API_TOKEN', ''),

    /*
     * The Cloudflare List ID.
     */
    'cloudflare-list-id' => env('BOT_COP_CLOUDFLARE_LIST_ID', ''),

    /*
     * The Laravel Forge API Key.
     */
    'forge-api-token' => env('BOT_COP_FORGE_API_TOKEN', ''),

    /*
     * The Forge Server ID.
     */
    'forge-server-id' => env('BOT_COP_FORGE_SERVER_ID', ''),

    /*
     * The whitelist IP addresses. We won't ban these IPs.
     */
    'whitelist-ips' => [
        // 'localhost',
        // '127.0.0.1',
        // '::1',
    ],

    /*
     * The blacklist paths.
     */
    'blacklist-paths' => [
        '.env',
        '.git/config',
        '.git/HEAD',
        '.well-known/security.txt',
        'wp-includes',
        'wp-admin/css',
        'x00',
        'x01',
        'x02',
        'x03',
        'x04',
        'x05',
        'x06',
        'x07',
        'x08',
    ],
];
