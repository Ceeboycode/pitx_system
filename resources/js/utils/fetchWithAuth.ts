/**
 * Authenticated fetch wrapper for same-origin web requests.
 *
 * Automatically:
 *  - Sends the session cookie (credentials: 'same-origin')
 *  - Sets Accept: application/json and X-Requested-With headers
 *  - Handles 419 (CSRF / session expired) → reloads the page
 *  - Handles 401 (unauthenticated) → redirects to /login
 *
 * Non-2xx responses other than 419/401 are returned as-is so callers
 * can parse the body and surface a meaningful API error message.
 */
export async function fetchWithAuth(
    url: string,
    options: RequestInit = {},
): Promise<Response> {
    const { headers = {}, ...rest } = options;

    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(headers as Record<string, string>),
        },
        ...rest,
    });

    if (response.status === 419) {
        window.location.reload();
        throw new Error('Session expired. The page will reload.');
    }

    if (response.status === 401) {
        window.location.href = '/login';
        throw new Error('Unauthenticated. Redirecting to login.');
    }

    return response;
}
