<?php

namespace kamermans\OAuth2\Tests\Signer\ClientCredentials;

use kamermans\OAuth2\Tests\BaseTestCase;
use kamermans\OAuth2\Signer\ClientCredentials\Json;

class JsonTest extends BaseTestCase
{
    public function testSign()
    {
        $clientId = 'foo';
        $clientSecret = 'bar';

        $request = $this->createRequest('POST', '/', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        $request = $this->setPostBody($request, []);

        $signer = new Json();
        $request = $signer->sign($request, $clientId, $clientSecret);

        $this->assertEquals('application/json', $this->getHeader($request, 'Content-Type'));

        $this->assertEquals($clientId, $this->getJsonValue($request, 'client_id'));
        $this->assertEquals($clientSecret, $this->getJsonValue($request, 'client_secret'));
    }

    public function testSignCustomFields()
    {
        $clientId = 'foo';
        $clientSecret = 'bar';

        $clientIdFieldName = 'foo_id';
        $clientSecretFieldName = 'foo_secret';

        $request = $this->createRequest('POST', '/', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        $request = $this->setPostBody($request, []);

        $signer = new Json($clientIdFieldName, $clientSecretFieldName);
        $request = $signer->sign($request, $clientId, $clientSecret);

        $this->assertEquals('application/json', $this->getHeader($request, 'Content-Type'));

        $this->assertEquals($clientId, $this->getJsonValue($request, $clientIdFieldName));
        $this->assertEquals($clientSecret, $this->getJsonValue($request, $clientSecretFieldName));

        $this->assertNull($this->getJsonValue($request, 'client_id'));
        $this->assertNull($this->getJsonValue($request, 'client_secret'));
    }

    public function testSignWithCustomFieldsAndScopeAndAudience()
    {
        $clientId = 'foo';
        $clientSecret = 'bar';

        $clientIdFieldName = 'foo_id';
        $clientSecretFieldName = 'foo_secret';

        $scope = 'foo,bar,baz,qux';
        $audience = 'http://localhost:20000';

        $request = $this->createRequest('POST', '/', [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        $request = $this->setPostBody($request, [
            'scope' => $scope,
            'audience' => $audience
        ]);

        $signer = new Json($clientIdFieldName, $clientSecretFieldName);
        $request = $signer->sign($request, $clientId, $clientSecret);

        $this->assertEquals('application/json', $this->getHeader($request, 'Content-Type'));

        $this->assertEquals($clientId, $this->getJsonValue($request, $clientIdFieldName));
        $this->assertEquals($clientSecret, $this->getJsonValue($request, $clientSecretFieldName));

        $this->assertNull($this->getJsonValue($request, 'client_id'));
        $this->assertNull($this->getJsonValue($request, 'client_secret'));

        $this->assertEquals($scope, $this->getJsonValue($request, 'scope'));
        $this->assertEquals($audience, $this->getJsonValue($request, 'audience'));
    }
}
