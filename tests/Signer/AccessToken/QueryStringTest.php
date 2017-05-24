<?php

namespace kamermans\OAuth2\Tests\Signer\AccessToken;

use kamermans\OAuth2\Tests\BaseTestCase;
use GuzzleHttp\Message\Request;
use kamermans\OAuth2\Signer\AccessToken\QueryString;

class QueryStringTest extends \kamermans\OAuth2\Tests\BaseTestCase
{
    public function testSign()
    {
        $fieldName = 'access_token';

        $request = new Request('GET', '/');

        $signer = new QueryString();
        $signer->sign($request, 'foobar');

        $this->assertEquals('foobar', $request->getQuery()->get($fieldName));
    }

    public function testSignCustomField()
    {
        $fieldName = 'someotherfieldname';

        $request = new Request('GET', '/');

        $signer = new QueryString($fieldName);
        $signer->sign($request, 'foobar');

        $this->assertEquals('foobar', $request->getQuery()->get($fieldName));
    }
}
