<?php

namespace kamermans\OAuth2;

use GuzzleHttp\Exception\BadResponseException;
use kamermans\OAuth2\GrantType\GrantTypeInterface;
use kamermans\OAuth2\Utils\Helper;

/**
 * OAuth2 plugin.
 *
 * @link http://tools.ietf.org/html/rfc6749 OAuth2 specification
 */
class OAuth2Handler
{
    /**
     * The grant type implementation used to acquire access tokens.
     *
     * @var GrantTypeInterface
     */
    protected $grantType;

    /**
     * The grant type implementation used to refresh access tokens.
     *
     * @var GrantTypeInterface
     */
    protected $refreshTokenGrantType;

    /**
     * The service in charge of including client credentials into requests.
     * to get an access token.
     *
     * @var AccessTokenSigner
     */
    protected $clientCredentialsSigner;

    /**
     * The service in charge of including the access token into requests.
     *
     * @var AccessTokenSigner
     */
    protected $accessTokenSigner;

    /**
     * The object including access token.
     *
     * @var TokenInterface
     */
    protected $rawToken;

    /**
     * The service in charge of persisting access token.
     *
     * @var TokenPersistenceInterface
     */
    protected $tokenPersistence;

    /**
     * @param GrantTypeInterface $grantType
     * @param GrantTypeInterface $refreshTokenGrantType
     */
    public function __construct(
                    GrantType\GrantTypeInterface $grantType,
                    GrantType\GrantTypeInterface $refreshTokenGrantType = null,
                    Signer\ClientCredentials\SignerInterface $clientCredentialsSigner = null,
                    Signer\AccessToken\SignerInterface $accessTokenSigner = null
    ) {
        $this->grantType = $grantType;
        $this->refreshTokenGrantType = $refreshTokenGrantType;
        $this->clientCredentialsSigner = $clientCredentialsSigner;
        $this->accessTokenSigner = $accessTokenSigner;

        if ($this->clientCredentialsSigner === null) {
            $this->clientCredentialsSigner = new Signer\ClientCredentials\BasicAuth();
        }

        if ($this->accessTokenSigner === null) {
            $this->accessTokenSigner = new Signer\AccessToken\BearerAuth();
        }

        $this->tokenPersistence = new Persistence\NullTokenPersistence();
        $this->tokenFactory = new Token\RawTokenFactory();
    }

    /**
     * @param Signer\ClientCredentials\SignerInterface $signer
     */
    public function setClientCredentialsSigner(Signer\ClientCredentials\SignerInterface $signer)
    {
        $this->clientCredentialsSigner = $signer;

        return $this;
    }

    /**
     * @param AccessToken\SignerInterface $signer
     */
    public function setAccessTokenSigner(Signer\AccessToken\SignerInterface $signer)
    {
        $this->accessTokenSigner = $signer;

        return $this;
    }

    /**
     * @param Persistence\TokenPersistenceInterface $tokenPersistence
     */
    public function setTokenPersistence(Persistence\TokenPersistenceInterface $tokenPersistence)
    {
        $this->tokenPersistence = $tokenPersistence;

        return $this;
    }

    /**
     * @param callable $tokenFactory
     */
    public function setTokenFactory(callable $tokenFactory)
    {
        $this->tokenFactory = $tokenFactory;

        return $this;
    }



    /**
     * Manually set the access token.
     *
     * @param string|array|TokenInterface $token An array of token data, an access token string, or a TokenInterface object
     */
    public function setAccessToken($token)
    {
        if ($token instanceof Token\TokenInterface) {
            $this->rawToken = $token;
        } else {
            $this->rawToken = is_array($token) ?
                $this->tokenFactory($token) :
                $this->tokenFactory(['access_token' => $token]);
        }

        if ($this->rawToken === null) {
            throw new Exception\OAuth2Exception("setAccessToken() takes a string, array or TokenInterface object");
        }

        return $this;
    }

    /**
     * Forcefully delete an access token, even if it's valid
     */
    public function deleteAccessToken()
    {
        $this->rawToken = null;
        $this->tokenPersistence->deleteToken();
    }

    /**
     * Get a valid access token.
     *
     * @return string|null A valid access token or null if unable to get one
     *
     * @throws AccessTokenRequestException while trying to run `requestNewAccessToken` method
     */
    public function getAccessToken()
    {
        // If token is not set try to get it from the persistent storage.
        if ($this->rawToken === null) {
            $this->rawToken = $this->tokenPersistence->restoreToken(new Token\RawToken());
        }

        // If token is not set or expired then try to acquire a new one...
        if ($this->rawToken === null || $this->rawToken->isExpired()) {
            $this->tokenPersistence->deleteToken();

            // Hydrate `rawToken` with a new access token
            $this->requestNewAccessToken();

            // ...and save it.
            if ($this->rawToken) {
                $this->tokenPersistence->saveToken($this->rawToken);
            }
        }

        return $this->rawToken? $this->rawToken->getAccessToken(): null;
    }

    /**
     * Gets the current Token object
     *
     * @return Token\RawToken|null
     */
    public function getRawToken()
    {
        return $this->rawToken;
    }

    protected function signRequest($request)
    {
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            return $request;
        }

        return $this->accessTokenSigner->sign($request, $accessToken);
    }

    /**
     * Helper method for (callable)tokenFactory
     *
     * @return TokenInterface
     */
    protected function tokenFactory()
    {
        return call_user_func_array($this->tokenFactory, func_get_args());
    }

    /**
     * Acquire a new access token from the server.
     *
     * @return TokenInterface|null
     *
     * @throws AccessTokenRequestException
     */
    protected function requestNewAccessToken()
    {
        if ($this->refreshTokenGrantType && $this->rawToken && $this->rawToken->getRefreshToken()) {
            try {
                // Get an access token using the stored refresh token.
                $rawData = $this->refreshTokenGrantType->getRawData(
                    $this->clientCredentialsSigner,
                    $this->rawToken->getRefreshToken()
                );

                $this->rawToken = $this->tokenFactory($rawData, $this->rawToken);

                return;
            } catch (BadResponseException $e) {
                // If the refresh token is invalid, then clear the entire token information.
                $this->rawToken = null;
            }
        }

        if ($this->grantType === null) {
            throw new Exception\ReauthorizationException('You must specify a grantType class to request an access token');
        }

        try {
            // Request an access token using the main grant type.
            $rawData = $this->grantType->getRawData($this->clientCredentialsSigner);

            $this->rawToken = $this->tokenFactory($rawData);
        } catch (BadResponseException $e) {
            throw new Exception\AccessTokenRequestException('Unable to request a new access token', $e);
        }
    }
}
