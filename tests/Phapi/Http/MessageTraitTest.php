<?php

namespace Phapi\Tests\Http;

use Phapi\Http\Request;
use Phapi\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Http\Request
 */
class MessageTraitTest extends TestCase
{

    public $message;
    public $stream;

    public function setUp()
    {
        $this->stream  = new Stream('php://memory', 'wb+');
        $this->message = new Request([], [], $this->stream);
    }

    public function testUsesStreamProvidedInConstructorAsBody()
    {
        $this->assertSame($this->stream, $this->message->getBody());
    }

    public function testBodyMutatorReturnsCloneWithChanges()
    {
        $stream  = new Stream('php://memory', 'wb+');
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    public function testGetHeaderReturnsHeaderValueAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }
    public function testGetHeaderLineReturnsHeaderValueAsCommaConcatenatedString()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }
    public function testGetHeadersKeepsHeaderCaseSensitivity()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }
    public function testGetHeadersReturnsCaseWithWhichHeaderFirstRegistered()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }
    public function testHasHeaderReturnsFalseIfHeaderIsNotPresent()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }
    public function testHasHeaderReturnsTrueIfHeaderIsPresent()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }
    public function testAddHeaderAppendsToExistingHeader()
    {
        $message  = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertEquals('Foo,Bar', $message2->getHeaderLine('X-Foo'));
    }
    public function testCanRemoveHeaders()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }
    public function testHeaderRemovalIsCaseInsensitive()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
        $headers = $message2->getHeaders();
        $this->assertEquals(0, count($headers));
    }
    public function invalidGeneralHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [[ 'foo' => [ 'bar' ] ]],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testWithHeaderRaisesExceptionForInvalidNestedHeaderValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', [ $value ]);
    }

    public function invalidHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidHeaderValues
     */
    public function testWithHeaderRaisesExceptionForInvalidValueType($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testWithAddedHeaderRaisesExceptionForNonStringNonArrayValue($value)
    {
        $message = $this->message->withAddedHeader('X-Foo', 'first');
        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $message = $message->withAddedHeader('X-Foo', $value);
    }
    public function testWithoutHeaderDoesNothingIfHeaderDoesNotExist()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }
    public function testHeadersInitialization()
    {
        $headers = [
            'HTTP_X_FOO' => ['bar'],
            'HTTP_COOKIE' => ['cookie'],
            'CONTENT_TYPE' => ['application/json'],
            'HTTP_X_HTTP_METHOD_OVERRIDE' => ['PUT']
        ];
        $this->message = new Request($headers);
        $this->assertSame(['X-Foo' => ['bar'], 'Content-Type' => ['application/json'], 'X-Http-Method-Override' => ['PUT']], $this->message->getHeaders());
        $this->assertFalse($this->message->hasHeader('Cookie'));
        $this->assertFalse($this->message->hasHeader('HTTP_COOKIE'));
    }
    public function testGetHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist()
    {
        $this->assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }
    public function testGetHeaderLineReturnsNullWhenHeaderDoesNotExist()
    {
        $this->assertNull($this->message->getHeaderLine('X-Foo-Bar'));
    }

    public function testGetHeaderLineReturnsNullWhenHeaderHasNoValue()
    {
        $message = $this->message->withAddedHeader('X-Foo-Null', []);
        $this->assertNull($message->getHeaderLine('X-Foo-Null'));
    }

    public function testInvalidHeaderName()
    {
        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $message = $this->message->withHeader(null, 'value');
    }

    public function testConstructStringValue()
    {
        $server = [
            'HTTP_X_FOO' => 'foobar'
        ];

        $message = new Request($server);
        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertEquals(['foobar'], $message->getHeader('X-Foo'));
    }

    public function testConstructInvalidValueType()
    {
        $server = [
            'HTTP_X_FOO' => null
        ];

        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $message = new Request($server);
    }

    public function testProtocolHasAcceptableDefault()
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());
    }

    public function testProtocolMutatorReturnsCloneWithChanges()
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

}