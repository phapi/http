<?php

namespace Phapi\Http;

use Phapi\Contract\Http\Request as RequestContract;
use Zend\Diactoros\ServerRequest;

/**
 * Extending Diactoros to add more functionality
 *
 * @category Phapi
 * @package  Phapi\Http
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/http
 */
class Request extends ServerRequest implements RequestContract
{

    /**
     * Check if the provided request method is the same
     * as the one in the request object.
     *
     * @param string $method Request method
     * @return bool
     */
    public function isMethod($method)
    {
        return ($method === $this->getMethod());
    }
}
