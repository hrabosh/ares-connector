<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Tests\Support;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class SimpleStreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new SimpleStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new \RuntimeException('Cannot read file: ' . $filename);
        }
        return new SimpleStream($content);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        $content = stream_get_contents($resource);
        if ($content === false) {
            $content = '';
        }
        return new SimpleStream($content);
    }
}
