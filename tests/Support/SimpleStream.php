<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Psr\Http\Message\StreamInterface;

final class SimpleStream implements StreamInterface
{
    private int $pos = 0;

    public function __construct(private string $contents) {}

    public function __toString(): string { return $this->contents; }
    public function close(): void {}
    public function detach() { return null; }
    public function getSize(): ?int { return strlen($this->contents); }
    public function tell(): int { return $this->pos; }
    public function eof(): bool { return $this->pos >= strlen($this->contents); }
    public function isSeekable(): bool { return true; }
    public function seek($offset, $whence = SEEK_SET): void
    {
        $len = strlen($this->contents);
        if ($whence === SEEK_SET) $this->pos = max(0, min($len, (int)$offset));
        if ($whence === SEEK_CUR) $this->pos = max(0, min($len, $this->pos + (int)$offset));
        if ($whence === SEEK_END) $this->pos = max(0, min($len, $len + (int)$offset));
    }
    public function rewind(): void { $this->pos = 0; }
    public function isWritable(): bool { return false; }
    public function write($string): int { throw new \BadMethodCallException(); }
    public function isReadable(): bool { return true; }
    public function read($length): string
    {
        $chunk = substr($this->contents, $this->pos, (int)$length);
        $this->pos += strlen($chunk);
        return $chunk;
    }
    public function getContents(): string
    {
        if ($this->eof()) return '';
        $chunk = substr($this->contents, $this->pos);
        $this->pos = strlen($this->contents);
        return $chunk;
    }
    public function getMetadata($key = null): mixed { return null; }
}
