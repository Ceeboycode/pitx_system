/**
 * Shared types for the Company screens (Show page tabs + document dialogs).
 * Import from '@/types/company'.
 */

export type UserMini = { id?: number; name: string };

export type Operator = {
    id?: number | null;
    name?: string | null;
    username?: string | null;
    email?: string | null;
    phone?: string | null;
    avatar?: string | null;
    email_verified?: boolean;
    email_verified_at?: string | null;
    created_at?: string | null;
};

export type CompanyDocument = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    file_path?: string;
    mime_type?: string | null;
    created_at?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
    uploader?: UserMini | null; //mali yung value nito, kasi pano kapag yung new upload ay uploaded by a new operator in charge?
    verifier?: UserMini | null; //mali yung value nito, kasi pano kapag yung new upload ay uploaded by a new operator in charge?
    verified_at?: string | null;
};
