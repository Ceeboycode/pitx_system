import type { InertiaForm } from '@inertiajs/vue3';

export type CompanyProfileDocument = {
    id: number;
    doc_type: string;
    status: string;
    remarks?: string | null;
    expires_at?: string | null;
    updated_at?: string | null;
};

export type CompanyProfile = {
    id: number;
    company_name: string;
    company_code?: string | null;
    company_email?: string | null;
    company_phone?: string | null;
    company_address?: string | null;
    status: string;
    business_type?: string | null;
    registration_number?: string | null;
    authorized_representative_name?: string | null;
    authorized_representative_position?: string | null;
    authorized_representative_contact?: string | null;
    logo_url?: string | null;
    created_at_human?: string | null;
    updated_at_human?: string | null;
    creator?: { name: string } | null;
    updater?: { name: string } | null;
    documents: CompanyProfileDocument[];
};

export type CompanyProfileChangeRequest = {
    id: number;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    created_at?: string | null;
    approved_at?: string | null;
    requested_values: Record<string, unknown>;
};

/** The fields a company may edit itself. Name, business type and registration number are managed by admin. */
export type CompanyProfileFormData = {
    company_email: string;
    company_phone: string;
    company_address: string;
    authorized_representative_name: string;
    authorized_representative_position: string;
    authorized_representative_contact: string;
    logo: File | null;
    /** Asks admin to drop the current logo; only takes effect on save. */
    remove_logo: boolean;
};

export type CompanyProfileForm = InertiaForm<CompanyProfileFormData>;

const documentLabels: Record<string, string> = {
    SEC_CERT: 'SEC Certificate',
    DTI_CERT: 'DTI Certificate',
    MAYORS_PERMIT: "Mayor's Permit",
    BIR_2303: 'BIR Form 2303',
    AUTHORIZATION_LETTER: 'Authorization Letter',
};

export function documentLabel(docType: string): string {
    return documentLabels[docType] ?? docType.replace(/_/g, ' ');
}
