/**
 * Canonical helper functions and styling for Vehicle Operational Statuses,
 * Verification Lifecycle Statuses, and Vehicle Document Statuses.
 */

export type VehicleOperationalStatus = 'active' | 'inactive' | 'suspended';

export type VehicleVerificationStatus =
    | 'draft'
    | 'for_verification'
    | 'pending'
    | 'needs_revision'
    | 'verified';

export type VehicleDocumentStatus =
    | 'pending'
    | 'verified'
    | 'approved'
    | 'invalid'
    | 'rejected'
    | 'expired';

export function humanize(text?: string | null): string {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

/**
 * Operational Status (vehicles.status): 'active' | 'inactive' | 'suspended'
 */
export function operationalStatusLabel(status?: string | null): string {
    switch (status) {
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        case 'suspended':
            return 'Suspended';
        default:
            return humanize(status);
    }
}

export function operationalStatusClass(status?: string | null): string {
    switch (status) {
        case 'active':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800';
        case 'suspended':
            return 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:border-orange-800';
        case 'inactive':
            return 'bg-rose-100 text-rose-600 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
}

export function operationalStatusDot(status?: string | null): string {
    switch (status) {
        case 'active':
            return 'bg-emerald-500';
        case 'suspended':
            return 'bg-orange-500';
        case 'inactive':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

/**
 * Verification Status (vehicles.verification_status):
 * 'draft' | 'for_verification' | 'pending' | 'needs_revision' | 'verified'
 */
export function verificationStatusLabel(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'Verified';
        case 'for_verification':
            return 'For Verification';
        case 'pending':
            return 'Pending Review';
        case 'needs_revision':
            return 'Needs Revision';
        case 'draft':
            return 'Draft';
        default:
            return humanize(status);
    }
}

export function verificationStatusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200 dark:bg-violet-950/50 dark:text-violet-300 dark:border-violet-800';
        case 'pending':
        case 'draft':
            return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800';
        case 'needs_revision':
        case 'invalid':
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
}

export function verificationStatusDot(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'pending':
        case 'draft':
            return 'bg-amber-500';
        case 'needs_revision':
        case 'invalid':
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

/**
 * Vehicle Document Status (vehicle_documents.status):
 * 'pending' | 'verified' | 'approved' | 'invalid' | 'rejected' | 'expired'
 */
export function vehicleDocumentStatusLabel(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'approved':
            return 'Verified';
        case 'pending':
            return 'Pending';
        case 'invalid':
        case 'rejected':
            return 'Invalid';
        case 'expired':
            return 'Expired';
        default:
            return humanize(status);
    }
}

export function vehicleDocumentStatusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'approved':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800';
        case 'invalid':
        case 'rejected':
        case 'expired':
            return 'bg-rose-100 text-rose-600 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800';
        default:
            return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
}

export function vehicleDocumentStatusDot(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'approved':
            return 'bg-emerald-500';
        case 'pending':
            return 'bg-amber-500';
        case 'invalid':
        case 'rejected':
        case 'expired':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}
