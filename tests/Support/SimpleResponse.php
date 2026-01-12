<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class SimpleResponse implements ResponseInterface
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly StreamInterface $body,
        private readonly string $protocolVersion = '1.1',
        private readonly string $reasonPhrase = '',
    ) {}

    public function getProtocolVersion(): string { return $this->protocolVersion; }
    public function withProtocolVersion($version): ResponseInterface { throw new \BadMethodCallException(); }
    public function getHeaders(): array { return $this->headers; }
    public function hasHeader($name): bool { return isset($this->headers[$name]); }
    public function getHeader($name): array { return $this->headers[$name] ?? []; }
    public function getHeaderLine($name): string { return implode(',', $this->getHeader($name)); }
    public function withHeader($name, $value): ResponseInterface { throw new \BadMethodCallException(); }
    public function withAddedHeader($name, $value): ResponseInterface { throw new \BadMethodCallException(); }
    public function withoutHeader($name): ResponseInterface { throw new \BadMethodCallException(); }
    public function getBody(): StreamInterface { return $this->body; }
    public function withBody(StreamInterface $body): ResponseInterface { throw new \BadMethodCallException(); }
    public function getStatusCode(): int { return $this->statusCode; }
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface { throw new \BadMethodCallException(); }
    public function getReasonPhrase(): string { return $this->reasonPhrase; }
}
