<?php

namespace kamermans\OAuth2\Tests;

use kamermans\OAuth2\Utils\Helper;
use GuzzleHttp\Post\PostBody;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Psr7\Request as Psr7Request;

if (!class_exists('\PHPUnit\Framework\TestCase')) {
    require_once __DIR__.'/PHPUnitNamespaceShim.php';
}

class BaseTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @before
     */
    public function _callCompatibleSetup()
    {
        if (method_exists($this, '_setUp')) {
            $this->_setUp();
        }
    }

    /**
     * @after
     */
    public function _callCompatibleTearDown()
    {
        if (method_exists($this, '_tearDown')) {
            $this->_tearDown();
        }
    }

    public function _expectException($exception)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($exception);
        } else {
            $this->setExpectedException($exception);
        }
    }

    public function _expectExceptionMessage($message)
    {
        if (method_exists($this,'expectExceptionMessage')) {
            $this->expectExceptionMessage($message);
        } else {
            $this->setExpectedException(
                $this->getExpectedException() ? $this->getExpectedException() : 'Exception',
                $message
            );
        }
    }

    protected function createRequest($method, $uri, $options=[])
    {
        return Helper::guzzleIs('>=', 6)?
            new Psr7Request($method, $uri, $options):
            new Request($method, $uri, ['headers' => $options]);
    }

    protected function getHeader($request, $header)
    {
        return Helper::guzzleIs('>=', 6)?
            $request->getHeaderLine($header):
            $request->getHeader($header);
    }

    protected function getQueryStringValue($request, $field)
    {
        if (Helper::guzzleIs('<', 6)) {
            return $request->getQuery()->get($field);
        }

        $query_string = $request->getUri()->getQuery();

        $values = $this->parseQueryString($query_string);

        return array_key_exists($field, $values)? $values[$field]: null;
    }

    protected function getFormPostBodyValue($request, $field)
    {
        if (Helper::guzzleIs('<', 6)) {
            return $request->getBody()->getField($field);
        }

        $query_string = (string)$request->getBody();

        $values = $this->parseQueryString($query_string);

        return array_key_exists($field, $values)? $values[$field]: null;
    }

    protected function parseQueryString($query_string)
    {
        $values = [];
        foreach (explode('&', $query_string) as $component) {
            list($key, $value) = explode('=', $component);
            $values[rawurldecode($key)] = rawurldecode($value);
        }

        return $values;
    }

    protected function setPostBody($request, array $data=[])
    {
        if (Helper::guzzleIs('>=', 6)) {
            return $request->withBody(Helper::streamFor(http_build_query($data, '', '&')));
        }

        $postBody = new PostBody();
        $postBody->replaceFields($data);
        $request->setBody($postBody);

        return $request;
    }

    protected function getJsonValue($request, $field)
    {
        $json = (string)$request->getBody();

        $values = json_decode($json, true);

        return array_key_exists($field, $values)? $values[$field]: null;
    }
}
