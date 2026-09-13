/**
 * Company-document status -> Tailwind class maps.
 *
 * Kept separate from any app-wide `statusClass`: the company-document status
 * vocabulary (verified / for_verification / docs_completed / pending / invalid /
 * expired / rejected) is not the same as the vehicle / gate / employee ones.
 */

/** Badge classes (background + text + border) for a company-document status. */
export function docStatusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'for_verification':
        case 'docs_completed':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'expired':
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

/** Status-dot color for a company-document status. */
export function docStatusDot(status?: string | null): string {
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
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}
