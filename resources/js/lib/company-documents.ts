import { isExpired } from '@/lib/format';

/**
 * Company-document status -> Canonical status, label, and Tailwind class maps.
 *
 * Precedence rules:
 * 1. Expired (status is 'expired' or expires_at is in the past)
 * 2. Has Issue (status is 'invalid', 'rejected', 'has_issue', or has remarks when not verified)
 * 3. Verified (status is 'verified' and neither expired nor having unresolved issues)
 * 4. Pending, rejected, or another existing status
 */

export type DocumentLike = {
    status?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
};

export type CanonicalDocStatus =
    | 'expired'
    | 'has_issue'
    | 'verified'
    | 'pending'
    | 'rejected'
    | 'for_verification'
    | 'docs_completed'
    | string;

/**
 * Returns the canonical document status based on backend rules and precedence.
 */
export function getCanonicalDocStatus(docOrStatus?: DocumentLike | string | null): CanonicalDocStatus {
    if (!docOrStatus) return 'pending';

    if (typeof docOrStatus === 'string') {
        if (docOrStatus === 'expired') return 'expired';
        if (docOrStatus === 'invalid' || docOrStatus === 'has_issue' || docOrStatus === 'rejected') {
            return 'has_issue';
        }
        return docOrStatus;
    }

    const doc = docOrStatus;

    // 1. Expired (Precedence #1)
    if (doc.status === 'expired' || (Boolean(doc.expires_at) && isExpired(doc.expires_at))) {
        return 'expired';
    }

    // 2. Has Issue (Precedence #2)
    if (
        doc.status === 'invalid' ||
        doc.status === 'rejected' ||
        doc.status === 'has_issue' ||
        (Boolean(doc.remarks && doc.remarks.trim() !== '') && doc.status !== 'verified')
    ) {
        return 'has_issue';
    }

    // 3. Verified (Precedence #3)
    if (doc.status === 'verified') {
        return 'verified';
    }

    // 4. Pending, rejected, or another existing status (Precedence #4)
    return doc.status || 'pending';
}

/**
 * Human-readable badge label for a canonical status or document.
 */
export function docStatusLabel(docOrStatus?: DocumentLike | string | null): string {
    const status = getCanonicalDocStatus(docOrStatus);

    switch (status) {
        case 'expired':
            return 'Expired';
        case 'has_issue':
        case 'invalid':
            return 'Has Issue';
        case 'verified':
            return 'Verified';
        case 'pending':
            return 'Pending';
        case 'for_verification':
            return 'For Verification';
        case 'docs_completed':
            return 'Docs Completed';
        case 'rejected':
            return 'Has Issue';
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
        case 'has_issue':
        case 'invalid':
        case 'expired':
        case 'rejected':
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
        case 'has_issue':
        case 'invalid':
        case 'expired':
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

/**
 * Returns whether a document is genuinely verified (verified status, not expired, no unresolved issues).
 */
export function isDocVerified(doc?: DocumentLike | null): boolean {
    if (!doc) return false;
    return getCanonicalDocStatus(doc) === 'verified';
}
