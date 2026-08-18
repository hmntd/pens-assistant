let inMemoryFrames: string[] | null = null;
const STORAGE_KEY = 'hero_ascii_frames_v1';

export async function getHeroFrames(): Promise<string[]> {
    if (inMemoryFrames && inMemoryFrames.length > 0) {
        return inMemoryFrames;
    }
    try {
        const cached = sessionStorage.getItem(STORAGE_KEY);
        if (cached) {
            const parsed = JSON.parse(cached);
            if (Array.isArray(parsed) && parsed.length > 0) {
                inMemoryFrames = parsed;
                return parsed;
            }
        }
    } catch (e) {
        // Fallback gracefully if storage is restricted
    }

    // 3. Bundled ES Module import (Vite pre-compiles JS array at build time, zero runtime fetch latency)
    try {
        const module = await import('@/data/rawFrames');
        const loadedFrames = module.rawFrames;

        if (Array.isArray(loadedFrames) && loadedFrames.length > 0) {
            inMemoryFrames = loadedFrames;
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(loadedFrames));
            } catch (e) {
                // Storage quota fallback
            }
            return loadedFrames;
        }
    } catch (e) {
        console.error('Failed to import rawFrames module:', e);
    }

    return [];
}
