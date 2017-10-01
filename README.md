# Guzzle OAuth 2.0 Subscriber

> [![Build Status](https://travis-ci.org/kamermans/guzzle-oauth2-subscriber.svg?branch=master)](https://travis-ci.org/kamermans/guzzle-oauth2-subscriber)
> Tested with Guzzle 4, 5, 6 and PHP 5.4, 5.5, 5.6, 7.0, 7.1 and HHVM

This is an OAuth 2.0 client for Guzzle which aims to be 100% compatible with Guzzle 4, 5, 6 and all future versions within a single package.
Although I love Guzzle, its interfaces keep changing, causing massive breaking changes every 12 months or so, so I have created this package
to help reduce the dependency hell that most third-party Guzzle dependencies bring with them.  I wrote the official Guzzle OAuth 2.0 plugin
which is still on the `oauth2` branch, [over at the official Guzzle repo](https://github.com/guzzle/oauth-subscriber/tree/oauth2), but I
see that they have dropped support for Guzzle < v6 on `master`, which prompted me to split this back off to a separate package.

## Features

- Acquires access tokens via one of the supported grant types (code, client credentials,
  user credentials, refresh token). Or you can set an access token yourself.
- Supports refresh tokens (stores them and uses them to get new access tokens).
- Handles token expiration (acquires new tokens and retries failed requests).
- Allows storage and lookup of access tokens via callbacks


## Installation

This project can be installed using Composer. Run `composer require kamermans/guzzle-oauth2-subscriber` or add the following to your `composer.json`:

```javascript
    {
        "require": {
            "kamermans/guzzle-oauth2-subscriber": "~1.0"
        }
    }
```

## Usage

This plugin integrates seamlessly with Guzzle, transparently adding authentication to outgoing requests and optionally attempting re-authorization if the access token is no longer valid.

There are multiple grant types available like `PasswordCredentials`, `ClientCredentials` and `AuthorizationCode`.

### Guzzle 4 & 5 vs Guzzle 6+
With the Guzzle 6 release, most of the library was refactored or completely rewritten, and as such, the integration of this library is different.

#### Emitters (Guzzle 4 & 5)
Guzzle 4 & 5 use **Event Subscribers**, and this library incudes `OAuth2Subscriber` for that purpose:

```php
$oauth = new OAuth2Subscriber($grant_type);

$client = new Client([
    'auth' => 'oauth',
]);

$client->getEmitter()->attach($oauth);
```

#### Middleware (Guzzle 6+)
Starting with Guzzle 6, **Middleware** is used to integrate OAuth, and this library includes `OAuth2Middleware` for that purpose:

```php
$oauth = new OAuth2Middleware($grant_type);

$stack = HandlerStack::create();
$stack->push($oauth);

$client = new Client([
    'auth'     => 'oauth',
    'handler'  => $stack,
]);
```

Alternatively, you can add the middleware to an existing Guzzle Client:

```php
$oauth = new OAuth2Middleware($grant_type);
$client->getConfig('handler')->push($oauth);
```


### Client Credentials Example
Client credentials are normally used in server-to-server authentication.  With this grant type, a client is requesting authorization in its own behalf, so there are only two parties involved.  At a minimum, a `client_id` and `client_secret` are required, although many services require a `scope` and other parameters.

Here's an example of the client credentials method in Guzzle 4 and Guzzle 5:

```php
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Subscriber;

// Authorization client - this is used to request OAuth access tokens
$reauth_client = new GuzzleHttp\Client([
    // URL for access_token request
    'base_url' => 'http://some_host/access_token_request_url',
]);
$reauth_config = [
    "client_id" => "your client id",
    "client_secret" => "your client secret",
    "scope" => "your scope(s)", // optional
    "state" => time(), // optional
];
$grant_type = new ClientCredentials($reauth_client, $reauth_config);
$oauth = new OAuth2Subscriber($grant_type);

// This is the normal Guzzle client that you use in your application
$client = new GuzzleHttp\Client([
    'auth' => 'oauth',
]);
$client->getEmitter()->attach($oauth);
$response = $client->get('http://somehost/some_secure_url');

echo "Status: ".$response->getStatusCode()."\n";
```

Here's the same example for Guzzle 6+:

```php
use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use GuzzleHttp\HandlerStack;

// Authorization client - this is used to request OAuth access tokens
$reauth_client = new GuzzleHttp\Client([
    // URL for access_token request
    'base_uri' => 'http://some_host/access_token_request_url',
]);
$reauth_config = [
    "client_id" => "your client id",
    "client_secret" => "your client secret",
    "scope" => "your scope(s)", // optional
    "state" => time(), // optional
];
$grant_type = new ClientCredentials($reauth_client, $reauth_config);
$oauth = new OAuth2Middleware($grant_type);

$stack = HandlerStack::create();
$stack->push($oauth);

// This is the normal Guzzle client that you use in your application
$client = new GuzzleHttp\Client([
    'handler' => $stack,
    'auth'    => 'oauth',
]);

$response = $client->get('http://somehost/some_secure_url');

echo "Status: ".$response->getStatusCode()."\n";
```

### Grant Types
The following OAuth grant types are supported directly, and you can always create your own by implementing `kamermans\OAuth2\GrantType\GrantTypeInterface`:
 - `AuthorizationCode`
 - `ClientCredentials`
 - `PasswordCredentials`
 - `RefreshToken`

Each of these takes a Guzzle client as the first argument.  This client is used to obtain or refresh your OAuth access token, out of band from the other requests you are making.


### Access Token Persistence
> Note: OAuth Access tokens should be stored somewhere securely and/or encrypted.  If an attacker gains access to your access token, they could have unrestricted access to whatever resources and scopes were allowed!

By default, access tokens are not persisted anywhere.  There are some built-in mechanisms for caching / persisting tokens (in `kamermans\OAuth2\Persistence`):
  - `NullTokenPersistence` (default) Disables persistence
  - `FileTokenPersitence` Takes the path to a file in which the access token will be saved.
  - `DoctrineCacheTokenPersistence` Takes a `Doctrine\Common\Cache\Cache` object and optionally a key name (default: `guzzle-oauth2-token`) where the access token will be saved.

If you want to use your own persistence layer, you should write your own class that implements `TokenPersistenceInterface`.
