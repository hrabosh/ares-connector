<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param array<string, ResponseInterface|callable(RequestInterface):ResponseInterface> $routes
     * @param list<ResponseInterface|callable(RequestInterface):ResponseInterface> $queue
     */
    public function __construct(
        private array $routes = [],
        private array $queue = [],
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $key = $request->getMethod() . ' ' . $request->getUri()->getPath();

        if (array_key_exists($key, $this->routes)) {
            $handler = $this->routes[$key];
            if (is_callable($handler)) {
                return $handler($request);
            }
            return $handler;
        }

        if ($this->queue !== []) {
            $next = array_shift($this->queue);
            if (is_callable($next)) {
                return $next($request);
            }
            return $next;
        }

        throw new \RuntimeException('No fake response configured for ' . $key);
    }
}
