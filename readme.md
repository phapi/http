# HTTP Message
HTTP Message is an implementation of [PSR-7](https://github.com/php-fig/http-message) with some additional complementary methods to simplify the usage of the request and response objects.

This package extends [zendframework/zend-diactoros](https://github.com/zendframework/zzend-diactoros).

## Installation
The package is installed by default by the Phapi framework. Installing the package to use is separately can be done by using composer:

```shell
$ composer require phapi/http:1.*
```

## Usage
Phapi is [PSR-7](https://github.com/php-fig/http-message/) compliant but does not implement the interfaces itself, instead Phapi depends on the  [zend-diactoros](https://github.com/zendframework/zend-diactoros) implementation. See the PSR-7 interfaces for more information about how to use them:

- [Psr\Http\Message\ServerRequestInterface](https://github.com/php-fig/http-message/blob/master/src/ServerRequestInterface.php)
- [Psr\Http\Message\ResponseInterface](https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php)
- [Psr\Http\Message\UriInterface](https://github.com/php-fig/http-message/blob/master/src/UriInterface.php)
- [Psr\Http\Message\StreamInterface](https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php)
- [Psr\Http\Message\UploadedFileInterface](https://github.com/php-fig/http-message/blob/master/src/UploadedFileInterface.php)

### Extended methods
#### Body
Use the <code>withUnserializedBody(array $data)</code> method on the response object to add or modify the body. The serializer middleware will then serialize the body and set the serialized string as the response body.

## License
Phapi HTTP Message is licensed under the MIT License - see the [license.md](https://github.com/phapi/http/blob/master/license.md) file for details

## Contribute
Contribution, bug fixes etc are [always welcome](https://github.com/phapi/http/issues/new).
