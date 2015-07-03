<?php


namespace Phapi\Tests\Http;

use Phapi\Http\ServerRequestFactory;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Http\ServerRequestFactory
 */
class ServerRequestFactoryTest extends TestCase
{
    public function testFactory()
    {
        $server = [
            'REQUEST_URI' => '/'
        ];

        $request = ServerRequestFactory::fromGlobals($server);

        $this->assertInstanceOf('Phapi\Http\Request', $request);
    }
}
