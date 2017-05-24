<?php

namespace kamermans\OAuth2\Tests\Utils;

use PHPUnit_Framework_TestCase;
use kamermans\OAuth2\Utils\Helper;

class HelperTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider guzzleVersionTestProvider
     */
    public function testGuzzleIs($my_version, $operator, $mock_version, $expected)
    {
        $result = Helper::guzzleIs($operator, $my_version, $mock_version);
        $this->assertSame($expected, $result, "Expression failed: $my_version $operator $mock_version === ".var_export($expected, true));
    }

    public function guzzleVersionTestProvider()
    {
        return [
            ["5.0", "<", "6.0", true],
            ["5.0", ">", "6.0", false],
            ["5.0.22", ">", "5.0.21", true],
            ["5.0.22-beta1", ">", "5.0.22", false],
            ["5.0.22", ">", "5", true],
            ["5.1", ">", "5.0.21", true],
            ["5.1", ">=", "5.0.21", true],
            ["5.1", ">=", "5.1.0", true],
            ["5.0.22", "<=", "6", true],
            ["5.0.22", "<=", "6.1", true],
            ["6.1.14", "<=", "6.1.14", true],
            ["5.0.22", "==", "5.0.21", false],
            ["6.1.14", "~", "6.1.14", true],
            ["5.0.22", "~", "5.0.21", false],
            ["5", "~", "5.0.21", true],
            ["5", "~", "6.0.21", false],
            ["5", "~", "4.0.21", false],
            ["5.0.21", "~", "5", true],
            ["6.0.21", "~", "5", false],
            ["4.0.21", "~", "5", false],
        ];
    }
}
