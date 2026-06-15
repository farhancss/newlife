// Common query-string keys carriers/retailers use for the tracking number.
// Compared case-insensitively. Mirrors App\Services\TrackingNumberExtractor.
const QUERY_KEYS = [
    'trknbr',
    'tracknum',
    'tracknumbers',
    'tracking_numbers',
    'tlabels',
    'qtc_tlabels1',
    'tracking-id',
    'trackingid',
    'trackingnumber',
    'tracking_number',
    'tracking',
    'tn',
    'id',
];

const clean = (value) => {
    const first = String(value).trim().split(/[,\s]+/)[0] ?? '';

    return first.slice(0, 64);
};

const looksLikeTrackingNumber = (candidate) =>
    /^[A-Za-z0-9]{8,40}$/.test(candidate) && /\d/.test(candidate);

/**
 * Extract a tracking number from a carrier/retailer tracking URL. A raw
 * tracking number (not a URL) is returned cleaned/unchanged.
 *
 * @param {string} input
 * @returns {string}
 */
export const extractTrackingNumber = (input) => {
    const value = String(input ?? '').trim();

    if (value === '') {
        return '';
    }

    if (!value.includes('://') && !value.includes('?') && !value.includes('/')) {
        return clean(value);
    }

    let url;
    try {
        url = new URL(value);
    } catch {
        return clean(value);
    }

    for (const key of QUERY_KEYS) {
        for (const [paramKey, paramValue] of url.searchParams.entries()) {
            if (paramKey.toLowerCase() === key && paramValue.trim() !== '') {
                return clean(paramValue);
            }
        }
    }

    const segments = url.pathname
        .split('/')
        .filter((segment) => segment !== '')
        .reverse();

    for (const segment of segments) {
        const decoded = decodeURIComponent(segment);

        if (looksLikeTrackingNumber(decoded)) {
            return clean(decoded);
        }
    }

    return '';
};
