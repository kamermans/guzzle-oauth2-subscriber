<?php

namespace kamermans\OAuth2\Tests\Signer\AccessToken;

use \kamermans\OAuth2\Tests\BaseTestCase;
use GuzzleHttp\Message\Request;
use kamermans\OAuth2\Signer\AccessToken\BasicAuth;

class BasicAuthTest extends \kamermans\OAuth2\Tests\BaseTestCase
{
    public function testSign()
    {
        $request = new Request('GET', '/');

        $signer = new BasicAuth();
        $signer->sign($request, 'foobar');

        $this->assertEquals('Bearer foobar', $request->getHeader('Authorization'));
    }
}
