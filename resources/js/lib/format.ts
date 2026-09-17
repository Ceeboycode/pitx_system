/**
 * Pure, framework-agnostic formatting helpers.
 *
 * Scoped for the Company screens for now; other pages still carry their own
 * copies and can migrate here in a later sweep.
 */

/** Turn a snake_case / lower-case token into Title Case ("for_verification" -> "For Verification"). */
export function humanize(text?: string | null): string {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

/** Locale date, e.g. "Sep 3, 2026". Returns "—" for empty / unparseable input. */
export function formatDate(date?: string | null): string {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/** Locale date + time, e.g. "09/03/2026, 02:15 PM". Returns "—" for empty / unparseable input. */
export function formatDateTime(date?: string | null): string {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

/** True when the given date is in the past (before today). */
export function isExpired(expiresAt?: string | null): boolean {
    if (!expiresAt) return false;
    if (/^\d{4}-\d{2}-\d{2}$/.test(expiresAt)) {
        const today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const todayStr = `${y}-${m}-${d}`;
        return expiresAt < todayStr;
    }
    const d = new Date(expiresAt);
    if (Number.isNaN(d.getTime())) return false;
    return d.getTime() < Date.now();
}

/** Distance from meters, e.g. "820 m" / "3.45 km". Returns "—" for 0 / nullish input. */
export function fmtDistance(meters?: number | null): string {
    if (!meters) return '—';
    if (meters < 1000) return `${Math.round(meters)} m`;
    return `${(meters / 1000).toFixed(2)} km`;
}

/** Duration from seconds, e.g. "45 min" / "1 hr 20 min". Returns "—" for 0 / nullish input. */
export function fmtDuration(seconds?: number | null): string {
    if (!seconds) return '—';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.ceil((seconds % 3600) / 60);
    if (hours > 0) return `${hours} hr ${minutes} min`;
    return `${Math.ceil(seconds / 60)} min`;
}
