<?php
namespace Phapi\Tests\Http;

use Phapi\Http\Request;
use Phapi\Http\UploadedFile;
use Phapi\Http\Uri;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

/**
 * @coversDefaultClass \Phapi\Http\Request
 */
class RequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new Request();
    }

    public function testServerParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getServerParams());
    }

    public function testQueryParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getQueryParams());
    }

    public function testQueryParamsMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withQueryParams($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getCookieParams());
    }

    public function testCookiesMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withCookieParams($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getCookieParams());
    }

    public function testUploadedFilesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getUploadedFiles());
    }

    public function testParsedBodyIsEmptyByDefault()
    {
        $this->assertEmpty($this->request->getParsedBody());
    }

    public function testParsedBodyMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withParsedBody($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getParsedBody());
    }

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
    }

    /**
     * @depends testAttributesAreEmptyByDefault
     */
    public function testAttributeMutatorReturnsCloneWithChanges()
    {
        $request = $this->request->withAttribute('foo', 'bar');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('bar', $request->getAttribute('foo'));
        return $request;
    }

    /**
     * @depends testAttributeMutatorReturnsCloneWithChanges
     */
    public function testRemovingAttributeReturnsCloneWithoutAttribute($request)
    {
        $new = $request->withoutAttribute('foo');
        $this->assertNotSame($request, $new);
        $this->assertNull($new->getAttribute('foo', null));
    }

    public function testUsesProvidedConstructorArguments()
    {
        $server = [
            'foo' => 'bar',
            'baz' => 'bat',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => 'http://example.com',
            'HTTP_HOST' => 'example.com'
        ];

        $server['server'] = true;

        $files = [
            'files' => new UploadedFile('php://temp', 0, 0),
        ];

        $uri = new Uri('http://example.com');
        $method = 'POST';
        $headers = [
            'Host' => ['example.com'],
        ];

        $request = new Request(
            $server,
            $files,
            'php://memory'
        );

        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($files, $request->getUploadedFiles());

        $this->assertInstanceOf('Phapi\Http\Uri', $request->getUri());
        $this->assertEquals($method, $request->getMethod());
        $this->assertTrue($request->isMethod('POST'));
        $this->assertFalse($request->isMethod('GET'));
        $this->assertEquals($headers, $request->getHeaders());

        $body = $request->getBody();
        $r = new ReflectionProperty($body, 'stream');
        $r->setAccessible(true);
        $stream = $r->getValue($body);
        $this->assertEquals('php://memory', $stream);
    }

    public function testMethodIsNullByDefault()
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('GET');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testRequestTargetIsSlashWhenNoUriPresent()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testRequestTargetIsSlashWhenUriHasNoPathOrQuery()
    {
        $request = (new Request())
            ->withUri(new Uri('http://example.com'));
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function requestsWithUri()
    {
        return [
            'absolute-uri' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user'
            ],
            'absolute-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar'
            ],
            'relative-uri' => [
                (new Request())
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user'
            ],
            'relative-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar'
            ],
        ];
    }

    /**
     * @dataProvider requestsWithUri
     */
    public function testReturnsRequestTargetWhenUriIsPresent($request, $expected)
    {
        $this->assertEquals($expected, $request->getRequestTarget());
    }

    public function validRequestTargets()
    {
        return [
            'asterisk-form'         => [ '*' ],
            'authority-form'        => [ 'api.example.com' ],
            'absolute-form'         => [ 'https://api.example.com/users' ],
            'absolute-form-query'   => [ 'https://api.example.com/users?foo=bar' ],
            'origin-form-path-only' => [ '/users' ],
            'origin-form'           => [ '/users?id=foo' ],
        ];
    }

    /**
     * @dataProvider validRequestTargets
     */
    public function testCanProvideARequestTarget($requestTarget)
    {
        $request = (new Request())->withRequestTarget($requestTarget);
        $this->assertEquals($requestTarget, $request->getRequestTarget());
    }

    public function testRequestTargetCannotContainWhitespace()
    {
        $request = new Request();
        $this->setExpectedException('InvalidArgumentException', 'Invalid request target');
        $request->withRequestTarget('foo bar baz');
    }

    public function testRequestTargetDoesNotCacheBetweenInstances()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://github.com/bar/baz'));
        $this->assertNotEquals($original, $newRequest->getRequestTarget());
    }

    public function testSettingNewUriResetsRequestTarget()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('https://github.com/bar/baz'));
        $this->assertNotEquals($original, $newRequest->getRequestTarget());
    }

    public function testConstructWithInvalidBody()
    {
        $this->setExpectedException('InvalidArgumentException', 'Body must be a string');
        $request = new Request([], [], new \stdClass());
    }

    public function testMethodValidationFailType()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        $this->request->withMethod(new \stdClass());
    }

    public function testMethodValidationFailMethod()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        $this->request->withMethod('CATCH');
    }

    public function testMethodValidationWithNull()
    {
        $new = $this->request->withMethod(null);
        $this->assertNull($new->getMethod());
    }

    public function testWithoutAttributeWithNotExistingAttribute()
    {
        $new = $this->request->withoutAttribute('nonExisting');
        $this->assertNotSame($new, $this->request);
        $this->assertEquals('wrong', $new->getAttribute('nonExisting', 'wrong'));
    }

    public function testWithUriPreserveHost()
    {
        $new = $this->request->withUri(new Uri('https://api.example.com/foo/bar'), true);
        $this->assertFalse($new->hasHeader('Host'));
        $this->assertEquals([], $new->getHeader('Host'));
    }

    public function testWithUriWithPort()
    {
        $new = $this->request->withUri(new Uri('https://api.example.com:8080/foo/bar'));
        $this->assertEquals(8080, $new->getUri()->getPort());
    }

    public function testWithUriWithoutHost()
    {
        $new = $this->request->withUri(new Uri('/foo/bar'));
        $this->assertEquals('/foo/bar', (string) $new->getUri());
    }

    public function testWithUploadedFiles()
    {
        $files = [
            'files' => new UploadedFile('php://temp', 0, 0),
            'anotherFile' => new UploadedFile('php://temp', 0, 0),
        ];
        $new = $this->request->withUploadedFiles($files);
        $this->assertNotSame($this->request, $new);
        $this->assertEquals($files, $new->getUploadedFiles());
    }

    public function testWithUploadedFilesArrayInArray()
    {
        $files = [[
            'files' => new UploadedFile('php://temp', 0, 0),
            'anotherFile' => new UploadedFile('php://temp', 0, 0),
        ]];
        $new = $this->request->withUploadedFiles($files);
        $this->assertNotSame($this->request, $new);
        $this->assertEquals($files, $new->getUploadedFiles());
    }

    public function testWithUploadedFilesNotInstanceOfUploadedFileInterface()
    {
        $files = [[
            'files' => new UploadedFile('php://temp', 0, 0),
            'anotherFile' => new UploadedFile('php://temp', 0, 0),
            'std' => new \stdClass(),
        ]];
        $this->setExpectedException('InvalidArgumentException', 'Invalid leaf in upload');
        $new = $this->request->withUploadedFiles($files);
    }

    public function testSetValidMethods()
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH'
        ];
        $validMethods = ['GET', 'POST'];

        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        $request = new Request($server, [], 'php://memory', $validMethods);
    }
}