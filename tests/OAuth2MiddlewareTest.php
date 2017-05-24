<?php

namespace kamermans\OAuth2\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Subscriber\Mock as MockResponder;
use GuzzleHttp\Subscriber\History;

use kamermans\OAuth2\Utils\Helper;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Token\RawToken;
use kamermans\OAuth2\Tests\BaseTestCase;

class OAuth2MiddlewareTest extends BaseTestCase
{

    public function setUp()
    {
        if (Helper::guzzleIs('<', 6)) {
            $this->markTestSkipped("Guzzle 6+ is required for this test");
        }
    }

    public function testConstruct()
    {
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            //->setConstructorArgs([$client, []])
            ->disableOriginalConstructor()
            ->getMock();

        $sub = new OAuth2Middleware($grant);
    }

    public function testDoesNotTriggerForNonOAuthRequests()
    {

        // Setup Reauthorization Client
        $reauth_responder = new MockHandler([
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
            new Psr7Response(200, [], json_encode(['access_token' => 'foobar'])),
        ]);

        $reauth_container = [];
        $reauth_history = Middleware::history($reauth_container);
        $reauth_handler = HandlerStack::create($reauth_responder)->push($reauth_history);

        $reauth_client = new Client([
            'handler'  => $reauth_handler,
            'base_uri' => 'http://localhost:10000/oauth_token',
        ]);

        // Setup User Client
        $response_data = [
            'foo' => 'bar',
            'key' => 'value',
        ];

        $responder = new MockHandler([
            new Psr7Response(200, [], json_encode($response_data)),
            new Psr7Response(200, [], json_encode($response_data)),
        ]);

        $container = [];
        $history = Middleware::history($container);
        $handler = HandlerStack::create($responder);

        $grant = new \kamermans\OAuth2\GrantType\ClientCredentials($reauth_client, [
            'client_id' => 'foo',
            'client_secret' => 'bar',
            'scope' => 'foo,bar',
        ]);

        $signer = new \kamermans\OAuth2\Signer\AccessToken\BasicAuth();

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);
        $sub->setAccessTokenSigner($signer);

        $handler->push($sub);
        $handler->push($history);

        $client = new Client([
            'handler'  => $handler,
            'base_uri' => 'http://localhost:10000/api/v1',
            'auth' => 'oauth',
        ]);

        // $request = new Request('GET', '/', [], null, ['auth' => 'oauth']);
        $response = $client->get('/');

        // $data = $grant->getRawData($signer);
        //
        // $this->assertNotEmpty($container);
        // $request_body = $container[0]['request']->getBody();
        //
        // die(var_export($request_body, true));
    }

    public function __DISABLED__testOnBeforeTriggersSignerAndGrantDataProcessor()
    {
        // Setup Access Token Signer
        $signer = $this->getMockBuilder('\kamermans\OAuth2\Signer\AccessToken\BasicAuth')
            ->setMethods(['sign'])
            ->getMock();

        $signer->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('foo'));

        // Setup Grant Type
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            ->setMethods(['getRawData'])
            ->disableOriginalConstructor()
            ->getMock();

        $grant->expects($this->once())
            ->method('getRawData')
            ->will($this->returnValue([
                'access_token' => '01234567890123456789abcdef',
                'refresh_token' => '01234567890123456789abcdef',
                'expires_in' => 3600,
            ]));

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);
        $sub->setAccessTokenSigner($signer);

        $client = new Client();
        $request = new Request('GET', '/', [], null, ['auth' => 'oauth']);
        $event = new BeforeEvent($this->getTransaction($client, $request));

        // Force an onBefore event, which triggers the signer and grant data processor
        $sub->onBefore($event);
    }

    public function __DISABLED__testOnErrorDoesNotTriggerForNonOAuthRequests()
    {
        // Setup Grant Type
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            ->setMethods(['getRawData'])
            ->disableOriginalConstructor()
            ->getMock();

        $grant->expects($this->exactly(0))
            ->method('getRawData');

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);

        $client = new Client();
        $request = new Request('GET', '/');
        $response = new Response(401);
        $transaction = $this->getTransaction($client, $request);
        $except = new RequestException('foo', $request, $response);
        $event = new ErrorEvent($transaction, $except);

        // Force an onError event, which triggers the signer and grant data processor
        $sub->onError($event);
    }

    public function __DISABLED__testOnErrorDoesNotTriggerForNon401Requests()
    {
        // Setup Grant Type
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            ->setMethods(['getRawData'])
            ->disableOriginalConstructor()
            ->getMock();

        $grant->expects($this->exactly(0))
            ->method('getRawData');

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);

        $client = new Client();
        $request = new Request('GET', '/', [], null, ['auth' => 'oauth']);
        $response = new Response(404);
        $transaction = $this->getTransaction($client, $request);

        if (Helper::guzzleIs('~', 4)) {
            $event = new ErrorEvent($transaction, new \GuzzleHttp\Exception\RequestException("error", $request, $response));
        } else {
            $event = new ErrorEvent($transaction);
        }

        $event->intercept($response);

        // Force an onError event, which triggers the signer and grant data processor
        $sub->onError($event);
    }

    public function __DISABLED__testOnErrorDoesNotLoop()
    {
        // Setup Grant Type
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            ->setMethods(['getRawData'])
            ->disableOriginalConstructor()
            ->getMock();

        $grant->expects($this->exactly(0))
            ->method('getRawData');

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);

        $client = new Client();
        $request = new Request('GET', '/', [], null, ['auth' => 'oauth']);
        // This header keeps the subscriber from trying to reauth a reauth request (infinte loop)
        $request->setHeader('X-Guzzle-Retry', 1);
        $response = new Response(401);
        $transaction = $this->getTransaction($client, $request);
        $except = new RequestException('foo', $request, $response);
        $event = new ErrorEvent($transaction, $except);

        // Force an onError event, which triggers the signer and grant data processor
        $sub->onError($event);
    }

    public function __DISABLED__testOnErrorTriggersSignerAndGrantDataProcessor()
    {
        // Setup Access Token Signer
        $signer = $this->getMockBuilder('\kamermans\OAuth2\Signer\AccessToken\BasicAuth')
            ->setMethods(['sign'])
            ->getMock();

        $signer->expects($this->once())
            ->method('sign')
            ->will($this->returnValue('foo'));

        // Setup Grant Type
        $grant = $this->getMockBuilder('\kamermans\OAuth2\GrantType\ClientCredentials')
            ->setMethods(['getRawData'])
            ->disableOriginalConstructor()
            ->getMock();

        $grant->expects($this->once())
            ->method('getRawData')
            ->will($this->returnValue([
                'access_token' => '01234567890123456789abcdef',
                'refresh_token' => '01234567890123456789abcdef',
                'expires_in' => 3600,
            ]));

        // Setup OAuth2Middleware
        $sub = new OAuth2Middleware($grant);
        $sub->setAccessTokenSigner($signer);

        $request = new Request('GET', '/', [], null, ['auth' => 'oauth']);
        $response = new Response(401);

        $client = $this->getMockBuilder('\GuzzleHttp\Client')
            ->setMethods(['send'])
            ->getMock();

        $client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $transaction = $this->getTransaction($client, $request);
        $except = new RequestException('foo', $request, $response);
        $event = new ErrorEvent($transaction, $except);

        // Force an onError event, which triggers the signer and grant data processor
        $sub->onError($event);
    }



    protected function getTransaction($client, $request)
    {
        if (Helper::guzzleIs('~', 4)) {
            return new \GuzzleHttp\Adapter\Transaction($client, $request);
        }

        return new \GuzzleHttp\Transaction($client, $request);
    }
}
