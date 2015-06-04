<?php

namespace Phapi\Http;

use Phapi\Contract\Http\Request as Contract;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Implementation of PSR server Request
 *
 * @category Phapi
 * @package  Phapi\Http
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/http
 * @link     https://github.com/phly/http This class is based upon
 *           Matthew Weier O'Phinney's Request implementation in phly/http.
 */
class Request implements Contract {

    /**
     * Use traits
     */
    use MessageTrait;

    private $validMethods = [
        'CONNECT', 'TRACE',
        'GET', 'HEAD', 'OPTIONS',
        'POST', 'PATCH', 'PUT',
        'DELETE', 'COPY',
        'LOCK', 'UNLOCK'
    ];

    /**
     * Current server params (usually same as $_SERVER]
     *
     * @var array
     */
    private $serverParams = [];

    /**
     * Current attributes
     *
     * @var array
     */
    private $attributes = [];

    /**
     * Current parsed body
     *
     * @var mixed
     */
    private $parsedBody;

    /**
     * Current query params
     *
     * @var array
     */
    private $queryParams = [];

    /**
     * Current cookie params
     *
     * @var array
     */
    private $cookieParams = [];

    /**
     * Current request target
     *
     * @var string
     */
    private $requestTarget;

    /**
     * Current request method
     *
     * @var string
     */
    private $method;

    /**
     * Current URI
     *
     * @var UriInterface
     */
    private $uri;

    /**
     * Uploaded files
     *
     * @var array
     */
    private $uploadedFiles;

    /**
     * Create an request
     *
     * @param array $serverParams
     * @param array $uploadedFiles
     * @param string $body
     * @param array $validMethods
     */
    public function __construct($serverParams = [], $uploadedFiles = [], $body = 'php://memory', $validMethods = [])
    {
        if (!empty($validMethods)) {
            $this->validMethods = $validMethods;
        }

        // Set server params
        $this->serverParams =
            ($serverParams !== null) ? array_merge($serverParams, $this->serverParams) : $this->serverParams;

        // Find and process headers
        $this->headers = $this->findHeaders($this->serverParams);
        $this->headerNames = $this->findHeaderNames($this->headers);

        // Make sure the body is valid
        $this->validateBody($body);
        // Set body
        $this->stream = ($body instanceof StreamInterface) ? $body : new Stream($body, 'r');

        // Find the request method from the server params
        $this->method = $this->findMethod($this->serverParams);

        // Create an Uri object by looking for the request uri in the server params
        $this->uri = (isset($this->serverParams['REQUEST_URI'])) ? new Uri($this->serverParams['REQUEST_URI']) : null;

        // Check for a query string
        if ($this->uri instanceof UriInterface) {
            parse_str($this->uri->getQuery(), $this->queryParams);
        }

        // Handle uploaded files
        $this->validateUploadedFiles($uploadedFiles);
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        if (! $this->uri) {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function isMethod($method)
    {
        return ($this->method === $method) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        $this->validateMethod($method);
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost) {
            return $new;
        }

        if (! $uri->getHost()) {
            return $new;
        }

        $host = $uri->getHost();
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        $new->headerNames['host'] = 'Host';
        $new->headers['Host'] = array($host);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (! array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute)
    {
        if (!isset($this->attributes[$attribute])) {
            return clone $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }

    /**
     * Validate the HTTP method
     *
     * @param null|string $method
     * @throws \InvalidArgumentException on invalid HTTP method.
     * @return bool
     */
    private function validateMethod($method)
    {
        if (null === $method) {
            return true;
        }

        if (!is_string($method)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);

        if (!in_array($method, $this->validMethods, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return true;
    }

    /**
     * Find request method from server params and make sure
     * its a valid one
     *
     * @param $serverParams
     * @return null
     */
    private function findMethod($serverParams)
    {
        if (!isset($serverParams['REQUEST_METHOD'])) {
            return null;
        }

        $method = $serverParams['REQUEST_METHOD'];
        $this->validateMethod($method);

        return $method;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @param array $uploadedFiles
     * @throws \InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    private function validateUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }
            if (! $file instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException('Invalid leaf in uploaded files structure');
            }
        }
    }

    /**
     * Validate the body
     *
     * @param $body
     */
    private function validateBody($body)
    {
        if (!is_string($body) && !is_resource($body) && !$body instanceof StreamInterface) {
            throw new \InvalidArgumentException(
                'Body must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }
    }
}