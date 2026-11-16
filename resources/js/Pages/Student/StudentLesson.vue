<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount, watch } from "vue";
import { useVideoPlayer } from "@/video/useVideoPlayer";
import { resolveResponse } from "@/utils/resolveResponse";
import TitleHotspot from "@/Components/TitleHotspot.vue";
import FeedbackHotspot from "@/Components/FeedbackHotspot.vue";
import ConfirmationHotspot from "@/Components/ConfirmationHotspot.vue";
import axios from "axios";

import { Play, Pause, SkipBack, SkipForward, Maximize2, X, Loader2, Volume2, VolumeX, RotateCcw, RotateCw } from "lucide-vue-next";
import { useFullscreen } from "@/Composables/useFullscreen";
import { Button } from "@/components/ui/button";

const props = defineProps<{ course: any; module: any; lesson: any }>();
const currentPlaybackRate = ref(1);

const currentVolume = ref(1);
const previousVolume = ref(1);
const isMuted = ref(false);

const setGlobalVolume = (val: number) => {
    currentVolume.value = val;
    isMuted.value = val === 0;
    activeVideo.value.setVolume?.(val);
    standbyVideo.value.setVolume?.(val);
};

const toggleMute = () => {
    if (isMuted.value) {
        setGlobalVolume(previousVolume.value > 0 ? previousVolume.value : 1);
    } else {
        previousVolume.value = currentVolume.value;
        setGlobalVolume(0);
    }
};

// =========================================================================
// 🔥 CACHE EM RAM (ZERO LATÊNCIA NA AWS) 🔥
// =========================================================================
const lessonDataCache = new Map<number, Promise<any>>();
const publicPublicationPath = window.location.pathname.match(/^\/p\/[^/]+$/) ? window.location.pathname : null;

const fetchLessonData = (lessonId: number) => {
    if (!lessonDataCache.has(lessonId)) {
        const url = publicPublicationPath
            ? `${publicPublicationPath}/lessons/${lessonId}`
            : `/api/courses/${props.course.id}/lessons/${lessonId}/data`;
        const request = axios.get(url)
            .then(res => res.data.lesson)
            .catch(err => {
                lessonDataCache.delete(lessonId);
                throw err;
            });
        lessonDataCache.set(lessonId, request);
    }
    return lessonDataCache.get(lessonId);
};

// =========================================================================
// 🔥 ARQUITETURA DOUBLE BUFFERING 🔥
// =========================================================================
const activePlayerId = ref<'A' | 'B'>('A');
const zIndexA = ref(20);
const zIndexB = ref(10);
const opacityA = ref(1);
const opacityB = ref(0);
const lessonA = ref(props.lesson);
const lessonB = ref<any>(null);

const currentTimeA = ref(0);
const durationA = ref(0);
const currentTimeB = ref(0);
const durationB = ref(0);

const playerContainerA = ref<HTMLElement | null>(null);
const playerContainerB = ref<HTMLElement | null>(null);
const stageRef = ref<HTMLElement | null>(null);

const currentLesson = computed(() => activePlayerId.value === 'A' ? lessonA.value : lessonB.value);
const currentTime = computed(() => {
    const playerState = activePlayerId.value === 'A' ? videoA.state.value?.state : videoB.state.value?.state;
    const activeState = playerState || activeVideo.value?.state.value?.state;
    return activeState ? activeState.currentTime.value : 0;
});

const duration = computed(() => {
    const playerState = activePlayerId.value === 'A' ? videoA.state.value?.state : videoB.state.value?.state;
    const activeState = playerState || activeVideo.value?.state.value?.state;
    return activeState ? activeState.duration.value : 0;
});

const isPlayerReady = ref(false);
const isLoadingBranch = ref(false);

const showSpinner = ref(false);
let spinnerTimer: any = null;
watch(isLoadingBranch, (val) => {
    if (val) {
        spinnerTimer = setTimeout(() => { showSpinner.value = true; }, 800);
    } else {
        if (spinnerTimer) clearTimeout(spinnerTimer);
        showSpinner.value = false;
    }
});
const hasForcedInitialStart = ref(false);
const isPandaVideo = computed(() => {
    const url = currentLesson.value?.video_url || '';
    return url.includes('panda') || url.includes('b-vz');
});
const isLoopBlinking = ref(false);
const showSpeedMenu = ref(false);
const availableSpeeds = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];

const setPlaybackSpeed = (rate: number) => {
    currentPlaybackRate.value = rate;
    activeVideo.value.setSpeed?.(rate);
    standbyVideo.value.setSpeed?.(rate);
    showSpeedMenu.value = false;
};

const videoA = useVideoPlayer({
    containerRef: playerContainerA,
    videoUrl: lessonA.value?.video_url,
    getPlaybackRate: () => currentPlaybackRate.value,
    onRateChange: (rate) => { currentPlaybackRate.value = rate; },
    onTimeUpdate: (t) => {
        currentTimeA.value = t;
        if (!durationA.value && videoA.state.value?.state.duration.value) durationA.value = videoA.state.value.state.duration.value;
        if (activePlayerId.value === 'A') checkInteractions(t);
    },
    onReady: () => {
        if (activePlayerId.value === 'A') {
            isPlayerReady.value = true;
            isLoadingBranch.value = false;
            // 🔥 APLICAÇÃO FORÇADA DE VELOCIDADE
            videoA.setSpeed?.(currentPlaybackRate.value);

            if (!hasForcedInitialStart.value) {
                hasForcedInitialStart.value = true;
                setTimeout(() => { genericSeek(0); }, 100);
            }
        }
    },
});

const videoB = useVideoPlayer({
    containerRef: playerContainerB,
    videoUrl: "",
    getPlaybackRate: () => currentPlaybackRate.value,
    onRateChange: (rate) => { currentPlaybackRate.value = rate; },
    onTimeUpdate: (t) => {
        currentTimeB.value = t;
        if (!durationB.value && videoB.state.value?.state.duration.value) durationB.value = videoB.state.value.state.duration.value;
        if (activePlayerId.value === 'B') checkInteractions(t);
    },
    onReady: () => {
        if (activePlayerId.value === 'B') {
            isPlayerReady.value = true;
            isLoadingBranch.value = false;
            // 🔥 APLICAÇÃO FORÇADA DE VELOCIDADE
            videoB.setSpeed?.(currentPlaybackRate.value);
        }
    },
});

const activeVideo = computed(() => activePlayerId.value === 'A' ? videoA : videoB);
const standbyVideo = computed(() => activePlayerId.value === 'A' ? videoB : videoA);

const getShapeClipPath = (shape?: string) => {
    switch (shape) {
        case 'circle': return 'ellipse(50% 50% at 50% 50%)';
        case 'triangle': return 'polygon(50% 0%, 0% 100%, 100% 100%)';
        case 'trapezoid': return 'polygon(20% 0%, 80% 0%, 100% 100%, 0% 100%)';
        case 'parallelogram': return 'polygon(25% 0%, 100% 0%, 75% 100%, 0% 100%)';
        case 'star': return 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)';
        case 'hexagon': return 'polygon(25% 0%, 75% 0%, 100% 50%, 75% 100%, 25% 100%, 0% 50%)';
        case 'rectangle':
        default: return 'none';
    }
};

const activeQuestion = ref<any>(null);
const activeFeedback = ref<{ type: "correct" | "wrong"; config: any; } | null>(null);

const selectedIndices = ref<number[]>([]);
const droppedItems = ref<Record<number, number[]>>({});
const draggedItemIndex = ref<number | null>(null);
const revealedOptions = ref<number[]>([]);

let feedbackTimeout: number | null = null;
const pendingFeedbackAction = ref<{ action: string | null, seekTo: number | null, targetLessonId: number | null, question: any } | null>(null);

const isInteractionActive = computed(() => !!activeQuestion.value || !!activeFeedback.value);
let isResumingFromInteraction = false;
let isLoopingBack = false;

const isSeeking = ref(false);
const interactionGateTime = ref(0);
const validInteractionFrom = ref(-1);
const lastSeekTarget = ref(-1);
const lastSeenTime = ref(-1);
let isProgrammaticSeek = false;
let lastLoopSeekTime = 0;

// Remova a declaração de lastResolvedQuestionId completamente, ela não será mais usada.
// Substitua seu resetUI atual por este:
const resetUI = () => {
    activeQuestion.value = null;
    activeFeedback.value = null;
    pendingFeedbackAction.value = null;
    isLoopingBack = false;
    lastSeenTime.value = -1;
    validInteractionFrom.value = -1; // Libera tudo
    lastSeekTarget.value = -1;
    isProgrammaticSeek = false;
    interactionGateTime.value = 0;
};

const hasClickableZones = (q: any): boolean => {
    if (!q?.options) return false;
    return q.options.some((o: any) => o.coordinates && o.coordinates.width > 0 && o.coordinates.height > 0);
};

const formatTimeHMS = (seconds: number) => {
    if (isNaN(seconds) || seconds < 0) return "00:00";
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    if (h > 0) return `${h}:${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
};

const DEFAULT_FEEDBACK_CONFIG = () => ({
    text: "", text_color: "#000000", text_font_size: 20, text_font_family: "Arial, sans-serif",
    text_align: "center", vertical_align: "center", background_color: "#ffffff", background_opacity: 0.9,
    background_image: null, background_image_preview: null, coordinates: { top: 50, left: 50, width: 40, height: 20, radius: 12 }, duration_seconds: 1.5,
});

const DEFAULT_CONFIRMATION_CONFIG = () => ({
    text: "", text_color: "#000000", text_font_size: 16, text_font_family: "Arial, sans-serif", text_align: "center", vertical_align: "center",
    background_color: "#ffffff", background_opacity: 0.9, background_image: null, background_image_preview: null,
    coordinates: { top: 30, left: 35, width: 30, height: 18, radius: 12 },
    button: { text: "Confirmar", text_color: "#000000", text_font_size: 14, text_font_family: "Arial, sans-serif", text_align: "center", vertical_align: "center", background_color: "#f59e0b", background_opacity: 1, coordinates: { top: 50, left: 40, width: 20, height: 8, radius: 8 } },
});

function normalizeFeedbackConfig(raw: any) {
    const def = DEFAULT_FEEDBACK_CONFIG();
    if (!raw) return JSON.parse(JSON.stringify(def));
    return { ...def, ...raw, coordinates: { ...def.coordinates, ...(raw.coordinates || {}), }, };
}

function normalizeConfirmationConfig(raw: any) {
    const def = DEFAULT_CONFIRMATION_CONFIG();
    if (!raw) return JSON.parse(JSON.stringify(def));
    return {
        ...def,
        ...raw,
        coordinates: { ...def.coordinates, ...(raw.coordinates || {}), },
        button: {
            ...def.button,
            ...(raw.button || {}),
            coordinates: { ...def.button.coordinates, ...(raw.button?.coordinates || {}) }
        },
    };
}

function normalizePreviewQuestion(q: any) {
    try {
        let loop_start = q.loop_start_seconds; let loop_end = q.loop_end_seconds;
        if (loop_start == null && loop_end == null && q.loop_seconds > 0) { loop_start = q.timestamp_seconds - q.loop_seconds; loop_end = q.timestamp_seconds; }

        const safeParse = (val: any) => {
            if (typeof val === 'string') {
                try { return JSON.parse(val); } catch (e) { return {}; }
            }
            return val || {};
        };

        let correctCfg = safeParse(q.correct_feedback_config);
        let wrongCfg = safeParse(q.wrong_feedback_config);
        let confCfg = safeParse(q.confirmation_config);

        return {
            ...q,
            loop_start_seconds: loop_start, loop_end_seconds: loop_end,
            correct_feedback_config: normalizeFeedbackConfig({ ...correctCfg, background_image: q.correct_feedback_image ?? null, }),
            wrong_feedback_config: normalizeFeedbackConfig({ ...wrongCfg, background_image: q.wrong_feedback_image ?? null, }),
            confirmation_config: normalizeConfirmationConfig({ ...confCfg, background_image: q.confirmation_image ?? null, }),
            options: (q.options || []).map((o: any) => {
                let isZone = o.is_zone == 1 || o.is_zone === true;
                let coords = o.coordinates;
                if (typeof coords === 'string') {
                    try { coords = JSON.parse(coords); } catch (e) { coords = null; }
                }

                return {
                    ...o,
                    is_zone: isZone,
                    _is_zone: isZone,
                    coordinates: coords ? { top: Number(coords.top), left: Number(coords.left), width: Number(coords.width), height: Number(coords.height), radius: coords.radius != null ? Number(coords.radius) : (isZone ? 0 : 8) } : null,
                    shape: o.shape || 'rectangle',
                    zone_image: o.zone_image || o.zone_image_url || null,
                    image_url: o.image_url || null,
                };
            }),
        };
    } catch (error) {
        console.error("Erro ao normalizar pergunta:", error);
        return q;
    }
}

//const lastResolvedQuestionId = ref<number | null>(null);

const requestSeek = (delta: number) => {
    if (isInteractionActive.value || isLoadingBranch.value) return;

    let targetTime = currentTime.value + delta;
    targetTime = Math.max(0, Math.min(targetTime, duration.value));

    if (delta > 0) {
        const pendingInteractions = currentLesson.value?.questions?.filter((q: any) => {
            const qTime = Number(q.timestamp_seconds);
            return qTime > currentTime.value && qTime <= targetTime;
        }) || [];

        if (pendingInteractions.length > 0) {
            pendingInteractions.sort((a: any, b: any) => Number(a.timestamp_seconds) - Number(b.timestamp_seconds));
            targetTime = Number(pendingInteractions[0].timestamp_seconds) - 0.2;
        }
    }

    if (delta < 0) {
        //lastResolvedQuestionId.value = null;
        isLoopingBack = false;
    }

    genericSeek(targetTime);
};

let activePreloadTask: Promise<void> | null = null;
let activePreloadTarget: number | null = null;

// 🔥 AGORA UTILIZA O CACHE DE RAM (fetchLessonData)
// 1. Modifique a assinatura adicionando o 'seekToTime'
const preloadBranch = async (rawLessonId: number | string | null | undefined, seekToTime: number = 0) => {
    const lessonId = Number(rawLessonId);

    if (!lessonId || Number(lessonA.value?.id) === lessonId || Number(lessonB.value?.id) === lessonId) return;

    if (activePreloadTask && activePreloadTarget === lessonId) return activePreloadTask;
    if (activePreloadTask) await activePreloadTask;

    activePreloadTarget = lessonId;

    activePreloadTask = (async () => {
        try {
            const targetLessonData = await fetchLessonData(lessonId);

            if (activePlayerId.value === 'A') lessonB.value = targetLessonData;
            else lessonA.value = targetLessonData;

            // 🔥 A MÁGICA: O standby agora faz o buffer do trecho exato do salto
            await standbyVideo.value.loadVideo(targetLessonData.video_url, seekToTime, false);
        } catch (e) {
            console.error("[Preload] Falha", e);
        } finally {
            activePreloadTask = null;
            activePreloadTarget = null;
        }
    })();

    return activePreloadTask;
};

// 🔥 SCANNER AGRESSIVO: Alimenta o Cache RAM imediatamente
watch(currentTime, (newTime) => {
    // 🔥 PRELOAD PREVENTIVO PARA TRANSIÇÕES (Look-ahead de 10s)
    if (currentLesson.value?.questions && !isLoadingBranch.value) {
        const upcoming = currentLesson.value.questions.find((q: any) => {
            const qTime = Number(q.timestamp_seconds);
            return qTime > newTime && (qTime - newTime) <= 10;
        });
        if (upcoming) {
            if (upcoming.correct_target_lesson_id && Number(upcoming.correct_target_lesson_id) !== Number(currentLesson.value.id)) {
                // Passa o tempo exato para o preload
                preloadBranch(upcoming.correct_target_lesson_id, upcoming.correct_seek_to || 0);
            }
            if (upcoming.wrong_target_lesson_id && Number(upcoming.wrong_target_lesson_id) !== Number(currentLesson.value.id)) {
                // Passa o tempo exato para o preload
                preloadBranch(upcoming.wrong_target_lesson_id, upcoming.wrong_seek_to || 0);
            }
        }
    }

    if (!activeQuestion.value) return;
    const q = activeQuestion.value;

    // 1. Revelar opções baseado no tempo
    const elapsed = Number(newTime) - Number(q.timestamp_seconds);
    q.options?.forEach((opt: any, idx: number) => {
        if (!revealedOptions.value.includes(idx)) {
            if (elapsed >= Number(opt.delay_seconds || 0) - 0.1) revealedOptions.value.push(idx);
        }
    });

    // 2. Motor de Loop Sincronizado
    const loopStart = q.loop_start_seconds != null ? Number(q.loop_start_seconds) : null;
    const loopEnd = q.loop_end_seconds != null ? Number(q.loop_end_seconds) : null;

    if (loopStart !== null && loopEnd !== null && loopEnd > loopStart) {
        // Tolerância aumentada para 0.2s para evitar corrida de buffers
        if (newTime >= loopEnd - 0.08) {
            if (!isLoopingBack) {
                isLoopingBack = true;

                // 1. Comando de Seek (o genericSeek já lida com o estado)
                genericSeek(loopStart, false);

                // 2. Garante o Play apenas após o Iframe confirmar a posição
                // Usamos o nextTick para assegurar que o DOM/Iframe foi atualizado
                nextTick(() => {
                    activeVideo.value.play();

                    if (isMuted.value) activeVideo.value.mute?.();
                    else activeVideo.value.setVolume?.(currentVolume.value);

                    // Reseta a trava apenas após garantir que o comando foi aceito
                    setTimeout(() => { isLoopingBack = false; }, 500);
                });
            }
        }
    }
});

const checkInteractions = (time: number) => {
    // 1. Controle de Gap Adaptativo (Protege contra velocidade 2x e falhas de buffer)
    const speedMultiplier = Math.max(1, currentPlaybackRate.value);
    const gapTolerance = 0.8 * speedMultiplier;

    if (lastSeenTime.value !== -1 && Math.abs(time - lastSeenTime.value) > gapTolerance) {
        if (isProgrammaticSeek) {
            isProgrammaticSeek = false;
        } else {
            lastSeekTarget.value = time;
            if (time < lastSeenTime.value) {
                // SE O ALUNO VOLTOU O VÍDEO: Destrava as perguntas para poder responder de novo!
                validInteractionFrom.value = -1;
                if (activeQuestion.value && time < Number(activeQuestion.value.timestamp_seconds) && !isLoopingBack) {
                    resetUI();
                }
            } else {
                validInteractionFrom.value = time;
            }
        }
    }
    lastSeenTime.value = time;

    if (lastSeekTarget.value !== -1 && time > lastSeekTarget.value) lastSeekTarget.value = -1;

    if (Date.now() < interactionGateTime.value) return;
    if (activeFeedback.value) return;

    if (activeQuestion.value) {
        const q = activeQuestion.value;
        const loopStart = q.loop_start_seconds != null ? Number(q.loop_start_seconds) : null;
        const loopEnd = q.loop_end_seconds != null ? Number(q.loop_end_seconds) : null;
        const hasLoop = loopStart !== null && loopEnd !== null && loopEnd > loopStart;

        if (hasLoop) return;
        else {
            if (isPlaying.value) activeVideo.value.pause();
            return;
        }
    }

    if (isInteractionActive.value || isLoadingBranch.value) return;
    if (!currentLesson.value || !currentLesson.value.questions) return;

    // 2. DETECÇÃO ROBUSTA (A Janela Base que já funcionava, mas agora imune ao 2x)
    const hit = currentLesson.value.questions.find((q: any) => {
        const qTime = Number(q.timestamp_seconds);

        // Bloqueio temporário da pergunta recém-respondida (só destrava se o aluno voltar o vídeo)
        if (validInteractionFrom.value !== -1 && qTime < validInteractionFrom.value) return false;

        const diff = time - (qTime - 0.15);
        // Em 1x = janela de 0.6s. Em 2x = janela de 1.2s. Impossível o player pular a interação.
        return diff >= 0 && diff < (0.6 * speedMultiplier);
    });

    if (!hit) return;

    if (hit.type === "automatic") {
        validInteractionFrom.value = time + 0.5;

        if (hit.correct_target_lesson_id && Number(hit.correct_target_lesson_id) !== Number(currentLesson.value.id)) {
            branchToLesson(hit.correct_target_lesson_id, hit.correct_seek_to || 0);
            return;
        }

        if (hit.correct_action === "seek" && hit.correct_seek_to != null) {
            genericSeek(Number(hit.correct_seek_to), true);
            // 🔥 REMOVA o nextTick e o activeVideo.value.play() que estavam aqui.
            // O genericSeek agora cuida de tudo sozinho.
            setTimeout(() => {
                if (isMuted.value) activeVideo.value.mute?.();
                else activeVideo.value.setVolume?.(currentVolume.value);
            }, 150);
        }
        return;
    }

    try {
        activeQuestion.value = normalizePreviewQuestion(hit);

        if (['info', 'single_choice', 'multiple_choice', 'drag_and_drop'].includes(activeQuestion.value.type)) {
            const targetsToLoad = new Set<number>();
            if (activeQuestion.value.correct_target_lesson_id) targetsToLoad.add(Number(activeQuestion.value.correct_target_lesson_id));
            if (activeQuestion.value.wrong_target_lesson_id) targetsToLoad.add(Number(activeQuestion.value.wrong_target_lesson_id));

            if (activeQuestion.value.options) {
                activeQuestion.value.options.forEach((o: any) => {
                    if (o.correct_target_lesson_id) targetsToLoad.add(Number(o.correct_target_lesson_id));
                    if (o.wrong_target_lesson_id) targetsToLoad.add(Number(o.wrong_target_lesson_id));
                });
            }

            targetsToLoad.delete(Number(currentLesson.value.id));

            if (targetsToLoad.size > 0) {
                preloadBranch(Array.from(targetsToLoad)[0]);
            }
        }
    } catch (e) { return; }

    selectedIndices.value = [];
    droppedItems.value = {};
    revealedOptions.value = [];

    const qNorm = activeQuestion.value;
    const hasLoop = qNorm.loop_start_seconds != null && qNorm.loop_end_seconds != null && Number(qNorm.loop_end_seconds) > Number(qNorm.loop_start_seconds);

    activeQuestion.value.options?.forEach((opt: any, idx: number) => {
        if (!hasLoop || Number(opt.delay_seconds || 0) <= 0) {
            revealedOptions.value.push(idx);
        }
    });

    if (!hasLoop) {
        activeVideo.value.pause();
        const exactTime = Number(hit.timestamp_seconds);
        if (Math.abs(time - exactTime) > 0.05) {
            // A CORREÇÃO DE OURO: Usar genericSeek blinda a UI de ser apagada pelo Anti-Cheat!
            genericSeek(exactTime, true);
        }
    }
};



const rawIsPlaying = computed(() => activeVideo.value.state.value?.state.isPlaying.value ?? false);
const isPlaying = ref(false);
let playFlickerTimer: any = null;

watch(rawIsPlaying, (newVal) => {
    if (newVal) {
        if (playFlickerTimer) clearTimeout(playFlickerTimer);
        isPlaying.value = true;
    } else {
        playFlickerTimer = setTimeout(() => {
            if (!rawIsPlaying.value) isPlaying.value = false;
        }, 250);
    }
}, { immediate: true });

const togglePlay = () => {
    if (isInteractionActive.value || isLoadingBranch.value) return;
    isPlaying.value ? activeVideo.value.pause() : activeVideo.value.play();
};

const genericSeek = (time: number, fromInteraction = false) => {
    if (isLoadingBranch.value) return;

    const d = Number(duration.value);
    const targetTime = Number(time);
    const t = d > 0 ? Math.max(0, Math.min(targetTime, d)) : Math.max(0, targetTime);

    // MANTÉM TODA A SUA LÓGICA DE ESTADO INTOCÁVEL
    isProgrammaticSeek = true;
    validInteractionFrom.value = t;
    lastSeenTime.value = t;
    lastSeekTarget.value = t;
    interactionGateTime.value = 0;

    activeVideo.value.seek(t);

    if (activePlayerId.value === 'A') currentTimeA.value = t;
    else currentTimeB.value = t;

    // 🔥 O ÚNICO ACRÉSCIMO SEGURO: A proteção do YouTube integrada à sua variável fromInteraction
    if (fromInteraction) {
        setTimeout(() => {
            // Só forçamos o play se o YouTube já não estiver rodando o vídeo sozinho
            if (!isPlaying.value) {
                activeVideo.value.play();
            }
        }, 50);
    }
};

onMounted(async () => {
    resetUI();
    currentVolume.value = 1;
    currentPlaybackRate.value = 1;
    await nextTick();
    await videoA.mount();
    window.addEventListener("keydown", handleKey);
});

onBeforeUnmount(() => {
    videoA.destroy();
    videoB.destroy();
    window.removeEventListener("keydown", handleKey);
});

const handleKey = (e: KeyboardEvent) => {
    if (isInteractionActive.value || isLoadingBranch.value) return;
    if (e.code === "Space") { e.preventDefault(); togglePlay(); }
    else if (e.code === "ArrowLeft") requestSeek(-10);
    else if (e.code === "ArrowRight") requestSeek(10);
};

const progressPercentage = computed(() => {
    const d = duration.value;
    if (!d || d === 0) return 0;
    return (currentTime.value / d) * 100;
});

const toggleSelection = (i: number) => {
    const q = activeQuestion.value;
    if (!q) return;

    if (['single_choice', 'info'].includes(q.type)) {
        selectedIndices.value = [i];

        if (q.type === 'info') {
            if (!q.options || q.options.length <= 1) {
                submitAnswer();
            }
        }
    } else {
        selectedIndices.value.includes(i) ? (selectedIndices.value = selectedIndices.value.filter((x) => x !== i)) : selectedIndices.value.push(i);
    }
};

const onDragStart = (e: DragEvent, i: number) => (draggedItemIndex.value = i);
const onDrop = (z: number) => { if (draggedItemIndex.value === null) return; if (!droppedItems.value[z]) droppedItems.value[z] = []; if (!droppedItems.value[z].includes(draggedItemIndex.value)) { for (const k in droppedItems.value) droppedItems.value[k] = droppedItems.value[k].filter((x) => x !== draggedItemIndex.value); droppedItems.value[z].push(draggedItemIndex.value); } draggedItemIndex.value = null; };
const resetDrop = (z: number, i: number) => (droppedItems.value[z] = droppedItems.value[z].filter((x) => x !== i));
const isItemDropped = (i: number) => Object.values(droppedItems.value).flat().includes(i);

const shouldShowQuestionModal = computed(() => {
    const q = activeQuestion.value;
    if (!q) return false;
    if (activeFeedback.value) return false;
    if (q.type === "automatic") return false;
    if (q.type === "drag_and_drop") return false;

    if (q.type === "info") {
        if (!q.options || q.options.length === 0) return false;
        return !hasClickableZones(q);
    }

    if (["single_choice", "multiple_choice"].includes(q.type)) {
        return !hasClickableZones(q);
    }

    return false;
});

function shouldShowConfirmation(q: any): boolean {
    if (!q || !q.confirmation_config) return false;
    if (activeFeedback.value) return false;

    if (q.type === "info") {
        if (q.options && q.options.length > 1) {
            return selectedIndices.value.length > 0;
        }
        return false;
    }

    if (["single_choice", "multiple_choice"].includes(q.type)) return selectedIndices.value.length > 0;
    if (q.type === "drag_and_drop") return Object.values(droppedItems.value).some((items) => Array.isArray(items) && items.length > 0);
    return false;
}

function canConfirm(q: any): boolean {
    if (!q) return false;
    if (activeFeedback.value) return false;
    if (q.type === "drag_and_drop") {
        const zoneIndexes = q.options.map((o: any, idx: number) => (o.is_zone ? idx : null)).filter((v: number | null) => v !== null) as number[];
        if (zoneIndexes.length === 0) return false;
        return zoneIndexes.every((zoneIdx) => Array.isArray(droppedItems.value[zoneIdx]) && droppedItems.value[zoneIdx].length > 0);
    }
    if (["single_choice", "multiple_choice", "info"].includes(q.type)) {
        if (q.type === 'info' && (!q.options || q.options.length === 0)) return true;
        return selectedIndices.value.length > 0;
    }
    return false;
}

const continueAfterInteraction = (q: any) => {
    const hasLoop = q.loop_start_seconds != null && q.loop_end_seconds != null && Number(q.loop_end_seconds) > Number(q.loop_start_seconds);

    // MÁGICA: Impede que a interação reabra no mesmo frame pós-resposta, mas deixa ela livre se o aluno voltar o vídeo depois.
    validInteractionFrom.value = Number(currentTime.value) + 0.5;
    isLoopingBack = false;

    // Limpa a tela antes do play
    activeQuestion.value = null;

    if (hasLoop) {
        const targetTime = Number(q.timestamp_seconds) > 0 ? Number(q.timestamp_seconds) + 0.2 : Number(currentTime.value) + 0.2;
        genericSeek(targetTime, true);
    }

    // 🔥 Troque o activeVideo.value.play() bruto por isto:
    setTimeout(() => {
        if (!isPlaying.value) activeVideo.value.play();
    }, 150);
};


// =========================================================================
// 🔥 TRANSIÇÃO BLINDADA COM CACHE E DISJUNTOR ANTI-FREEZE 🔥
// =========================================================================
// =========================================================================
// 🔥 TRANSIÇÃO BLINDADA COM CACHE E DISJUNTOR ANTI-FREEZE 🔥
// =========================================================================
const branchToLesson = async (rawLessonId: any, seekToTime: number = 0) => {
    const lessonId = Number(rawLessonId);
    if (!lessonId) return;

    if (Number(currentLesson.value.id) === lessonId) {
        resetUI();
        genericSeek(seekToTime, true);
        if (!isPlaying.value) {
            activeVideo.value.play();
            setTimeout(() => {
                if (isMuted.value) activeVideo.value.mute?.();
                else activeVideo.value.setVolume?.(currentVolume.value);
            }, 150);
        }
        return;
    }

    if (activePreloadTask && activePreloadTarget === lessonId) {
        isLoadingBranch.value = true;
        await activePreloadTask;
    }

    const isVideoInA = Number(lessonA.value?.id) === lessonId;
    const isVideoInB = Number(lessonB.value?.id) === lessonId;

    if (isVideoInA || isVideoInB) {
        isLoadingBranch.value = true;

        const targetPlayerId = isVideoInA ? 'A' : 'B';
        const targetVideo = targetPlayerId === 'A' ? videoA : videoB;
        const previousVideo = activeVideo.value;

        targetVideo.setSpeed(currentPlaybackRate.value);
        if (isMuted.value) targetVideo.mute?.();
        else targetVideo.setVolume?.(currentVolume.value);
        resetUI();

        const dynamicGate = 1000 / currentPlaybackRate.value;
        interactionGateTime.value = Date.now() + dynamicGate;

        if (targetPlayerId === 'A') currentTimeA.value = seekToTime;
        else currentTimeB.value = seekToTime;

        targetVideo.seek(seekToTime);
        targetVideo.setSpeed(currentPlaybackRate.value);

        const targetZ = targetPlayerId === 'A' ? zIndexA : zIndexB;
        const currentZ = targetPlayerId === 'A' ? zIndexB : zIndexA;
        const targetOpacity = targetPlayerId === 'A' ? opacityA : opacityB;
        const currentOpacity = targetPlayerId === 'A' ? opacityB : opacityA;

        targetZ.value = 10;
        currentZ.value = 20;
        targetOpacity.value = 1;

        targetVideo.mute?.();
        targetVideo.play();

        let checkInterval: any;
        let fallbackTimeout: any;
        let swapFired = false;

        const executeSwap = () => {
            if (swapFired) return;
            swapFired = true;
            clearInterval(checkInterval);
            clearTimeout(fallbackTimeout);

            // 🔥 Apenas silenciamos o vídeo. REMOVA o previousVideo.pause() daqui!
            if (previousVideo.setVolume) {
                previousVideo.setVolume(0);
            }
            previousVideo.mute?.();

            // Definição do novo player...
            activePlayerId.value = targetPlayerId; // ou nextPlayerId no caso do catch/fallback

            if (isMuted.value) targetVideo.mute?.();
            else targetVideo.setVolume?.(currentVolume.value);
            targetVideo.setSpeed?.(currentPlaybackRate.value);

            // Inicia a transição CSS suave
            currentOpacity.value = 0;
            isLoadingBranch.value = false;

            // ⏱️ O SEGREDO DO CROSSFADE: O vídeo velho continua rodando visualmente até "sumir"
            setTimeout(() => {
                targetZ.value = 20;
                currentZ.value = 10;

                // 🔥 Aumentamos o delay para 1200ms.
                // Quando o pause do YouTube for acionado, o vídeo já sumiu da tela há muito tempo.
                previousVideo.pause();
            }, 1200);

            // Garante que o novo vídeo comece caso estivesse em Standby
            setTimeout(() => {
                if (!isPlaying.value) activeVideo.value.play();
            }, 100);
        };
        checkInterval = setInterval(() => {
            const state = targetVideo.state.value?.state;
            if (!state) return;
            // Só faz o crossfade quando houver garantias matemáticas de que há frames renderizando
            if (state.isPlaying.value && state.currentTime.value > seekToTime + 0.05) {
                executeSwap();
            }
        }, 20);

        fallbackTimeout = setTimeout(executeSwap, 2500);
        return;
    }

    // Fallback: Carregamento a frio (Cold Start) - Quando a ramificação não foi pré-carregada
    const previousVideo = activeVideo.value;
    isLoadingBranch.value = true;
    isPlayerReady.value = false;
    resetUI();

    try {
        const targetLessonData = await fetchLessonData(lessonId);

        const nextPlayerId = activePlayerId.value === 'A' ? 'B' : 'A';
        const targetVideo = nextPlayerId === 'A' ? videoA : videoB;

        if (targetVideo.state.value?.state) {
            targetVideo.state.value.state.duration.value = 0;
            targetVideo.state.value.state.currentTime.value = 0;
        }

        if (nextPlayerId === 'A') lessonA.value = targetLessonData;
        else lessonB.value = targetLessonData;

        await targetVideo.loadVideo(targetLessonData.video_url, seekToTime, true);

        const targetZ = nextPlayerId === 'A' ? zIndexA : zIndexB;
        const currentZ = nextPlayerId === 'A' ? zIndexB : zIndexA;
        const targetOpacity = nextPlayerId === 'A' ? opacityA : opacityB;
        const currentOpacity = nextPlayerId === 'A' ? opacityB : opacityA;

        targetZ.value = 10;
        currentZ.value = 20;
        targetOpacity.value = 1;

        targetVideo.mute?.();
        targetVideo.setSpeed(currentPlaybackRate.value);
        targetVideo.play();

        let checkInterval: any;
        let fallbackTimeout: any;
        let swapFired = false;

        const executeSwap = () => {
            if (swapFired) return;
            swapFired = true;
            clearInterval(checkInterval);
            clearTimeout(fallbackTimeout);

            activePlayerId.value = nextPlayerId;
            if (isMuted.value) targetVideo.mute?.();
            else targetVideo.setVolume?.(currentVolume.value);

            // Inicia a transição CSS suave
            currentOpacity.value = 0;
            isLoadingBranch.value = false;
            isPlayerReady.value = true;

            // ⏱️ O SEGREDO DO CROSSFADE: Esperamos exatos 700ms
            setTimeout(() => {
                targetZ.value = 20;
                currentZ.value = 10;
                previousVideo.pause();
            }, 700);
        };

        checkInterval = setInterval(() => {
            const state = targetVideo.state.value?.state;
            if (!state) return;
            if (state.isPlaying.value && state.currentTime.value > seekToTime + 0.05) {
                executeSwap();
            }
        }, 20);

        fallbackTimeout = setTimeout(executeSwap, 3000);

    } catch (e) {
        isLoadingBranch.value = false;
        isPlayerReady.value = true;
        activeVideo.value.play();
    }
};

const executeFeedbackAction = (action: string | null, seekTo: number | null, targetLessonId: number | null, q: any) => {
    activeFeedback.value = null;
    pendingFeedbackAction.value = null;
    if (feedbackTimeout) { clearTimeout(feedbackTimeout); feedbackTimeout = null; }

    if (targetLessonId && Number(targetLessonId) !== Number(currentLesson.value.id)) {
        branchToLesson(targetLessonId, seekTo || 0);
        return;
    }

    if (action === "seek" && seekTo != null) {
        genericSeek(Number(seekTo), true);
        setTimeout(() => {
            if (!isPlaying.value) activeVideo.value.play();
            setTimeout(() => {
                if (isMuted.value) {
                    activeVideo.value.mute?.();
                } else {
                    activeVideo.value.setVolume?.(currentVolume.value);
                }
            }, 150);
        }, 50);
    } else {
        continueAfterInteraction(q);
    }
};

const closeManualFeedback = () => {
    if (pendingFeedbackAction.value) {
        executeFeedbackAction(
            pendingFeedbackAction.value.action,
            pendingFeedbackAction.value.seekTo,
            pendingFeedbackAction.value.targetLessonId,
            pendingFeedbackAction.value.question
        );
    } else if (activeFeedback.value && activeQuestion.value) {
        continueAfterInteraction(activeQuestion.value);
        activeFeedback.value = null;
    }
};

const submitAnswer = () => {
    if (activeFeedback.value || !activeQuestion.value) return;

    const q = activeQuestion.value;
    let context: any = {};

    if (q.type === "drag_and_drop") {
        const required = q.options.filter((o: any) => o.is_zone);
        if (Object.keys(droppedItems.value).length < required.length) return;
        context = { droppedItems: droppedItems.value };
    } else {
        if (q.type === "info" && (!q.options || q.options.length === 0)) { }
        else if (selectedIndices.value.length === 0) return;
        context = { selectedIndices: selectedIndices.value };
    }

    // lastResolvedQuestionId.value = q.id;

    if (q.type === "info") {
        let action = q.correct_action || "continue";
        let seekTo = q.correct_seek_to;
        let targetId = q.correct_target_lesson_id;

        if (q.options && q.options.length > 0 && selectedIndices.value.length > 0) {
            const selectedOpt = q.options[selectedIndices.value[0]];
            if (selectedOpt.correct_action) action = selectedOpt.correct_action;
            if (selectedOpt.correct_seek_to !== null) seekTo = selectedOpt.correct_seek_to;
            if (selectedOpt.correct_target_lesson_id) targetId = selectedOpt.correct_target_lesson_id;
        }

        activeQuestion.value = null;

        if (targetId && Number(targetId) !== Number(currentLesson.value.id)) {
            branchToLesson(targetId, seekTo || 0);
            return;
        }

        if (action === "seek" && seekTo != null) {
            genericSeek(Number(seekTo), true);
            // 🔥 REMOVA o activeVideo.value.play() que estava no meio dos setTimeouts.
            setTimeout(() => {
                if (isMuted.value) activeVideo.value.mute?.();
                else activeVideo.value.setVolume?.(currentVolume.value);
            }, 150);
        } else {
            continueAfterInteraction(q);
        }
        return;
    }

    const result = resolveResponse(q, context);
    const isCorrect = result.result === "correct";
    const baseConfig = isCorrect ? q.correct_feedback_config : q.wrong_feedback_config;
    const feedbackConfig = JSON.parse(JSON.stringify(baseConfig || {}));

    let customTexts: string[] = [];
    if (['single_choice', 'multiple_choice'].includes(q.type) && selectedIndices.value.length > 0) {
        customTexts = selectedIndices.value.map(idx => q.options[idx].feedback_text).filter(t => typeof t === 'string' && t.trim().length > 0);
    } else if (q.type === 'drag_and_drop') {
        Object.values(droppedItems.value).forEach(itemIndices => {
            itemIndices.forEach(idx => {
                const txt = q.options[idx]?.feedback_text;
                if (typeof txt === 'string' && txt.trim().length > 0) customTexts.push(txt.trim());
            });
        });
    }

    if (customTexts.length > 0) feedbackConfig.text = customTexts.join('\n\n');
    activeQuestion.value = null;

    if (feedbackConfig.text || feedbackConfig.background_image) {
        activeFeedback.value = { type: isCorrect ? "correct" : "wrong", config: feedbackConfig };
        let durationVal = parseFloat(String(feedbackConfig.duration_seconds));

        if (!isNaN(durationVal) && durationVal > 0) {
            feedbackTimeout = window.setTimeout(() => {
                executeFeedbackAction(result.action, result.seekTo, result.targetLessonId, q);
            }, durationVal * 1000);
        } else {
            pendingFeedbackAction.value = { action: result.action, seekTo: result.seekTo, targetLessonId: result.targetLessonId, question: q };
        }
        return;
    }

    if (result.targetLessonId && Number(result.targetLessonId) !== Number(currentLesson.value.id)) {
        branchToLesson(result.targetLessonId, result.seekTo || 0);
        return;
    }

    if (result.action === "seek" && result.seekTo != null) {
        genericSeek(Number(result.seekTo), true);
        setTimeout(() => {
            activeVideo.value.play();
            setTimeout(() => {
                if (isMuted.value) {
                    activeVideo.value.mute?.();
                } else {
                    activeVideo.value.setVolume?.(currentVolume.value);
                }
            }, 150);
        }, 50);
    }
    else { continueAfterInteraction(q); }
};

const { toggleFullscreen } = useFullscreen(stageRef);
</script>
<template>
    <div class="flex flex-col w-full h-screen font-sans text-white bg-slate-950">
        <header class="flex items-center justify-between px-6 py-4 border-b border-slate-800 bg-slate-900 shrink-0">
            <div>
                <h1 class="text-xl font-bold text-amber-500">{{ props.course.title || 'Curso' }}</h1>
                <p class="text-sm text-slate-400">Módulo: {{ props.module.title }} &mdash; Aula: {{ currentLesson.title
                }}</p>
            </div>
        </header>

        <main class="flex flex-col items-center justify-center flex-1 p-4 overflow-hidden lg:p-8">
            <div ref="stageRef"
                class="relative w-full max-w-5xl overflow-hidden bg-black shadow-2xl aspect-video rounded-xl ring-1 ring-slate-800 group">
                <div ref="playerContainerA"
                    class="absolute inset-0 transition-opacity duration-700 ease-in-out bg-black"
                    :style="{ zIndex: zIndexA, opacity: opacityA }"
                    :class="activePlayerId === 'A' ? 'pointer-events-auto' : 'pointer-events-none'">
                </div>

                <div ref="playerContainerB"
                    class="absolute inset-0 transition-opacity duration-700 ease-in-out bg-black"
                    :style="{ zIndex: zIndexB, opacity: opacityB }"
                    :class="activePlayerId === 'B' ? 'pointer-events-auto' : 'pointer-events-none'">
                </div>

                <!-- <div v-if="showSpinner"
                    class="absolute inset-0 z-[100] bg-black/60 backdrop-blur-sm flex flex-col items-center justify-center">
                    <Loader2 class="w-12 h-12 text-amber-500 animate-spin" />
                    <span class="mt-4 text-sm font-bold tracking-widest uppercase text-slate-200">Carregando
                        Cena...</span>
                </div> -->
                <div v-if="!isInteractionActive && !isLoadingBranch" class="absolute inset-0 z-[25]" @click="togglePlay"
                    @dblclick="toggleFullscreen">
                </div>
                <div v-if="activeFeedback && !isLoadingBranch"
                    class="absolute z-[60] overflow-hidden shadow-2xl flex flex-col pointer-events-auto" :style="{
                        top: (activeFeedback.config.coordinates?.top || 50) + '%',
                        left: (activeFeedback.config.coordinates?.left || 50) + '%',
                        width: (activeFeedback.config.coordinates?.width || 40) + '%',
                        height: (activeFeedback.config.coordinates?.height || 20) + '%',
                        borderRadius: (activeFeedback.config.coordinates?.radius ?? 12) + 'px'
                    }">
                    <div class="absolute inset-0 z-0 pointer-events-none"
                        :style="{ backgroundColor: activeFeedback.config.background_color || 'transparent', opacity: activeFeedback.config.background_opacity ?? 1 }">
                    </div>
                    <img v-if="activeFeedback.config.background_image" :src="activeFeedback.config.background_image"
                        class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
                        :style="{ opacity: activeFeedback.config.background_opacity ?? 1 }" />

                    <button
                        v-if="!activeFeedback.config.duration_seconds || parseFloat(activeFeedback.config.duration_seconds) <= 0"
                        @click.stop="closeManualFeedback"
                        class="absolute z-30 flex items-center justify-center w-6 h-6 text-white transition-colors rounded-full shadow cursor-pointer top-2 right-2 bg-black/40 hover:bg-black/80 backdrop-blur">
                        <X class="w-4 h-4" />
                    </button>

                    <div class="relative z-20 flex flex-col flex-1 w-full h-full p-4 overflow-y-auto"
                        :class="{ 'pr-10 pt-10': !activeFeedback.config.duration_seconds || parseFloat(activeFeedback.config.duration_seconds) <= 0 }">
                        <div v-if="activeFeedback.config.vertical_align === 'center' || activeFeedback.config.vertical_align === 'bottom'"
                            class="flex-grow shrink-0 min-h-[0px]"></div>
                        <span class="w-full font-bold whitespace-pre-wrap drop-shadow-md shrink-0"
                            :style="{ color: activeFeedback.config.text_color, fontSize: activeFeedback.config.text_font_size + 'px', fontFamily: activeFeedback.config.text_font_family, textAlign: activeFeedback.config.text_align || 'center' }">
                            {{ activeFeedback.config.text }}
                        </span>
                        <div v-if="activeFeedback.config.vertical_align === 'center' || activeFeedback.config.vertical_align === 'top' || !activeFeedback.config.vertical_align"
                            class="flex-grow shrink-0 min-h-[0px]"></div>
                    </div>
                </div>

                <ConfirmationHotspot v-if="shouldShowConfirmation(activeQuestion) && !isLoadingBranch"
                    :data="{ ...activeQuestion.confirmation_config, disabled: !canConfirm(activeQuestion), background_image: activeQuestion.confirmation_image, button_image: activeQuestion.confirmation_button_image }"
                    class="z-[55]" @confirm="canConfirm(activeQuestion) && submitAnswer()" />

                <div v-if="activeQuestion && !isLoadingBranch" class="absolute z-10 overflow-hidden pointer-events-none"
                    :style="{
                        top: (activeQuestion.global_background_coordinates?.top ?? 0) + '%', left: (activeQuestion.global_background_coordinates?.left ?? 0) + '%',
                        width: (activeQuestion.global_background_coordinates?.width ?? 100) + '%', height: (activeQuestion.global_background_coordinates?.height ?? 100) + '%',
                        borderRadius: (activeQuestion.global_background_coordinates?.radius ?? 0) + 'px'
                    }">
                    <div class="absolute inset-0"
                        :style="{ backgroundColor: activeQuestion.global_background_color || 'transparent', opacity: activeQuestion.global_background_opacity ?? 1 }">
                    </div>
                    <img v-if="activeQuestion.global_background_image" :src="activeQuestion.global_background_image"
                        class="absolute inset-0 object-cover w-full h-full"
                        :style="{ opacity: activeQuestion.global_background_opacity ?? 1 }" />
                    <div class="absolute inset-0 z-20 flex p-4"
                        :style="{ justifyContent: activeQuestion.global_background_text_align === 'left' ? 'flex-start' : (activeQuestion.global_background_text_align === 'right' ? 'flex-end' : 'center'), alignItems: activeQuestion.global_background_vertical_align === 'top' ? 'flex-start' : (activeQuestion.global_background_vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                        <span v-if="activeQuestion.global_background_text"
                            class="font-bold text-center break-words drop-shadow-md"
                            :style="{ color: activeQuestion.global_background_text_color, fontSize: activeQuestion.global_background_text_font_size + 'px', fontFamily: activeQuestion.global_background_text_font_family }">
                            {{ activeQuestion.global_background_text }}
                        </span>
                    </div>
                </div>

                <TitleHotspot
                    v-if="activeQuestion?.show_title && activeQuestion.type !== 'automatic' && !isLoadingBranch"
                    :data="{ coordinates: activeQuestion.question_coordinates, background_color: activeQuestion.background_color, background_opacity: activeQuestion.background_opacity, background_image: activeQuestion.title_image || activeQuestion.title_image_url || null, text: activeQuestion.title, text_style: { color: activeQuestion.title_color, size: activeQuestion.title_font_size, family: activeQuestion.title_font_family, align: activeQuestion.title_text_align ?? 'center', vertical: activeQuestion.title_vertical_align ?? 'center' } }" />
                <div v-if="activeQuestion && activeQuestion.type === 'info' && (!activeQuestion.options || activeQuestion.options.length === 0) && !isLoadingBranch"
                    class="absolute inset-0 z-40 cursor-pointer" @click="submitAnswer()">
                </div>
                <template
                    v-if="activeQuestion && ['single_choice', 'multiple_choice', 'info'].includes(activeQuestion.type) && !isLoadingBranch">
                    <template v-for="(opt, idx) in activeQuestion.options" :key="'zone-prev-' + idx">
                        <div v-if="opt.coordinates && revealedOptions.includes(idx)" @click="toggleSelection(idx)"
                            @mouseenter="preloadBranch(opt.correct_target_lesson_id || opt.wrong_target_lesson_id)"
                            class="absolute z-50 overflow-hidden transition-all cursor-pointer group"
                            :class="[selectedIndices.includes(idx) ? 'ring-4 ring-amber-500' : 'hover:ring-2 hover:ring-white/50']"
                            :style="{ top: opt.coordinates.top + '%', left: opt.coordinates.left + '%', width: opt.coordinates.width + '%', height: opt.coordinates.height + '%', borderRadius: (opt.coordinates.radius ?? 8) + 'px', clipPath: getShapeClipPath(opt.shape) }">
                            <div class="absolute inset-0 transition-colors pointer-events-none group-hover:bg-white/10"
                                :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                            </div>
                            <img v-if="opt.zone_image" :src="opt.zone_image"
                                class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                :style="{ opacity: opt.background_opacity ?? 1 }" />
                            <div class="absolute z-30 flex items-center justify-center w-5 h-5 border-2 top-2 left-2"
                                :class="[selectedIndices.includes(idx) ? 'border-amber-500 bg-amber-500' : 'border-slate-400 bg-black/30', ['single_choice', 'info'].includes(activeQuestion.type) ? 'rounded-full' : 'rounded-md']">
                                <div v-if="selectedIndices.includes(idx)" class="w-2 h-2 bg-white rounded-full" />
                            </div>
                            <div class="absolute inset-0 z-20 flex p-3 pointer-events-none"
                                :style="{ justifyContent: opt.text_align === 'left' ? 'flex-start' : (opt.text_align === 'right' ? 'flex-end' : 'center'), alignItems: opt.vertical_align === 'top' ? 'flex-start' : (opt.vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                                <span v-if="opt.show_text" class="font-medium drop-shadow-md"
                                    :style="{ color: opt.text_color, fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">{{
                                        opt.text }}</span>
                            </div>
                        </div>
                    </template>
                </template>

                <template v-if="activeQuestion && activeQuestion.type === 'drag_and_drop' && !isLoadingBranch">
                    <div v-for="(opt, idx) in activeQuestion.options" :key="'z' + idx">
                        <div v-if="opt.coordinates && opt.is_zone"
                            class="absolute z-50 flex flex-wrap overflow-hidden border-2 border-white/50"
                            :style="{ top: opt.coordinates.top + '%', left: opt.coordinates.left + '%', width: opt.coordinates.width + '%', height: opt.coordinates.height + '%', borderRadius: (opt.coordinates.radius ?? 0) + 'px', clipPath: getShapeClipPath(opt.shape) }"
                            @drop="onDrop(idx)" @dragover.prevent>
                            <div class="absolute inset-0 z-0 pointer-events-none"
                                :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                            </div>
                            <img v-if="opt.zone_image" :src="opt.zone_image"
                                class="absolute inset-0 z-0 object-cover w-full h-full pointer-events-none"
                                :style="{ opacity: opt.background_opacity ?? 1 }" />
                            <div v-if="!droppedItems[idx]?.length"
                                class="absolute inset-0 z-10 flex p-2 pointer-events-none"
                                :style="{ justifyContent: opt.text_align === 'left' ? 'flex-start' : (opt.text_align === 'right' ? 'flex-end' : 'center'), alignItems: opt.vertical_align === 'top' ? 'flex-start' : (opt.vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                                <span v-if="opt.show_text" class="font-bold drop-shadow-md"
                                    :style="{ color: opt.text_color || '#ffffff', fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">{{
                                        opt.text }}</span>
                            </div>
                            <div v-if="droppedItems[idx]?.length"
                                class="relative z-20 flex flex-wrap items-center justify-center w-full h-full gap-1 p-2 overflow-y-auto">
                                <div v-for="i in droppedItems[idx]" :key="i"
                                    class="flex flex-col items-center justify-center gap-1 p-1 text-xs border rounded shadow-md cursor-pointer"
                                    :style="{ backgroundColor: activeQuestion.options[i].background_color === 'transparent' ? '#3b82f6' : activeQuestion.options[i].background_color, borderColor: activeQuestion.options[i].text_color }"
                                    @click="resetDrop(idx, i)">
                                    <img v-if="activeQuestion.options[i].image_url"
                                        :src="activeQuestion.options[i].image_url"
                                        class="object-contain w-10 h-10 rounded pointer-events-none" />
                                    <span v-if="activeQuestion.options[i].show_text"
                                        class="text-[10px] text-center font-bold drop-shadow-md"
                                        :style="{ color: activeQuestion.options[i].text_color, fontFamily: activeQuestion.options[i].text_font_family }">{{
                                            activeQuestion.options[i].text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="(opt, idx) in activeQuestion.options" :key="'item-abs-' + idx">
                        <div v-if="!opt.is_zone && opt.coordinates && revealedOptions.includes(idx)"
                            v-show="!isItemDropped(idx)" draggable="true" @dragstart="onDragStart($event, idx)"
                            class="absolute z-[60] flex shadow-lg cursor-grab hover:ring-2 hover:ring-amber-400 overflow-hidden"
                            :style="{ top: opt.coordinates.top + '%', left: opt.coordinates.left + '%', width: opt.coordinates.width + '%', height: opt.coordinates.height + '%', borderRadius: (opt.coordinates.radius ?? 8) + 'px', clipPath: getShapeClipPath(opt.shape) }">
                            <div class="absolute inset-0 z-0 pointer-events-none"
                                :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                            </div>
                            <img v-if="opt.image_url" :src="opt.image_url"
                                class="absolute inset-0 z-0 object-contain w-full h-full pointer-events-none"
                                :style="{ opacity: opt.background_opacity ?? 1 }" />
                            <div class="absolute inset-0 z-10 flex p-2 pointer-events-none"
                                :style="{ justifyContent: opt.text_align === 'left' ? 'flex-start' : (opt.text_align === 'right' ? 'flex-end' : 'center'), alignItems: opt.vertical_align === 'top' ? 'flex-start' : (opt.vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                                <span v-if="opt.show_text" class="font-bold drop-shadow-md"
                                    :style="{ color: opt.text_color || '#000000', fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">{{
                                        opt.text }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute left-0 right-0 z-[60] flex justify-center gap-2 bottom-10 pointer-events-none">
                        <div v-for="(opt, idx) in activeQuestion.options" :key="'d-bottom-' + idx"
                            v-show="!opt.is_zone && !opt.coordinates && !isItemDropped(idx) && revealedOptions.includes(idx)"
                            draggable="true" @dragstart="onDragStart($event, idx)"
                            @mouseenter="preloadBranch(opt.correct_target_lesson_id || opt.wrong_target_lesson_id)"
                            class="relative flex items-center gap-2 px-4 py-2 overflow-hidden font-bold shadow-xl pointer-events-auto cursor-grab hover:ring-2 hover:ring-amber-400"
                            :style="{ backgroundColor: opt.background_color === 'transparent' ? '#ffffff' : opt.background_color, borderRadius: (opt.coordinates?.radius ?? 8) + 'px', clipPath: getShapeClipPath(opt.shape) }">
                            <div class="absolute inset-0 pointer-events-none"
                                :style="{ opacity: opt.background_opacity ?? 1, backgroundColor: opt.background_color === 'transparent' ? '#ffffff' : opt.background_color }">
                            </div>
                            <img v-if="opt.image_url" :src="opt.image_url"
                                class="relative z-10 object-cover w-10 h-10 rounded pointer-events-none" />
                            <span v-if="opt.show_text" class="relative z-10 drop-shadow-md"
                                :style="{ color: opt.text_color || '#000000', fontFamily: opt.text_font_family }">{{
                                    opt.text }}</span>
                        </div>
                    </div>
                </template>

                <div v-if="shouldShowQuestionModal && !isLoadingBranch"
                    class="absolute inset-0 z-40 pointer-events-none">
                    <div class="absolute z-50 p-6 overflow-y-auto shadow-2xl pointer-events-auto"
                        :style="{ top: (activeQuestion.modal_coordinates?.top || 50) + '%', left: (activeQuestion.modal_coordinates?.left || 50) + '%', width: (activeQuestion.modal_coordinates?.width || 40) + '%', height: (activeQuestion.modal_coordinates?.height || 50) + '%', transform: 'translate(-50%, -50%)', borderRadius: (activeQuestion.modal_coordinates?.radius ?? 12) + 'px' }">
                        <div class="absolute inset-0 pointer-events-none -z-10"
                            :style="{ backgroundColor: activeQuestion.modal_background_color || '#ffffff', opacity: activeQuestion.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                        </div>
                        <div v-if="activeQuestion.modal_image" class="absolute inset-0 pointer-events-none -z-10"
                            :style="{ backgroundImage: `url(${activeQuestion.modal_image})`, backgroundSize: 'cover', backgroundPosition: 'center', opacity: activeQuestion.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                        </div>

                        <div class="relative z-10 flex flex-col h-full gap-3">
                            <template v-for="(opt, idx) in activeQuestion.options" :key="idx">
                                <div v-if="!opt.coordinates && revealedOptions.includes(idx)"
                                    @click="toggleSelection(idx)"
                                    @mouseenter="preloadBranch(opt.correct_target_lesson_id || opt.wrong_target_lesson_id)"
                                    class="relative flex items-center gap-3 p-3 overflow-hidden transition-all border-2 cursor-pointer group"
                                    :class="selectedIndices.includes(idx) ? 'ring-2 ring-amber-500 border-amber-500' : 'border-transparent hover:ring-2 hover:ring-slate-400'"
                                    :style="{ borderColor: (opt.text_color || '#000') + '40', borderRadius: (opt.coordinates?.radius ?? 8) + 'px' }">
                                    <div class="absolute inset-0 transition-colors pointer-events-none group-hover:bg-black/5"
                                        :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                                    </div>
                                    <img v-if="opt.image_url" :src="opt.image_url"
                                        class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                        :style="{ opacity: opt.background_opacity ?? 1 }" />
                                    <div class="z-20 flex items-center justify-center w-5 h-5 border-2 shrink-0 bg-black/5"
                                        :class="[selectedIndices.includes(idx) ? 'border-amber-500 bg-amber-500' : 'border-slate-400', ['single_choice', 'info'].includes(activeQuestion.type) ? 'rounded-full' : 'rounded-md',]"
                                        :style="{ borderColor: selectedIndices.includes(idx) ? undefined : (opt.text_color || '#000') }">
                                        <div v-if="selectedIndices.includes(idx)"
                                            class="w-2 h-2 bg-white rounded-full" />
                                    </div>
                                    <div class="z-20 flex flex-1 pointer-events-none"
                                        :style="{ justifyContent: opt.text_align === 'left' ? 'flex-start' : (opt.text_align === 'right' ? 'flex-end' : 'center') }">
                                        <span class="font-bold drop-shadow-md"
                                            :style="{ color: opt.text_color, fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">{{
                                                opt.text }}</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-if="isInteractionActive || isLoadingBranch" class="absolute inset-0 bg-transparent z-5"></div>
            </div>

            <div class="w-full max-w-5xl mt-6">
                <div class="relative h-2 mb-4 overflow-hidden rounded bg-slate-800">
                    <div class="absolute top-0 left-0 h-full transition-all duration-100 ease-linear rounded bg-amber-500"
                        :style="{ width: progressPercentage + '%' }"></div>
                    <div v-for="q in currentLesson.questions" :key="q.id" class="absolute z-10 w-1.5 h-full bg-white/50"
                        :style="{ left: (Number(q.timestamp_seconds) / (duration || 1)) * 100 + '%' }">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 lg:gap-4">

                        <Button variant="ghost" size="icon" @click="togglePlay"
                            class="w-12 h-12 transition-all duration-300 rounded-full bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-slate-950 hover:scale-105 active:scale-95"
                            :disabled="isInteractionActive || isLoadingBranch">
                            <Pause v-if="isPlaying" class="w-6 h-6" />
                            <Play v-else class="w-6 h-6 ml-1" />
                        </Button>

                        <Button variant="ghost"
                            class="relative flex items-center gap-1.5 px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800/80 rounded-full transition-all group/btn"
                            @click="requestSeek(-10)" :disabled="isInteractionActive || isLoadingBranch">
                            <RotateCcw class="w-4 h-4 transition-transform group-hover/btn:-rotate-45" />
                            <span class="text-xs font-bold tracking-wider">10s</span>
                        </Button>

                        <Button variant="ghost"
                            class="relative flex items-center gap-1.5 px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800/80 rounded-full transition-all group/btn"
                            @click="requestSeek(10)" :disabled="isInteractionActive || isLoadingBranch">
                            <span class="text-xs font-bold tracking-wider">10s</span>
                            <RotateCw class="w-4 h-4 transition-transform group-hover/btn:rotate-45" />
                        </Button>

                        <div class="relative flex items-center h-12 gap-2 ml-2 group/volume">
                            <Button variant="ghost" size="icon" @click="toggleMute"
                                class="transition-colors rounded-full text-slate-400 hover:text-white hover:bg-slate-800/80"
                                :disabled="isInteractionActive || isLoadingBranch">
                                <VolumeX v-if="isMuted" class="w-5 h-5 text-red-400" />
                                <Volume2 v-else class="w-5 h-5" />
                            </Button>

                            <div
                                class="flex items-center w-0 overflow-hidden transition-all duration-300 ease-in-out opacity-0 group-hover/volume:w-20 group-hover/volume:opacity-100">
                                <input type="range" min="0" max="1" step="0.05" v-model.number="currentVolume"
                                    @input="setGlobalVolume(currentVolume)"
                                    class="w-full h-1.5 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-amber-500" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 font-mono lg:gap-4 text-slate-400">

                        <span class="px-3 py-1.5 text-xs font-medium tracking-wide bg-slate-800/40 rounded-full">
                            {{ formatTimeHMS(currentTime) }} / {{ formatTimeHMS(duration) }}
                        </span>

                        <div class="relative">
                            <div v-if="showSpeedMenu" @click="showSpeedMenu = false" class="fixed inset-0 z-40">
                            </div>

                            <Button variant="ghost" size="sm" @click="showSpeedMenu = !showSpeedMenu"
                                class="relative z-50 flex items-center gap-1 px-3 py-2 font-mono text-sm transition-colors rounded-full"
                                :class="showSpeedMenu ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/80'">
                                {{ currentPlaybackRate }}x
                            </Button>

                            <div v-if="showSpeedMenu"
                                class="absolute right-0 z-50 flex flex-col py-2 mb-2 overflow-hidden origin-bottom-right border shadow-2xl rounded-xl bottom-full w-28 bg-slate-800/95 backdrop-blur border-slate-700/50">
                                <button v-for="rate in availableSpeeds" :key="rate" @click="setPlaybackSpeed(rate)"
                                    class="px-4 py-2 text-sm text-left transition-colors hover:bg-slate-700/50"
                                    :class="currentPlaybackRate === rate ? 'text-amber-500 font-bold bg-slate-700/30' : 'text-slate-300'">
                                    {{ rate === 1 ? 'Normal' : rate + 'x' }}
                                </button>
                            </div>
                        </div>

                        <Button variant="ghost" size="icon" @click="toggleFullscreen"
                            class="transition-all rounded-full text-slate-400 hover:text-white hover:bg-slate-800/80 hover:scale-110 active:scale-95">
                            <Maximize2 class="w-5 h-5" />
                        </Button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
<style>
/* CSS Focado apenas no Plyr. O Panda tem de ser configurado no painel de controlo deles. */
.plyr .plyr__control--overlaid,
.plyr--video .plyr__control--overlaid {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

.plyr__controls [data-plyr="play"] {
    display: flex !important;
    visibility: visible !important;
}

.plyr__poster {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

.plyr--youtube iframe {
    pointer-events: none !important;
}

/* 🔥 MATA O SPINNER: Garante que o loading nativo do Plyr não assuma
   o ecrã e pisque ao voltar ao início do loop */
.plyr--loading .plyr__video-wrapper::before,
.plyr--loading .plyr__video-wrapper::after {
    display: none !important;
    opacity: 0 !important;
    content: none !important;
}
</style>
