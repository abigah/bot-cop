<?php

use Abigah\BotCop\Services\ForgeService;
use Abigah\BotCop\Services\LoggingService;
use Abigah\BotCop\Services\CloudflareService;

return [

    'enabled' => explode(',', env('BOT_COP_ENABLED_SERVICES', 'logging')),

    'services' => [
        'forge' => [
            'service' => ForgeService::class,
            'api_token' => env('BOT_COP_FORGE_API_TOKEN', ''),
            'server_id' => env('BOT_COP_FORGE_SERVER_ID', ''),
            'rule_name' => env('BOT_COP_FORGE_RULE_NAME', 'BotCop:temporary'),
            'remove_after' => env('BOT_COP_FORGE_REMOVE_AFTER', 60),
        ],
        'cloudflare' => [
            'service' => CloudflareService::class,
            'api_token' => env('BOT_COP_CLOUDFLARE_API_TOKEN', ''),
            'account_id' => env('BOT_COP_CLOUDFLARE_ACCOUNT_ID', ''),
            'list_id' => env('BOT_COP_CLOUDFLARE_LIST_ID', ''),
            'rule_name' => env('BOT_COP_CLOUDFLARE_RULE_NAME', 'BotCop:temporary'),
            'remove_after' => env('BOT_COP_CLOUDFLARE_REMOVE_AFTER', 60),
        ],
        'logging' => [
            'service' => LoggingService::class,
            'log_name' => env('BOT_COP_LOG_NAME', 'botcop'),
            'delete_log_after' => env('BOT_COP_DELETE_LOG_AFTER', 7),
        ]
    ],

    /*
     * The whitelist IP addresses. We won't ban these IPs.
     * If you want to add more IPs, just add them to this array.
     * localhost etc. If testing on local, comment these out.
     * Cloudflare IPs from https://www.cloudflare.com/ips/
     */
    'whitelist-ips' => [
        # localhost, etc.
        'localhost',
        '127.0.0.1',
        '::1',
        # Cloudflare
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32'
    ],

    /*
     * The blacklist paths.
     * Note: If you create a page or route using one of these paths,
     * BotCop will not trigger as it won't 404.
     */
    'blacklist-paths' => [
        '.env',
        '.git/config',
        '.git/HEAD',
        '.vscode',
        'wp-config',
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
        'x09',
        'x0A',
        'x0B',
        'x0C',
        'x0D',
        'x0E',
        'x0F',
        'x10',
        'x11',
        'x12',
        'x13',
        'x14',
        'x15',
    ],
];
