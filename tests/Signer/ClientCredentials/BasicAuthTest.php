<?php

namespace kamermans\OAuth2\Tests\Signer\ClientCredentials;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Message\Request;
use kamermans\OAuth2\Signer\ClientCredentials\BasicAuth;

class BasicAuthTest extends PHPUnit_Framework_TestCase
{
    public function testSign()
    {
        $clientId = 'foo';
        $clientSecret = 'bar';

        $request = new Request('GET', '/');
        $signer = new BasicAuth();
        $signer->sign($request, $clientId, $clientSecret);

        $this->assertEquals('Basic '.base64_encode($clientId.':'.$clientSecret), $request->getHeader('Authorization'));
    }
}
