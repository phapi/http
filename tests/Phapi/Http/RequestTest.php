<?php


namespace Phapi\Tests\Http;

use Phapi\Http\Request;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Http\Request
 */
class RequestTest extends TestCase
{

    public function testIsMethod()
    {
        $request = new Request();
        $new = $request->withMethod('POST');

        $this->assertFalse($new->isMethod('GET'));
        $this->assertTrue($new->isMethod('POST'));
    }
}
