<?php

namespace Phapi\Http;

use Psr\Http\Message\StreamInterface;

trait MessageTrait {

    /**
     * Current body stream
     *
     * @var resource
     */
    private $stream;

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    private $specialHeaders = [
        'php_auth_user',
        'php_auth_pw',
        'php_auth_digest',
        'auth_type'
    ];

    /**
     * All headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Storage of original header names
     *
     * @var array
     */
    private $headerNames = [];

    /**
     * Current protocol version
     *
     * @var string
     */
    private $protocol = '1.1';

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        $clone = clone $this;
        $clone->protocol = $version;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return array_key_exists(strtolower($name), $this->headerNames);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $original = $this->headerNames[strtolower($name)];

        $value = $this->headers[$original];
        $value = is_array($value) ? $value : [$value];
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        if (!$this->hasHeader($name)) {
            return null;
        }

        $value = $this->getHeader($name);

        if (empty($value)) {
            return null;
        }

        return implode(',', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        if (!$this->isValidHeader($name, $value)) {
            throw new \InvalidArgumentException(
                'Invalid header value, must be a string or array of strings'
            );
        }

        if (is_string($value)) {
            $value = [$value];
        }

        $clone = clone $this;
        $clone->headerNames[strtolower($name)] = $name;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->isValidHeader($name, $value)) {
            throw new \InvalidArgumentException(
                'Unsupported header value, must be a string or array of strings'
            );
        }

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        if (is_string($value)) {
            $value = [$value];
        }

        $name = $this->headerNames[strtolower($name)];
        $clone = clone $this;
        $clone->headers[$name] = array_merge($this->headers[$name], $value);
        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return clone $this;
        }

        $normalized = strtolower($name);
        $original   = $this->headerNames[$normalized];

        $clone = clone $this;
        unset($clone->headers[$original], $clone->headerNames[$normalized]);
        return $clone;
    }

    /**
     * Find all headers from an array (usually the global $_SERVER variable)
     *
     * @param $serverParams
     * @return array
     */
    private function findHeaders($serverParams)
    {
        $headers = [];

        // Loop through server params
        foreach ($serverParams as $name => $value) {
            if (strpos($name, 'HTTP_COOKIE') === 0) {
                // Cookies are handled using the $_COOKIE superglobal
                continue;
            }

            // Normalize header name
            $normalized = strtolower($name);

            // Check if name is in special array or if name starts with http
            if (
                in_array($normalized, $this->specialHeaders) ||
                0 === strpos($normalized, 'http_') ||
                0 === strpos($normalized, 'content_')
            ) {
                // Filter header name
                $name = $this->filterHeaderName($name);

                // Filter header value
                $value = $this->filterHeaderValue($value);

                // Checks if it is a valid header
                if ($this->isValidHeader($name, $value)) {
                    // Valid header
                    $headers[$name] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Filter header values
     *
     * Makes sure that header values are stored in arrays and checks so the array
     * only contains strings. Throws exception if array contains something else.
     *
     * @param string|array $value
     * @throws \InvalidArgumentException when array contains something else than strings
     * @return array
     */
    private function filterHeaderValue($value)
    {
        if (is_string($value)) {
            $value = [ $value ];
        }

        if (!is_array($value) || !(array_filter($value, 'is_string') === $value)) {
            throw new \InvalidArgumentException(
                'Invalid header value; must be a string or array of strings'
            );
        }

        return $value;
    }

    /**
     * Filter header names.
     *
     * Removes any occurranse of "http_" and normalizes the string
     * to upper casing all words and replacing all underscores
     * with dashes.
     *
     * @param $name
     * @return mixed|string
     */
    private function filterHeaderName($name)
    {
        // Transform to lowercase
        $name = strtolower($name);
        // Remove http_ in beginning of string
        if (substr($name, 0, strlen('http_')) == 'http_') {
            $name = substr($name, strlen('http_'));
        }
        // Replace all underscores with space
        $name = str_replace('_', ' ', $name);
        // Uppercase all words
        $name = ucwords($name);
        // Replace space with dash
        $name = str_replace(' ', '-', $name);

        return $name;
    }

    /**
     * Check if a header is valid
     *
     * @param $name
     * @param $value
     * @return bool
     */
    private function isValidHeader($name, $value)
    {
        if (!is_string($name)) {
            return false;
        }

        if (is_string($value)) {
            return true;
        }

        if (is_array($value) && (array_filter($value, 'is_string') === $value)) {
            return true;
        }

        return false;
    }

    /**
     * Create an array with normalized (lowercase) header names as keys
     * and the original header name as value
     *
     * @param array $headers
     * @return array
     */
    private function findHeaderNames(array $headers = [])
    {
        $headerNames = [];
        foreach ($headers as $name => $value) {
            $headerNames[strtolower($name)] = $name;
        }

        return $headerNames;
    }
}