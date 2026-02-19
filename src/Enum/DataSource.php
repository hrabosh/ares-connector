<?php

declare(strict_types=1);

namespace Lustrace\Ares2\Enum;

/**
 * ARES2 exposes multiple "public services" (datasets) as separate endpoints.
 *
 * IMPORTANT:
 * - Not every economic subject exists in every dataset.
 * - Some datasets may require authentication / authorization (401/403),
 *   so callers should handle partial availability.
 *
 * Source list is based on the official "Katalog veřejných služeb" (MFČR).
 */
enum DataSource: string
{
    /** Core ARES "EkonomickeSubjekty" service */
    case CORE = 'ekonomicke-subjekty';

    /** RES */
    case RES = 'ekonomicke-subjekty-res';

    /** Veřejný rejstřík (VR) */
    case VR  = 'ekonomicke-subjekty-vr';

    /** Živnostenský rejstřík (RŽP) */
    case RZP = 'ekonomicke-subjekty-rzp';

    /** Registr církví a náboženských společností (RCNS) */
    case RCNS = 'ekonomicke-subjekty-rcns';

    /** Registr osob (ROS) */
    case ROS = 'ekonomicke-subjekty-ros';

    /** Rejstřík politických stran a hnutí (RPSH) */
    case RPSH = 'ekonomicke-subjekty-rpsh';

    /** Centrální evidence úpadců (CEÚ) */
    case CEU = 'ekonomicke-subjekty-ceu';

    /** Rejstřík škol (RŠ) */
    case RS = 'ekonomicke-subjekty-rs';

    /** Státní zemědělský registr (SZR) */
    case SZR = 'ekonomicke-subjekty-szr';

    /** Národní registr poskytovatelů zdravotních služeb (NRPZS) */
    case NRPZS = 'ekonomicke-subjekty-nrpzs';

    /**
     * Fetch subject detail by IČO from a specific dataset.
     */
    public function detailPath(string $ico): string
    {
        $ico = trim($ico);
        return sprintf('%s/%s', $this->value, $ico);
    }

    /**
     * Search endpoint path (POST).
     *
     * Note: most non-CORE datasets typically only support "registrace" (IČO list)
     * search filters, while CORE supports a richer filter (name + address, etc.).
     */
    public function searchPath(): string
    {
        return sprintf('%s/vyhledat', $this->value);
    }

    /**
     * Whether this source is part of the "Ekonomické subjekty" family (IČO-based).
     */
    public function isEconomicSubjectSource(): bool
    {
        return true;
    }
}
