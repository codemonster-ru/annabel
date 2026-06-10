<?php

namespace Codemonster\Annabel\Http;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Http\Exceptions\BadRequestHttpException;
use Codemonster\Annabel\Http\Exceptions\ForbiddenHttpException;
use Codemonster\Annabel\Http\Exceptions\HttpException;
use Codemonster\Annabel\Http\Exceptions\MethodNotAllowedHttpException;
use Codemonster\Annabel\Http\Exceptions\NotFoundHttpException;
use Codemonster\Annabel\Http\Exceptions\UnauthorizedHttpException;
use Codemonster\Errors\Contracts\ExceptionHandlerInterface;
use Codemonster\Http\Request;
use Codemonster\Http\Response;
use Codemonster\Router\Route;
use Codemonster\Router\Router;
use Codemonster\Validation\ValidationException;
use JsonSerializable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Kernel
{
    protected Application $app;
    protected Router $router;
    /** @var list<callable|MiddlewareInterface|string> */
    protected array $middleware = [];
    /** @var array<string, string> */
    protected array $middlewareAliases = [];
    /** @var array<string, list<string|array{0: string, 1?: mixed}>> */
    protected array $middlewareGroups = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->runMiddleware($request, fn (Request $req) => $this->dispatch($req));

            $response = $this->normalizeResponse($response);

            if ($response->getStatusCode() < 400) {
                return $response;
            }

            return $this->normalizeErrorResponse($response);
        } catch (Throwable $e) {
            if ($this->shouldReport($e)) {
                $this->reportException($e);
            }

            return $this->handleException($e, $request);
        }
    }

    protected function normalizeErrorResponse(Response $response): Response
    {
        if (trim($response->getContent()) !== '') {
            return $response;
        }

        return $this->handleHttpError($response->getStatusCode());
    }

    protected function normalizeResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof ResponseInterface) {
            return new Response(
                (string) $response->getBody(),
                $response->getStatusCode(),
                $this->normalizeHeaders($response->getHeaders()),
            );
        }

        if (is_array($response) || $response instanceof JsonSerializable) {
            return Response::json($response);
        }

        if ($response === null) {
            return new Response();
        }
        if (is_string($response) || is_int($response) || is_float($response) || is_bool($response)) {
            return new Response((string) $response);
        }

        throw new \UnexpectedValueException('HTTP handler returned an unsupported response type.');
    }

    /** @param array<mixed, mixed> $headers
     *  @return array<string, mixed>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (is_string($name)) {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    protected function handleException(Throwable $e, Request $request): Response
    {
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e, $request);
        }

        if ($e instanceof HttpException && $this->expectsJson($request)) {
            return Response::json([
                'message' => $e->getMessage(),
                'status' => $e->getStatusCode(),
            ], $e->getStatusCode(), $e->getHeaders());
        }

        $status = 500;

        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();

            if (is_int($statusCode)) {
                $status = $statusCode;
            }
        }

        $message = $e->getMessage() ?: 'Internal Server Error';

        try {
            $handler = $this->app->make(\Codemonster\Errors\Contracts\ExceptionHandlerInterface::class);

            $response = $handler->handle($e);

            if ($e instanceof HttpException) {
                $response->setHeaders($e->getHeaders());
            }

            return $response;
        } catch (Throwable $inner) {
            return $this->handleHttpError($status, $message);
        }
    }

    protected function handleValidationException(ValidationException $e, Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return Response::json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->getStatusCode());
        }

        $this->flashValidationState($e, $request);

        $target = $this->validationRedirectTarget($request);

        if ($target !== null) {
            return Response::redirect($target);
        }

        return $this->handleHttpError($e->getStatusCode(), $e->getMessage());
    }

    protected function expectsJson(Request $request): bool
    {
        $requestedWith = $request->header('X-Requested-With', '');
        $contentType = $request->header('Content-Type', '');

        return $request->wantsJson()
            || (is_string($requestedWith) && strcasecmp($requestedWith, 'XMLHttpRequest') === 0)
            || (is_string($contentType) && str_contains(strtolower($contentType), 'application/json'));
    }

    protected function flashValidationState(ValidationException $e, Request $request): void
    {
        try {
            $session = $this->app->make('session');

            $flash = is_object($session) ? [$session, 'flash'] : null;

            if (is_callable($flash)) {
                $flash('errors', $e->errors());
                $input = $request->input();
                $flash('_old_input', $this->filterSensitiveInput(is_array($input) ? $input : []));
            }
        } catch (Throwable $sessionError) {
            // A validation response must still be renderable without sessions.
        }
    }

    protected function validationRedirectTarget(Request $request): ?string
    {
        $referer = $request->header('Referer');

        if (!is_string($referer) || $referer === '' || preg_match('/[\x00-\x1F\x7F\\\\]/', $referer)) {
            return null;
        }

        if (str_starts_with($referer, '/') && !str_starts_with($referer, '//')) {
            return $referer;
        }

        $parts = parse_url($referer);

        if (!is_array($parts) || !isset($parts['host'])) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $refererScheme = strtolower((string) ($parts['scheme'] ?? ''));
        $requestScheme = strtolower($request->scheme());
        $refererHost = strtolower((string) $parts['host']);
        $requestHost = parse_url("{$requestScheme}://{$request->host()}", PHP_URL_HOST);
        $requestPort = parse_url("{$requestScheme}://{$request->host()}", PHP_URL_PORT);
        $refererPort = $parts['port'] ?? ($refererScheme === 'https' ? 443 : 80);
        $requestPort ??= $requestScheme === 'https' ? 443 : 80;

        if (
            $refererScheme !== $requestScheme
            || $refererHost !== strtolower((string) $requestHost)
            || $refererPort !== $requestPort
        ) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $target = str_starts_with($path, '/') ? $path : '/' . $path;

        if (isset($parts['query']) && $parts['query'] !== '') {
            $target .= '?' . $parts['query'];
        }

        return $target;
    }

    /**
     * @param array<string|int, mixed> $input
     * @return array<string|int, mixed>
     */
    protected function filterSensitiveInput(array $input): array
    {
        $configured = config('validation.sensitive_fields', [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
            'secret',
            'api_key',
        ]);
        $sensitive = is_array($configured)
            ? array_values(array_map('strtolower', array_filter($configured, 'is_string')))
            : [];

        return $this->removeSensitiveInput($input, $sensitive);
    }

    /**
     * @param list<string> $sensitive
     * @param array<string|int, mixed> $input
     * @return array<string|int, mixed>
     */
    protected function removeSensitiveInput(array $input, array $sensitive): array
    {
        foreach ($input as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : (string) $key;
            if (in_array($normalizedKey, $sensitive, true)) {
                unset($input[$key]);

                continue;
            }

            if (is_array($value)) {
                $input[$key] = $this->removeSensitiveInput($value, $sensitive);
            }
        }

        return $input;
    }

    protected function shouldReport(Throwable $e): bool
    {
        return !$e instanceof ValidationException
            && (!$e instanceof HttpException || $e->getStatusCode() >= 500);
    }

    protected function reportException(Throwable $e): void
    {
        try {
            $logger = $this->app->make(LoggerInterface::class);
            $logger->error($e->getMessage(), [
                'exception' => $e,
                'class' => $e::class,
            ]);
        } catch (Throwable $loggingError) {
            // Reporting must never change the HTTP response path.
        }
    }

    protected function dispatch(Request $request): mixed
    {
        $route = $this->router->dispatch($request->method(), $request->uri());

        if (!$route) {
            return $this->handleHttpError(404, 'Page not found');
        }

        return $this->runRoute($route, $request);
    }

    protected function handleHttpError(int $status, string $message = ''): Response
    {
        if ($status < 400) {
            return new Response('', $status);
        }

        try {
            $handler = $this->app->make(ExceptionHandlerInterface::class);

            $e = $this->httpException($status, $message);
            $response = $handler->handle($e);
            $response->setHeaders($e->getHeaders());

            return $response;
        } catch (Throwable $inner) {
            return new Response(
                sprintf('HTTP %d: %s', $status, $inner->getMessage()),
                $status,
                ['Content-Type' => 'text/plain'],
            );
        }
    }

    protected function httpException(int $status, string $message = ''): HttpException
    {
        return match ($status) {
            400 => new BadRequestHttpException($message ?: 'Bad Request'),
            401 => new UnauthorizedHttpException($message ?: 'Unauthorized'),
            403 => new ForbiddenHttpException($message ?: 'Forbidden'),
            404 => new NotFoundHttpException($message ?: 'Not Found'),
            405 => new MethodNotAllowedHttpException([], $message ?: 'Method Not Allowed'),
            default => new HttpException($status, $message),
        };
    }

    public function addMiddleware(callable|MiddlewareInterface|string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function aliasMiddleware(string $alias, string $middleware): void
    {
        if ($alias === '' || $middleware === '') {
            throw new \InvalidArgumentException('Middleware alias and class cannot be empty.');
        }

        $this->middlewareAliases[$alias] = $middleware;
    }

    /**
     * @param list<string|array{0: string, 1?: mixed}> $middleware
     */
    public function middlewareGroup(string $group, array $middleware): void
    {
        if ($group === '') {
            throw new \InvalidArgumentException('Middleware group name cannot be empty.');
        }

        $this->middlewareGroups[$group] = $middleware;
    }

    /** @param string|array{0: string, 1?: mixed} $middleware */
    public function appendMiddlewareToGroup(string $group, string|array $middleware): void
    {
        if ($group === '') {
            throw new \InvalidArgumentException('Middleware group name cannot be empty.');
        }

        if (is_array($middleware) && !is_string($middleware[0] ?? null)) {
            throw new \InvalidArgumentException('Invalid middleware group entry.');
        }

        $this->middlewareGroups[$group] ??= [];
        $this->middlewareGroups[$group][] = $middleware;
    }

    /**
     * @return array<string, string>
     */
    public function getMiddlewareAliases(): array
    {
        return $this->middlewareAliases;
    }

    /**
     * @return array<string, list<string|array{0: string, 1?: mixed}>>
     */
    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    protected function runMiddleware(Request $request, callable $next): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, callable|MiddlewareInterface|string $middleware): callable =>
                fn (Request $req) => $this->runSingleMiddleware($middleware, $req, $next),
            $next,
        );

        return $pipeline($request);
    }

    protected function runSingleMiddleware(callable|MiddlewareInterface|string $middleware, Request $request, callable $next): mixed
    {
        if (is_string($middleware)) {
            $middleware = $this->app->make($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $this->requestHandler($next));
        }

        if (is_callable($middleware)) {
            return $middleware($request, $next, $this->app);
        }

        throw new \RuntimeException('Invalid middleware definition.');
    }

    protected function requestHandler(callable $next): RequestHandlerInterface
    {
        return new class ($next) implements RequestHandlerInterface {
            private \Closure $next;

            public function __construct(callable $next)
            {
                $this->next = \Closure::fromCallable($next);
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (!$request instanceof Request) {
                    throw new \InvalidArgumentException('Annabel middleware requires Codemonster HTTP requests.');
                }

                $response = ($this->next)($request);

                if (!$response instanceof ResponseInterface) {
                    if ($response === null) {
                        return new Response();
                    }
                    if (is_string($response) || is_int($response) || is_float($response) || is_bool($response)) {
                        return new Response((string) $response);
                    }

                    throw new \UnexpectedValueException('Middleware returned an unsupported response type.');
                }

                return $response;
            }
        };
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    protected function runRoute(Route $route, Request $request): mixed
    {
        $handler = $route->handler;
        $routeParameters = $route->parameters();
        $request = $request->withAttribute('route.parameters', $routeParameters);
        $middlewareList = $this->normalizeMiddlewareList($route->getMiddleware());

        $kernel = $this;

        $core = function (Request $req) use ($handler, $kernel, $routeParameters) {

            if (is_array($handler)) {
                $class = $handler[0] ?? null;
                $method = $handler[1] ?? null;

                if (!is_string($class) || !is_string($method)) {
                    return $kernel->handleHttpError(500, 'Invalid route handler definition');
                }

                $controller = $kernel->app->make($class);
                if (!is_object($controller)) {
                    return $kernel->handleHttpError(
                        500,
                        sprintf('Handler controller [%s] did not resolve to an object', $class),
                    );
                }

                $callable = [$controller, $method];

                if (!is_callable($callable)) {
                    return $kernel->handleHttpError(
                        500,
                        sprintf('Handler [%s@%s] not found', $class, $method),
                    );
                }

                $ref = new \ReflectionMethod($controller, $method);

                return $callable(...$kernel->routeHandlerArguments($ref, $req, $routeParameters));
            }

            if (is_callable($handler)) {
                $callable = \Closure::fromCallable($handler);
                $ref = new \ReflectionFunction($callable);

                return $callable(...$kernel->routeHandlerArguments($ref, $req, $routeParameters));
            }

            return $kernel->handleHttpError(500, 'Invalid route handler definition');
        };

        $pipeline = array_reduce(
            array_reverse($middlewareList),
            function ($next, $middleware) use ($kernel) {

                return function (Request $req) use ($middleware, $next, $kernel) {
                    $class = $middleware[0] ?? null;
                    $role = $middleware[1] ?? null;

                    if (!is_string($class)) {
                        return $next($req);
                    }

                    $instance = $kernel->app->make($class);

                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->process($req, $kernel->requestHandler($next));
                    }

                    $handler = [$instance, 'handle'];
                    if (!is_callable($handler)) {
                        throw new \RuntimeException("Middleware [{$class}] must be callable or define handle().");
                    }

                    return $handler($req, $next, $role);
                };
            },
            $core,
        );

        return $pipeline($request);
    }

    /**
     * @param array<string, string> $routeParameters
     * @return list<mixed>
     */
    protected function routeHandlerArguments(
        \ReflectionFunctionAbstract $reflection,
        Request $request,
        array $routeParameters,
    ): array {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if ($typeName === Request::class || is_a(Request::class, $typeName, true)) {
                    $arguments[] = $request;

                    continue;
                }

                $arguments[] = $this->app->make($typeName);

                continue;
            }

            $name = $parameter->getName();
            if (array_key_exists($name, $routeParameters)) {
                $arguments[] = $routeParameters[$name];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new \RuntimeException("Unable to resolve route parameter [{$name}].");
        }

        return $arguments;
    }

    /**
     * @param array<mixed> $middlewareList
     * @return list<array{0: string, 1: mixed}>
     */
    protected function normalizeMiddlewareList(array $middlewareList): array
    {
        $normalized = [];

        foreach ($middlewareList as $middleware) {
            if (is_string($middleware)) {
                array_push($normalized, ...$this->resolveMiddlewareString($middleware));

                continue;
            }

            if (!is_array($middleware) || $middleware === []) {
                $this->warnInvalidMiddleware($middleware);

                continue;
            }

            if (count($middleware) == 1 && is_array($middleware[0])) {
                $middleware = $middleware[0];
            }

            if ($this->isMiddlewareGroup($middleware)) {
                foreach ($middleware as $item) {
                    if (is_string($item)) {
                        array_push($normalized, ...$this->resolveMiddlewareString($item));
                    } elseif (is_array($item) && is_string($item[0] ?? null)) {
                        array_push($normalized, ...$this->resolveMiddlewareArray($item));
                    }
                }

                continue;
            }

            if (is_string($middleware[0] ?? null)) {
                array_push($normalized, ...$this->resolveMiddlewareArray($middleware));
            } else {
                $this->warnInvalidMiddleware($middleware);
            }
        }

        return $normalized;
    }

    /**
     * @return list<array{0: string, 1: mixed}>
     */
    protected function resolveMiddlewareString(string $middleware): array
    {
        if (isset($this->middlewareGroups[$middleware])) {
            return $this->normalizeMiddlewareList($this->middlewareGroups[$middleware]);
        }

        [$name, $argument] = $this->parseMiddlewareString($middleware);
        $class = $this->middlewareAliases[$name] ?? $name;

        return [[$class, $argument]];
    }

    /**
     * @param array<mixed> $middleware
     * @return list<array{0: string, 1: mixed}>
     */
    protected function resolveMiddlewareArray(array $middleware): array
    {
        $name = $middleware[0] ?? null;

        if (!is_string($name)) {
            return [];
        }

        if (count($middleware) === 1 && isset($this->middlewareGroups[$name])) {
            return $this->normalizeMiddlewareList($this->middlewareGroups[$name]);
        }

        [$parsedName, $parsedArgument] = $this->parseMiddlewareString($name);
        $class = $this->middlewareAliases[$parsedName] ?? $parsedName;

        return [[$class, $middleware[1] ?? $parsedArgument]];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    protected function parseMiddlewareString(string $middleware): array
    {
        if (!str_contains($middleware, ':')) {
            return [$middleware, null];
        }

        [$name, $arguments] = explode(':', $middleware, 2);

        return [$name, $arguments];
    }

    /**
     * @param array<mixed> $middleware
     */
    protected function isMiddlewareGroup(array $middleware): bool
    {
        $count = count($middleware);

        if ($count < 2) {
            return false;
        }

        foreach ($middleware as $item) {
            if (!is_string($item)) {
                return false;
            }
        }

        if ($count > 2) {
            return true;
        }

        $first = $middleware[0];
        $second = $middleware[1];

        return is_string($first)
            && is_string($second)
            && class_exists($first)
            && class_exists($second);
    }

    protected function warnInvalidMiddleware(mixed $middleware): void
    {
        if (!ini_get('display_errors')) {
            return;
        }

        $type = get_debug_type($middleware);
        $encoded = is_array($middleware) ? json_encode($middleware) : false;
        $detail = is_string($encoded) ? $encoded : $type;

        trigger_error("Invalid middleware definition: {$detail}", E_USER_WARNING);
    }
}
