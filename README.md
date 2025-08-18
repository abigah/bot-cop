# Bot Cop

> Tired of those bots cluttering your server logs, looking for Wordpress?
> Sure, you can setup fail2ban and lock yourself out of your server. Or...
> you can use Bot Cop to watch 404 traffic and integrate with Cloudflare or
> Laravel Forge to prevent the bot from getting to your server in the first
> place.

## Features

This addon integrates with:

- Cloudflare IP List (then you can setup rules on how to handle it)
- Laravel Forge (UFW)

## How to Install

You can install this addon via Composer:

```bash
composer require abigah/bot-cop
```

## How to Use

The following Environment Variables are mandatory for the addon to operate.
Add this to your project.

If you aren't using Cloudflare or Forge, remove that option. You can just use Logging but that wouldn't
be very helpful.

```php
BOT_COP_ENABLED_SERVICES=logging,cloudflare,forge
```

### Add your Cloudflare information. 
This is the hardest part. You'll need an Account API token with 2 privilages, your account Id, and the list Id. You then need to create the Rule to tell Cloudflare what to do with the IPs on the list.

```php
BOT_COP_ENABLED_SERVICES=
BOT_COP_CLOUDFLARE_API_TOKEN=
BOT_COP_CLOUDFLARE_ACCOUNT_ID=
BOT_COP_CLOUDFLARE_LIST_ID=
```


### Add your Laravel Forge information. 
This is the hardest part. You'll need an Account API token with 2 privilages, your account Id, and the list Id.

Note: If you use Cloudflare Proxy or another firewall that acts as a proxy and changes the IP address, UFW will not see the real IP. So Bot Cop will add it to the firewall but it won't actually deny the right IP. I personally run the Cloudflare and Forge options. I just feel more powerful banning the IP from both. 

```php
BOT_COP_FORGE_API_TOKEN=
BOT_COP_FORGE_SERVER_ID=
```

## Things to watch for

### Cloudflare only allows 1 custom list on the free plan.
It can handle a million IPs so you should be okay. This addon doesn't use WAF due to requiring the Enterprise Plan, but if you want us to, reach out. If you have multiple domains you want to protect, you can. Read on...

### Multiple Projects
There are a number of options in the config file that you can override. If you use this addon in multiple projects, you can setup the Cloudflare and Forge Rule Names so each proejct will add and remove the IPs with that name filter. HOWEVER, It won't allow you to add the same IP address if it is already in the list, so you may end up removing it on the first site while it's active on the second. The first 404 on the second will add it back though.

### Statamic Multisite / Other sites on the same server
Once an IP is added to the list, it is unable to see any other sites using the same IP list (Cloudflare) or on the smae server (Laravel Forge).
