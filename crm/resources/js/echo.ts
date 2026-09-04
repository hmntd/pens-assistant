import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

export function initializeEcho(): Echo<'reverb'> | null {
    if (typeof window === 'undefined') return null;

    if (window.Echo) {
        return window.Echo;
    }

    const appKey = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const port = import.meta.env.VITE_REVERB_PORT || '80';
    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http';

    if (!appKey) {
        console.warn('[Echo] VITE_REVERB_APP_KEY is not set. Real-time updates will use polling fallback.');
        return null;
    }

    window.Pusher = Pusher;

    window.Echo = new Echo<'reverb'>({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return window.Echo;
}
