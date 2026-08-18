let cachedCss: string | null = null;
let refCount = 0;

export function getParticleCss(): string {
    if (cachedCss) {
        return cachedCss;
    }

    const total = 300;
    const orbSize = 300;
    const time = 14;
    const baseHue = 156; // #31DE97 brand green hue
    let cssText = '';

    for (let i = 1; i <= total; i++) {
        const z = Math.random() * 360;
        const y = Math.random() * 360;
        const hue = (40 / total * i) + baseHue;
        const delay = i * 0.01;

        cssText += `
        .c:nth-child(${i}) {
            animation: orbit${i} ${time}s infinite;
            animation-delay: ${delay}s;
            background-color: hsla(${hue}, 100%, 50%, 1);
        }
    
        @keyframes orbit${i} {
            20% {
                opacity: 1;
            }
            30% {
                transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${orbSize}px) rotateZ(${z}deg);
            }
            80% {
                transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${orbSize}px) rotateZ(${z}deg);
                opacity: 1;
            }
            100% {
                transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${orbSize * 3}px) rotateZ(${z}deg);
            }
        }
        `;
    }

    cachedCss = cssText;
    return cachedCss;
}

export function mountParticleStyles(): void {
    refCount++;

    let styleEl = document.getElementById('particle-sphere-styles') as HTMLStyleElement | null;

    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'particle-sphere-styles';
        styleEl.setAttribute('data-particle-sphere', 'true');
        styleEl.innerHTML = getParticleCss();
        document.head.appendChild(styleEl);
    }
}

export function unmountParticleStyles(): void {
    refCount = Math.max(0, refCount - 1);

    if (refCount === 0) {
        const styleEl = document.getElementById('particle-sphere-styles');
        if (styleEl && styleEl.parentNode) {
            styleEl.parentNode.removeChild(styleEl);
        }
    }
}
