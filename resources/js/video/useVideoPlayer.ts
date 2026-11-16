import { ref, shallowRef } from "vue";
import type { UseVideoPlayerOptions, VideoAdapter } from "./types";
import { PlyrAdapter } from "./PlyrAdapter";
import { normalizePandaUrl, loadPandaScript, getVideoSource } from "./utils";

export const useVideoPlayer = (opts: UseVideoPlayerOptions) => {
    const adapter = shallowRef<VideoAdapter | null>(null);
    const currentVideoUrl = ref(opts.videoUrl);

    const handleFullscreenChange = () => {
        const fs = document.fullscreenElement;
        if (fs && fs.tagName === "IFRAME") {
            document.exitFullscreen();
        }
    };

    document.addEventListener("fullscreenchange", handleFullscreenChange);

    const destroyPlayer = () => {
        if (adapter.value) {
            adapter.value.destroy();
            adapter.value = null;
        }
        if (opts.containerRef.value) {
            opts.containerRef.value.innerHTML = "";
        }
    };

    const mount = async () => {
        const container = opts.containerRef.value;
        if (!container) return;
        if (!currentVideoUrl.value) return;

        destroyPlayer();

        const source = getVideoSource(currentVideoUrl.value);

        if (source.type === "panda") {
            let url = normalizePandaUrl(currentVideoUrl.value);
            const desiredControls = "none";

            // 🔥 Adicionado param de preload do Panda Video para antecipar pacotes HLS
            if (!url.includes("preload=")) {
                if (url.includes("?"))
                    url += `&preload=true&controls=${desiredControls}`;
                else url += `?preload=true&controls=${desiredControls}`;
            }

            const iframeId = "panda-" + Math.random().toString(36).slice(2);

            const iframe = document.createElement("iframe");
            iframe.id = iframeId;
            iframe.src = url;
            iframe.allow =
                "accelerometer; encrypted-media; gyroscope; autoplay;";
            iframe.allowFullscreen = false;
            iframe.setAttribute("playsinline", "true");
            iframe.setAttribute("webkit-playsinline", "true");
            iframe.style.width = "100%";
            iframe.style.height = "100%";
            iframe.style.border = "0";

            container.appendChild(iframe);

            iframe.addEventListener("dblclick", (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            await loadPandaScript();

            (window as any).pandascripttag =
                (window as any).pandascripttag || [];
            (window as any).pandascripttag.push(() => {
                const player = new (window as any).PandaPlayer(iframeId, {
                    onReady: () => {
                        try {
                            adapter.value!.state.duration.value =
                                player.getDuration?.() ?? 0;
                        } catch {}

                        const currentVueRate = opts.getPlaybackRate
                            ? opts.getPlaybackRate()
                            : 1;
                        console.log(
                            `[Panda Debug] onReady acionado. Enviando velocidade: ${currentVueRate}`
                        );
                        try {
                            if (player.setSpeed)
                                player.setSpeed(currentVueRate);
                        } catch (e) {}

                        opts.onReady?.();
                    },
                });

                const state = {
                    currentTime: ref(0),
                    duration: ref(0),
                    isPlaying: ref(false),
                };

                let isDestroyed = false;
                let rafId: number;

                // 🔥 MOTOR DE PRECISÃO DO PANDA: Milissegundos em tempo real
                const syncTime = () => {
                    if (isDestroyed) return;
                    try {
                        const t = player.getCurrentTime();
                        if (
                            typeof t === "number" &&
                            Math.abs(state.currentTime.value - t) > 0.015
                        ) {
                            state.currentTime.value = t;
                            opts.onTimeUpdate?.(t);
                        }
                    } catch (e) {}
                    rafId = requestAnimationFrame(syncTime);
                };
                rafId = requestAnimationFrame(syncTime);

                let syncTimer: any = null;

                player.onEvent(async (event: any) => {
                    const message = event.message;

                    if (message === "panda_play") {
                        state.isPlaying.value = true;
                        const desiredRate = opts.getPlaybackRate
                            ? opts.getPlaybackRate()
                            : 1;

                        let currentPandaRate: any = 1;
                        try {
                            if (player.getSpeed) {
                                const res = player.getSpeed();
                                currentPandaRate =
                                    res instanceof Promise ? await res : res;
                                if (
                                    typeof currentPandaRate === "object" &&
                                    currentPandaRate !== null
                                ) {
                                    currentPandaRate =
                                        currentPandaRate.speed ||
                                        currentPandaRate.rate ||
                                        1;
                                }
                            }
                        } catch (e) {}

                        if (
                            Number(currentPandaRate) !== desiredRate &&
                            !isNaN(Number(desiredRate))
                        ) {
                            if (player.setSpeed) player.setSpeed(desiredRate);
                        }
                    } else if (message === "panda_pause") {
                        state.isPlaying.value = false;
                    } else if (message === "panda_speed_update") {
                        try {
                            let rawRate: any =
                                event.speed || event.rate || event.data;
                            if (!rawRate && player.getSpeed) {
                                const res = player.getSpeed();
                                rawRate =
                                    res instanceof Promise ? await res : res;
                                if (
                                    typeof rawRate === "object" &&
                                    rawRate !== null
                                ) {
                                    rawRate = rawRate.speed || rawRate.rate;
                                }
                            }
                            const actualRate = Number(rawRate);
                            if (!isNaN(actualRate) && actualRate > 0) {
                                opts.onRateChange?.(actualRate);
                            }
                        } catch (e) {}
                    }
                });

                adapter.value = {
                    mount: async () => {},
                    play: () => {
                        const rate = opts.getPlaybackRate
                            ? opts.getPlaybackRate()
                            : 1;
                        if (player.setSpeed) {
                            try {
                                player.setSpeed(rate);
                            } catch (e) {}
                        }
                        try {
                            player.play();
                        } catch (e) {}
                        state.isPlaying.value = true;
                    },
                    pause: () => {
                        player.pause();
                        state.isPlaying.value = false;
                    },
                    seek: (t: number) => player.setCurrentTime(t),
                    unmute: () => {
                        try {
                            if (player.unmute) player.unmute();
                            if (player.setVolume) player.setVolume(1);
                        } catch (e) {}
                    },
                    mute: () => {
                        try {
                            if (player.mute) player.mute();
                            if (player.setVolume) player.setVolume(0);
                        } catch (e) {}
                    },
                    setVolume: (v: number) => {
                        try {
                            if (player.setVolume) player.setVolume(v);
                            if (player.unmute && v > 0) player.unmute();
                            if (player.mute && v === 0) player.mute();
                        } catch (e) {}
                    },
                    setSpeed: (rate: number) => {
                        try {
                            if (player.setSpeed) player.setSpeed(rate);
                        } catch (e) {}
                    },
                    destroy: () => {
                        isDestroyed = true;
                        if (rafId) cancelAnimationFrame(rafId);
                        try {
                            player.destroy();
                        } catch (e) {}
                    },
                    state,
                };
            });
            return;
        }

        const initialPlyrRate = opts.getPlaybackRate
            ? opts.getPlaybackRate()
            : opts.initialRate || 1;

        adapter.value = new PlyrAdapter(
            container,
            source as { type: "youtube" | "vimeo" | "mp4"; src: string },
            opts.onTimeUpdate,
            opts.onReady,
            opts.onRateChange,
            initialPlyrRate
        );
    };

    const loadVideo = (
        newUrl: string,
        startAt: number = 0,
        autoplay: boolean = true
    ): Promise<void> => {
        return new Promise((resolve) => {
            console.log(
                `[DoubleBuffer] Iniciando loadVideo para: ${newUrl} | Autoplay: ${autoplay}`
            );
            currentVideoUrl.value = newUrl;

            const originalOnReady = opts.onReady;

            opts.onReady = () => {
                const currentVueRate = opts.getPlaybackRate
                    ? opts.getPlaybackRate()
                    : 1;
                if (adapter.value && adapter.value.setSpeed) {
                    (adapter.value as any).setSpeed(currentVueRate);
                }

                adapter.value?.seek(startAt);

                // 🔥 LIMPEZA: O componente Vue é 100% responsável por gerenciar o Play.
                // O adapter apenas reporta que o Iframe montou e está pronto.
                if (originalOnReady) {
                    opts.onReady = originalOnReady;
                    originalOnReady();
                }
                resolve();
            };

            mount();
        });
    };

    return {
        mount,
        loadVideo,
        play: () => adapter.value?.play(),
        pause: () => adapter.value?.pause(),
        seek: (t: number) => adapter.value?.seek(t),
        unmute: () => adapter.value?.unmute?.(),
        mute: () => adapter.value?.mute?.(), // 🔥 LINHA ADICIONADA AQUI
        setVolume: (v: number) => {
            if (adapter.value && "setVolume" in adapter.value) {
                (adapter.value as any).setVolume(v);
            }
        },
        setSpeed: (rate: number) => {
            if (adapter.value && "setSpeed" in adapter.value) {
                (adapter.value as any).setSpeed(rate);
            }
        },
        destroy: () => {
            document.removeEventListener(
                "fullscreenchange",
                handleFullscreenChange
            );
            destroyPlayer();
        },
        state: adapter,
    };
};
