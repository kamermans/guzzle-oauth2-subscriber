<?php

namespace kamermans\OAuth2\GrantType;

use GuzzleHttp\Post\PostBody;
use GuzzleHttp\ClientInterface;
use kamermans\OAuth2\Utils\Helper;
use kamermans\OAuth2\Utils\Collection;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface;

/**
 * Authorization code grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-4.1
 */
class AuthorizationCode implements GrantTypeInterface
{
    /**
     * The token endpoint client.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Configuration settings.
     *
     * @var Collection
     */
    private $config;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->config = Collection::fromConfig($config,
            // Defaults
            [
                'client_secret' => '',
                'scope' => '',
                'redirect_uri' => '',
            ],
            // Required
            [
                'client_id',
                'code',
            ]
        );
    }

    public function getRawData(SignerInterface $clientCredentialsSigner, $refreshToken = null)
    {
        if (Helper::guzzleIs('>=', 6)) {
            $request = new \GuzzleHttp\Psr7\Request('POST', null, [
                'body' => $this->getPostBody(),
            ]);
        } else {
            $request = $this->client->createRequest('POST', null);
            $request->setBody($this->getPostBody());
        }

        $request = $clientCredentialsSigner->sign(
            $request,
            $this->config['client_id'],
            $this->config['client_secret']
        );

        $response = $this->client->send($request);

        return $response->json();
    }

    /**
     * @return PostBody
     */
    protected function getPostBody()
    {
        $postBody = new PostBody();
        $postBody->replaceFields([
            'grant_type' => 'authorization_code',
            'code' => $this->config['code'],
        ]);

        if ($this->config['scope']) {
            $postBody->setField('scope', $this->config['scope']);
        }

        if ($this->config['redirect_uri']) {
            $postBody->setField('redirect_uri', $this->config['redirect_uri']);
        }

        return $postBody;
    }
}
