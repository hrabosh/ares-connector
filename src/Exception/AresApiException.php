<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Exception;

final class AresApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?string $errorCode = null,
        public readonly ?string $subCode = null,
        public readonly ?string $errorDescription = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
