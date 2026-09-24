export function dispatchStatusClass(status?: string | null): string {
    if (status === 'arrived') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending') return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

export function dispatchStatusDot(status?: string | null): string {
    if (status === 'arrived') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-400';
    return 'bg-slate-400';
}

export function dispatchStatusLabel(status?: string | null): string {
    if (!status) return 'Unknown';
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
