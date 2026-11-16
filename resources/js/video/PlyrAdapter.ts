import Plyr from "plyr";
import "plyr/dist/plyr.css";
import { ref, shallowRef } from "vue";
import type { VideoAdapter } from "./types";

export class PlyrAdapter implements VideoAdapter {
    private player!: Plyr;
    private ready = false;
    private isDestroyed = false; // 🔥 Proteção de memória
    private rafId: number | null = null; // 🔥 Motor de 60fps

    private pendingPlay = false;
    private pendingUnmute = false;

    private hasPlayedOnce = false;
    private initialSeekTime: number | null = null;

    private onTimeUpdateCallback?: (t: number) => void;

    state = {
        currentTime: ref(0),
        duration: ref(0),
        isPlaying: shallowRef(false),
    };

    constructor(
        container: HTMLElement,
        source: { type: "vimeo" | "youtube" | "mp4"; src: string },
        onTimeUpdate?: (t: number) => void,
        onReady?: () => void,
        onRateChange?: (rate: number) => void,
        initialRate: number = 1
    ) {
        this.onTimeUpdateCallback = onTimeUpdate;

        const video = document.createElement("video");
        video.className = "object-contain w-full h-full";
        video.setAttribute("playsinline", "true");
        video.setAttribute("webkit-playsinline", "true");

        video.addEventListener("dblclick", (e) => {
            e.preventDefault();
            e.stopPropagation();
        });

        container.appendChild(video);

        this.player = new Plyr(video, {
            muted: true,
            autoplay: false,
            clickToPlay: false,
            speed: {
                selected: initialRate,
                options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2],
            },

            // 🔥 CORREÇÃO 1: Matamos a lista de controlos do Plyr
            controls: [],

            settings: ["quality", "speed"],
            fullscreen: { enabled: false, fallback: false },
            youtube: {
                noCookie: true,
                rel: 0,
                showinfo: 0, // (Legado) Oculta título onde possível
                iv_load_policy: 3, // Remove anotações e cartões do vídeo
                modestbranding: 1, // Remove o logo do YouTube
                autoplay: 0,
                mute: 1,

                // 🔥 A MÁGICA: Define 0 para remover 100% da barra inferior nativa
                controls: 0,

                // 🔥 Bloqueia as setas do teclado para o aluno não poder avançar o vídeo pelo teclado
                disablekb: 1,

                // 🔥 Força a reprodução inline no iOS (evita que o iPhone abra o player nativo dele)
                playsinline: 1,
            },
            vimeo: {
                byline: false,
                portrait: false,
                title: false,
                autopause: false,
                transparent: true, // 🔥 Alterado para true para fundir com o fundo preto
                background: true,
                dnt: true, // Do Not Track (ajuda a evitar popups de cookies do Vimeo na transição)
                controls: false, // Força a remoção de controlos via API do Vimeo
            },
        });
        video.addEventListener("loadedmetadata", () => {
            const d = this.player.duration;
            if (d && !isNaN(d) && d > 0) this.state.duration.value = d;
        });

        if (source.type === "mp4") {
            this.player.source = {
                type: "video",
                sources: [{ src: source.src, type: "video/mp4" }],
            };
        } else {
            this.player.source = {
                type: "video",
                sources: [{ src: source.src, provider: source.type }],
            };
        }

        this.player.on("ready", () => {
            this.ready = true;
            this.state.duration.value = this.player.duration || 0;
            onReady?.();
        });

        // 🔥 MOTOR DE PRECISÃO: Substitui o lento 'timeupdate' nativo por uma checagem sincronizada com a tela do usuário (16ms)
        const checkTime = () => {
            if (this.ready && !this.isDestroyed) {
                try {
                    const t = this.player.currentTime;
                    if (
                        t !== undefined &&
                        Math.abs(this.state.currentTime.value - t) > 0.015
                    ) {
                        this.state.currentTime.value = t;
                        if (this.onTimeUpdateCallback)
                            this.onTimeUpdateCallback(t);
                    }
                    const d = this.player.duration;
                    if (
                        d &&
                        !isNaN(d) &&
                        d > 0 &&
                        Math.abs(d - this.state.duration.value) > 0.1
                    ) {
                        this.state.duration.value = d;
                    }
                } catch (e) {}
            }
            if (!this.isDestroyed)
                this.rafId = requestAnimationFrame(checkTime);
        };
        this.rafId = requestAnimationFrame(checkTime);

        this.player.on("ratechange", () => {
            if (this.player.speed) onRateChange?.(this.player.speed);
        });

        this.player.on("seeked", () => {
            if (this.pendingPlay && !this.state.isPlaying.value) {
                const playPromise = this.player.play();
                if (playPromise !== undefined) playPromise.catch(() => {});
            }
        });

        this.player.on("playing", () => {
            this.state.isPlaying.value = true;
            this.pendingPlay = false;
            this.hasPlayedOnce = true;
            if (this.initialSeekTime !== null) {
                const target = this.initialSeekTime;
                this.initialSeekTime = null;
                this.player.currentTime = target;
            }
            if (this.pendingUnmute) {
                this.player.muted = false;
                this.player.volume = 1;
                this.pendingUnmute = false;
            }
        });

        this.player.on("pause", () => {
            this.state.isPlaying.value = false;
        });
        this.player.fullscreen.enter = () => {};
        this.player.fullscreen.toggle = () => {};
    }

    async mount(): Promise<void> {}

    play(): void {
        if (!this.ready) return;
        this.pendingPlay = true;

        const playPromise = this.player.play();

        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    this.hasPlayedOnce = true;
                })
                .catch((error) => {
                    console.warn(
                        "[Plyr] Autoplay bloqueado. Forçando fallback mutado.",
                        error
                    );
                    this.player.muted = true;

                    // 🔥 CORREÇÃO: Tratamento estrito do TS para 'void | Promise<void>'
                    const fallbackPromise = this.player.play();
                    if (
                        fallbackPromise !== undefined &&
                        typeof fallbackPromise.catch === "function"
                    ) {
                        fallbackPromise.catch(() => {});
                    }
                });
        }
    }

    pause(): void {
        if (!this.ready) return;
        this.pendingPlay = false;
        this.player.pause();
    }

    seek(time: number): void {
        if (!this.ready) return;
        const t = Math.max(
            0,
            Math.min(time, this.state.duration.value || time)
        );

        if (!this.hasPlayedOnce && t > 0) {
            this.initialSeekTime = t;
        } else {
            this.player.currentTime = t;
        }
    }

    unmute() {
        if (!this.player || !this.ready) return;
        this.player.muted = false;
        this.player.volume = 1;
    }

    mute() {
        if (!this.player || !this.ready) return;
        this.player.muted = true;
        this.player.volume = 0;
    }
    setVolume(volume: number): void {
        if (!this.player || !this.ready) return;
        this.player.volume = volume;
        this.player.muted = volume === 0;
        if (volume > 0) this.pendingUnmute = false;
    }
    destroy(): void {
        this.isDestroyed = true;
        if (this.rafId) cancelAnimationFrame(this.rafId);
        try {
            this.player.destroy();
        } catch {}
    }

    setSpeed(rate: number): void {
        if (!this.ready || !this.player) return;
        try {
            this.player.speed = rate;
        } catch (e) {}
    }
}
