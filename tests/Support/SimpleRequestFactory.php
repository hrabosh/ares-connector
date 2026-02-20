<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

final class SimpleRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new SimpleRequest($method, (string) $uri);
    }
}

final class SimpleRequest implements RequestInterface
{
    /** @var array<string, list<string>> */
    private array $headers = [];
    private ?StreamInterface $body = null;

    public function __construct(private string $method, private string $uri) {}

    public function getRequestTarget(): string { return $this->uri; }
    public function withRequestTarget($requestTarget): RequestInterface { throw new \BadMethodCallException(); }
    public function getMethod(): string { return $this->method; }
    public function withMethod($method): RequestInterface { throw new \BadMethodCallException(); }
    public function getUri() { return $this->uri; }
    public function withUri(\Psr\Http\Message\UriInterface $uri, $preserveHost = false): RequestInterface { throw new \BadMethodCallException(); }
    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion($version): RequestInterface { throw new \BadMethodCallException(); }
    public function getHeaders(): array { return $this->headers; }
    public function hasHeader($name): bool { return isset($this->headers[$name]); }
    public function getHeader($name): array { return $this->headers[$name] ?? []; }
    public function getHeaderLine($name): string { return implode(',', $this->getHeader($name)); }
    public function withHeader($name, $value): RequestInterface
    {
        $vals = is_array($value) ? array_map('strval', $value) : [strval($value)];
        $this->headers[$name] = $vals;
        return $this;
    }
    public function withAddedHeader($name, $value): RequestInterface
    {
        $vals = is_array($value) ? array_map('strval', $value) : [strval($value)];
        $this->headers[$name] = array_merge($this->headers[$name] ?? [], $vals);
        return $this;
    }
    public function withoutHeader($name): RequestInterface { throw new \BadMethodCallException(); }
    public function getBody(): StreamInterface { return $this->body ?? new SimpleStream(''); }
    public function withBody(StreamInterface $body): RequestInterface { $this->body = $body; return $this; }
}
