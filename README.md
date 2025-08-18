# Bot Cop - Statamic Addon

WARNING: THIS IS PRE-ALPHA SOFTWARE. IT IS PUBLIC ONLY FOR TESTING. FEEL FREE TO STAR/SUBSCRIBE.
USE AT YOUR OWN RISK! IT WILL BE A PAID LICENSE THROUGH THE STATAMIC MARKETPLACE WHEN RELEASED.

> Tired of those bots cluttering your server logs, looking for Wordpress vulnerabilies? There's
> a reason you are using Statamic, you don't need to provide valuable server resources to these
> bots.
> Sure, you can setup fail2ban everywhere and accidentally lock yourself out of your server.
> Or... you can use Bot Cop to watch 404 traffic and integrate with Cloudflare or
> Laravel Forge to prevent the bot from getting to your server in the first place.

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

### Add your Cloudflare information. (Requires Cloudflare Proxying turned on in DNS)
1. Create an Account API Token. On your Cloudflare dashboard, go to Manage Account > Account API Tokens and Create Token. 
Give it two privileges, Account.Account Filter Lists.Read and Account.Account Filter Lists.Edit

```php
BOT_COP_CLOUDFLARE_API_TOKEN=
```

2. On your Cloudflare dashboard, go to Manage Account > Configurations > Lists Create a new list
4. You then need to create the Rule to tell Cloudflare what to do with the IPs on the list. Head to your project's domain and go to Security > Security Rules and Create a Rule.
Call it whatever you want. Choose IP Source Address is in list botcop. Select the action you want to take, then save it.

3. Add your API token, Account ID and List Id to the following .env variables. They are the UUIDs found in the URL when looking at the list.
_https://dash.cloudflare.com/waasofu9qgfqtc0h97gl5o2amt1tn0ts/configurations/lists/bxwtz2gmte7115m3vamy8yq7ly2m4i1i_

```php
BOT_COP_CLOUDFLARE_ACCOUNT_ID=waasofu9qgfqtc0h97gl5o2amt1tn0ts
BOT_COP_CLOUDFLARE_LIST_ID=bxwtz2gmte7115m3vamy8yq7ly2m4i1i
```


### Add your Laravel Forge information. 
If you aren't using Cloudflare or Proxying, you can use Laravel Forge's API to work with UFW.

Note: If you use Cloudflare Proxy or another firewall that acts as a proxy and changes the IP address, UFW will not see the real IP. So Bot Cop will add it to the firewall but it won't actually deny the right IP. I personally run the Cloudflare and Forge options. I just feel more powerful banning the IP from both. 

1. Head to https://forge.laravel.com/user-profile/api and create a token. Copy and paste it into the following .env variable. 
IT IS VERY LONG. LEAVE IT ON ONE LINE.

```php
BOT_COP_FORGE_API_TOKEN=
```

Choose one of your servers and grab the server ID. Add it to this .env variable.
https://forge.laravel.com/servers/889273/sites

```php
BOT_COP_FORGE_SERVER_ID=889273
```

### Ensure the scheduler is setup (the Statamic one)
As long as the scheduler is setup, IPs will be unbanned after an hour (customizable). If you don't set it up, you'll have to remove the IPs manually.
https://statamic.dev/scheduling#

## Some things to watch for...

### Cloudflare only allows 1 custom list on the free plan.
It can handle 10000 or 1000000 IPs (depending on whether you are looking at the API documentation or the List Creation UI) so you should be okay as long as you are treating the bans as temporary. This addon doesn't use WAF due to requiring the Enterprise Plan, but if you want us to, reach out. If you have multiple domains you want to protect, you can. Read on...

### Multiple Projects
There are a number of options in the config file that you can override. If you use this addon in multiple projects, you can setup the Cloudflare and Forge Rule Names so each proejct will add and remove the IPs with that name filter. HOWEVER, It won't allow you to add the same IP address if it is already in the list, so you may end up removing it on the first site while it's active on the second. The first 404 on the second will add it back though.

```php
BOT_COP_CLOUDFLARE_RULE_NAME=YouCanMakeThisSiteSpecific
BOT_COP_FORGE_RULE_NAME=YouCanMakeThisSiteSpecific
```

### Statamic Multisite / Other sites on the same server
Once an IP is added to the list, it is unable to see any other sites using the same IP list (Cloudflare) or on the same server (Laravel Forge).

### Temporary Bans vs Permanent
Most jailing of bots and spiders is done temporarily. If you want to use the same IP list or firewall to ban an IP permanently, give it a different name or comment than the config file and it won't automatically remove it.



