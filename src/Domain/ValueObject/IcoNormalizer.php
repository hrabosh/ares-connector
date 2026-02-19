<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Domain\ValueObject;

/**
 * Normalizes common user input forms into canonical 8-digit IČO.
 *
 * ARES UI examples often accept "6947" for "00006947". The API commonly works with
 * 8 digits. This helper makes the client more forgiving while still producing a strict IČO.
 */
final class IcoNormalizer
{
    public static function normalize(string $input): string
    {
        $s = preg_replace('/\s+/', '', $input) ?? '';
        $s = trim($s);

        // keep digits only
        $digits = preg_replace('/\D+/', '', $s) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) > 8) {
            // do not truncate silently
            return $digits;
        }

        return str_pad($digits, 8, '0', STR_PAD_LEFT);
    }
}
