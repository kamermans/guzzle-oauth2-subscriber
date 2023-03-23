<?php

namespace kamermans\OAuth2\Tests\Persistence;

use kamermans\OAuth2\Tests\BaseTestCase;
use kamermans\OAuth2\Token\RawToken;
use kamermans\OAuth2\Token\RawTokenFactory;

abstract class TokenPersistenceTestBase extends BaseTestCase
{
    abstract public function getInstance();

    // We have to mock these because PHPUnit broke the function definitions when they added strict typing
    public function doSetUp() {}
    public function doTearDown() {}

    public function testSaveToken()
    {
        $this->doSetUp();
        $factory = new RawTokenFactory();
        $token = $factory([
            'access_token' => 'abcdefghijklmnop',
            'refresh_token' => '0123456789abcdef',
            'expires_in' => 3600,
        ]);
        $this->getInstance()->saveToken($token);
        $this->doTearDown();
    }

    public function testRestoreToken()
    {
        $this->doSetUp();
        $factory = new RawTokenFactory();
        $token = $factory([
            'access_token' => 'abcdefghijklmnop',
            'refresh_token' => '0123456789abcdef',
            'expires_in' => 3600,
        ]);
        $this->getInstance()->saveToken($token);

        $restoredToken = $this->getInstance()->restoreToken(new RawToken);
        $this->assertInstanceOf('\kamermans\OAuth2\Token\RawToken', $restoredToken);

        $token_before = $token->serialize();
        $token_after = $restoredToken->serialize();

        $this->assertEquals($token_before, $token_after);
        $this->doTearDown();
    }

    public function testHasToken()
    {
        $this->doSetUp();
        $factory = new RawTokenFactory();
        $token = $factory([
            'access_token' => 'abcdefghijklmnop',
            'refresh_token' => '0123456789abcdef',
            'expires_in' => 3600,
        ]);

        $this->assertFalse($this->getInstance()->hasToken());
        $this->getInstance()->saveToken($token);
        $this->assertTrue($this->getInstance()->hasToken());
        $this->doTearDown();
    }

    public function testDeleteToken()
    {
        $this->doSetUp();
        $factory = new RawTokenFactory();
        $token = $factory([
            'access_token' => 'abcdefghijklmnop',
            'refresh_token' => '0123456789abcdef',
            'expires_in' => 3600,
        ]);

        $persist = $this->getInstance();

        $persist->saveToken($token);

        $restoredToken = $persist->restoreToken(new RawToken);
        $this->assertInstanceOf('\kamermans\OAuth2\Token\RawToken', $restoredToken);

        $persist->deleteToken();

        $restoredToken = $persist->restoreToken(new RawToken);
        $this->assertNull($restoredToken);
        $this->assertFalse($persist->hasToken());
        $this->doTearDown();
    }
}
