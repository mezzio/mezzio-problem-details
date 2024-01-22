# Quick Start

This package provides three primary mechanisms for creating and returning
Problem Details responses:

- `ProblemDetailsResponseFactory` for generating problem details responses on
  the fly from either PHP primitives or exceptions/throwables.
- `ProblemDetailsExceptionInterface` for creating exceptions with additional problem
  details that may be used when generating a response.
- `ProblemDetailsMiddleware` that acts as error/exception handler middleware,
  casting and throwing PHP errors as `ErrorException` instances, and all caught
  exceptions as problem details responses using the
  `ProblemDetailsResponseFactory`.

## ProblemDetailsResponseFactory

If you are using [Mezzio](https://docs.mezzio.dev/mezzio/)
and have installed [laminas-component-installer](https://docs.laminas.dev/laminas-component-installer)
(which is installed by default in v2.0 and above), you can write middleware that
composes the `Mezzio\ProblemDetails\ProblemDetailsResponseFactory` immediately, and
inject that service in your middleware.

As an example, the following catches domain exceptions and uses them to create
problem details responses:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;

class DomainTransactionMiddleware implements MiddlewareInterface
{
    private $domainService;

    private $problemDetailsFactory;

    public function __construct(
        DomainService $service,
        ProblemDetailsResponseFactory $problemDetailsFactory
    ) {
        $this->domainService = $service;
        $this->problemDetailsFactory = $problemDetailsFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            $result = $this->domainService->transaction($request->getParsedBody());
            return new JsonResponse($result);
        } catch (DomainException $e) {
            return $this->problemDetailsFactory->createResponseFromThrowable($request, $e);
        }
    }
}
```

The factory for the above might look like:

```php
use Psr\Container\ContainerInterface;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;

class DomainTransactionMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new DomainTransactionMiddleware(
            $container->get(DomainService::class),
            $container->get(ProblemDetailsResponseFactory::class)
        );
    }
}
```

Another way to use the factory is to provide PHP primitives to the factory. As
an example, validation failure is an expected condition, but should likely
result in problem details to the end user.

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\InputFilter\InputFilterInterface;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;

class DomainTransactionMiddleware implements MiddlewareInterface
{
    private $domainService;

    private $inputFilter;

    private $problemDetailsFactory;

    public function __construct(
        DomainService $service,
        InputFilterInterface $inputFilter,
        ProblemDetailsResponseFactory $problemDetailsFactory
    ) {
        $this->domainService = $service;
        $this->inputFilter = $inputFilter;
        $this->problemDetailsFactory = $problemDetailsFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $this->inputFilter->setData($request->getParsedBody());
        if (! $this->inputFilter->isValid()) {
            return $this->problemDetailsFactory->createResponse(
                $request,
                422,
                'Domain transaction request failed validation',
                '',
                '',
                ['messages' => $this->inputFilter->getMessages()]
            );
        }

        try {
            $result =
            $this->domainService->transaction($this->inputFilter->getValues());
            return new JsonResponse($result);
        } catch (DomainException $e) {
            return $this->problemDetailsFactory->createResponseFromThrowable($request, $e);
        }
    }
}
```

The above modifies the original example to add validation and, on failed
validation, return a custom response that includes the validation failure
messages.

## Custom Exceptions

In the above examples, we have a `DomainException` that is used to create a
Problem Details response. By default, in production mode, the factory will use
the exception message as the Problem Details description, and the exception code
as the HTTP status if it falls in the 400 or 500 range (500 will be used
otherwise).

You can also create custom exceptions that provide details for the factory to
consume by implementing `Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface`,
which defines the following:

```php
namespace Mezzio\ProblemDetails\Exception;

use JsonSerializable;

interface ProblemDetailsExceptionInterface extends JsonSerializable
{
    public function getStatus() : int;
    public function getType() : string;
    public function getTitle() : string;
    public function getDetail() : string;
    public function getAdditionalData() : array;
    public function toArray() : array;
}
```

We also provide the trait `CommonProblemDetailsExceptionTrait`, which implements each
of the above, the `jsonSerialize()` method, and also defines the following
instance properties:

```php
/**
 * @var int
 */
private $status;

/**
 * @var string
 */
private $detail;

/**
 * @var string
 */
private $title;

/**
 * @var string
 */
private $type;

/**
 * @var array
 */
private $additional = [];
```

By composing this trait, you can easily define custom exception types:

```php
namespace Api;

use DomainException as PhpDomainException;
use Mezzio\ProblemDetails\Exception\CommonProblemDetailsExceptionTrait;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;

class DomainException extends PhpDomainException implements ProblemDetailsExceptionInterface
{
    use CommonProblemDetailsExceptionTrait;

    public static function create(string $message, array $details) : self
    {
        $e = new self($message)
        $e->status = 417;
        $e->detail = $message;
        $e->type = 'https://example.com/api/doc/domain-exception';
        $e->title = 'Domain transaction failed';
        $e->additional['transaction'] = $details;
        return $e;
    }
}
```

The data present in the generated exception will then be used by the
`ProblemDetailsResponseFactory` to generate full Problem Details.

## Error handling

When writing APIs, you may not want to handle every error or exception manually,
or may not be aware of problems in your code that might lead to them. In such
cases, having error handling middleware that can generate problem details can be
handy.

This package provides `ProblemDetailsMiddleware` for that situation. It composes
a `ProblemDetailsResponseFactory`, and does the following:

- If the request can not accept either JSON or XML responses, it simply
  passes handling to the request handler.
- Otherwise, it creates a PHP error handler that converts PHP errors to
  `ErrorException` instances, and then wraps processing of the request handler
  in a try/catch block.
- Any throwable or exception caught is passed to the
  `ProblemDetailsResponseFactory::createResponseFromThrowable()` method, and the
  response generated is returned.

When using Mezzio, the middleware service is already wired to a factory that
ensures the `ProblemDetailsResponseFactory` is composed. As such, you can wire
it into your workflow in several ways.

First, you can have it intercept every request:

```php
$app->pipe(ProblemDetailsMiddleware::class);
```

With Mezzio, you can also segregate this to a subpath:

```php
$app->pipe('/api', ProblemDetailsMiddleware::class);
```

Finally, you can include it in a route-specific pipeline:

```php
$app->post('/api/domain/transaction', [
    ProblemDetailsMiddleware::class,
    BodyParamsMiddleware::class,
    DomainTransactionMiddleware::class,
]);
```

## Not Found handling

When writing APIs you may also want 404 responses be in the accepted content-type.
This package provides `ProblemDetailsNotFoundHandler` which will return a
problem details `Response` with a `404` status if the request can accept either
JSON or XML.

To use this handler in Mezzio add it into your pipeline immediate before the
default `NotFoundHandler`:

```php
$app->pipe(\Mezzio\ProblemDetails\ProblemDetailsNotFoundHandler::class);
$app->pipe(NotFoundHandler::class);
```
