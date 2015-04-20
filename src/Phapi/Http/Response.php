<?php

namespace Phapi\Http;

use Phapi\Contract\Http\Response as ResponseContract;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR Response
 *
 * @category Phapi
 * @package  Phapi\Http
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/http
 * @link     https://github.com/phly/http This class is based upon
 *           Matthew Weier O'Phinney's Stream implementation in phly/http.
 */
class Response implements ResponseContract
{

    use MessageTrait;

    /**
     * Success!
     */
    const STATUS_OK = 200;

    /**
     * New resource created
     */
    const STATUS_CREATED = 201;

    /**
     * Request accepted and set to be performed in a background task. Useful if your client is
     * requesting something on the API that is time-consuming and you don?t want the client to have to wait.
     */
    const STATUS_ACCEPTED = 202;

    /**
     * The response does not include any content.
     */
    const STATUS_NO_CONTENT = 204;

    /**
     * Moved permanently
     */
    const STATUS_MOVED_PERMANENTLY = 301;

    /**
     * There was no new data to return.
     */
    const STATUS_NOT_MODIFIED = 304;

    /**
     * Temporary Redirect
     */
    const STATUS_TEMPORARY_REDIRECT = 307;

    /**
     * The request was invalid or cannot be otherwise served. An accompanying error message will explain further.
     */
    const STATUS_BAD_REQUEST = 400;

    /**
     * Authentication credentials were missing or incorrect.
     */
    const STATUS_UNAUTHORIZED = 401;

    /**
     * The request is understood, but it has been refused or access is not allowed. An accompanying
     * error message will explain why.
     */
    const STATUS_FORBIDDEN = 403;

    /**
     * The URI requested is invalid or the resource requested, such as a user, does not exists.
     * Also returned when the requested format is not supported by the requested method.
     */
    const STATUS_NOT_FOUND = 404;

    /**
     * Returned by the API when an invalid format is specified in the request.
     */
    const STATUS_NOT_ACCEPTABLE = 406;

    /**
     * This resource is gone. Used to indicate that an API endpoint has been turned off.
     * For example: "The REST API v1 will soon stop functioning. Please migrate to API v1.1."
     */
    const STATUS_GONE = 410;

    /**
     * Payment is required before the requested method/resource can be requested.
     */
    const STATUS_PAYMENT_REQUIRED = 402;

    /**
     * The requested method is not allowed.
     */
    const STATUS_METHOD_NOT_ALLOWED = 405;

    /**
     * The request timed out.
     */
    const STATUS_REQUEST_TIMEOUT = 408;

    /**
     * The submitted data is causing a conflict with the current state of the resource.
     * An accompanying error message will explain why.
     */
    const STATUS_CONFLICT = 409;

    /**
     * The requested entity is too large.
     */
    const STATUS_REQUEST_ENTITY_TOO_LARGE = 413;

    /**
     * Media type not supported.
     */
    const STATUS_UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * Returned when an uploaded file is unable to be processed.
     */
    const STATUS_UNPROCESSABLE_ENTITY = 422;

    /**
     * The requested resource is currently locked
     */
    const STATUS_LOCKED = 423;

    /**
     * Returned when a request cannot be served due to the application's rate
     * limit having been exhausted for the resource.
     */
    const STATUS_TOO_MANY_REQUESTS = 429;

    /**
     * Something is broken.
     */
    const STATUS_INTERNAL_SERVER_ERROR = 500;

    /**
     * The requested method is not implemented.
     */
    const STATUS_NOT_IMPLEMENTED = 501;

    /**
     * The API is down or being upgraded
     */
    const STATUS_BAD_GATEWAY = 502;

    /**
     * The API is up, but overloaded with requests. Try again later.
     */
    const STATUS_SERVICE_UNAVAILABLE = 503;

    /**
     * The response status code
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * The response reason phrase
     *
     * @var string
     */
    protected $reasonPhrase;

    /**
     * List of standard reason phrases
     *
     * @var array
     */
    protected $reasonPhrases = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * The unparsed body
     *
     * @var array
     */
    protected $unparsedBody;

    public function __construct($body = 'php://memory', $code = 200, $headers = [])
    {
        if (!is_string($body) && !is_resource($body) && !$body instanceof StreamInterface) {
            throw new \InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamInterface implementation'
            );
        }

        if ($code === null) {
            $code = 200;
        }

        $this->validateStatus($code);

        $this->statusCode = (int) $code;
        $this->stream = ($body instanceof StreamInterface) ? $body : new Stream($body, 'w+');
        list($this->headerNames, $this->headers) = $this->filterHeaders($headers);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        if (!isset($this->reasonPhrase) && isset($this->reasonPhrases[$this->statusCode])) {
            $this->reasonPhrase = $this->reasonPhrases[$this->statusCode];
        }

        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = null)
    {
        $this->validateStatus($code);

        $clone = clone ($this);
        $clone->statusCode = (int)$code;
        $clone->reasonPhrase = ($reasonPhrase === null) ? $this->reasonPhrases[$code] : $reasonPhrase;

        return $clone;
    }

    /**
     * Create a new instance with the specified unparsed/unserialized body.
     *
     * @param array $body
     * @return Response
     */
    public function withUnparsedBody(array $body = [])
    {
        $clone = clone $this;
        $clone->unparsedBody = $body;
        return $clone;
    }

    /**
     * Get the unparsed/unserialized body
     *
     * @return mixed
     */
    public function getUnparsedBody()
    {
        return $this->unparsedBody;
    }

    /**
     * Validate a status code.
     *
     * @param int|string $code
     * @throws \InvalidArgumentException on an invalid status code.
     */
    private function validateStatus($code)
    {
        if (!is_numeric($code)
            || is_float($code)
            || $code < 100
            || $code >= 600
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid status code "%s"; must be an integer between 100 and 599, inclusive',
                (is_scalar($code) ? $code : gettype($code))
            ));
        }
    }

    /**
     * Filter a set of headers to ensure they are in the correct internal format.
     *
     * Used by message constructors to allow setting all initial headers at once.
     *
     * @param array $originalHeaders Headers to filter.
     * @return array Filtered headers and names.
     */
    private function filterHeaders(array $originalHeaders)
    {
        $headerNames = $headers = [];
        foreach ($originalHeaders as $header => $value) {
            if (! is_string($header)) {
                continue;
            }
            if (! is_array($value) && ! is_string($value)) {
                continue;
            }
            if (! is_array($value)) {
                $value = [ $value ];
            }
            $headerNames[strtolower($header)] = $header;
            $headers[$header] = $value;
        }
        return [ $headerNames, $headers ];
    }
}