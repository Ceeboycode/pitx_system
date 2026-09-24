/**
 * Company-document status -> Canonical status, label, and Tailwind class maps.
 *
 * Supported database statuses (from company_documents table):
 * - pending
 * - invalid
 * - expired
 * - verified
 */

export type DocumentLike = {
    status?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
};

export type CanonicalDocStatus =
    | 'pending'
    | 'invalid'
    | 'expired'
    | 'verified'
    | 'for_verification'
    | 'docs_completed'
    | string;

/**
 * Returns the canonical document status reflecting the actual database value.
 */
export function getCanonicalDocStatus(docOrStatus?: DocumentLike | string | null): CanonicalDocStatus {
    if (!docOrStatus) return 'pending';

    const rawStatus = typeof docOrStatus === 'string' ? docOrStatus : docOrStatus.status;

    if (!rawStatus) return 'pending';

    const normalized = rawStatus.toLowerCase().trim();

    if (normalized === 'pending') return 'pending';
    if (normalized === 'invalid' || normalized === 'rejected') return 'invalid';
    if (normalized === 'expired') return 'expired';
    if (normalized === 'verified') return 'verified';

    return normalized;
}

/**
 * Human-readable badge label for a canonical status or document.
 */
export function docStatusLabel(docOrStatus?: DocumentLike | string | null): string {
    const status = getCanonicalDocStatus(docOrStatus);

    switch (status) {
        case 'pending':
            return 'Pending';
        case 'invalid':
            return 'Invalid';
        case 'expired':
            return 'Expired';
        case 'verified':
            return 'Verified';
        case 'for_verification':
            return 'For Verification';
        case 'docs_completed':
            return 'Docs Completed';
        default:
            return status ? status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : 'Pending';
    }
}

/**
 * Badge classes (background + text + border) with light and dark mode support.
 */
export function docStatusClass(docOrStatus?: DocumentLike | string | null): string {
    const status = getCanonicalDocStatus(docOrStatus);

    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800';
        case 'for_verification':
        case 'docs_completed':
            return 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-950/40 dark:text-violet-400 dark:border-violet-800';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800';
        case 'invalid':
        case 'expired':
            return 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
}

/**
 * Status-dot color for a company-document status.
 */
export function docStatusDot(docOrStatus?: DocumentLike | string | null): string {
    const status = getCanonicalDocStatus(docOrStatus);

    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'for_verification':
        case 'docs_completed':
            return 'bg-violet-500';
        case 'pending':
            return 'bg-amber-500';
        case 'invalid':
        case 'expired':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

/**
 * Returns whether a document is genuinely verified (verified status).
 */
export function isDocVerified(doc?: DocumentLike | null): boolean {
    if (!doc) return false;
    return getCanonicalDocStatus(doc) === 'verified';
}
