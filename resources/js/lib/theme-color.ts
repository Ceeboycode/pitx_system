/**
 * Resolves a theme token to a colour that libraries drawing their own graphics can read.
 *
 * The tokens in app.css are `oklch()` values behind CSS variables, which Mapbox (markers, line layers)
 * does not understand, so the value is painted onto a one-pixel canvas and read back as `#rrggbb`.
 * The result follows the current theme, so call it again after the theme changes.
 */
let context: CanvasRenderingContext2D | null | undefined;

function getContext(): CanvasRenderingContext2D | null {
    if (context === undefined) {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        context = canvas.getContext('2d', { willReadFrequently: true });
    }

    return context;
}

/**
 * @param token A custom property such as `--custom-primary`.
 * @param fallback Returned when the token is missing or the browser cannot parse it.
 */
export function themeColor(token: string, fallback = '#64748b'): string {
    if (typeof document === 'undefined') return fallback;

    const value = getComputedStyle(document.documentElement).getPropertyValue(token).trim();
    const ctx = getContext();

    if (!value || !ctx || !CSS.supports('color', value)) return fallback;

    // A colour the canvas cannot parse leaves `fillStyle` unchanged (and an unpainted pixel reads as
    // black), so a sentinel tells a parsed colour from a rejected one.
    const sentinel = '#010203';

    ctx.clearRect(0, 0, 1, 1);
    ctx.fillStyle = sentinel;
    ctx.fillStyle = value;

    if (ctx.fillStyle === sentinel) return fallback;

    ctx.fillRect(0, 0, 1, 1);

    const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

    return '#' + [r, g, b].map((channel) => channel.toString(16).padStart(2, '0')).join('');
}
