<?php

namespace kamermans\OAuth2\GrantType;

use GuzzleHttp\Post\PostBody;
use GuzzleHttp\ClientInterface;
use kamermans\OAuth2\Utils\Collection;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface;

/**
 * Resource owner password credentials grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-4.3
 */
class PasswordCredentials implements GrantTypeInterface
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
            // Default
            [
                'client_secret' => '',
                'scope' => '',
            ],
            // Required
            [
                'client_id',
                'username',
                'password',
            ]
        );
    }

    public function getRawData(SignerInterface $clientCredentialsSigner, $refreshToken = null)
    {
        $request = $this->client->createRequest('POST', null);
        $request->setBody($this->getPostBody());

        $clientCredentialsSigner->sign(
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
            'grant_type' => 'password',
            'username'   => $this->config['username'],
            'password'   => $this->config['password'],
        ]);

        if ($this->config['scope']) {
            $postBody->setField('scope', $this->config['scope']);
        }

        return $postBody;
    }
}
