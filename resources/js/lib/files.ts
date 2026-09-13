/**
 * Pure file-type helpers for uploaded documents (company docs, vehicle docs, …).
 *
 * Accepts any object shaped like an uploaded file; no domain type coupling.
 */

export type FileLike = {
    file_path?: string | null;
    mime_type?: string | null;
    original_name?: string | null;
};

const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

function extensionOf(file: FileLike): string {
    return (
        (file.original_name ?? file.file_path ?? '')
            .split('.')
            .pop()
            ?.toLowerCase() ?? ''
    );
}

/** Public storage URL for a stored file path. */
export function fileUrl(file: FileLike): string {
    if (!file.file_path) return '';
    return `/storage/${file.file_path}`;
}

export function isImage(file: FileLike): boolean {
    if (file.mime_type) return file.mime_type.startsWith('image/');
    return IMAGE_EXTENSIONS.includes(extensionOf(file));
}

export function isPdf(file: FileLike): boolean {
    if (file.mime_type) return file.mime_type === 'application/pdf';
    return extensionOf(file) === 'pdf';
}

/** Whether the file can be shown in the inline preview dialog. */
export function canPreview(file: FileLike): boolean {
    return isImage(file) || isPdf(file);
}
