=======================
Guzzle OAuth 2.0 Subscriber
=======================

This is an OAuth 2.0 client for Guzzle which aims to be 100% compatible with Guzzle 4, 5, 6 and all future versions within a single package.
Although I love Guzzle, its interfaces keep changing, causing massive breaking changes every 12 months or so, so I have created this package
to help reduce the dependency hell that most third-party Guzzle dependencies bring with them.  I wrote the official Guzzle OAuth 2.0 plugin
which is still on the `oauth2` branch, [over at the official Guzzle repo](https://github.com/guzzle/oauth-subscriber/tree/oauth2), but I
see that they have dropped support for Guzzle < v6 on `master`, which prompted me to split this back off to a separate package.

Installing
==========

This project can be installed using Composer. Add the following to your
composer.json:

```javascript
    {
        "require": {
            "kamermans/guzzle-oauth2-subscriber": "~1.0"
        }
    }
```
