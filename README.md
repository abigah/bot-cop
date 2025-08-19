# Bot Cop - Statamic Addon

WARNING: THIS IS BETA SOFTWARE. ALTHOUGH IT IS INSTALLED ON SOME LARGE SITES ALREADY, IT IS 
RECOMMENDED FOR NON-CRITICAL SITES. FEEL FREE TO STAR/SUBSCRIBE TO THE REPO FOR WHEN WE LAUNCH.

USE AT YOUR OWN RISK! IT WILL BE A PAID LICENSE THROUGH THE STATAMIC MARKETPLACE WHEN RELEASED.

**Tired of those bots cluttering your server logs, looking for Wordpress vulnerabilies? There's
a reason you are using Statamic, you don't need to provide valuable server resources to these
bots.**

**Sure, you can setup fail2ban everywhere and accidentally lock yourself out of your server.
Or... you can use Bot Cop to watch 404 traffic and integrate with Cloudflare or
Laravel Forge to prevent the bot from getting to your server in the first place.**

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
3. You then need to create the Rule to tell Cloudflare what to do with the IPs on the list. Head to your project's domain and go to Security > Security Rules and Create a Rule.
Call it whatever you want. Choose IP Source Address - is in list - botcop. Select the action you want to take (Managed Challenge is fine), then save it.
4. Add your API token, Account ID and List Id to the following .env variables. They are the UUIDs found in the URL when looking at the list.
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
BOT_COP_FORGE_API_TOKEN=THISSTRINGGOESONANDONANDONANDONONANDONANDONANDONONANDONANDONANDONONANDONANDONANDONONANDONANDONANDONONANDONANDONANDONONANDONANDONANDONONANDONANDONANDON
```
2. Choose the server your project is on and grab the server ID. Add it to this .env variable. If you have multiple servers hosting the site, you'll want to add the server ID to each individual .env file.
https://forge.laravel.com/servers/0000000/sites
```php
BOT_COP_FORGE_SERVER_ID=0000000
```
### Ensure the scheduler is setup (the Statamic one)
As long as the scheduler is setup, IPs will be unbanned after an hour (customizable). If you don't set it up, you'll have to remove the IPs manually. We default to running the removal command every 30 minutes to prevent issues with rate-limiting.
https://statamic.dev/scheduling#

## Some things to watch for...

### Cloudflare only allows 1 custom list on the free plan.
It can handle 10000 IPs so you should be okay as long as you are treating the bans as temporary. This addon doesn't use WAF due to requiring the Enterprise Plan, but if you want us to, reach out. If you have multiple domains you want to protect, you can. Read on...

### Multiple Projects
There are a number of options in the config file that you can override. If you use this addon in multiple projects, you can setup the Cloudflare and Forge Rule Names so each project will add and remove the IPs with that name filter. However, It won't allow you to add the same IP address if it is already in the list, so you may end up removing it even though the bot may be trying to hit the second. The first 404 on the other site will add it back though. it could have some interesting race conditions in the logs but it shouldn't cause a problem.

```php
BOT_COP_CLOUDFLARE_RULE_NAME=YouCanMakeThisSiteSpecific
BOT_COP_FORGE_RULE_NAME=YouCanMakeThisSiteSpecific
```

### Statamic Multisite / Other sites on the same server
Once an IP is added to the list, it is unable to see any other sites using the same IP list (Cloudflare) or on the same server (Laravel Forge). This addon is live on a multisite with over 30 URLs and works great.

### Temporary Bans vs Permanent
Most jailing of bots and spiders is done temporarily. If you want to use the same IP list or firewall to ban an IP permanently, give it a different name or comment than the config file and it won't automatically remove it.

### Can I add to the allowed list?
Of course, you can publish the config or give us a PR. I'm also working on a Control Panel dashboard to make it something that can be done there to prevent having to deploy. Note that when you publish the config, you won't get our updates to it so you may want to hold off until we solidify all the variables.

### Can I add to the blocked list?
For sure, you can publish the config or give us a PR. I'm also working on a Control Panel dashboard to make it something that can be done there to prevent having to deploy. Note that when you publish the config, you won't get our updates to it so you may want to hold off until we solidify all the variables.

### I'm using a different firewall or proxy, will you support it?
Maybe. You can do a PR or if you want to work with us to do it, let me know and I'll see what I can do.

### I found a bug / security issue
For security related issues, email sheldon@abigah.com. For other issues, put them into the issues board in Github. Don't put your tokens or anything in there please.




