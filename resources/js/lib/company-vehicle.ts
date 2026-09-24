/**
 * Business rules for an operator's vehicle: when its documents block editing or activation. Shared by
 * the vehicle list and the vehicle page so both gate the same actions the same way.
 */
type RuleDocument = {
    status: string;
    expires_at?: string | null;
};

type RuleVehicle = {
    status: string;
    documents?: RuleDocument[];
};

const PENDING_STATUSES = ['pending', 'rejected', 'for_verification', 'draft'];

export function isDocExpired(doc?: RuleDocument): boolean {
    if (!doc) return false;
    if (doc.status === 'expired') return true;
    if (!doc.expires_at) return false;

    return new Date(doc.expires_at) < new Date();
}

export function needsResubmission(doc?: RuleDocument): boolean {
    if (!doc) return false;

    return doc.status === 'invalid' || doc.status === 'needs_revision' || isDocExpired(doc);
}

export function isSuspended(status?: string | null): boolean {
    return status === 'suspended';
}

export function hasDocuments(vehicle: RuleVehicle): boolean {
    return !!vehicle.documents?.length;
}

export function hasPendingOrRejected(vehicle: RuleVehicle): boolean {
    return vehicle.documents?.some((doc) => PENDING_STATUSES.includes(doc.status)) ?? false;
}

export function hasDocsNeedingResubmission(vehicle: RuleVehicle): boolean {
    return vehicle.documents?.some((doc) => needsResubmission(doc)) ?? false;
}

export function businessCanEdit(vehicle: RuleVehicle): boolean {
    return !isSuspended(vehicle.status) && hasDocsNeedingResubmission(vehicle);
}

export function businessCanActivate(vehicle: RuleVehicle): boolean {
    if (isSuspended(vehicle.status)) return false;
    if (!hasDocuments(vehicle)) return false;
    if (hasPendingOrRejected(vehicle)) return false;
    if (hasDocsNeedingResubmission(vehicle)) return false;

    return true;
}

export function businessCanToggle(vehicle: RuleVehicle): boolean {
    if (vehicle.status === 'active') return !isSuspended(vehicle.status);
    if (vehicle.status === 'inactive') return businessCanActivate(vehicle);

    return false;
}

export function toggleLabel(status?: string | null): string {
    return status === 'active' ? 'Inactivate' : 'Activate';
}

export function firstBlockingReason(vehicle: RuleVehicle): string {
    if (isSuspended(vehicle.status)) return 'Suspended vehicles cannot change status.';
    if (!hasDocuments(vehicle)) return 'Upload required documents first.';
    if (hasPendingOrRejected(vehicle)) return 'Documents must be verified before activation.';
    if (hasDocsNeedingResubmission(vehicle)) return 'Resubmit invalid or expired documents before activation.';

    return '';
}

export function vehicleActionNote(vehicle: RuleVehicle): string {
    if (!businessCanEdit(vehicle) && !isSuspended(vehicle.status) && !hasDocsNeedingResubmission(vehicle)) {
        return 'No invalid or expired documents available for resubmission.';
    }

    return firstBlockingReason(vehicle);
}
