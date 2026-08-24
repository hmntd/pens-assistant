/*!
 * AsciiScreen v2.3
 * Copyright (c) 2025 Injosoft. All rights reserved.
 * Part of the asciiart.eu project.
 */

export interface AsciiScreenOptions {
    mode?: 'mono' | 'palette' | 'rgb';
    renderer?: 'dom' | 'canvas' | 'webgl';
    palette?: string[];
    minCols?: number;
    minRows?: number;
    maxCols?: number;
    maxRows?: number;
    defaultRows?: number;
    autoCardBodyHeight?: boolean;
}

export class AsciiScreen {
    el: HTMLElement;
    mode: 'mono' | 'palette' | 'rgb';
    renderer: 'dom' | 'canvas' | 'webgl';
    palette: string[];
    minCols: number;
    minRows: number;
    maxCols: number;
    maxRows: number;
    defaultRows: number;
    autoCardBodyHeight: boolean;
    fullscreen: boolean;
    _lastFullscreenState: boolean;
    cols: number;
    rows: number;
    buffer: string[];
    color: (number | string)[];
    _prevBuffer: string[];
    _prevColor: (number | string)[];
    _dirty: boolean;
    _forceFullRedraw: boolean;
    canvas: HTMLCanvasElement | null;
    ctx: CanvasRenderingContext2D | null;
    _charWidth: number;
    _charHeight: number;
    _glyphCache: Map<any, any>;
    _colorBatches: Map<string, number[]>;
    _gl: WebGLRenderingContext | null;
    _glProgram: WebGLProgram | null;
    _glFontTexture: WebGLTexture | null;
    _glVertexBuffer: WebGLBuffer | null;
    _glTexCoordBuffer: WebGLBuffer | null;
    _glColorBuffer: WebGLBuffer | null;
    _glAtlasChars: string;
    _glAtlasCols: number;
    _glAtlasRows: number;
    _glCellW: number;
    _glCellH: number;
    _measureSpan: HTMLSpanElement | null;
    _lastCharWidth: number;
    _lastCharHeight: number;
    onResize: (cols: number, rows: number) => void;
    _resizeHandler: () => void;
    _resizeObserver: ResizeObserver | null;
    _glAttribPos?: number;
    _glAttribTex?: number;
    _glAttribColor?: number;
    _glUniformRes?: WebGLUniformLocation | null;
    _glUniformTex?: WebGLUniformLocation | null;
    _glAtlasW?: number;
    _glAtlasH?: number;
    _glDpr?: number;
    _glScreenCellW?: number;
    _glScreenCellH?: number;

    constructor(t: HTMLElement, e: AsciiScreenOptions = {}) {
        this.el = t;
        this.mode = e.mode || "mono";
        this.renderer = e.renderer || "dom";
        this.palette = e.palette || ["#0f0"];
        this.minCols = e.minCols || 10;
        this.minRows = e.minRows || 10;
        this.maxCols = e.maxCols || 500;
        this.maxRows = e.maxRows || 300;
        this.defaultRows = e.defaultRows || 50;
        this.autoCardBodyHeight = e.autoCardBodyHeight ?? true;
        this.fullscreen = false;
        this._lastFullscreenState = this._detectFullscreen();
        this.cols = 0;
        this.rows = 0;
        this.buffer = [];
        this.color = [];
        this._prevBuffer = [];
        this._prevColor = [];
        this._dirty = true;
        this._forceFullRedraw = true;
        this.canvas = null;
        this.ctx = null;
        this._charWidth = 0;
        this._charHeight = 0;
        this._glyphCache = new Map();
        this._colorBatches = new Map();
        this._gl = null;
        this._glProgram = null;
        this._glFontTexture = null;
        this._glVertexBuffer = null;
        this._glTexCoordBuffer = null;
        this._glColorBuffer = null;
        this._glAtlasChars = " !\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~░▒▓█▄▀▌▐■│┤╡╢╖╕╣║╗╝╜╛┐└┴┬├─┼╞╟╚╔╩╦╠═╬╧╨╤╥╙╘╒╓╫╪┘┌╭╮╯╰●○◐◑◒◓•·∙°¤¶§†‡※✓✗✕✖★☆♠♣♥♦♪♫◄►▲▼←→↑↓↔↕";
        this._glAtlasCols = 16;
        this._glAtlasRows = 12;
        this._glCellW = 0;
        this._glCellH = 0;
        this._measureSpan = null;
        this._resizeObserver = null;

        this.el.style.textAlign = "center";
        this.el.style.margin = "0 auto";

        if ("canvas" === this.renderer) {
            this._initCanvas();
        } else if ("webgl" === this.renderer) {
            if (!this._initWebGL()) {
                console.warn("WebGL not available, falling back to canvas");
                this.renderer = "canvas";
                this._initCanvas();
            }
        } else {
            this._measureSpan = this._createMeasureSpan();
        }

        this._lastCharWidth = 0;
        this._lastCharHeight = 0;
        this.onResize = () => { };

        this._resizeHandler = () => this.resize();
        this._initFontResizeObserver();
        window.addEventListener("resize", this._resizeHandler);
        this.resize();
    }

    setMode(t: 'mono' | 'palette' | 'rgb') {
        if ("mono" === t || "palette" === t || "rgb" === t) {
            this.mode = t;
            this._forceFullRedraw = true;
            this.resize();
        }
    }

    setRenderer(t: 'dom' | 'canvas' | 'webgl') {
        if (t !== this.renderer) {
            this._cleanupRenderer();
            if ("canvas" === t) {
                this.renderer = "canvas";
                this._initCanvas();
            } else if ("webgl" === t) {
                this.renderer = "webgl";
                if (!this._initWebGL()) {
                    console.warn("WebGL not available, falling back to canvas");
                    this.renderer = "canvas";
                    this._initCanvas();
                }
            } else {
                this.renderer = "dom";
                this.el.style.display = "";
                if (!this._measureSpan) {
                    this._measureSpan = this._createMeasureSpan();
                }
            }
            this._forceFullRedraw = true;
            this.resize();
        }
    }

    _cleanupRenderer() {
        if (this._gl) {
            if (this._glProgram) this._gl.deleteProgram(this._glProgram);
            if (this._glFontTexture) this._gl.deleteTexture(this._glFontTexture);
            if (this._glVertexBuffer) this._gl.deleteBuffer(this._glVertexBuffer);
            if (this._glTexCoordBuffer) this._gl.deleteBuffer(this._glTexCoordBuffer);
            if (this._glColorBuffer) this._gl.deleteBuffer(this._glColorBuffer);
            this._gl = null;
            this._glProgram = null;
            this._glFontTexture = null;
            this._glVertexBuffer = null;
            this._glTexCoordBuffer = null;
            this._glColorBuffer = null;
        }
        this.ctx = null;
        if (this.canvas) {
            this.canvas.remove();
            this.canvas = null;
        }
        this.el.style.display = "";
    }

    setPalette(t: string[]) {
        this.palette = t;
        this._glyphCache.clear();
        this._forceFullRedraw = true;
    }

    extendAtlas(t: string) {
        if (!t) return;
        let e = false;
        for (const s of t) {
            if (!this._glAtlasChars.includes(s)) {
                this._glAtlasChars += s;
                e = true;
            }
        }
        if (e && "webgl" === this.renderer && this._gl) {
            const t = this._glAtlasChars.length;
            this._glAtlasCols = Math.ceil(Math.sqrt(t));
            this._glAtlasRows = Math.ceil(t / this._glAtlasCols);
            this._createFontTexture();
            this._forceFullRedraw = true;
        }
    }

    getAtlasChars() {
        return this._glAtlasChars;
    }

    _initCanvas() {
        this.el.style.display = "none";
        this.canvas = document.createElement("canvas");
        this.canvas.style.display = "block";
        this.canvas.style.width = "100%";
        this.canvas.style.height = "100%";
        this.canvas.style.imageRendering = "pixelated";
        if (this.el.parentNode) {
            this.el.parentNode.insertBefore(this.canvas, this.el);
        }
        this.ctx = this.canvas.getContext("2d", { alpha: true });
        if (!this._measureSpan) {
            this._measureSpan = this._createMeasureSpan();
        }
    }

    _initWebGL(): boolean {
        this.el.style.display = "none";
        this.canvas = document.createElement("canvas");
        this.canvas.style.display = "block";
        this.canvas.style.width = "100%";
        this.canvas.style.height = "100%";
        if (this.el.parentNode) {
            this.el.parentNode.insertBefore(this.canvas, this.el);
        }
        const t = this.canvas.getContext("webgl", { alpha: true, antialias: false });
        if (!t) return false;
        this._gl = t;
        if (!this._measureSpan) {
            this._measureSpan = this._createMeasureSpan();
        }
        const e = this._glCompileShader(t.VERTEX_SHADER, `
            attribute vec2 aPosition;
            attribute vec2 aTexCoord;
            attribute vec3 aColor;
            uniform vec2 uResolution;
            varying vec2 vTexCoord;
            varying vec3 vColor;
            void main() {
                vec2 pos = (aPosition / uResolution) * 2.0 - 1.0;
                gl_Position = vec4(pos.x, -pos.y, 0.0, 1.0);
                vTexCoord = aTexCoord;
                vColor = aColor;
            }
        `);
        const s = this._glCompileShader(t.FRAGMENT_SHADER, `
            precision mediump float;
            uniform sampler2D uTexture;
            varying vec2 vTexCoord;
            varying vec3 vColor;
            void main() {
                float alpha = texture2D(uTexture, vTexCoord).a;
                if (alpha < 0.1) discard;
                gl_FragColor = vec4(vColor * 0.97, alpha);
            }
        `);
        if (!e || !s) return false;
        const i = t.createProgram();
        if (!i) return false;
        t.attachShader(i, e);
        t.attachShader(i, s);
        t.linkProgram(i);
        if (!t.getProgramParameter(i, t.LINK_STATUS)) {
            console.error("WebGL program link error:", t.getProgramInfoLog(i));
            return false;
        }
        this._glProgram = i;
        this._glAttribPos = t.getAttribLocation(i, "aPosition");
        this._glAttribTex = t.getAttribLocation(i, "aTexCoord");
        this._glAttribColor = t.getAttribLocation(i, "aColor");
        this._glUniformRes = t.getUniformLocation(i, "uResolution");
        this._glUniformTex = t.getUniformLocation(i, "uTexture");
        this._glVertexBuffer = t.createBuffer();
        this._glTexCoordBuffer = t.createBuffer();
        this._glColorBuffer = t.createBuffer();
        t.useProgram(i);
        t.enable(t.BLEND);
        t.blendFunc(t.SRC_ALPHA, t.ONE_MINUS_SRC_ALPHA);
        t.clearColor(0, 0, 0, 0);
        this._createFontTexture();
        return true;
    }

    _glCompileShader(t: number, e: string): WebGLShader | null {
        const s = this._gl;
        if (!s) return null;
        const i = s.createShader(t);
        if (!i) return null;
        s.shaderSource(i, e);
        s.compileShader(i);
        if (!s.getShaderParameter(i, s.COMPILE_STATUS)) {
            console.error("WebGL shader compile error:", s.getShaderInfoLog(i));
            s.deleteShader(i);
            return null;
        }
        return i;
    }

    _createFontTexture() {
        const t = this._gl;
        if (!t) return;
        const e = window.devicePixelRatio || 1;
        const s = getComputedStyle(this.el);
        const i = this._getCharMetrics();
        const r = Math.ceil(i.w);
        const l = Math.ceil(i.h);
        this._glCellW = r;
        this._glCellH = l;
        const o = this._glAtlasCols;
        const h = o * r;
        const n = this._glAtlasRows * l;
        const a = document.createElement("canvas");
        a.width = h * e;
        a.height = n * e;
        const c = a.getContext("2d");
        if (!c) return;
        c.scale(e, e);
        const f = s.fontWeight || "normal";
        c.font = `${f} ${s.fontSize} ${s.fontFamily}`;
        c.textAlign = "center";
        c.textBaseline = "middle";
        c.fillStyle = "#ffffff";
        const _ = this._glAtlasChars;
        for (let t = 0; t < _.length; t++) {
            const e = t % o * r;
            const s = Math.floor(t / o) * l;
            c.save();
            c.beginPath();
            c.rect(e, s, r, l);
            c.clip();
            c.fillText(_[t], e + .5 * r, s + .5 * l);
            c.restore();
        }
        this._glAtlasW = h * e;
        this._glAtlasH = n * e;
        this._glDpr = e;
        if (this._glFontTexture) t.deleteTexture(this._glFontTexture);
        const u = t.createTexture();
        t.bindTexture(t.TEXTURE_2D, u);
        t.texImage2D(t.TEXTURE_2D, 0, t.RGBA, t.RGBA, t.UNSIGNED_BYTE, a);
        t.texParameteri(t.TEXTURE_2D, t.TEXTURE_MIN_FILTER, t.LINEAR);
        t.texParameteri(t.TEXTURE_2D, t.TEXTURE_MAG_FILTER, t.LINEAR);
        t.texParameteri(t.TEXTURE_2D, t.TEXTURE_WRAP_S, t.CLAMP_TO_EDGE);
        t.texParameteri(t.TEXTURE_2D, t.TEXTURE_WRAP_T, t.CLAMP_TO_EDGE);
        this._glFontTexture = u;
    }

    _detectFullscreen(): boolean {
        let t: HTMLElement | null = this.el;
        while (t) {
            if (t.classList && t.classList.contains("is-fullscreen")) return true;
            t = t.parentElement;
        }
        return false;
    }

    _createMeasureSpan(): HTMLSpanElement {
        const t = document.createElement("span");
        t.textContent = "M";
        Object.assign(t.style, {
            position: "absolute",
            left: "-9999px",
            top: "-9999px",
            visibility: "hidden",
            whiteSpace: "pre"
        });
        document.body.appendChild(t);
        return t;
    }

    _getCharMetrics() {
        const t = getComputedStyle(this.el);
        if (this._measureSpan) {
            this._measureSpan.style.fontFamily = t.fontFamily;
            this._measureSpan.style.fontSize = t.fontSize;
        }
        const e = this._measureSpan ? this._measureSpan.getBoundingClientRect().width : 8;
        const s = parseFloat(t.lineHeight);
        return {
            w: Number.isFinite(e) && e > 0 ? e : 8,
            h: Number.isFinite(s) && s > 0 ? s : 16
        };
    }

    _initFontResizeObserver() {
        const t = document.createElement("div");
        t.style.position = "absolute";
        t.style.left = "-9999px";
        t.style.top = "-9999px";
        t.style.whiteSpace = "pre";
        t.textContent = "X";
        document.body.appendChild(t);
        const e = () => {
            const e = getComputedStyle(this.el);
            t.style.fontSize = e.fontSize;
            t.style.fontFamily = e.fontFamily;
            t.style.lineHeight = e.lineHeight;
        };
        e();
        this._resizeObserver = new ResizeObserver(entries => {
            for (const s of entries) {
                const t = s.contentRect.height;
                if (t && Math.abs(t - this._lastCharHeight) > 1.5) {
                    this._lastCharHeight = t;
                    e();
                    this._glyphCache.clear();
                    this._forceFullRedraw = true;
                    this.resize();
                }
            }
        });
        this._resizeObserver.observe(t);
    }

    _setCardBodyHeightFromRows(t: number, e: number) {
        if (!this.autoCardBodyHeight) return;
        const s = this.el.closest(".card-body") as HTMLElement | null;
        if (!s) return;
        const i = getComputedStyle(s);
        const r = parseFloat(i.paddingTop) || 0;
        const l = parseFloat(i.paddingBottom) || 0;
        const o = Math.ceil(t * e + r + l);
        s.style.height = `${o}px`;
    }

    resize() {
        const t = this._detectFullscreen();
        this.fullscreen = t;
        const e = this._getCharMetrics();
        this._charWidth = e.w;
        this._charHeight = e.h;
        const parent = this.el.parentElement;
        const i = (parent && parent.clientWidth > 0 ? parent.clientWidth : 0) || window.innerWidth || 800;
        const r = "webgl" === this.renderer ? Math.ceil(e.w) : e.w;
        const l = Math.min(this.maxCols, Math.max(this.minCols, Math.floor(i / r)));
        const o = "webgl" === this.renderer ? Math.ceil(e.h) : e.h;
        let h: number;
        if (this.fullscreen) {
            const t = this.el.closest(".is-fullscreen") as HTMLElement | null;
            if (t) {
                const e = t.getBoundingClientRect();
                const s = t.querySelector(".card-header") as HTMLElement | null;
                const i = t.querySelector(".card-footer") as HTMLElement | null;
                let r = 0;
                if (s) r += s.offsetHeight;
                if (i) r += i.offsetHeight;
                const l = Math.max(0, e.height - r);
                h = Math.min(this.maxRows, Math.max(this.minRows, Math.floor(l / o)));
            } else {
                h = this.defaultRows;
            }
        } else {
            h = this.defaultRows;
            this._setCardBodyHeightFromRows(h, o);
        }
        this.cols = l;
        this.rows = h;
        this._allocateBuffers();
        if ("canvas" === this.renderer && this.canvas && this.ctx) {
            const t = window.devicePixelRatio || 1;
            const s = Math.ceil(l * e.w);
            const i = Math.ceil(h * e.h);
            this.canvas.width = s * t;
            this.canvas.height = i * t;
            this.canvas.style.width = s + "px";
            this.canvas.style.height = i + "px";
            this.ctx.setTransform(t, 0, 0, t, 0, 0);
            this._setupCanvasFont();
            this._glyphCache.clear();
            this._forceFullRedraw = true;
        }
        if ("webgl" === this.renderer && this.canvas && this._gl) {
            const t = Math.ceil(e.w);
            const s = Math.ceil(e.h);
            const i = l * t;
            const r = h * s;
            this.canvas.width = i;
            this.canvas.height = r;
            this.canvas.style.width = i + "px";
            this.canvas.style.height = r + "px";
            this._glScreenCellW = t;
            this._glScreenCellH = s;
            this._gl.viewport(0, 0, i, r);
            this._createFontTexture();
            this._forceFullRedraw = true;
        }
        this.onResize(l, h);
        this._lastFullscreenState = this.fullscreen;
    }

    _setupCanvasFont() {
        if (!this.ctx) return;
        const t = getComputedStyle(this.el);
        const e = t.fontWeight || "normal";
        this.ctx.font = `${e} ${t.fontSize} ${t.fontFamily}`;
        this.ctx.textAlign = "center";
        this.ctx.textBaseline = "middle";
    }

    _allocateBuffers() {
        const t = this.rows * this.cols;
        this.buffer = new Array(t).fill(" ");
        this._prevBuffer = new Array(t).fill("");
        if ("mono" !== this.mode) {
            this.color = new Array(t).fill(0);
            this._prevColor = new Array(t).fill(-1);
        } else {
            this.color = [];
            this._prevColor = [];
        }
        this._forceFullRedraw = true;
    }

    clear() {
        this.buffer.fill(" ");
        if ("mono" !== this.mode && Array.isArray(this.color)) {
            this.color.fill(0);
        }
        this._forceFullRedraw = true;
    }

    put(t: number, e: number, s: string, i: number | string = 0) {
        if (t < 0 || t >= this.cols || e < 0 || e >= this.rows) return;
        const r = e * this.cols + t;
        this.buffer[r] = s;
        if ("palette" === this.mode) {
            this.color[r] = Number(i) || 0;
        } else if ("rgb" === this.mode) {
            this.color[r] = i;
        }
    }

    renderToElement() {
        if ("canvas" === this.renderer) {
            this._renderCanvas();
        } else if ("webgl" === this.renderer) {
            this._renderWebGL();
        } else if ("mono" === this.mode) {
            this.el.textContent = this._renderMono();
        } else if ("palette" === this.mode) {
            this.el.innerHTML = this._renderPalette();
        } else {
            this.el.innerHTML = this._renderRGB();
        }

        const t = this.buffer.length;
        for (let e = 0; e < t; e++) {
            this._prevBuffer[e] = this.buffer[e];
            if ("mono" !== this.mode) {
                this._prevColor[e] = this.color[e];
            }
        }
        this._forceFullRedraw = false;
    }

    _escapeHtml(t: string) {
        return /[<>&]/.test(t) ? t.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : t;
    }

    _renderMono(): string {
        const t = this.cols;
        const e = this.rows;
        const s = this.buffer;
        const i = new Array(e);
        let r = 0;
        for (let l = 0; l < e; l++) {
            i[l] = s.slice(r, r + t).join("");
            r += t;
        }
        return i.join("\n");
    }

    _renderPalette(): string {
        const t = this.cols;
        const e = this.rows;
        const s = this.buffer;
        const i = this.color;
        const r = this.palette;
        let l = 0;
        const o = new Array(e);
        for (let h = 0; h < e; h++) {
            const e: string[] = [];
            let n = l;
            const a = l + t;
            while (n < a) {
                if (" " === s[n]) {
                    const t = n;
                    while (n < a && " " === s[n]) n++;
                    e.push(" ".repeat(n - t));
                    continue;
                }
                const t = Number(i[n]) || 0;
                const l = r[t] || r[0];
                const o = n;
                while (n < a && i[n] === t && " " !== s[n]) n++;
                const h = this._escapeHtml(s.slice(o, n).join(""));
                e.push(`<span style="color:${l}">${h}</span>`);
            }
            o[h] = e.join("");
            l += t;
        }
        return o.join("\n");
    }

    _renderRGB(): string {
        const t = this.cols;
        const e = this.rows;
        const s = this.buffer;
        const i = this.color;
        let r = 0;
        const l = new Array(e);
        for (let o = 0; o < e; o++) {
            const e: string[] = [];
            let h = r;
            const n = r + t;
            while (h < n) {
                if (" " === s[h]) {
                    const t = h;
                    while (h < n && " " === s[h]) h++;
                    e.push(" ".repeat(h - t));
                    continue;
                }
                const t = i[h];
                const r = h;
                while (h < n && i[h] === t && " " !== s[h]) h++;
                const l = this._escapeHtml(s.slice(r, h).join(""));
                e.push(`<span style="color:${t}">${l}</span>`);
            }
            l[o] = e.join("");
            r += t;
        }
        return l.join("\n");
    }

    _renderCanvas() {
        const t = this.ctx;
        if (!t) return;
        const e = this.cols;
        const s = this.rows;
        const i = this.buffer;
        const r = this.color;
        const l = this._charWidth;
        const o = this._charHeight;
        const h = i.length;

        if (this._forceFullRedraw) {
            t.clearRect(0, 0, e * l, s * o);

            this._colorBatches.clear();
            for (let t = 0; t < h; t++) {
                if (" " === i[t]) continue;
                let e: string;
                e = "mono" === this.mode ? "#ffffff" : "palette" === this.mode ? this.palette[Number(r[t])] || this.palette[0] : (r[t] as string) || "#ffffff";
                if (!this._colorBatches.has(e)) this._colorBatches.set(e, []);
                this._colorBatches.get(e)!.push(t);
            }
            for (const [s, r] of this._colorBatches) {
                t.fillStyle = s;
                for (const s of r) {
                    const r = s % e;
                    const h = Math.floor(s / e);
                    t.fillText(i[s], r * l + .5 * l, h * o + .5 * o);
                }
            }
        } else {
            for (let s = 0; s < h; s++) {
                const h = i[s];
                const n = this._prevBuffer[s];
                const a = "mono" !== this.mode ? r[s] : 0;
                const c = "mono" !== this.mode ? this._prevColor[s] : 0;

                if (h === n && a === c) continue;

                const f = (s % e) * l;
                const _ = Math.floor(s / e) * o;

                t.clearRect(f, _, l, o);

                if (" " !== h) {
                    t.fillStyle = "mono" === this.mode ? "#ffffff" : "palette" === this.mode ? this.palette[Number(a)] || this.palette[0] : (a as string) || "#ffffff";
                    t.fillText(h, f + .5 * l, _ + .5 * o);
                }
            }
        }
    }

    _renderWebGL() {
        const t = this._gl;
        if (!t) return;
        const e = this.cols;
        const s = this.buffer;
        const i = this.color;
        const r = this._glCellW;
        const l = this._glCellH;
        const o = this._glScreenCellW || r;
        const h = this._glScreenCellH || l;
        const n = s.length;
        t.clear(t.COLOR_BUFFER_BIT);
        const a: number[] = [];
        const c: number[] = [];
        const f: number[] = [];
        const _ = this._glAtlasChars;
        const u = this._glAtlasCols;
        const g = this._glAtlasW || 1;
        const d = this._glAtlasH || 1;
        const p = this._glDpr || 1;
        const m = (r * p) / g;
        const C = (l * p) / d;

        for (let t = 0; t < n; t++) {
            const r = s[t];
            if (" " === r) continue;
            const l = _.indexOf(r);
            if (l < 0) continue;
            const n = l % u;
            const g = Math.floor(l / u);
            const d = (t % e) * o;
            const p = Math.floor(t / e) * h;
            const x = n * m;
            const R = g * C;
            const v = (n + 1) * m;
            const A = (g + 1) * C;
            let T: number, w: number, y: number;

            if ("mono" === this.mode) {
                T = w = y = 1;
            } else if ("palette" === this.mode) {
                const e = this.palette[Number(i[t])] || this.palette[0] || "#ffffff";
                [T, w, y] = this._hexToRGB(e);
            } else {
                const e = (i[t] as string) || "#ffffff";
                [T, w, y] = this._hexToRGB(e);
            }

            a.push(d, p, d + o, p, d, p + h);
            c.push(x, R, v, R, x, A);
            f.push(T, w, y, T, w, y, T, w, y);
            a.push(d + o, p, d + o, p + h, d, p + h);
            c.push(v, R, v, A, x, A);
            f.push(T, w, y, T, w, y, T, w, y);
        }

        if (0 !== a.length && this._glVertexBuffer && this._glTexCoordBuffer && this._glColorBuffer && this._glFontTexture) {
            t.bindBuffer(t.ARRAY_BUFFER, this._glVertexBuffer);
            t.bufferData(t.ARRAY_BUFFER, new Float32Array(a), t.DYNAMIC_DRAW);
            if (this._glAttribPos !== undefined && this._glAttribPos >= 0) {
                t.enableVertexAttribArray(this._glAttribPos);
                t.vertexAttribPointer(this._glAttribPos, 2, t.FLOAT, false, 0, 0);
            }

            t.bindBuffer(t.ARRAY_BUFFER, this._glTexCoordBuffer);
            t.bufferData(t.ARRAY_BUFFER, new Float32Array(c), t.DYNAMIC_DRAW);
            if (this._glAttribTex !== undefined && this._glAttribTex >= 0) {
                t.enableVertexAttribArray(this._glAttribTex);
                t.vertexAttribPointer(this._glAttribTex, 2, t.FLOAT, false, 0, 0);
            }

            t.bindBuffer(t.ARRAY_BUFFER, this._glColorBuffer);
            t.bufferData(t.ARRAY_BUFFER, new Float32Array(f), t.DYNAMIC_DRAW);
            if (this._glAttribColor !== undefined && this._glAttribColor >= 0) {
                t.enableVertexAttribArray(this._glAttribColor);
                t.vertexAttribPointer(this._glAttribColor, 3, t.FLOAT, false, 0, 0);
            }

            if (this._glUniformRes && this.canvas) {
                t.uniform2f(this._glUniformRes, this.canvas.width, this.canvas.height);
            }
            t.activeTexture(t.TEXTURE0);
            t.bindTexture(t.TEXTURE_2D, this._glFontTexture);
            if (this._glUniformTex) {
                t.uniform1i(this._glUniformTex, 0);
            }
            t.drawArrays(t.TRIANGLES, 0, a.length / 2);
        }
    }

    _hexToRGB(t: string): [number, number, number] {
        if ("#" === t[0]) t = t.slice(1);
        if (3 === t.length) t = t[0] + t[0] + t[1] + t[1] + t[2] + t[2];
        const e = parseInt(t, 16);
        return [(e >> 16 & 255) / 255, (e >> 8 & 255) / 255, (255 & e) / 255];
    }

    destroy() {
        window.removeEventListener("resize", this._resizeHandler);
        if (this._resizeObserver) {
            this._resizeObserver.disconnect();
        }
        this._cleanupRenderer();
        if (this._measureSpan) {
            this._measureSpan.remove();
        }
    }
}
