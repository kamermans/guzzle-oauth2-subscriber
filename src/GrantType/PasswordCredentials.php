<?php

namespace kamermans\OAuth2\GrantType;

use GuzzleHttp\Post\PostBody;
use GuzzleHttp\ClientInterface;
use kamermans\OAuth2\Utils\Helper;
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
        if (Helper::guzzleIs('>=', 6)) {
            $request = (new \GuzzleHttp\Psr7\Request('POST', $this->client->getConfig()['base_uri']))
                        ->withBody($this->getPostBody())
                        ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
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

        return json_decode($response->getBody(), true);
    }

     /**
     * @return PostBody
     */
    protected function getPostBody()
    {
        if (Helper::guzzleIs('>=', '6')) {
            $data = [
                'grant_type' => 'password',
                'username'   => $this->config['username'],
                'password'   => $this->config['password'],
            ];

            if ($this->config['scope']) {
                $data['scope'] = $this->config['scope'];
            }

            return \GuzzleHttp\Psr7\stream_for(http_build_query($data, '', '&'));
        }

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
