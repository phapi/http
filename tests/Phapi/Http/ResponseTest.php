<?php


namespace Phapi\Tests\Http;

use Phapi\Http\Response;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Http\Response
 */
class ResponseTest extends TestCase
{

    public function testUnserializedBody()
    {
        $array = [
            'key' => 'value',
            'anotherKey' => 'the second value',
        ];

        $response = new Response();
        $new = $response->withUnserializedBody($array);

        $this->assertNotSame($response, $new);

        $this->assertEquals($array, $new->getUnserializedBody());
    }

}