<?php


namespace Phapi\Http;

use Zend\Diactoros\ServerRequestFactory as DiactorosServerRequestFactory;

/**
 * Extends Diactoros ServerRequestFactory so we use our own version
 * of the (server)request class.
 *
 * @category Phapi
 * @package  Phapi\Http
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/
 */
class ServerRequestFactory extends DiactorosServerRequestFactory
{

    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
        $server  = static::normalizeServer($server ?: $_SERVER);
        $files   = static::normalizeFiles($files ?: $_FILES);
        $headers = static::marshalHeaders($server);
        $request = new Request(
            $server,
            $files,
            static::marshalUriFromServer($server, $headers),
            static::get('REQUEST_METHOD', $server, 'GET'),
            'php://input',
            $headers
        );
        return $request
            ->withCookieParams($cookies ?: $_COOKIE)
            ->withQueryParams($query ?: $_GET)
            ->withParsedBody($body ?: $_POST);
    }
}
