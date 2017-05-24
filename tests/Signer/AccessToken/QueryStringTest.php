<?php

namespace kamermans\OAuth2\Tests\Signer\AccessToken;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Message\Request;
use kamermans\OAuth2\Signer\AccessToken\QueryString;

class QueryStringTest extends PHPUnit_Framework_TestCase
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
