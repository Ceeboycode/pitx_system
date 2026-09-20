import type { Component } from 'vue';
import { RiCheckLine, RiCloseLine, RiRestartLine } from 'vue-remix-icons';

import { docStatusClass, docStatusDot, docStatusLabel } from '@/lib/company-documents';
import { fileUrl } from '@/lib/files';
import { humanize, isExpired } from '@/lib/format';
import {
    vehicleDocumentStatusClass,
    vehicleDocumentStatusDot,
    vehicleDocumentStatusLabel,
} from '@/lib/vehicle-status';
import { download as downloadCompanyDocument } from '@/routes/companies/documents';
import type { CompanyDocument } from '@/types/company';

/** What the Documents tables, preview card and viewer dialog need to show a document, whichever module it belongs to. */
export type PreviewDocument = {
    id: number;
    title: string;
    typeLabel: string;
    /** File to show inline (image or PDF). */
    url: string;
    mime?: string | null;
    fileName?: string | null;
    status?: { label: string; class: string; dot: string } | null;
    /** Vehicle documents flag a lapsed expiry date separately from their status. */
    expiredBadge?: boolean;
    issuedAt?: string | null;
    expiresAt?: string | null;
    uploadedAt?: string | null;
    uploadedBy?: string | null;
    verifiedAt?: string | null;
    verifiedBy?: string | null;
    remarks?: string | null;
    downloadUrl?: string | null;
};

export type PreviewAction = {
    key: 'verify' | 'unverify' | 'invalidate';
    label: string;
    icon: Component;
};

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_mime_type?: string | null;
    file_url?: string | null;
    status?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
    created_at?: string | null;
};

export function companyDocumentPreview(doc: CompanyDocument, companyId: number): PreviewDocument;
export function companyDocumentPreview(doc: CompanyDocument | null, companyId: number): PreviewDocument | null;
export function companyDocumentPreview(doc: CompanyDocument | null, companyId: number): PreviewDocument | null {
    if (!doc) return null;

    return {
        id: doc.id,
        title: doc.original_name ?? humanize(doc.doc_type),
        typeLabel: humanize(doc.doc_type),
        url: fileUrl(doc),
        mime: doc.mime_type,
        fileName: doc.original_name,
        status: { label: docStatusLabel(doc), class: docStatusClass(doc), dot: docStatusDot(doc) },
        issuedAt: doc.issued_at,
        expiresAt: doc.expires_at,
        uploadedAt: doc.created_at,
        uploadedBy: doc.uploader?.name,
        verifiedAt: doc.verified_at,
        verifiedBy: doc.verifier?.name,
        remarks: doc.remarks,
        downloadUrl: downloadCompanyDocument({ company: companyId, document: doc.id }).url,
    };
}

export function companyPreviewActions(
    doc: CompanyDocument | null,
    allowed: { verify: boolean; invalidate: boolean },
): PreviewAction[] {
    if (!doc) return [];

    return [
        ...(allowed.verify && doc.status !== 'verified' ? [{ key: 'verify', label: 'Verify', icon: RiCheckLine } as const] : []),
        ...(allowed.invalidate && doc.status !== 'invalid' ? [{ key: 'invalidate', label: 'Mark as Invalid', icon: RiCloseLine } as const] : []),
    ];
}

export function vehicleDocumentPreview(doc: VehicleDocument): PreviewDocument;
export function vehicleDocumentPreview(doc: VehicleDocument | null): PreviewDocument | null;
export function vehicleDocumentPreview(doc: VehicleDocument | null): PreviewDocument | null {
    if (!doc) return null;

    return {
        id: doc.id,
        title: doc.file_name ?? humanize(doc.document_type),
        typeLabel: humanize(doc.document_type),
        url: doc.file_url ?? '',
        mime: doc.file_mime_type,
        fileName: doc.file_name,
        status: {
            label: vehicleDocumentStatusLabel(doc.status),
            class: vehicleDocumentStatusClass(doc.status),
            dot: vehicleDocumentStatusDot(doc.status),
        },
        expiredBadge: isExpired(doc.expires_at) && doc.status !== 'expired' && doc.status !== 'invalid',
        issuedAt: doc.issued_at,
        expiresAt: doc.expires_at,
        uploadedAt: doc.created_at,
        remarks: doc.remarks,
        downloadUrl: doc.file_url ?? null,
    };
}

export function vehiclePreviewActions(
    doc: VehicleDocument | null,
    allowed: { verify: boolean; unverify: boolean; invalidate: boolean },
): PreviewAction[] {
    if (!doc) return [];

    return [
        ...(allowed.verify && doc.status !== 'verified' ? [{ key: 'verify', label: 'Verify', icon: RiCheckLine } as const] : []),
        ...(allowed.unverify && doc.status === 'verified' ? [{ key: 'unverify', label: 'Move to Pending', icon: RiRestartLine } as const] : []),
        ...(allowed.invalidate ? [{ key: 'invalidate', label: 'Mark Invalid', icon: RiCloseLine } as const] : []),
    ];
}
