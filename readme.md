# Phapi HTTP Message
Phapi HTTP Message is an implementation of [PSR-7](https://github.com/php-fig/http-message) with some additional complementary methods to simplify the usage of the request and response objects.

Some of these classes are based on Matthew Weier O'Phinney's implementations in [phly/http](https://github.com/phly/http).

## Request
The request object represents the request made by the client as well as the parameters set by the server.

### Get request method
Retrieve the HTTP method of the request by using the <code>getMethod()</code> method.

```php
<?php
$method = $request->getMethod();
```

### Detect request method
Use the <code>isMethod()</code> method to detect if the request method is the expected method.

```php
<?php
$bool = $request->isMethod('PUT');
```

### Server parameters
Retrieve server parameters with the <code>getServerParams()</code> method.

### Request attributes
Additional attributes derived from the request, usually set by middleware can be retrieved with the <code>getAttributes()</code> method.

#### Single attributes
Retrieve a single request attribute with the <code>getAttribute($name, $default = null)</code> method. If the attribute does not exists the provided <code>$default</code> value will be returned instead.

```php
<?php
$value = $request->getAttribute('anAttribute', null);
```

### Headers
There are several methods that can be used to inspect the Request object's headers.

#### Get all headers
Use the <code>getHeaders()</code> method to receive an array with all existing headers in the request.

```php
<?php
$headers = $request->getHeaders();
```

#### Detect header
Checks if a header exists by the given case-insensitive name by using the <code>hasHeader($name)</code> method.

```php
<?php
$bool = $request->hasHeader('Accept');
```

#### Fetch single header
Retrieve a header by the given case-insensitive name, as an array by using the <code>getHeader($name)</code> method.

```php
<?php
$header = $request->getHeader('Accept'); // Returns array
```

Use <code>getHeaderLine()</code> method to get a string.

```php
<?php
$header = $request->getHeaderLine('Accept'); // Returns string
```


### Request body
Depending on the request method there are mainly two ways to get the content of the request body.

### GET
If the request method is of type GET, use the <code>getQueryParams()</code> method to get the query string content as an array.

```php
<?php
$queryParams = $request->getQueryParams();
```

### Other than GET
You have two options of the request method is something else than a GET. You can get the original body using the <code>getBody()</code> method. Please notice that you will receive an instance of <code>Phapi\Http\Body</code>. To get the request body as a string, use the <code>getContents()</code> method.

```php
<?php
$content = $request->getBody()->getContents();
```

Use the <code>getParsedBody()</code> method if you want to get the unserialized body (given that the unserializer middleware has executed correctly). You will receive the content in an array.

```php
<?php
$content = $request->getParsedBody();
```

### Changing the request object
Following methods must be used to change the request object. The request object should usually only be modified by middleware. Please note that each of these methods clones the request object, makes the modifications and then returns the clone.

#### Query params
Create a new instance with the specified query string arguments using the <code>withQueryParams(array $query)</code> method. All original query params will be replaces with the provided ones.

```php
<?php
$newResponse = $response->withQueryParams([ 'limit' => '20' ]);
```

#### Parsed body
Use the <code>withParsedBody(array $data)</code> method to add or modify a parsed body. <code>null</code> could be passed as the argument if the parsed body should be removed.

#### Original body
The original unserialized body can be modified by using the <code>withBody(StreamableInterface $body)</code> method.

#### Attributes
The <code>withAttribute($name, $value)</code> method adds a new attribute to the request object. If the attribute already exists it will be replaced.

Use the <code>withoutAttribute($name)</code> to remove an attribute.

#### Request method
The <code>withMethod($method)</code> method can be used to change the request http method where <code>$method</code> is a string.

#### Headers
Headers can be modified with three different methods. The <code>withHeader($name, $value)</code> method adds a new header (or replaces an existing one) while the <code>withAddedHeader($name, $value)</code> appends the given value to an existing header.

The <code>withoutHeader($name)</code> method removes the header if it exists.

## Response
The response object represents the response that will be returned to the client.

### Status code and Reason Phrase
Retrieve the status code by using the <code>getStatusCode()</code> method and the reason phrase with the <code>getReasonPhrase()</code> method.

```php
<?php
$code = $respone->getStatusCode(); // 200 by default
$phrase = $response->getReasonPhrase(); // OK by default
```

### Headers
The response headers can be retrieved by using the <code>getHeaders()</code> method to get all headers or by using the <code>getHeader($name)</code> to get a specific header.

The <code>hasHeader($name)</code> method can be used to detect if a header is set.

### Body
The response object has two methods for viewing the body. The <code>getUnserializedBody()</code> method returns the unserialized body as an array while the <code>getBody()</code> method returns the serialized body as a string (if the serializer middleware has been executed).

### Changing the response object
The following methods must be used to change the response object. Please note that each of these methods clones the response object, makes the modifications and then returns the clone.

#### Status code and reason phrase
Change the status code by using the <code>withStatus($code, $reasonPhrase = null)</code> method. If no reason phrase is provided the response object will find the correct reason phrase by itself.

#### Headers
Headers can be modified with three different methods. The <code>withHeader($name, $value)</code> method adds a new header (or replaces an existing one) while the <code>withAddedHeader($name, $value)</code> appends the given value to an existing header.

The <code>withoutHeader($name)</code> method removes the header if it exists.

#### Body
Use the withUnserializedBody(array $data) method to add or modify the body. The serializer middleware will then serialize the body and set the serialized string as the response body.


## License
Phapi HTTP Message is licensed under the MIT License - see the [license.md](https://github.com/phapi/http/blob/master/license.md) file for details

## Contribute
Contribution, bug fixes etc are [always welcome](https://github.com/phapi/http/issues/new).
