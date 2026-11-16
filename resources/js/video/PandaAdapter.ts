// resources/js/video/PandaAdapter.ts
import { ref, shallowRef } from "vue";
import type { VideoAdapter } from "./types";
import { normalizePandaUrl, loadPandaScript } from "./utils";

export class PandaAdapter implements VideoAdapter {
    private player: any;
    private ready = false;

    // 🔥 MEMÓRIA DA VELOCIDADE
    private targetRate = 1;

    state = {
        currentTime: ref(0),
        duration: ref(0),
        isPlaying: shallowRef(false),
    };

    constructor(
        container: HTMLElement,
        videoUrl: string,
        opts: {
            autoplay?: boolean;
            muted?: boolean;
            onTimeUpdate?: (t: number) => void;
            onReady?: () => void;
            getPlaybackRate?: () => number; // Lemos a velocidade global
            onRateChange?: (rate: number) => void; // Avisamos se mudar
        }
    ) {
        // Inicializa a velocidade correta lendo do Vue
        this.targetRate = opts.getPlaybackRate ? opts.getPlaybackRate() : 1;
        const normalized = normalizePandaUrl(videoUrl);

        const iframeId = "panda-" + Math.random().toString(36).slice(2);
        const iframe = document.createElement("iframe");

        iframe.id = iframeId;
        iframe.src = normalized;
        iframe.style.width = "100%";
        iframe.style.height = "100%";
        iframe.style.border = "0";
        iframe.allow =
            "accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture";

        container.appendChild(iframe);

        loadPandaScript().then(() => {
            (window as any).pandascripttag.push(() => {
                this.player = new (window as any).PandaPlayer(iframeId, {
                    onReady: () => {
                        this.ready = true;

                        try {
                            this.state.duration.value =
                                this.player.getDuration?.() || 0;
                        } catch {}

                        if (opts.muted) {
                            try {
                                this.player.setMuted(true);
                            } catch {}
                        }

                        if (opts.autoplay) {
                            try {
                                this.player.play();
                                this.state.isPlaying.value = true;
                            } catch {}
                        }

                        opts.onReady?.();
                    },

                    onError: (e: any) => {
                        console.error("Panda error", e);
                    },
                });

                this.player.onEvent(({ message }: any) => {
                    if (message === "panda_play") {
                        this.state.isPlaying.value = true;

                        // 🔥 APLICA A VELOCIDADE NO PLAY NATIVO
                        if (this.targetRate !== 1) {
                            try {
                                if (this.player.setPlaybackRate) {
                                    this.player.setPlaybackRate(
                                        this.targetRate
                                    );
                                }
                            } catch (e) {}
                        }
                    }
                    if (message === "panda_pause") {
                        this.state.isPlaying.value = false;
                    }
                });

                setInterval(() => {
                    if (!this.ready) return;
                    try {
                        const t = this.player.getCurrentTime();
                        if (typeof t === "number") {
                            this.state.currentTime.value = t;
                            opts.onTimeUpdate?.(t);
                        }

                        // 🔥 VIGIA SE O ALUNO MEXEU NA ENGRENAGEM (Só enquanto o vídeo toca)
                        if (
                            this.state.isPlaying.value &&
                            this.player.getPlaybackRate
                        ) {
                            const r = this.player.getPlaybackRate();
                            if (r !== this.targetRate && r > 0) {
                                this.targetRate = r;
                                opts.onRateChange?.(r);
                            }
                        }
                    } catch {}
                }, 250);
            });
        });
    }

    async mount(): Promise<void> {}

    play(): void {
        if (!this.ready) return;

        // Aplica também no play forçado por código
        if (this.targetRate !== 1) {
            try {
                if (this.player.setPlaybackRate)
                    this.player.setPlaybackRate(this.targetRate);
            } catch (e) {}
        }

        this.player.play();
    }

    pause(): void {
        if (!this.ready) return;
        this.player.pause();
    }

    seek(time: number): void {
        if (!this.ready) return;
        this.player.setCurrentTime(time);
    }

    unmute(): void {
        if (!this.ready) return;
        try {
            if (this.player.unmute) this.player.unmute();
            if (this.player.setVolume) this.player.setVolume(1);
        } catch (e) {}
    }

    setSpeed(rate: number): void {
        this.targetRate = rate;
        if (!this.ready) return;
        try {
            if (this.player.setPlaybackRate) {
                this.player.setPlaybackRate(rate);
            }
        } catch (e) {}
    }

    destroy(): void {
        try {
            this.player.destroy?.();
        } catch {}
    }
}
