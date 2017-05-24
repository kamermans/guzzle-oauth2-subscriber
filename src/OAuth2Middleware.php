<?php

namespace kamermans\OAuth2;

use Psr\Http\Message\RequestInterface;

/**
 * OAuth2 plugin.
 *
 * @link http://tools.ietf.org/html/rfc6749 OAuth2 specification
 */
class OAuth2Middleware extends OAuth2Handler
{

    /**
     * Guzzle middleware invocation.
     *
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $this->signRequest($request);

            return $handler($request, $options)->then(
                $this->onFulfilled($request, $options),
                $this->onRejected($request, $options)
            );
        };
    }

    /**
      * Request error event handler.
      *
      * Handles unauthorized errors by acquiring a new access token and
      * retrying the request.
      *
      * @param ErrorEvent $event Event received
      */
    private function onFulfilled(RequestInterface $request, array $options)
    {
        return function ($response) use ($request, $options) {
            // Only deal with Unauthorized response.
            if ($response && $response->getStatusCode() != 401) {
                return $response;
            }

            // If we already retried once, give up.
            if ($request->hasHeader('X-Guzzle-Retry')) {
                return $response;
            }

            // Acquire a new access token, and retry the request.
            $accessToken = $this->getAccessToken();
            if ($accessToken === null) {
                return $response;
            }

            $request = $request->withHeader('X-Guzzle-Retry', '1');
            $request = $this->signRequest($request);

            return $this($request, $options);
        };
    }

    private function onRejected(RequestInterface $request, array $options)
    {
        return function ($reason) use ($request, $options) {
            return \GuzzleHttp\Promise\rejection_for($reason);
        };
    }
}
