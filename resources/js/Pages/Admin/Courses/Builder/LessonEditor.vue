<script setup lang="ts">
import {
    ref,
    reactive,
    onMounted,
    onBeforeUnmount,
    computed,
    nextTick,
    watchEffect,
    watch,
} from "vue";
import { useForm, router, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import NativeSwitch from "@/Components/NativeSwitch.vue";
import TimeInput from "@/Components/TimeInput.vue";
import { useVideoPlayer } from "@/video/useVideoPlayer";
// UI Components
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Separator } from "@/components/ui/separator";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import {
    ChevronLeft, Plus, Trash2, X, Zap, Scan, SkipBack, SkipForward, Play, Pause, Repeat,
    GripHorizontal, ArrowRightLeft, Move, Maximize2, Eye, EyeOff, ChevronDown, ChevronUp, ExternalLink, Volume2, VolumeX
} from "lucide-vue-next";
import { useFullscreen } from "@/Composables/useFullscreen";
import { resolveResponse } from "@/utils/resolveResponse";
import TitleHotspot from "@/Components/TitleHotspot.vue";
import FeedbackHotspot from "@/Components/FeedbackHotspot.vue";
import ConfirmationHotspot from "@/Components/ConfirmationHotspot.vue";

const props = defineProps<{
    course: any;
    module: any;
    lesson: any;
    studentLink?: string;
    allLessons?: any[]; // <-- NOVO: Recebe a lista de aulas do Controller
}>();
// --- CONSTANTES E SHAPES ---
const fontOptions = [
    { label: "Arial", value: "Arial, sans-serif" },
    { label: "Verdana", value: "Verdana, sans-serif" },
    { label: "Times New Roman", value: '"Times New Roman", serif' },
    { label: "Georgia", value: "Georgia, serif" },
    { label: "Courier New", value: '"Courier New", monospace' },
    { label: "Impact", value: "Impact, sans-serif" },
    { label: "Comic Sans", value: '"Comic Sans MS", cursive' },
];

const shapeOptions = [
    { label: "Retângulo", value: "rectangle" },
    { label: "Círculo / Oval", value: "circle" },
    { label: "Triângulo", value: "triangle" },
    { label: "Trapézio", value: "trapezoid" },
    { label: "Paralelogramo", value: "parallelogram" },
    { label: "Estrela", value: "star" },
    { label: "Hexágono", value: "hexagon" },
];

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

const hasAnyHotspot = computed(() => {
    if (!activePreviewQuestion.value) return false;
    return activePreviewQuestion.value.options?.some((o: any) => o.coordinates);
});

// --- INTERFACES ---
interface Option {
    text: string;
    is_correct: boolean;
    coordinates: {
        top: number;
        left: number;
        width: number;
        height: number;
        radius?: number;
    } | null;
    shape: string;
    group_id: number;
    text_color: string;
    text_font_size: number;
    text_font_family: string;
    show_text: boolean;
    correct_action?: "continue" | "seek" | null;
    correct_seek_to?: number | null;
    correct_target_lesson_id?: number | null; // <-- NOVO
    wrong_action?: "continue" | "seek" | null;
    wrong_seek_to?: number | null;
    wrong_target_lesson_id?: number | null;
    _is_zone?: boolean;
}

const canvasFocus = ref<
    | 'title'
    | 'modal'
    | 'zones'
    | 'feedback_correct'
    | 'feedback_wrong'
    | 'confirmation'
    | 'global_background'
    | null
>(null)

const defaultLayers = {
    global_background: true,
    title: true,
    modal: true,
    zones: true,
    feedback_correct: true,
    feedback_wrong: true,
    confirmation: true,
}

const canvasLayers = reactive<Record<string, boolean>>(loadLayers())

function loadLayers() {
    const saved = localStorage.getItem('lesson_editor_layers')
    if (saved) {
        return { ...defaultLayers, ...JSON.parse(saved) }
    }
    return { ...defaultLayers }
}

watch(canvasLayers, (val) => { localStorage.setItem('lesson_editor_layers', JSON.stringify(val)) }, { deep: true })

const panelState = reactive(loadPanelState())

function loadPanelState() {
    const saved = localStorage.getItem('lesson_editor_panel')
    return saved ? JSON.parse(saved) : { x: 20, y: 20, minimized: false }
}

watch(panelState, (val) => { localStorage.setItem('lesson_editor_panel', JSON.stringify(val)) }, { deep: true })

const isDraggingPanel = ref(false)
let panelStartX = 0
let panelStartY = 0
const floatingPanelRef = ref<HTMLElement | null>(null);
function startPanelDrag(e: MouseEvent) {
    isDraggingPanel.value = true
    panelStartX = e.clientX - panelState.x
    panelStartY = e.clientY - panelState.y
    document.addEventListener('mousemove', onPanelDrag)
    document.addEventListener('mouseup', stopPanelDrag)
}

function onPanelDrag(e: MouseEvent) {
    if (!isDraggingPanel.value) return;

    let targetX = e.clientX - panelStartX;
    let targetY = e.clientY - panelStartY;

    // 🔥 A MÁGICA DA COLISÃO: Não deixa o painel escapar do container do vídeo
    if (stageRef.value && floatingPanelRef.value) {
        const stageRect = stageRef.value.getBoundingClientRect();
        const panelRect = floatingPanelRef.value.getBoundingClientRect();

        const maxX = Math.max(0, stageRect.width - panelRect.width);
        const maxY = Math.max(0, stageRect.height - panelRect.height);

        targetX = Math.max(0, Math.min(targetX, maxX));
        targetY = Math.max(0, Math.min(targetY, maxY));
    }

    panelState.x = targetX;
    panelState.y = targetY;
}

function stopPanelDrag() {
    isDraggingPanel.value = false
    document.removeEventListener('mousemove', onPanelDrag)
    document.removeEventListener('mouseup', stopPanelDrag)
}

function togglePanelMinimize() {
    panelState.minimized = !panelState.minimized;

    // 🔥 PROTEÇÃO SANFONA: Se maximizar o painel perto do rodapé,
    // ele auto-corrige a posição para cima, impedindo de vazar.
    nextTick(() => {
        if (stageRef.value && floatingPanelRef.value && !panelState.minimized) {
            const stageRect = stageRef.value.getBoundingClientRect();
            const panelRect = floatingPanelRef.value.getBoundingClientRect();
            const maxY = Math.max(0, stageRect.height - panelRect.height);

            if (panelState.y > maxY) {
                panelState.y = maxY;
            }
        }
    });
}
function toggleLayer(key: keyof typeof canvasLayers) { canvasLayers[key] = !canvasLayers[key] }
function showAllLayers() { Object.keys(canvasLayers).forEach((k) => { canvasLayers[k as keyof typeof canvasLayers] = true }) }

// --- PLAYER STATE ---
const playerContainer = ref<HTMLElement | null>(null);
const stageRef = ref<HTMLElement | null>(null);

const formatTimeHMSms = (seconds: number) => {
    if (isNaN(seconds) || seconds < 0) return "00:00:00.000";
    const totalMs = Math.floor(Number(seconds) * 1000);
    const ms = totalMs % 1000;
    const totalSeconds = Math.floor(totalMs / 1000);
    const s = totalSeconds % 60;
    const totalMinutes = Math.floor(totalSeconds / 60);
    const m = totalMinutes % 60;
    const h = Math.floor(totalMinutes / 60);
    return (
        `${h.toString().padStart(2, "0")}:` +
        `${m.toString().padStart(2, "0")}:` +
        `${s.toString().padStart(2, "0")}.` +
        `${ms.toString().padStart(3, "0")}`
    );
};


interface InteractionDetail {
    text: string;
    colorClass: string;
}

// 🔥 ATUALIZADO: Converte o tipo da interação para um nome mais amigável
const getInteractionTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        single_choice: 'Única Escolha',
        multiple_choice: 'Múltipla Escolha',
        info: 'Informativo',
        drag_and_drop: 'Drag & Drop',
        automatic: 'Automático'
    };
    return labels[type] || type;
};

// 🔥 ATUALIZADO: Busca o nome do vídeo baseado no ID
const getLessonTitle = (lessonId: number | null) => {
    if (!lessonId || lessonId === props.lesson.id) return 'Neste vídeo';
    if (!props.allLessons) return 'Vídeo Externo';
    const lesson = props.allLessons.find(l => l.id === lessonId);
    return lesson ? lesson.title : 'Vídeo Desconhecido';
};

// 🔥 ATUALIZADO: Mapeia as regras com a diretriz de cores (Verde, Vermelho, Azul)
const getInteractionDetails = (q: any) => {
    const typeLabel = getInteractionTypeLabel(q.type);
    let details: InteractionDetail[] = [];

    // --- 1. MAPEIA AS REGRAS GLOBAIS ---
    if (q.correct_action === 'seek') {
        const target = getLessonTitle(q.correct_target_lesson_id);
        const time = formatTimeHMSms(q.correct_seek_to || 0);

        // Se for Info ou Auto, é apenas um avanço contínuo (Azul). Se for Quiz, é um acerto (Verde).
        const isContinueAction = (q.type === 'info' || q.type === 'automatic');
        const prefix = isContinueAction ? 'Ação Padrão' : 'Acerto Global';
        const color = isContinueAction ? 'text-blue-500 dark:text-blue-400' : 'text-green-600 dark:text-green-400';
        const icon = isContinueAction ? '➡️' : '✅';

        details.push({ text: `${icon} ${prefix}: ➔ ${target} em ${time}`, colorClass: color });
    }

    if (!['info', 'automatic'].includes(q.type) && q.wrong_action === 'seek') {
        const target = getLessonTitle(q.wrong_target_lesson_id);
        const time = formatTimeHMSms(q.wrong_seek_to || 0);
        // Erro é sempre Vermelho
        details.push({ text: `❌ Erro Global: ➔ ${target} em ${time}`, colorClass: 'text-red-600 dark:text-red-400' });
    }

    // --- 2. MAPEIA AS REGRAS INDIVIDUAIS (SOBREPOSIÇÕES) ---
    if (q.options && q.options.length > 0 && ['single_choice', 'multiple_choice', 'info'].includes(q.type)) {
        q.options.forEach((opt: any, index: number) => {
            const optName = opt.text ? opt.text : `Opção ${index + 1}`;

            // Ação de avanço / acerto da opção
            if (opt.correct_action === 'seek') {
                const target = getLessonTitle(opt.correct_target_lesson_id);
                const time = formatTimeHMSms(opt.correct_seek_to || 0);

                const isInfo = q.type === 'info';
                const prefix = isInfo ? 'Ao Clicar' : (opt.is_correct ? 'Acerto Específico' : 'Ação Específica');

                // Azul se for info (seguir) ou se não tiver certo/errado definido. Verde se for a opção correta.
                const color = isInfo ? 'text-blue-500 dark:text-blue-400' : (opt.is_correct ? 'text-green-600 dark:text-green-400' : 'text-blue-500 dark:text-blue-400');
                const icon = isInfo ? '➡️' : (opt.is_correct ? '✅' : '➡️');

                details.push({ text: `${icon} ${optName} (${prefix}): ➔ ${target} em ${time}`, colorClass: color });
            }

            // Ação de erro da opção
            if (q.type !== 'info' && opt.wrong_action === 'seek' && !opt.is_correct) {
                const target = getLessonTitle(opt.wrong_target_lesson_id);
                const time = formatTimeHMSms(opt.wrong_seek_to || 0);
                // Erro é sempre Vermelho
                details.push({ text: `❌ ${optName} (Erro): ➔ ${target} em ${time}`, colorClass: 'text-red-600 dark:text-red-400' });
            }
        });
    }

    // --- 3. SE NÃO HOUVER NADA ---
    if (details.length === 0) {
        // Segue o fluxo normal do vídeo, logo é Azul
        details.push({ text: "➡️ Nenhum desvio (Segue reprodução)", colorClass: "text-blue-500 dark:text-blue-400" });
    }

    return { typeLabel, details };
};
const video = useVideoPlayer({
    containerRef: playerContainer,
    videoUrl: props.lesson.video_url,
    getPlaybackRate: () => currentPlaybackRate.value,
    onRateChange: (rate) => { currentPlaybackRate.value = rate; },
    onTimeUpdate: (t) => {
        currentTime.value = t;
        currentTimeDisplay.value = formatTimeHMSms(t);
        if (!duration.value && video.state.value?.state.duration.value) {
            duration.value = video.state.value.state.duration.value;
        }
        checkInteractions(t);
    },
    onReady: () => {
        isPlayerReady.value = true;
        if (video.state.value?.state.duration.value) {
            duration.value = video.state.value.state.duration.value;
        }
        video.setVolume?.(currentVolume.value);
        video.setSpeed?.(currentPlaybackRate.value);
    },
});

const duration = ref(0);
const currentTime = ref(0);
const currentTimeDisplay = ref("00:00:00.000");
const isPreviewMode = ref(false);
const activeFeedback = ref<{ type: "correct" | "wrong"; config: any; } | null>(null);
let feedbackTimeout: number | null = null;
const pendingFeedbackAction = ref<{ action: string | null, seekTo: number | null, targetLessonId: number | string | null, question: any } | null>(null);
// --- INTERACTION STATE ---
const activePreviewQuestion = ref<any>(null);
const selectedIndices = ref<number[]>([]);
const previewFeedback = ref<{ type: "success" | "error"; msg: string } | null>(null);
const droppedItems = ref<Record<number, number[]>>({});
const draggedItemIndex = ref<number | null>(null);
const revealedOptions = ref<number[]>([]);

const showRedirectModal = ref(false);
const redirectModalData = ref({ videoName: '', timeFormatted: '', question: null as any });

const openRedirectModal = (videoName: string, timeFormatted: string, question: any) => {
    redirectModalData.value = { videoName, timeFormatted, question };
    showRedirectModal.value = true;
};

const confirmRedirectModal = () => {
    showRedirectModal.value = false;
    continueAfterInteraction(redirectModalData.value.question);
};
// ==========================================================
// 🔥 CONTROLES DE ÁUDIO E VELOCIDADE
// ==========================================================
const currentPlaybackRate = ref(Number(localStorage.getItem('editor_playback_rate')) || 1);
const currentVolume = ref(Number(localStorage.getItem('editor_volume') ?? 1));
const previousVolume = ref(1);
const isMuted = ref(localStorage.getItem('editor_muted') === 'true');
const showSpeedMenu = ref(false);
const availableSpeeds = [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];

const setGlobalVolume = (val: number) => {
    currentVolume.value = val;
    isMuted.value = val === 0;
    localStorage.setItem('editor_volume', val.toString());
    localStorage.setItem('editor_muted', isMuted.value.toString());
    video.setVolume?.(val);
};

const toggleMute = () => {
    if (isMuted.value) {
        setGlobalVolume(previousVolume.value > 0 ? previousVolume.value : 1);
    } else {
        previousVolume.value = currentVolume.value;
        setGlobalVolume(0);
    }
};

const setPlaybackSpeed = (rate: number) => {
    currentPlaybackRate.value = rate;
    localStorage.setItem('editor_playback_rate', rate.toString());
    video.setSpeed?.(rate);
    showSpeedMenu.value = false;
};

watch(currentTime, (newTime) => {
    if (!activePreviewQuestion.value) return;
    const q = activePreviewQuestion.value;

    // Revelar opções baseado no tempo
    const elapsed = Number(newTime) - Number(q.timestamp_seconds);
    q.options?.forEach((opt: any, idx: number) => {
        if (!revealedOptions.value.includes(idx) && elapsed >= Number(opt.delay_seconds || 0)) {
            revealedOptions.value.push(idx);
        }
    });

    // 🔥 MOTOR DE LOOP CONTÍNUO E IMPLACÁVEL
    const loopStart = q.loop_start_seconds != null ? Number(q.loop_start_seconds) : null;
    const loopEnd = q.loop_end_seconds != null ? Number(q.loop_end_seconds) : null;

    if (loopStart !== null && loopEnd !== null && loopEnd > loopStart) {
        // Verifica se passou do ponto final (margem de segurança maior para altas velocidades)
        if (newTime >= loopEnd - 0.08) {
            if (!isLoopingBack) {
                isLoopingBack = true;

                // Força o salto e garante que o player receba o comando
                video.seek(loopStart);

                // Garante que o play seja reativado independente da velocidade
                // setTimeout(() => {
                //     video.play();
                // }, 50);

                // Reset do flag após um intervalo seguro
                setTimeout(() => { isLoopingBack = false; }, 500);
            }
        }
    }
});

// --- EDITOR STATE (DRAG & RESIZE) ---
interface DragState {
    type: "modal" | "title" | "zone" | "feedback_correct" | "feedback_wrong" | "confirmation" | "confirmation_button" | "global_background";
    index?: number; action: "move" | "resize";
    startX: number; startY: number; startLeft: number; startTop: number;
    startWidth?: number; startHeight?: number; stageWidth: number; stageHeight: number;
}
const draggingElement = ref<DragState | null>(null);
const isDrawingMode = ref(false);
const drawingBox = ref<{ top: number; left: number; width: number; height: number; radius?: number } | null>(null);
let startX = 0, startY = 0;
const activeOptionIndex = ref<number | null>(null);

const isInteractionPanelOpen = ref(false);
const editingQuestionId = ref<number | null>(null);
// 🔥 NOVA IMPLEMENTAÇÃO: Memória de scroll
const panelScrollContainer = ref<HTMLElement | null>(null);
let savedScrollPosition = 0;
const progressPercentage = computed(() => {
    if (!duration.value || duration.value <= 0) return 0;
    const pct = (Number(currentTime.value) / Number(duration.value)) * 100;
    return Math.min(Math.max(pct, 0), 100);
});

// --- HELPERS ---
function clampToStage(coords: { top: number, left: number, width: number, height: number }) {
    if (coords.left < 0) coords.left = 0
    if (coords.top < 0) coords.top = 0
    if (coords.left + coords.width > 100) coords.left = 100 - coords.width
    if (coords.top + coords.height > 100) coords.top = 100 - coords.height
    coords.left = Math.max(0, Math.min(coords.left, 100))
    coords.top = Math.max(0, Math.min(coords.top, 100))
    return coords
}

function closePreviewInteraction() {
    activePreviewQuestion.value = null;
    previewFeedback.value = null;
    selectedIndices.value = [];
    droppedItems.value = {};
    draggedItemIndex.value = null;
    revealedOptions.value = [];
}

const ASIDE_STATE_KEY = "interaction_editor_sections";
function saveSectionState(id: string, open: boolean) {
    const raw = localStorage.getItem(ASIDE_STATE_KEY);
    const state = raw ? JSON.parse(raw) : {};
    state[id] = open;
    localStorage.setItem(ASIDE_STATE_KEY, JSON.stringify(state));
}
function getSectionState(id: string, defaultOpen = false): boolean {
    const raw = localStorage.getItem(ASIDE_STATE_KEY);
    if (!raw) return defaultOpen;
    const state = JSON.parse(raw);
    return state[id] ?? defaultOpen;
}

const hasClickableZones = (q: any): boolean => {
    if (!q?.options) return false;
    return q.options.some((o: any) => o.coordinates && o.coordinates.width > 0 && o.coordinates.height > 0);
};

const shouldShowEditorModal = computed(() => {
    if (form.type === "automatic") return false;
    if (form.type === "drag_and_drop") return false;

    // Se for INFO e não houver opções reais (ignorando templates vazios), não mostre.
    if (form.type === "info") {
        if (!form.options || form.options.length === 0) return false;
        return !form.options.some((o) => o.coordinates);
    }

    if (["single_choice", "multiple_choice"].includes(form.type)) {
        return !form.options.some((o) => o.coordinates);
    }

    return false;
});
const needsConfirmation = computed(() => {
    if (form.type === 'automatic') return false;
    // Se for 'info', SÓ precisa de confirmação se tiver mais de 1 opção!
    if (form.type === 'info') return form.options.length > 1;
    return true; // Single, Multiple e Drag and Drop sempre precisam
});
const shouldShowQuestionModal = computed(() => {
    const q = activePreviewQuestion.value;
    if (!q) return false;
    if (activeFeedback.value) return false;
    if (q.type === "automatic") return false;
    if (q.type === "drag_and_drop") return false;

    // Regra blindada para INFO:
    if (q.type === "info") {
        // Se não tiver opções, ou for um array vazio, ESCONDE o modal.
        if (!q.options || q.options.length === 0) return false;

        // Se tiver opções, só mostra o modal se NENHUMA das opções for uma "Zona Clicável"
        // (senão, o aluno tem que clicar direto no vídeo e não precisa da caixa)
        return !hasClickableZones(q);
    }

    // Para single e multiple choice
    if (["single_choice", "multiple_choice"].includes(q.type)) {
        return !hasClickableZones(q);
    }

    return false;
});

const continueAfterInteraction = (q: any) => {
    lastResolvedQuestionId.value = q.id;
    const hasLoop = q.loop_start_seconds != null && q.loop_end_seconds != null && Number(q.loop_end_seconds) > Number(q.loop_start_seconds);

    if (hasLoop) {
        const targetTime = Number(q.timestamp_seconds) > 0 ? Number(q.timestamp_seconds) + 0.2 : Number(currentTime.value) + 0.2;
        genericSeek(targetTime, true);
    }
    // 🔥 Proteção de estado
    setTimeout(() => { if (!isPlaying.value) genericPlay(); }, 150);
};

const executeFeedbackAction = (action: string | null, seekTo: number | null, targetLessonId: number | string | null, q: any) => {
    activeFeedback.value = null;
    pendingFeedbackAction.value = null;
    if (feedbackTimeout) {
        clearTimeout(feedbackTimeout);
        feedbackTimeout = null;
    }

    // 🔥 NOVO: Simulação de Redirecionamento após o feedback
    if (targetLessonId && targetLessonId !== 'current' && Number(targetLessonId) !== props.lesson.id) {
        const videoName = getLessonTitle(Number(targetLessonId));
        const timeFormatted = formatTimeHMSms(seekTo || 0);
        openRedirectModal(videoName, timeFormatted, q);
        return;
    }

    if (action === "seek" && seekTo != null) {
        lastResolvedQuestionId.value = q.id;
        genericSeek(Number(seekTo), true);
        setTimeout(() => genericPlay(), 150);
    } else {
        continueAfterInteraction(q);
    }
};

const closeManualFeedback = () => {
    if (pendingFeedbackAction.value) {
        executeFeedbackAction(
            pendingFeedbackAction.value.action,
            pendingFeedbackAction.value.seekTo,
            pendingFeedbackAction.value.targetLessonId, // 🔥 ADICIONADO
            pendingFeedbackAction.value.question
        );
    } else if (activeFeedback.value && activePreviewQuestion.value) {
        continueAfterInteraction(activePreviewQuestion.value);
        activeFeedback.value = null;
    }
};

const isSeeking = ref(false);
const interactionGateTime = ref(0);
const validInteractionFrom = ref(-1);
const lastSeekTarget = ref(-1);
const lastSeenTime = ref(-1);
let isResumingFromInteraction = false;
let isLoopingBack = false; // 🔥 ADICIONADO PARA BLINDAR O LOOP
let isProgrammaticSeek = false;
let lastLoopSeekTime = 0;



const requestSeek = (targetTime: number) => {
    // REGRA 1: Não pode avançar/retroceder com interação aberta
    if (isPreviewMode.value && (activePreviewQuestion.value || activeFeedback.value)) {
        console.warn("Ação bloqueada: Responda a interação antes de pular o vídeo.");
        return;
    }

    let t = Math.max(0, Math.min(targetTime, Number(duration.value)));

    // REGRA 2: Porteiro de Avanço
    if (isPreviewMode.value && t > Number(currentTime.value)) {
        const pendingInteractions = props.lesson.questions.filter((q: any) => {
            const qTime = Number(q.timestamp_seconds);
            // 🔥 AJUSTE: Retirada a verificação do resolvedInteractionIds
            return qTime > Number(currentTime.value) && qTime <= t;
        });

        if (pendingInteractions.length > 0) {
            pendingInteractions.sort((a: any, b: any) => Number(a.timestamp_seconds) - Number(b.timestamp_seconds));
            t = Number(pendingInteractions[0].timestamp_seconds) - 0.1;
        }
    }

    genericSeek(t);
};



const checkInteractions = (time: number) => {
    if (lastSeenTime.value !== -1 && Math.abs(time - lastSeenTime.value) > 0.8) {
        if (isProgrammaticSeek) {
            isProgrammaticSeek = false;
        } else {
            lastSeekTarget.value = time;
            if (time < lastSeenTime.value) {
                // 🔥 FORÇA O RESET: Se o aluno voltou o vídeo, ele PODE responder novamente.
                lastResolvedQuestionId.value = null;
                validInteractionFrom.value = -1;

                if (activePreviewQuestion.value && time < Number(activePreviewQuestion.value.timestamp_seconds)) {
                    closePreviewInteraction();
                }
            } else {
                validInteractionFrom.value = time;
            }
        }
    }
    lastSeenTime.value = time;

    if (lastSeekTarget.value !== -1 && time > lastSeekTarget.value) {
        lastSeekTarget.value = -1;
    }

    if (lastResolvedQuestionId.value) {
        const lastQ = props.lesson.questions.find((q: any) => q.id === lastResolvedQuestionId.value);
        if (lastQ) {
            const qTime = Number(lastQ.timestamp_seconds);
            if (time < qTime - 0.5 || time > qTime + 1.5) {
                lastResolvedQuestionId.value = null;
            }
        } else {
            lastResolvedQuestionId.value = null;
        }
    }

    if (Date.now() < interactionGateTime.value) return;
    if (activeFeedback.value) return;

    if (activePreviewQuestion.value) {
        const q = activePreviewQuestion.value;
        const hasLoop = q.loop_start_seconds != null && q.loop_end_seconds != null && Number(q.loop_end_seconds) > Number(q.loop_start_seconds);

        if (hasLoop) {
            // 🔥 Se tem loop, ele deixa rodar. O watch(currentTime) acima assume o controle.
            return;
        } else {
            if (isPlaying.value) genericPause();
            return;
        }
    }

    if (!isPreviewMode.value || activePreviewQuestion.value) return;

    const hit = props.lesson.questions.find((q: any) => {
        // 🔥 Garante que NUNCA ignora se acabamos de rebobinar
        if (q.id === lastResolvedQuestionId.value) return false;
        const qTime = Number(q.timestamp_seconds);
        if (validInteractionFrom.value !== -1 && qTime < validInteractionFrom.value) {
            return false;
        }
        const diff = time - (qTime - 0.15);
        return diff >= 0 && diff < 0.5;
    });

    if (!hit) return;

    if (hit.type === "automatic") {
        lastResolvedQuestionId.value = hit.id;

        if (hit.correct_target_lesson_id && hit.correct_target_lesson_id !== 'current' && Number(hit.correct_target_lesson_id) !== props.lesson.id) {
            const videoName = getLessonTitle(Number(hit.correct_target_lesson_id));
            const timeFormatted = formatTimeHMSms(hit.correct_seek_to || 0);
            genericPause();
            setTimeout(() => {
                openRedirectModal(videoName, timeFormatted, hit);
            }, 50);
            return;
        }

        if (hit.correct_action === "seek" && hit.correct_seek_to != null) {
            genericSeek(Number(hit.correct_seek_to), true);
            setTimeout(() => genericPlay(), 150);
        }
        return;
    }

    const hasLoop = hit.loop_start_seconds != null && hit.loop_end_seconds != null && Number(hit.loop_end_seconds) > Number(hit.loop_start_seconds);

    activePreviewQuestion.value = normalizePreviewQuestion(hit);
    selectedIndices.value = [];
    droppedItems.value = {};
    previewFeedback.value = null;
    revealedOptions.value = [];

    if (!hasLoop) {
        genericPause();
        const exactTime = Number(hit.timestamp_seconds);
        if (Math.abs(time - exactTime) > 0.05) {
            genericSeek(exactTime, true);
        }
    }
};

const isPlayerReady = ref(false);
const initPlayer = async () => { await nextTick(); await video.mount(); };
const isPlaying = computed(() => video.state.value?.state.isPlaying.value ?? false);
const genericPlay = () => video.play();
const genericPause = () => video.pause();

const genericSeek = (time: number, fromInteraction = false) => {
    if (!isPlayerReady.value) return;
    const d = Number(duration.value);
    const targetTime = Number(time);
    const t = d > 0 ? Math.max(0, Math.min(targetTime, d)) : Math.max(0, targetTime);

    if (!fromInteraction && isPreviewMode.value) {
        lastResolvedQuestionId.value = null;
    }

    isProgrammaticSeek = true;
    validInteractionFrom.value = t;
    lastSeenTime.value = t;
    lastSeekTarget.value = t;
    interactionGateTime.value = Date.now() + 400;

    video.seek(t);
    currentTime.value = t;
    currentTimeDisplay.value = formatTimeHMSms(t);
};

const togglePlay = () => (isPlaying.value ? genericPause() : genericPlay());
const relativeSeek = (delta: number) => {
    if (isPreviewMode.value) {
        // No modo aluno (teste), passa pelo Porteiro
        requestSeek(Number(currentTime.value) + Number(delta));
    } else {
        // No modo editor, pulo livre total para facilitar a edição
        genericSeek(Number(currentTime.value) + Number(delta));
    }
};
const handleTimelineClick = (e: MouseEvent) => {
    if (isPreviewMode.value) return;
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
    const percent = (e.clientX - rect.left) / rect.width;
    genericSeek(percent * (Number(duration.value) || 1));
};

onMounted(async () => {
    await initPlayer();
    // 🔥 LÓGICA DE AUTO-CONEXÃO
    const urlParams = new URLSearchParams(window.location.search);
    const autoTargetId = urlParams.get('auto_target');

    if (urlParams.has('auto_target')) {
        setTimeout(() => {
            openNewInteractionPanel();
        }, 1000); // Aguarda 1 segundinho pro vídeo carregar a thumb
    }
    window.addEventListener("keydown", handleKey);
    window.addEventListener("mousemove", handleMouseMove);
    window.addEventListener("mouseup", handleMouseUp);
});

onBeforeUnmount(() => { video.destroy(); window.removeEventListener("keydown", handleKey); window.removeEventListener("mousemove", handleMouseMove); window.removeEventListener("mouseup", handleMouseUp); });

const handleKey = (e: KeyboardEvent) => {
    if (["INPUT", "TEXTAREA"].includes((e.target as HTMLElement).tagName)) return;
    if (e.code === "Space") { e.preventDefault(); togglePlay(); }
    else if (e.code === "ArrowLeft") genericSeek(Number(currentTime.value) - 5);
    else if (e.code === "ArrowRight") genericSeek(Number(currentTime.value) + 5);
};

type HorizontalAlign = "left" | "center" | "right";
type VerticalAlign = "top" | "center" | "bottom";

const FIELD_SECTION_MAP: Record<string, string> = {
    title: "basic", type: "basic", title_color: "style", title_font_size: "style", title_font_family: "style",
    loop_start_seconds: "loop", loop_end_seconds: "loop", options: "options",
    correct_action: "rules", wrong_action: "rules", confirmation_config: "confirmation",
    correct_feedback_config: "feedback_correct", wrong_feedback_config: "feedback_wrong",
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

const form = useForm({
    title: "", timestamp_seconds: 0, loop_seconds: 0, loop_start_seconds: null as number | null, loop_end_seconds: null as number | null,
    type: "single_choice", show_title: true, title_color: "#000000", title_font_size: 20, title_font_family: "Arial, sans-serif",
    title_text_align: "center" as HorizontalAlign, title_vertical_align: "center" as VerticalAlign, background_color: "#ffffff", background_opacity: 1,
    modal_coordinates: { top: 50, left: 50, width: 30, height: 40, radius: 12 }, modal_background_color: "#ffffff", modal_background_opacity: 1,
    question_coordinates: { top: 10, left: 50, width: 40, height: 10, radius: 8 },
    options: [
        {
            text: "",
            is_correct: false,
            coordinates: null,
            shape: 'rectangle',
            group_id: 1,
            text_color: "#000000",
            text_font_size: 14,
            text_font_family: "Arial",
            show_text: true,
            correct_action: null,
            correct_seek_to: null,
            correct_target_lesson_id: null,
            wrong_action: null,
            wrong_seek_to: null,
            wrong_target_lesson_id: null,
            image: null,
            image_preview: null,
            remove_image: false,
            zone_image: null, zone_image_preview: null, remove_zone_image: false, feedback_text: null, _is_zone: false, delay_seconds: 0
        } as any,
    ],
    correct_action: "continue",
    correct_seek_to: null as number | null,
    correct_target_lesson_id: 'current' as string | number | null,
    wrong_action: "continue",
    wrong_seek_to: null as number | null,
    wrong_target_lesson_id: 'current' as string | number | null,

    feedback_text: "", correct_feedback_image: null as File | null, wrong_feedback_image: null as File | null, correct_feedback_image_preview: null as string | null, wrong_feedback_image_preview: null as string | null, remove_correct_feedback_image: false as boolean, remove_wrong_feedback_image: false as boolean,
    correct_feedback_config: DEFAULT_FEEDBACK_CONFIG(), wrong_feedback_config: DEFAULT_FEEDBACK_CONFIG(), confirmation_config: DEFAULT_CONFIRMATION_CONFIG(),
    confirmation_image: null as File | null, confirmation_image_preview: null as string | null, remove_confirmation_image: false as boolean, confirmation_button_image: null as File | null, confirmation_button_image_preview: null as string | null, remove_confirmation_button_image: false as boolean,
    global_background_color: "transparent", global_background_opacity: 1, global_background_coordinates: { top: 0, left: 0, width: 100, height: 100, radius: 0 }, global_background_text: "", global_background_text_color: "#ffffff", global_background_text_font_size: 24, global_background_text_font_family: "Arial, sans-serif", global_background_text_align: "center" as HorizontalAlign, global_background_vertical_align: "center" as VerticalAlign, global_background_image: null as File | null, global_background_image_preview: null as string | null, remove_global_background_image: false as boolean,
    title_image: null as File | null, modal_image: null as File | null, background_image: null as File | null, title_image_preview: null as string | null, modal_image_preview: null as string | null, background_image_preview: null as string | null, remove_title_image: false as boolean, remove_modal_image: false as boolean, remove_background_image: false as boolean,
});

const openNewInteractionPanel = () => {
    if (isPreviewMode.value) return;
    let t = currentTime.value;
    genericPause();
    editingQuestionId.value = null;
    form.reset();
    form.type = "single_choice";
    form.title = "";
    form.show_title = true;
    form.title_color = "#000000";
    form.title_font_size = 20;
    form.title_font_family = "Arial; sans-serif";
    form.title_text_align = "center" as HorizontalAlign;
    form.title_vertical_align = "center" as VerticalAlign;
    form.background_color = "#ffffff";
    form.background_opacity = 1;
    form.modal_coordinates = { top: 50, left: 50, width: 30, height: 40, radius: 12 };
    form.modal_background_color = "#ffffff";
    form.modal_background_opacity = 1;
    form.global_background_color = "transparent";
    form.global_background_opacity = 1;
    form.global_background_coordinates = { top: 0, left: 0, width: 100, height: 100, radius: 0 };
    form.global_background_text = "";
    form.global_background_text_color = "#ffffff";
    form.global_background_text_font_size = 24;
    form.global_background_text_font_family = "Arial, sans-serif";
    form.global_background_text_align = "center";
    form.global_background_vertical_align = "center";
    form.global_background_image = null;
    form.global_background_image_preview = null;
    form.remove_global_background_image = false;
    form.question_coordinates = { top: 10, left: 50, width: 40, height: 10, radius: 8 };

    const tNum = Number(parseFloat(t.toFixed(3)));

    form.options = [
        {
            id: null, text: "", is_correct: false, coordinates: null, shape: 'rectangle', delay_seconds: tNum, group_id: 1, text_color: "#000000", text_font_size: 14, text_font_family: "Arial", show_text: true,
            correct_action: null,
            correct_seek_to: null,
            correct_target_lesson_id: 'current' as string | number | null, // 🔥 CORRIGIDO PARA STRING SEGURA
            wrong_action: null,
            wrong_seek_to: null,
            wrong_target_lesson_id: 'current' as string | number | null,   // 🔥 CORRIGIDO PARA STRING SEGURA
            image: null, image_preview: null, remove_image: false, zone_image: null, zone_image_preview: null, remove_zone_image: false, background_color: "transparent", background_opacity: 1, text_align: "center", vertical_align: "center", feedback_text: null, _is_zone: false
        }
    ];
    form.timestamp_seconds = tNum;
    form.loop_seconds = 0;
    form.loop_start_seconds = null as number | null;
    form.loop_end_seconds = null as number | null;
    form.correct_action = "continue";
    form.correct_seek_to = null as number | null;
    form.correct_target_lesson_id = 'current' as any; // 🔥 CORRIGIDO
    form.wrong_action = "continue";
    form.wrong_seek_to = null as number | null;
    form.wrong_target_lesson_id = 'current' as any;   // 🔥 CORRIGIDO
    form.feedback_text = ""; form.correct_feedback_image = null as File | null; form.wrong_feedback_image = null as File | null; form.correct_feedback_image_preview = null as string | null; form.wrong_feedback_image_preview = null as string | null; form.remove_correct_feedback_image = false as boolean; form.remove_wrong_feedback_image = false as boolean;
    form.correct_feedback_config = DEFAULT_FEEDBACK_CONFIG(); form.wrong_feedback_config = DEFAULT_FEEDBACK_CONFIG(); form.confirmation_config = DEFAULT_CONFIRMATION_CONFIG();
    form.confirmation_image = null as File | null; form.confirmation_image_preview = null as string | null; form.remove_confirmation_image = false as boolean; form.confirmation_button_image = null; form.confirmation_button_image_preview = null; form.remove_confirmation_button_image = false;
    form.title_image = null as File | null; form.modal_image = null as File | null; form.background_image = null as File | null; form.title_image_preview = null as string | null; form.modal_image_preview = null as string | null; form.background_image_preview = null as string | null; form.remove_title_image = false as boolean; form.remove_modal_image = false as boolean; form.remove_background_image = false as boolean;

    const urlParams = new URLSearchParams(window.location.search);
    const autoTargetId = urlParams.get('auto_target');

    if (autoTargetId) {
        form.correct_action = 'seek';
        form.correct_target_lesson_id = String(autoTargetId); // O ID vem como string do URL, perfeito
        form.title = "Nova Ramificação";
    }
    isInteractionPanelOpen.value = true;
};
watch(
    () => form.type,
    (newType, oldType) => {
        if (!isInteractionPanelOpen.value) return;
        if (newType === 'drag_and_drop' && oldType !== 'drag_and_drop') {
            const hasZone = form.options.some((o) => o._is_zone);
            if (!hasZone) {
                form.options = [
                    { id: null, text: "Zona 1", is_correct: false, coordinates: null, shape: 'rectangle', delay_seconds: Number(form.timestamp_seconds), group_id: 1, text_color: "#ffffff", text_font_size: 14, text_font_family: "Arial, sans-serif", show_text: true, correct_action: null, correct_seek_to: null, wrong_action: null, wrong_seek_to: null, zone_image: null, zone_image_preview: null, remove_zone_image: false, background_color: "transparent", background_opacity: 1, text_align: "center", vertical_align: "center", feedback_text: null, _is_zone: true }
                ];
            }
        }
    }
);

function normalizeFeedbackConfig(raw: any) {
    const def = DEFAULT_FEEDBACK_CONFIG();
    if (!raw) return structuredClone(def);
    return { ...def, ...raw, coordinates: { ...def.coordinates, ...(raw.coordinates || {}), }, };
}

function normalizeConfirmationConfig(raw: any) {
    const def = DEFAULT_CONFIRMATION_CONFIG();
    if (!raw) return structuredClone(def);
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

const editInteraction = (q: any) => {
    if (isPreviewMode.value) return;
    genericSeek(Number(q.timestamp_seconds));
    genericPause();
    editingQuestionId.value = q.id;
    form.title = q.title;
    form.show_title = q.show_title;
    form.timestamp_seconds = Number(q.timestamp_seconds);
    form.title_color = q.title_color;
    form.title_font_size = q.title_font_size;
    form.title_font_family = q.title_font_family;
    form.title_text_align = q.title_text_align ?? "center";
    form.title_vertical_align = q.title_vertical_align ?? "center";
    form.background_color = q.background_color;
    form.background_opacity = q.background_opacity ?? 1;

    form.global_background_color = q.global_background_color || "transparent";
    form.global_background_opacity = q.global_background_opacity ?? 1;
    form.global_background_coordinates = { top: 0, left: 0, width: 100, height: 100, radius: 0, ...(q.global_background_coordinates || {}) };
    form.global_background_text = q.global_background_text || "";
    form.global_background_text_color = q.global_background_text_color || "#ffffff";
    form.global_background_text_font_size = q.global_background_text_font_size ?? 24;
    form.global_background_text_font_family = q.global_background_text_font_family || "Arial, sans-serif";
    form.global_background_text_align = q.global_background_text_align || "center";
    form.global_background_vertical_align = q.global_background_vertical_align || "center";
    form.global_background_image = null;
    form.global_background_image_preview = q.global_background_image ?? null;
    form.remove_global_background_image = false;

    form.modal_coordinates = { top: q.modal_coordinates?.top ?? 50, left: q.modal_coordinates?.left ?? 50, width: q.modal_coordinates?.width ?? 30, height: q.modal_coordinates?.height ?? 40, radius: q.modal_coordinates?.radius ?? 12 };
    form.modal_background_color = q.modal_background_color || "#ffffff";
    form.modal_background_opacity = q.modal_background_opacity ?? 1;
    form.question_coordinates = { top: q.question_coordinates?.top ?? 10, left: q.question_coordinates?.left ?? 50, width: q.question_coordinates?.width ?? 40, height: q.question_coordinates?.height ?? 10, radius: q.question_coordinates?.radius ?? 8 };
    form.loop_seconds = Number(q.loop_seconds || 0);
    if (q.loop_start_seconds != null && q.loop_end_seconds != null) { form.loop_start_seconds = Number(q.loop_start_seconds); form.loop_end_seconds = Number(q.loop_end_seconds); } else if (q.loop_seconds > 0) { form.loop_start_seconds = Number((q.timestamp_seconds - q.loop_seconds).toFixed(3)); form.loop_end_seconds = Number(q.timestamp_seconds); } else { form.loop_start_seconds = null; form.loop_end_seconds = null; }
    form.type = q.type;

    form.correct_action = q.correct_action;
    form.correct_seek_to = q.correct_seek_to != null ? Number(q.correct_seek_to) : null;
    form.correct_target_lesson_id = q.correct_target_lesson_id ? String(q.correct_target_lesson_id) : 'current'; // 🔥 CORRIGIDO

    form.wrong_action = q.wrong_action;
    form.wrong_seek_to = q.wrong_seek_to != null ? Number(q.wrong_seek_to) : null;
    form.wrong_target_lesson_id = q.wrong_target_lesson_id ? String(q.wrong_target_lesson_id) : 'current'; // 🔥 CORRIGIDO

    form.feedback_text = q.correct_feedback;

    form.title_image = null; form.modal_image = null; form.background_image = null; form.remove_title_image = false; form.remove_modal_image = false; form.remove_background_image = false;
    form.title_image_preview = q.title_image && q.title_image.length > 0 ? q.title_image : null; form.modal_image_preview = q.modal_image ?? null; form.background_image_preview = q.background_image ?? null;
    form.correct_feedback_image = null; form.wrong_feedback_image = null; form.correct_feedback_image_preview = q.correct_feedback_image ?? null; form.wrong_feedback_image_preview = q.wrong_feedback_image ?? null; form.remove_correct_feedback_image = false; form.remove_wrong_feedback_image = false;
    form.correct_feedback_config = normalizeFeedbackConfig(q.correct_feedback_config); form.wrong_feedback_config = normalizeFeedbackConfig(q.wrong_feedback_config);
    form.confirmation_image = null; form.confirmation_image_preview = q.confirmation_image ?? null; form.remove_confirmation_image = false;
    form.confirmation_config = normalizeConfirmationConfig(q.confirmation_config);
    form.confirmation_button_image = null; form.confirmation_button_image_preview = q.confirmation_button_image ?? null; form.remove_confirmation_button_image = false;

    const opts = q.options.map((o: any) => {
        let isZone = o.is_zone == 1 || o.is_zone === true;
        return {
            id: o.id,
            ...o,
            coordinates: o.coordinates ? { top: Number(o.coordinates.top), left: Number(o.coordinates.left), width: Number(o.coordinates.width), height: Number(o.coordinates.height), radius: o.coordinates.radius != null ? Number(o.coordinates.radius) : (isZone ? 0 : 8) } : null,
            shape: o.shape || 'rectangle',
            delay_seconds: o.delay_seconds != null ? Number((Number(q.timestamp_seconds) + Number(o.delay_seconds)).toFixed(3)) : Number(q.timestamp_seconds),
            feedback_text: o.feedback_text || null,
            _is_zone: isZone,
            group_id: o.group_id || 1,
            text_color: o.text_color || "#000000",
            text_font_size: o.text_font_size || 14,
            text_font_family: o.text_font_family || "Arial, sans-serif",
            show_text: o.show_text ?? true,
            background_color: o.background_color || "transparent",
            background_opacity: o.background_opacity ?? 1,
            text_align: o.text_align || "center",
            vertical_align: o.vertical_align || "center",
            correct_action: o.correct_action ?? null,
            correct_seek_to: o.correct_seek_to != null ? Number(o.correct_seek_to) : null,
            correct_target_lesson_id: o.correct_target_lesson_id ? String(o.correct_target_lesson_id) : 'current', // 🔥 CORRIGIDO
            wrong_action: o.wrong_action ?? null,
            wrong_seek_to: o.wrong_seek_to != null ? Number(o.wrong_seek_to) : null,
            wrong_target_lesson_id: o.wrong_target_lesson_id ? String(o.wrong_target_lesson_id) : 'current', // 🔥 CORRIGIDO
            image: null,
            image_preview: o.image_url ?? null,
            _has_image: !!o.image_url,
            remove_image: false,
            zone_image: null,
            zone_image_preview: o.zone_image ?? o.zone_image_url ?? null,
            remove_zone_image: false,
            _has_zone_image: !!o.zone_image_url,
        };
    });

    form.options = opts;
    isInteractionPanelOpen.value = true;
};

const submitInteraction = () => {
    const params = { course: props.course.id, module: props.module.id, lesson: props.lesson.id, };
    const opts = { forceFormData: true, preserveScroll: true, onSuccess: () => { isInteractionPanelOpen.value = false; router.reload({ only: ["lesson"], }); }, };

    const transformData = (data: any) => {
        let loop_seconds = 0;
        if (data.loop_start_seconds != null && data.loop_end_seconds != null && data.loop_end_seconds > data.loop_start_seconds) {
            loop_seconds = Number((data.loop_end_seconds - data.loop_start_seconds).toFixed(3));
        }

        const baseTimestamp = Number(data.timestamp_seconds) || 0;

        return {
            ...data,
            loop_seconds: Number(loop_seconds),
            timestamp_seconds: baseTimestamp,
            correct_seek_to: data.correct_seek_to != null && data.correct_seek_to !== "" ? Number(data.correct_seek_to) : null,
            // 🔥 CONVERSÃO SEGURA PARA O BACKEND
            correct_target_lesson_id: (data.correct_target_lesson_id && data.correct_target_lesson_id !== 'current') ? Number(data.correct_target_lesson_id) : null,
            wrong_seek_to: data.wrong_seek_to != null && data.wrong_seek_to !== "" ? Number(data.wrong_seek_to) : null,
            // 🔥 CONVERSÃO SEGURA PARA O BACKEND
            wrong_target_lesson_id: (data.wrong_target_lesson_id && data.wrong_target_lesson_id !== 'current') ? Number(data.wrong_target_lesson_id) : null,
            title_text_align: form.title_text_align,
            title_vertical_align: form.title_vertical_align,
            options: data.options.map((o: any) => {
                const optInputDelay = (o.delay_seconds != null && o.delay_seconds !== "") ? Number(o.delay_seconds) : baseTimestamp;
                let relDelay = optInputDelay - baseTimestamp;
                if (relDelay < 0 || isNaN(relDelay)) relDelay = 0;

                return {
                    ...o,
                    is_zone: o._is_zone ? 1 : 0,
                    shape: o.shape || 'rectangle',
                    correct_seek_to: o.correct_seek_to != null && o.correct_seek_to !== "" ? Number(o.correct_seek_to) : null,
                    // 🔥 CONVERSÃO SEGURA PARA O BACKEND NAS OPÇÕES
                    correct_target_lesson_id: (o.correct_target_lesson_id && o.correct_target_lesson_id !== 'current') ? Number(o.correct_target_lesson_id) : null,
                    wrong_seek_to: o.wrong_seek_to != null && o.wrong_seek_to !== "" ? Number(o.wrong_seek_to) : null,
                    // 🔥 CONVERSÃO SEGURA PARA O BACKEND NAS OPÇÕES
                    wrong_target_lesson_id: (o.wrong_target_lesson_id && o.wrong_target_lesson_id !== 'current') ? Number(o.wrong_target_lesson_id) : null,
                    delay_seconds: Number(relDelay.toFixed(3)),
                };
            })
        };
    };

    if (editingQuestionId.value) {
        form.transform(transformData);
        form.post(route("admin.courses.builder.interactions.update", { ...params, question: editingQuestionId.value, }), opts);
    } else {
        form.transform(transformData);
        form.post(route("admin.courses.builder.interactions.store", params), opts);
    }
};

const deleteInteraction = (id: number) => {
    if (confirm("Excluir?")) router.delete(route("admin.courses.builder.interactions.destroy", { course: props.course.id, module: props.module.id, lesson: props.lesson.id, question: id, }));
};

const addOption = (isZone: boolean = false) => {
    const newOpt = {
        id: null, text: "", is_correct: false, coordinates: null, shape: 'rectangle', delay_seconds: Number(form.timestamp_seconds), group_id: 1, text_color: "#000000", text_font_size: 14, text_font_family: "Arial", show_text: true,
        correct_action: null,
        correct_seek_to: null,
        correct_target_lesson_id: null,
        wrong_action: null,
        wrong_seek_to: null,
        wrong_target_lesson_id: null,
        image: null, image_preview: null, background_color: "transparent", background_opacity: 1, text_align: "center", vertical_align: "center", feedback_text: null, _is_zone: isZone,
    };
    form.options.push(newOpt);
};

const removeOption = (i: number) => form.options.splice(i, 1);

const setCorrectOption = (i: number) => {
    if (form.type === "multiple_choice") return;
    form.options.forEach((o, idx) => { o.is_correct = idx === i; if (o.is_correct) { o.wrong_action = null; o.wrong_seek_to = null; } });
};

const addNewZoneGroup = () => {
    const maxGroupId = form.options.reduce((max: number, opt: any) => Math.max(max, opt.group_id || 0), 0);
    const nextGroupId = maxGroupId + 1;
    const newZone = {
        id: null, text: "Nova Zona", is_correct: false, coordinates: null, shape: 'rectangle', delay_seconds: Number(form.timestamp_seconds), group_id: nextGroupId, text_color: "#ffffff", text_font_size: 14, text_font_family: "Arial, sans-serif", show_text: true,
        correct_action: null,
        correct_seek_to: null,
        correct_target_lesson_id: null,
        wrong_action: null,
        wrong_seek_to: null,
        wrong_target_lesson_id: null,
        zone_image: null, zone_image_preview: null, remove_zone_image: false, background_color: "transparent", background_opacity: 1, text_align: "center", vertical_align: "center", feedback_text: null, _is_zone: true,
    };
    form.options.push(newZone);
};

const addItemToZone = (groupId: number) => {
    const newItem = {
        id: null, text: "Nova Peça", is_correct: false, coordinates: null, shape: 'rectangle', delay_seconds: Number(form.timestamp_seconds), group_id: groupId, text_color: "#000000", text_font_size: 14, text_font_family: "Arial, sans-serif", show_text: true,
        correct_action: null,
        correct_seek_to: null,
        correct_target_lesson_id: null,
        wrong_action: null,
        wrong_seek_to: null,
        wrong_target_lesson_id: null,
        image: null, image_preview: null, remove_image: false, background_color: "transparent", background_opacity: 1, text_align: "center", vertical_align: "center", feedback_text: null, _is_zone: false,
    };
    form.options.push(newItem);
};

const removeZoneAndItems = (groupId: number) => {
    if (confirm("Deseja remover esta Zona e todos os itens arrastáveis atrelados a ela?")) {
        for (let i = form.options.length - 1; i >= 0; i--) {
            if (form.options[i].group_id === groupId) form.options.splice(i, 1);
        }
    }
};

const optionOverrideUnused = (opt: any) => { return (form.type === "multiple_choice" && (opt.correct_action || opt.wrong_action)); };

const getActionForAnswer = (q: any, option: any, isCorrect: boolean) => {
    if (isCorrect) return { action: option.correct_action ?? q.correct_action, seek: option.correct_seek_to ?? q.correct_seek_to, };
    return { action: option.wrong_action ?? q.wrong_action, seek: option.wrong_seek_to ?? q.wrong_seek_to, };
};

const lastResolvedQuestionId = ref<number | null>(null);

function shouldShowConfirmation(cfg: any): boolean { return !!cfg; }

function shouldShowConfirmationPreview(q: any): boolean {
    if (!q) return false;
    if (activeFeedback.value) return false;

    // Regra específica para o tipo "info"
    if (q.type === "info") {
        // Se tem mais de 1 opção e o aluno já selecionou algo, mostra o botão Confirmar.
        if (q.options && q.options.length > 1) {
            return selectedIndices.value.length > 0;
        }
        // Se tem 0 ou 1 opção, NUNCA mostra o botão de confirmar (o clique na opção/fundo resolve direto).
        return false;
    }

    if (["single_choice", "multiple_choice"].includes(q.type)) return selectedIndices.value.length > 0;

    if (q.type === "drag_and_drop") return Object.values(droppedItems.value).some((items) => Array.isArray(items) && items.length > 0);

    return false;
}

function shouldShowFeedback(cfg: any): boolean {
    if (!cfg) return false;
    const hasText = typeof cfg.text === "string" && cfg.text.trim().length > 0;
    const hasImage = !!cfg.background_image || !!cfg.background_image_preview;
    return hasText || hasImage;
}


const submitPreviewAnswer = () => {
    if (activeFeedback.value) return;
    if (!activePreviewQuestion.value) return;

    const q = activePreviewQuestion.value;
    let context: any = {};

    if (q.type === "drag_and_drop") {
        const required = q.options.filter((o: any) => o._is_zone);
        if (Object.keys(droppedItems.value).length < required.length) { alert("Preencha todas as zonas."); return; }
        context = { droppedItems: droppedItems.value };
    } else if (q.type !== "automatic") {
        if (q.type === "info" && (!q.options || q.options.length === 0)) { }
        else if (selectedIndices.value.length === 0) return;
        context = { selectedIndices: selectedIndices.value };
    }

    lastResolvedQuestionId.value = q.id;

    if (q.type === "info") {
        let action = q.correct_action || "continue";
        let seekTo = q.correct_seek_to;
        let targetId = q.correct_target_lesson_id;

        if (q.options && q.options.length > 0 && selectedIndices.value.length > 0) {
            const selectedOpt = q.options[selectedIndices.value[0]];
            if (selectedOpt.correct_action) action = selectedOpt.correct_action;
            if (selectedOpt.correct_seek_to !== null) seekTo = selectedOpt.correct_seek_to;
            if (selectedOpt.correct_target_lesson_id) targetId = selectedOpt.correct_target_lesson_id;

            if (selectedOpt.feedback_text && selectedOpt.feedback_text.trim().length > 0) {
                const feedbackConfig = JSON.parse(JSON.stringify(q.correct_feedback_config || DEFAULT_FEEDBACK_CONFIG()));
                feedbackConfig.text = selectedOpt.feedback_text.trim();

                closePreviewInteraction();
                activeFeedback.value = { type: "correct", config: feedbackConfig };
                let durationVal = parseFloat(String(feedbackConfig.duration_seconds));

                if (!isNaN(durationVal) && durationVal > 0) {
                    feedbackTimeout = window.setTimeout(() => { executeFeedbackAction(action, seekTo, targetId, q); }, durationVal * 1000);
                } else {
                    pendingFeedbackAction.value = { action: action, seekTo: seekTo, targetLessonId: targetId, question: q };
                }
                return;
            }
        }

        closePreviewInteraction();

        // 🔥 NOVO: Alerta amigável e retomada de vídeo nas interações do tipo Info
        if (targetId && String(targetId) !== 'current' && Number(targetId) !== props.lesson.id) {
            const videoName = getLessonTitle(Number(targetId));
            const timeFormatted = formatTimeHMSms(seekTo || 0);
            openRedirectModal(videoName, timeFormatted, q);
            return;
        }

        if (action === "seek" && seekTo != null) {
            genericSeek(Number(seekTo), true);
            setTimeout(() => { if (!isPlaying.value) genericPlay(); }, 150);
        } else {
            continueAfterInteraction(q);
        }
        return;
    }

    const result = resolveResponse(q, context);
    const isCorrect = result.result === "correct";
    const baseConfig = isCorrect ? q.correct_feedback_config : q.wrong_feedback_config;
    const feedbackConfig = JSON.parse(JSON.stringify(baseConfig));

    let customTexts: string[] = [];
    if (['single_choice', 'multiple_choice', 'info'].includes(q.type) && selectedIndices.value.length > 0) {
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
    closePreviewInteraction();

    if (shouldShowFeedback(feedbackConfig)) {
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

    // 🔥 NOVO: Alerta amigável e retomada de vídeo após Quizzes diretos (sem feedback)
    if (result.targetLessonId && String(result.targetLessonId) !== 'current' && Number(result.targetLessonId) !== props.lesson.id) {
        const videoName = getLessonTitle(Number(result.targetLessonId));
        const timeFormatted = formatTimeHMSms(result.seekTo || 0);
        openRedirectModal(videoName, timeFormatted, q);
        return;
    }

    if (result.action === "seek" && result.seekTo != null) {
        genericSeek(Number(result.seekTo), true);
        setTimeout(() => genericPlay(), 150);
    } else {
        continueAfterInteraction(q);
    }
};

function canConfirmPreview(q: any): boolean {
    if (!q) return false;
    if (activeFeedback.value) return false;
    if (q.type === "drag_and_drop") {
        const zoneIndexes = q.options.map((o: any, idx: number) => (o._is_zone ? idx : null)).filter((v: number | null) => v !== null) as number[];
        if (zoneIndexes.length === 0) return false;
        return zoneIndexes.every((zoneIdx) => Array.isArray(droppedItems.value[zoneIdx]) && droppedItems.value[zoneIdx].length > 0);
    }
    if (["single_choice", "multiple_choice", "info"].includes(q.type)) {
        if (q.type === 'info' && (!q.options || q.options.length === 0)) return true;
        return selectedIndices.value.length > 0;
    }
    return false;
}

const forceCloseAndSeek = (t: number) => {
    if (feedbackTimeout) { clearTimeout(feedbackTimeout); feedbackTimeout = null; }
    activeFeedback.value = null; pendingFeedbackAction.value = null; activePreviewQuestion.value = null; previewFeedback.value = null; selectedIndices.value = []; droppedItems.value = {}; revealedOptions.value = []; genericSeek(Number(t)); genericPlay();
};
const resumeVideo = () => {
    if (feedbackTimeout) { clearTimeout(feedbackTimeout); feedbackTimeout = null; }
    activeFeedback.value = null; pendingFeedbackAction.value = null; const t = activePreviewQuestion.value?.timestamp_seconds; activePreviewQuestion.value = null; previewFeedback.value = null; selectedIndices.value = []; droppedItems.value = {}; revealedOptions.value = []; if (t) genericSeek(Number(t) + 1.5); genericPlay();
};
function handleDetailsToggle(section: string, event: Event) { const target = event.target as HTMLDetailsElement | null; if (!target) return; saveSectionState(section, target.open); }

const toggleSelection = (i: number) => {
    const q = activePreviewQuestion.value;
    if (!q) return;

    if (['single_choice', 'info'].includes(q.type)) {
        selectedIndices.value = [i];

        // Se for "info" e tiver APENAS 1 opção (ou nenhuma), resolve direto ao clicar na opção.
        // Se tiver mais de 1, não submete (espera o aluno clicar no botão de confirmar que apareceu).
        if (q.type === 'info') {
            if (!q.options || q.options.length <= 1) {
                submitPreviewAnswer();
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

const startElementDrag = (e: MouseEvent, type: "modal" | "title" | "zone" | "feedback_correct" | "feedback_wrong" | "confirmation" | "confirmation_button" | "global_background", index?: number) => {
    if (isPreviewMode.value || isDrawingMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return;
    const rect = stageRef.value.getBoundingClientRect();
    const mouseXPct = ((e.clientX - rect.left) / rect.width) * 100; const mouseYPct = ((e.clientY - rect.top) / rect.height) * 100;
    let startL = 0, startT = 0, startW = 0, startH = 0;
    if (type === "global_background") { const c = form.global_background_coordinates; draggingElement.value = { type, action: "move", startX: mouseXPct, startY: mouseYPct, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height, }; return; }
    if (type === "feedback_correct") { const el = e.currentTarget as HTMLElement; const elRect = el.getBoundingClientRect(); const offsetXPct = ((e.clientX - elRect.left) / rect.width) * 100; const offsetYPct = ((e.clientY - elRect.top) / rect.height) * 100; const c = form.correct_feedback_config.coordinates; draggingElement.value = { type, action: "move", startX: offsetXPct, startY: offsetYPct, startLeft: c.left || 50, startTop: c.top || 50, startWidth: c.width || 40, startHeight: c.height || 20, stageWidth: rect.width, stageHeight: rect.height, }; return; }
    if (type === "feedback_wrong") { const el = e.currentTarget as HTMLElement; const elRect = el.getBoundingClientRect(); const offsetXPct = ((e.clientX - elRect.left) / rect.width) * 100; const offsetYPct = ((e.clientY - elRect.top) / rect.height) * 100; const c = form.wrong_feedback_config.coordinates; draggingElement.value = { type, action: "move", startX: offsetXPct, startY: offsetYPct, startLeft: c.left || 50, startTop: c.top || 50, startWidth: c.width || 40, startHeight: c.height || 20, stageWidth: rect.width, stageHeight: rect.height, }; return; }

    if (type === "confirmation" || type === "confirmation_button") {
        const el = e.currentTarget as HTMLElement;
        const elRect = el.getBoundingClientRect();
        const offsetXPct = ((e.clientX - elRect.left) / rect.width) * 100;
        const offsetYPct = ((e.clientY - elRect.top) / rect.height) * 100;
        const c = type === 'confirmation' ? form.confirmation_config.coordinates : form.confirmation_config.button.coordinates;
        draggingElement.value = { type, action: "move", startX: offsetXPct, startY: offsetYPct, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height };
        return;
    }
    if (type === "modal") { const el = e.currentTarget as HTMLElement; const elRect = el.getBoundingClientRect(); const offsetXPct = ((e.clientX - elRect.left) / rect.width) * 100; const offsetYPct = ((e.clientY - elRect.top) / rect.height) * 100; draggingElement.value = { type: "modal", action: "move", startX: offsetXPct, startY: offsetYPct, startLeft: form.modal_coordinates.left, startTop: form.modal_coordinates.top, startWidth: form.modal_coordinates.width, startHeight: form.modal_coordinates.height, stageWidth: rect.width, stageHeight: rect.height, }; return; }
    if (type === "title") { const el = e.currentTarget as HTMLElement; const elRect = el.getBoundingClientRect(); const offsetXPct = ((e.clientX - elRect.left) / rect.width) * 100; const offsetYPct = ((e.clientY - elRect.top) / rect.height) * 100; draggingElement.value = { type, action: "move", startX: offsetXPct, startY: offsetYPct, startLeft: form.question_coordinates.left, startTop: form.question_coordinates.top, startWidth: form.question_coordinates.width, startHeight: form.question_coordinates.height, stageWidth: rect.width, stageHeight: rect.height, }; return; }
    if (type === "zone" && index !== undefined) { const c = form.options[index]?.coordinates; if (!c) return; startL = c.left; startT = c.top; startW = c.width; startH = c.height; }
    draggingElement.value = { type, index, action: "move", startX: mouseXPct, startY: mouseYPct, startLeft: startL, startTop: startT, startWidth: startW, startHeight: startH, stageWidth: rect.width, stageHeight: rect.height, };
};

const startResizeConfirmation = (e: MouseEvent, subType: 'box' | 'button') => {
    if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return;
    const rect = stageRef.value.getBoundingClientRect();

    const type = subType === 'button' ? 'confirmation_button' : 'confirmation';
    const c = type === 'confirmation' ? form.confirmation_config.coordinates : form.confirmation_config.button.coordinates;

    draggingElement.value = { type: type as any, action: "resize", startX: ((e.clientX - rect.left) / rect.width) * 100, startY: ((e.clientY - rect.top) / rect.height) * 100, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height };
};

const startResize = (e: MouseEvent, index: number) => {
    if (isPreviewMode.value || isDrawingMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return;
    const rect = stageRef.value.getBoundingClientRect(); const mouseXPct = ((e.clientX - rect.left) / rect.width) * 100; const mouseYPct = ((e.clientY - rect.top) / rect.height) * 100;
    const c = form.options[index].coordinates; if (!c) return;
    draggingElement.value = { type: "zone", index, action: "resize", startX: mouseXPct, startY: mouseYPct, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height, };
};

const SNAP_TOLERANCE = 1.5; const GRID_SIZE = 2; const snapToGrid = (v: number) => Math.round(v / GRID_SIZE) * GRID_SIZE; const snapIfClose = (v: number, target: number) => Math.abs(v - target) <= SNAP_TOLERANCE ? target : v;

const handleMouseMove = (e: MouseEvent) => {
    if (isDrawingMode.value && drawingBox.value && stageRef.value) {
        const r = stageRef.value.getBoundingClientRect(); const currentX = e.clientX - r.left; const currentY = e.clientY - r.top;
        const startXPct = (startX / r.width) * 100; const startYPct = (startY / r.height) * 100;
        const currentXPct = (currentX / r.width) * 100; const currentYPct = (currentY / r.height) * 100;
        drawingBox.value.left = Math.min(startXPct, currentXPct); drawingBox.value.top = Math.min(startYPct, currentYPct);
        drawingBox.value.width = Math.max(1, Math.abs(currentXPct - startXPct)); drawingBox.value.height = Math.max(1, Math.abs(currentYPct - startYPct));
        return;
    }
    if (!draggingElement.value || !stageRef.value) return;
    const d = draggingElement.value; const rect = stageRef.value.getBoundingClientRect();
    const mouseXPct = ((e.clientX - rect.left) / rect.width) * 100; const mouseYPct = ((e.clientY - rect.top) / rect.height) * 100;
    const deltaXPct = mouseXPct - d.startX; const deltaYPct = mouseYPct - d.startY;

    if (d.action === "move") {
        const applyMove = (coords: any, newLeft: number, newTop: number) => { const limitedLeft = Math.max(0, Math.min(newLeft, 100 - coords.width)); const limitedTop = Math.max(0, Math.min(newTop, 100 - coords.height)); coords.left = snapToGrid(limitedLeft); coords.top = snapToGrid(limitedTop); };
        if (d.type === "global_background") { applyMove(form.global_background_coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "title") { applyMove(form.question_coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "feedback_correct") { applyMove(form.correct_feedback_config.coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "feedback_wrong") { applyMove(form.wrong_feedback_config.coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "confirmation") { applyMove(form.confirmation_config.coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "confirmation_button") { applyMove(form.confirmation_config.button.coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "modal") { applyMove(form.modal_coordinates, mouseXPct - d.startX, mouseYPct - d.startY); return; }
        if (d.type === "zone" && d.index !== undefined) {
            const z = form.options[d.index]?.coordinates; if (!z) return;
            const newLeft = d.startLeft + deltaXPct; const newTop = d.startTop + deltaYPct;
            const limitedLeft = Math.max(0, Math.min(newLeft, 100 - z.width)); const limitedTop = Math.max(0, Math.min(newTop, 100 - z.height));
            z.left = snapToGrid(limitedLeft); z.top = snapToGrid(limitedTop);
        }
    }
    if (d.action === "resize") {
        const applyResize = (coords: any, newWidth: number, newHeight: number) => { coords.width = Math.min(Math.max(5, newWidth), 100 - coords.left); coords.height = Math.min(Math.max(5, newHeight), 100 - coords.top); };
        if (d.type === "global_background") { applyResize(form.global_background_coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "title") { applyResize(form.question_coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "feedback_correct") { applyResize(form.correct_feedback_config.coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "feedback_wrong") { applyResize(form.wrong_feedback_config.coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "confirmation") { applyResize(form.confirmation_config.coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "confirmation_button") { applyResize(form.confirmation_config.button.coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "modal") { applyResize(form.modal_coordinates, mouseXPct - d.startLeft, mouseYPct - d.startTop); return; }
        if (d.type === "zone" && d.index !== undefined) { const z = form.options[d.index]?.coordinates; if (!z) return; applyResize(z, mouseXPct - d.startLeft, mouseYPct - d.startTop); }
    }
};

const handleMouseUp = () => {
    if (isDrawingMode.value && drawingBox.value && activeOptionIndex.value !== null) {
        const isZone = form.options[activeOptionIndex.value]?._is_zone;
        const box = { ...drawingBox.value, radius: isZone ? 0 : 8 };
        box.width = Math.max(5, box.width); box.height = Math.max(5, box.height);
        const updatedOptions = [...form.options];
        if (updatedOptions[activeOptionIndex.value]) { updatedOptions[activeOptionIndex.value].coordinates = { ...box }; }
        form.options = updatedOptions;
        isDrawingMode.value = false;
        drawingBox.value = null;
        isInteractionPanelOpen.value = true;
        // 🔥 RESTAURA O SCROLL APÓS DESENHAR
        nextTick(() => { if (panelScrollContainer.value) panelScrollContainer.value.scrollTop = savedScrollPosition; });
    }
    draggingElement.value = null;
};

const startDrawing = (e: MouseEvent) => {
    if (isPreviewMode.value) return;
    if (!isDrawingMode.value || !playerContainer.value) return;
    e.preventDefault();
    if (!stageRef.value) return;
    const r = stageRef.value.getBoundingClientRect();
    startX = e.clientX - r.left; startY = e.clientY - r.top;
    drawingBox.value = { left: (startX / r.width) * 100, top: (startY / r.height) * 100, width: 0, height: 0, radius: 8 };
};
const cancelDrawing = () => {
    isDrawingMode.value = false;
    drawingBox.value = null;
    isInteractionPanelOpen.value = true;
    // 🔥 RESTAURA O SCROLL AO CANCELAR
    nextTick(() => { if (panelScrollContainer.value) panelScrollContainer.value.scrollTop = savedScrollPosition; });
};
const enableDrawing = (i: number) => {
    // 🔥 GUARDA O SCROLL ANTES DE FECHAR O PAINEL
    if (panelScrollContainer.value) savedScrollPosition = panelScrollContainer.value.scrollTop;
    isInteractionPanelOpen.value = false;
    activeOptionIndex.value = i;
    isDrawingMode.value = true;
};
const clearHotspot = (i: number) => { if (form.options[i]) form.options[i].coordinates = null; };

function onImageSelect(event: Event, target: "title" | "modal" | "background" | "global_background") {
    const input = event.target as HTMLInputElement | null; if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    if (target === "title") { form.title_image = file; form.title_image_preview = preview; form.remove_title_image = false; }
    if (target === "modal") { form.modal_image = file; form.modal_image_preview = preview; form.remove_modal_image = false; }
    if (target === "background") { form.background_image = file; form.background_image_preview = preview; form.remove_background_image = false; }
    if (target === "global_background") { form.global_background_image = file; form.global_background_image_preview = preview; form.remove_global_background_image = false; }
}

function removeGlobalBackgroundImage() { form.global_background_image = null; form.global_background_image_preview = null; form.remove_global_background_image = true; }

function onFeedbackImageSelect(event: Event, type: "correct" | "wrong") {
    const input = event.target as HTMLInputElement;
    if (!input?.files?.length) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    if (type === "correct") { form.correct_feedback_image = file; form.correct_feedback_image_preview = preview; form.remove_correct_feedback_image = false; }
    else { form.wrong_feedback_image = file; form.wrong_feedback_image_preview = preview; form.remove_wrong_feedback_image = false; }
}

function onConfirmationImageSelect(event: Event) {
    const input = event.target as HTMLInputElement; if (!input?.files?.length) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    form.confirmation_image = file; form.confirmation_image_preview = preview; form.remove_confirmation_image = false;
}

function onConfirmationButtonImageSelect(event: Event) {
    const input = event.target as HTMLInputElement; if (!input?.files?.length) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    form.confirmation_button_image = file; form.confirmation_button_image_preview = preview; form.remove_confirmation_button_image = false;
}

function removeCorrectFeedbackImage() { form.correct_feedback_image = null; form.correct_feedback_image_preview = null; form.remove_correct_feedback_image = true; }
function removeWrongFeedbackImage() { form.wrong_feedback_image = null; form.wrong_feedback_image_preview = null; form.remove_wrong_feedback_image = true; }
function onZoneImageSelect(event: Event, idx: number) {
    const input = event.target as HTMLInputElement; if (!input?.files?.length) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    form.options[idx].zone_image = file; form.options[idx].zone_image_preview = preview;
}

function onOptionImageSelect(event: Event, idx: number) {
    const input = event.target as HTMLInputElement | null; if (!input || !input.files || input.files.length === 0) return;
    const file = input.files[0]; const preview = URL.createObjectURL(file);
    form.options[idx].image = file; form.options[idx].image_preview = preview;
}

function zoneImagePreview(zone: any) { const item = form.options.find((o: any) => !o._is_zone && o.group_id === zone.group_id && o.image_preview); return item?.image_preview ?? null; }
function resolveZoneImage(zone: any, options: any[]) { const item = options.find((o: any) => !o._is_zone && o.group_id === zone.group_id && (o.image_preview || o.image_url)); return item?.image_preview || item?.image_url || null; }

function normalizePreviewQuestion(q: any) {
    let loop_start = q.loop_start_seconds; let loop_end = q.loop_end_seconds;
    if (loop_start == null && loop_end == null && q.loop_seconds > 0) { loop_start = q.timestamp_seconds - q.loop_seconds; loop_end = q.timestamp_seconds; }

    return {
        ...q,
        loop_start_seconds: loop_start, loop_end_seconds: loop_end,
        correct_feedback_config: normalizeFeedbackConfig({ ...q.correct_feedback_config, background_image: q.correct_feedback_image ?? null, }),
        wrong_feedback_config: normalizeFeedbackConfig({ ...q.wrong_feedback_config, background_image: q.wrong_feedback_image ?? null, }),
        confirmation_config: normalizeConfirmationConfig({ ...q.confirmation_config, background_image: q.confirmation_image ?? null, }),
        options: (q.options || []).map((o: any) => {
            let isZone = o.is_zone == 1 || o.is_zone === true;
            return {
                ...o,
                _is_zone: isZone,
                coordinates: o.coordinates ? { top: Number(o.coordinates.top), left: Number(o.coordinates.left), width: Number(o.coordinates.width), height: Number(o.coordinates.height), radius: o.coordinates.radius != null ? Number(o.coordinates.radius) : (isZone ? 0 : 8) } : null,
                shape: o.shape || 'rectangle',
                zone_image: o.zone_image || o.zone_image_url || null,
                image_url: o.image_url || null,
            };
        }),
    };
}

function removeTitleImage() { form.title_image = null; form.title_image_preview = null; form.remove_title_image = true; }
function removeOptionImage(idx: number) { const opt = form.options[idx]; opt.image = null; opt.image_preview = null; opt.remove_image = true; }
function removeZoneImage(idx: number) { const opt = form.options[idx]; opt.zone_image = null; opt.zone_image_preview = null; opt.remove_zone_image = true; }
function removeModalImage() { form.modal_image = null; form.modal_image_preview = null; form.remove_modal_image = true; }
function removeBackgroundImage() { form.background_image = null; form.background_image_preview = null; form.remove_background_image = true; }

// --- FUNÇÕES DE RESIZE QUE FALTARAM ---
const startResizeGlobalBackground = (e: MouseEvent) => { if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return; const rect = stageRef.value.getBoundingClientRect(); draggingElement.value = { type: "global_background", action: "resize", startX: ((e.clientX - rect.left) / rect.width) * 100, startY: ((e.clientY - rect.top) / rect.height) * 100, startLeft: form.global_background_coordinates.left, startTop: form.global_background_coordinates.top, startWidth: form.global_background_coordinates.width, startHeight: form.global_background_coordinates.height, stageWidth: rect.width, stageHeight: rect.height, }; };
const startResizeTitle = (e: MouseEvent) => { if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return; const rect = stageRef.value.getBoundingClientRect(); const mouseXPct = ((e.clientX - rect.left) / rect.width) * 100; const mouseYPct = ((e.clientY - rect.top) / rect.height) * 100; const c = form.question_coordinates; draggingElement.value = { type: "title", action: "resize", startX: mouseXPct, startY: mouseYPct, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height, }; };
const startResizeModal = (e: MouseEvent) => { if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return; const rect = stageRef.value.getBoundingClientRect(); const mouseXPct = ((e.clientX - rect.left) / rect.width) * 100; const mouseYPct = ((e.clientY - rect.top) / rect.height) * 100; const c = form.modal_coordinates; draggingElement.value = { type: "modal", action: "resize", startX: mouseXPct, startY: mouseYPct, startLeft: c.left, startTop: c.top, startWidth: c.width, startHeight: c.height, stageWidth: rect.width, stageHeight: rect.height, }; };
const startResizeFeedbackCorrect = (e: MouseEvent) => { if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return; const rect = stageRef.value.getBoundingClientRect(); const c = form.correct_feedback_config.coordinates; draggingElement.value = { type: "feedback_correct", action: "resize", startX: ((e.clientX - rect.left) / rect.width) * 100, startY: ((e.clientY - rect.top) / rect.height) * 100, startLeft: c.left || 50, startTop: c.top || 50, startWidth: c.width || 40, startHeight: c.height || 20, stageWidth: rect.width, stageHeight: rect.height, }; };
const startResizeFeedbackWrong = (e: MouseEvent) => { if (isPreviewMode.value) return; e.stopPropagation(); e.preventDefault(); if (!stageRef.value) return; const rect = stageRef.value.getBoundingClientRect(); const c = form.wrong_feedback_config.coordinates; draggingElement.value = { type: "feedback_wrong", action: "resize", startX: ((e.clientX - rect.left) / rect.width) * 100, startY: ((e.clientY - rect.top) / rect.height) * 100, startLeft: c.left || 50, startTop: c.top || 50, startWidth: c.width || 40, startHeight: c.height || 20, stageWidth: rect.width, stageHeight: rect.height, }; };

const optionIsCorrect = (opt: any) => opt.is_correct === true;
const optionIsWrong = (opt: any) => opt.is_correct !== true;
const resolveOptionFlow = (opt: any) => { const correct = opt.is_correct; const action = correct ? opt.correct_action ?? form.correct_action : opt.wrong_action ?? form.wrong_action; const seek = correct ? opt.correct_seek_to ?? form.correct_seek_to : opt.wrong_seek_to ?? form.wrong_seek_to; if (action === "seek" && typeof seek === "number" && !isNaN(seek)) { return `→ ${formatTimeHMSms(Number(seek))}`; } return "→ continuar"; };

const { isFullscreen, toggleFullscreen } = useFullscreen(stageRef);
watchEffect(() => { const errors = form.errors; if (!errors || Object.keys(errors).length === 0) return; const firstField = Object.keys(errors)[0]; const section = FIELD_SECTION_MAP[firstField]; if (!section) return; saveSectionState(section, true); nextTick(() => { const el = document.querySelector(`[data-section="${section}"]`) as HTMLElement | null; el?.scrollIntoView({ behavior: "smooth", block: "start" }); el?.classList.add("ring-2", "ring-red-400"); setTimeout(() => { el?.classList.remove("ring-2", "ring-red-400"); }, 2000); }); });
watchEffect(() => {
    if (isPlayerReady.value) {
        video.setVolume?.(currentVolume.value);
        video.setSpeed?.(currentPlaybackRate.value);
    }
});
const onTitleMouseDown = (e: MouseEvent) => { const target = e.target as HTMLElement; if (target.closest("[data-resize-handle]")) return; startElementDrag(e, "title"); };
</script>

<template>
    <AuthenticatedLayout title="Editor de Aula">
        <TooltipProvider>
            <div class="h-[calc(100vh-4rem)] flex overflow-hidden bg-slate-950 text-slate-200">
                <div class="flex flex-col flex-1 p-6 overflow-y-auto transition-all duration-300"
                    :class="isInteractionPanelOpen ? 'w-2/3 pr-2' : 'w-full'">
                    <div class="w-full max-w-6xl mx-auto">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <Link :href="route('admin.courses.builder.index', props.course.id)">
                                    <Button variant="secondary" size="icon">
                                        <ChevronLeft />
                                    </Button>
                                </Link>
                                <div>
                                    <h1 class="text-xl font-bold">
                                        {{ props.lesson.title }}
                                    </h1>
                                    <div class="font-mono text-sm text-slate-400">
                                        {{ currentTimeDisplay }} /
                                        {{ formatTimeHMSms(duration) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">

                                <a v-if="props.studentLink" :href="props.studentLink" target="_blank"
                                    class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-blue-400 transition-colors border rounded-full bg-blue-600/20 border-blue-500/30 hover:bg-blue-600/40 hover:text-blue-300">
                                    <ExternalLink class="w-4 h-4" />
                                    Visão do Aluno
                                </a>

                                <div
                                    class="flex items-center gap-3 px-4 py-2 border rounded-full bg-slate-900 border-slate-800">
                                    <Label class="text-xs font-bold uppercase cursor-pointer text-slate-400">Modo
                                        Teste</Label>
                                    <NativeSwitch v-model="isPreviewMode" />
                                    <Button variant="ghost" size="icon" @click="toggleFullscreen">
                                        <Maximize2 />
                                    </Button>
                                </div>

                            </div>
                        </div>
                        <div ref="stageRef"
                            class="relative overflow-hidden bg-black border shadow-2xl select-none aspect-video rounded-xl border-slate-800 group"
                            @mousedown="isDrawingMode && startDrawing($event)">
                            <div ref="playerContainer" class="absolute inset-0 z-0"></div>

                            <div ref="floatingPanelRef" v-if="!isPreviewMode && isInteractionPanelOpen"
                                class="absolute top-0 left-0 z-[120] select-none"
                                :style="{ transform: `translate(${panelState.x}px, ${panelState.y}px)` }">
                                <div
                                    class="overflow-hidden border shadow-2xl w-52 rounded-xl bg-slate-900/95 backdrop-blur border-slate-700">
                                    <div class="flex items-center justify-between px-3 py-2 cursor-move bg-slate-800"
                                        @mousedown.stop="startPanelDrag">
                                        <span class="text-xs font-bold text-slate-300">Camadas</span>
                                        <button @click.stop="togglePanelMinimize"
                                            class="text-slate-400 hover:text-white">
                                            <component :is="panelState.minimized ? ChevronDown : ChevronUp"
                                                class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div v-show="!panelState.minimized" class="p-3 space-y-2 text-xs">
                                        <div v-for="(visible, key) in canvasLayers" :key="key"
                                            class="flex items-center justify-between cursor-pointer group"
                                            @click="toggleLayer(key)">
                                            <span class="capitalize text-slate-300 group-hover:text-white">
                                                {{ key.replace('_', ' ') }}
                                            </span>
                                            <component :is="visible ? Eye : EyeOff" class="w-4 h-4 transition-opacity"
                                                :class="visible ? 'text-amber-400' : 'text-slate-500'" />
                                        </div>
                                        <div class="pt-2 mt-2 border-t border-slate-700">
                                            <button @click="showAllLayers"
                                                class="w-full py-1 text-xs font-bold text-center text-amber-400 hover:text-amber-300">Mostrar
                                                Tudo</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="activeFeedback"
                                class="absolute z-[60] overflow-hidden shadow-2xl flex flex-col pointer-events-auto"
                                :style="{
                                    top: (activeFeedback.config.coordinates?.top || 50) + '%',
                                    left: (activeFeedback.config.coordinates?.left || 50) + '%',
                                    width: (activeFeedback.config.coordinates?.width || 40) + '%',
                                    height: (activeFeedback.config.coordinates?.height || 20) + '%',
                                    borderRadius: (activeFeedback.config.coordinates?.radius ?? 12) + 'px'
                                }"
                                :class="activeFeedback.type === 'correct' ? 'ring-2 ring-green-500 border border-green-500' : 'ring-2 ring-red-500 border border-red-500'">

                                <div class="absolute inset-0 z-0 pointer-events-none"
                                    :style="{ backgroundColor: activeFeedback.config.background_color || 'transparent', opacity: activeFeedback.config.background_opacity ?? 1 }">
                                </div>

                                <img v-if="activeFeedback.config.background_image || activeFeedback.config.background_image_preview"
                                    :src="activeFeedback.config.background_image || activeFeedback.config.background_image_preview"
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

                                    <span class="w-full font-bold whitespace-pre-wrap drop-shadow-md shrink-0" :style="{
                                        color: activeFeedback.config.text_color,
                                        fontSize: activeFeedback.config.text_font_size + 'px',
                                        fontFamily: activeFeedback.config.text_font_family,
                                        textAlign: activeFeedback.config.text_align || 'center'
                                    }">
                                        {{ activeFeedback.config.text }}
                                    </span>

                                    <div v-if="activeFeedback.config.vertical_align === 'center' || activeFeedback.config.vertical_align === 'top' || !activeFeedback.config.vertical_align"
                                        class="flex-grow shrink-0 min-h-[0px]"></div>
                                </div>
                            </div>

                            <ConfirmationHotspot
                                v-if="activePreviewQuestion && !activeFeedback && shouldShowConfirmation(activePreviewQuestion.confirmation_config) && shouldShowConfirmationPreview(activePreviewQuestion)"
                                :data="{
                                    ...activePreviewQuestion.confirmation_config,
                                    disabled: !canConfirmPreview(activePreviewQuestion),
                                    background_image: activePreviewQuestion.confirmation_image,
                                    button_image: activePreviewQuestion.confirmation_button_image
                                }" class="z-[55]"
                                @confirm="canConfirmPreview(activePreviewQuestion) && submitPreviewAnswer()" />

                            <div v-if="!isPreviewMode && isInteractionPanelOpen && canvasLayers.global_background"
                                class="absolute z-10 overflow-hidden border border-dashed cursor-move group border-slate-500/50"
                                :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'global_background' }"
                                :style="{
                                    top: (form.global_background_coordinates?.top ?? 0) + '%', left: (form.global_background_coordinates?.left ?? 0) + '%',
                                    width: (form.global_background_coordinates?.width ?? 100) + '%', height: (form.global_background_coordinates?.height ?? 100) + '%',
                                    borderRadius: (form.global_background_coordinates?.radius ?? 0) + 'px'
                                }" @mousedown.stop="startElementDrag($event, 'global_background')">

                                <div class="absolute inset-0 pointer-events-none"
                                    :style="{ backgroundColor: form.global_background_color || 'transparent', opacity: form.global_background_opacity ?? 1 }">
                                </div>
                                <img v-if="form.global_background_image_preview"
                                    :src="form.global_background_image_preview"
                                    class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                    :style="{ opacity: form.global_background_opacity ?? 1 }" />

                                <div class="absolute inset-0 z-20 flex p-4 pointer-events-none"
                                    :style="{ justifyContent: form.global_background_text_align === 'left' ? 'flex-start' : (form.global_background_text_align === 'right' ? 'flex-end' : 'center'), alignItems: form.global_background_vertical_align === 'top' ? 'flex-start' : (form.global_background_vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                                    <span v-if="form.global_background_text"
                                        class="font-bold text-center break-words drop-shadow-md"
                                        :style="{ color: form.global_background_text_color, fontSize: form.global_background_text_font_size + 'px', fontFamily: form.global_background_text_font_family }">
                                        {{ form.global_background_text }}
                                    </span>
                                </div>
                                <div class="absolute bottom-0 right-0 z-30 hidden w-4 h-4 bg-slate-500 cursor-nwse-resize group-hover:block"
                                    @mousedown.stop="startResizeGlobalBackground"></div>
                            </div>

                            <div v-if="isPreviewMode && activePreviewQuestion"
                                class="absolute z-10 overflow-hidden pointer-events-none" :style="{
                                    top: (activePreviewQuestion.global_background_coordinates?.top ?? 0) + '%', left: (activePreviewQuestion.global_background_coordinates?.left ?? 0) + '%',
                                    width: (activePreviewQuestion.global_background_coordinates?.width ?? 100) + '%', height: (activePreviewQuestion.global_background_coordinates?.height ?? 100) + '%',
                                    borderRadius: (activePreviewQuestion.global_background_coordinates?.radius ?? 0) + 'px'
                                }">
                                <div class="absolute inset-0"
                                    :style="{ backgroundColor: activePreviewQuestion.global_background_color || 'transparent', opacity: activePreviewQuestion.global_background_opacity ?? 1 }">
                                </div>
                                <img v-if="activePreviewQuestion.global_background_image"
                                    :src="activePreviewQuestion.global_background_image"
                                    class="absolute inset-0 object-cover w-full h-full"
                                    :style="{ opacity: activePreviewQuestion.global_background_opacity ?? 1 }" />

                                <div class="absolute inset-0 z-20 flex p-4"
                                    :style="{ justifyContent: activePreviewQuestion.global_background_text_align === 'left' ? 'flex-start' : (activePreviewQuestion.global_background_text_align === 'right' ? 'flex-end' : 'center'), alignItems: activePreviewQuestion.global_background_vertical_align === 'top' ? 'flex-start' : (activePreviewQuestion.global_background_vertical_align === 'bottom' ? 'flex-end' : 'center') }">
                                    <span v-if="activePreviewQuestion.global_background_text"
                                        class="font-bold text-center break-words drop-shadow-md"
                                        :style="{ color: activePreviewQuestion.global_background_text_color, fontSize: activePreviewQuestion.global_background_text_font_size + 'px', fontFamily: activePreviewQuestion.global_background_text_font_family }">
                                        {{ activePreviewQuestion.global_background_text }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="isDrawingMode" class="absolute inset-0 z-[100] cursor-crosshair bg-black/20">
                                <div v-if="drawingBox" class="absolute border-2 border-amber-500 bg-amber-500/30"
                                    :style="{ top: drawingBox.top + '%', left: drawingBox.left + '%', width: drawingBox.width + '%', height: drawingBox.height + '%', borderRadius: (drawingBox.radius ?? 8) + 'px' }">
                                </div>
                                <div
                                    class="absolute px-4 py-2 text-sm font-bold text-white -translate-x-1/2 rounded-full shadow-lg pointer-events-none top-4 left-1/2 bg-black/80">
                                    Desenhe a Zona
                                </div>
                                <button @click.stop="cancelDrawing"
                                    class="absolute px-3 py-1 text-xs text-white bg-red-600 rounded pointer-events-auto top-4 right-4">
                                    Cancelar
                                </button>
                            </div>

                            <template v-if="!isPreviewMode && (isInteractionPanelOpen || isDrawingMode)">
                                <FeedbackHotspot
                                    v-if="!['automatic', 'info'].includes(form.type) && canvasLayers.feedback_correct"
                                    :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'feedback_correct' }"
                                    :data="{ ...form.correct_feedback_config, background_image: form.correct_feedback_image_preview }"
                                    editable @drag-start="startElementDrag($event, 'feedback_correct')"
                                    @resize-start="startResizeFeedbackCorrect" />

                                <FeedbackHotspot
                                    v-if="!['automatic', 'info'].includes(form.type) && canvasLayers.feedback_wrong"
                                    :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'feedback_wrong' }"
                                    :data="{ ...form.wrong_feedback_config, background_image: form.wrong_feedback_image_preview }"
                                    editable @drag-start="startElementDrag($event, 'feedback_wrong')"
                                    @resize-start="startResizeFeedbackWrong" />

                                <ConfirmationHotspot v-if="needsConfirmation && canvasLayers.confirmation"
                                    :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'confirmation' }"
                                    :data="{
                                        ...form.confirmation_config,
                                        background_image: form.confirmation_image_preview,
                                        button_image: form.confirmation_button_image_preview
                                    }" editable
                                    @drag-start="(e, subType) => startElementDrag(e, subType === 'button' ? 'confirmation_button' : 'confirmation')"
                                    @resize-start="(e, subType) => startResizeConfirmation(e, subType)" />
                                <TitleHotspot v-if="canvasLayers.title"
                                    :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'title' }"
                                    :data="{
                                        coordinates: form.question_coordinates, background_color: form.background_color, background_opacity: form.background_opacity,
                                        background_image: form.title_image_preview, text: form.title || 'Título',
                                        text_style: { color: form.title_color, size: form.title_font_size, family: form.title_font_family, align: form.title_text_align, vertical: form.title_vertical_align },
                                    }" editable @drag-start="startElementDrag($event, 'title')"
                                    @resize-start="startResizeTitle" />

                                <div v-if="shouldShowEditorModal && canvasLayers.modal"
                                    :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'modal' }"
                                    class="absolute z-50 p-4 overflow-y-auto border-2 border-dashed shadow-2xl cursor-move group"
                                    :style="{ top: form.modal_coordinates.top + '%', left: form.modal_coordinates.left + '%', width: form.modal_coordinates.width + '%', height: form.modal_coordinates.height + '%', transform: 'translate(-50%, -50%)', borderRadius: (form.modal_coordinates.radius ?? 12) + 'px' }"
                                    @mousedown.stop="startElementDrag($event, 'modal')">
                                    <div class="absolute inset-0 pointer-events-none -z-10"
                                        :style="{ backgroundColor: form.modal_background_color || '#ffffff', opacity: form.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                                    </div>
                                    <div v-if="form.modal_image_preview"
                                        class="absolute inset-0 pointer-events-none -z-10"
                                        :style="{ backgroundImage: `url(${form.modal_image_preview})`, backgroundSize: 'cover', backgroundPosition: 'center', opacity: form.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                                    </div>
                                    <div class="absolute z-20 top-2 right-2 text-slate-400 hover:text-amber-500">
                                        <Move class="w-5 h-5" />
                                    </div>

                                    <!-- <div v-if="form.type === 'info' && (!form.options || form.options.length === 0)"
                                        class="flex items-center justify-center h-full text-center">
                                        <p class="text-lg font-bold text-slate-800 drop-shadow-sm">{{ form.feedback_text
                                            || "Atenção." }}</p>
                                    </div> -->
                                    <div class="relative z-10 flex flex-col h-full gap-3 mt-2">
                                        <template v-for="(o, i) in form.options" :key="i">
                                            <div v-if="!o.coordinates && !o._is_zone"
                                                class="relative flex items-center gap-3 p-3 overflow-hidden border shadow-sm"
                                                :style="{ borderColor: (o.text_color || '#000') + '40', borderRadius: (o.coordinates?.radius ?? 8) + 'px' }">
                                                <div class="absolute inset-0 pointer-events-none"
                                                    :style="{ backgroundColor: o.background_color || 'transparent', opacity: o.background_opacity ?? 1 }">
                                                </div>
                                                <img v-if="o.image_preview" :src="o.image_preview"
                                                    class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                                    :style="{ opacity: o.background_opacity ?? 1 }" />
                                                <div class="z-10 flex items-center justify-center w-5 h-5 border-2 shrink-0 bg-black/10"
                                                    :class="['single_choice', 'info'].includes(form.type) ? 'rounded-full' : 'rounded-md'"
                                                    :style="{ borderColor: o.text_color || '#000' }"></div>
                                                <div class="z-10 flex flex-1 pointer-events-none"
                                                    :style="{ justifyContent: o.text_align === 'left' ? 'flex-start' : (o.text_align === 'right' ? 'flex-end' : 'center') }">
                                                    <span class="font-bold drop-shadow-md"
                                                        :style="{ color: o.text_color, fontSize: o.text_font_size + 'px', fontFamily: o.text_font_family }">{{
                                                            o.text || "Opção " + (i + 1) }}</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="absolute bottom-0 right-0 z-20 hidden w-5 h-5 rounded-tl bg-amber-500 cursor-nwse-resize group-hover:block"
                                        @mousedown.stop="startResizeModal" />
                                </div>

                                <template v-for="(opt, idx) in form.options"
                                    :key="'edit-opt-' + idx + '-' + (opt.coordinates ? 'coord' : 'null')">
                                    <div v-if="opt.coordinates && canvasLayers.zones"
                                        :class="{ 'opacity-30 pointer-events-none': canvasFocus && canvasFocus !== 'zones' }"
                                        class="absolute z-50 overflow-visible cursor-move group" :style="{
                                            top: opt.coordinates.top + '%',
                                            left: opt.coordinates.left + '%',
                                            width: opt.coordinates.width + '%',
                                            height: opt.coordinates.height + '%'
                                        }" @mousedown.stop="startElementDrag($event, 'zone', idx)">

                                        <div class="absolute inset-0 overflow-hidden" :style="{
                                            borderRadius: (opt.coordinates.radius ?? (opt._is_zone ? 0 : 8)) + 'px',
                                            borderStyle: opt._is_zone ? 'dashed' : 'solid',
                                            borderWidth: '2px',
                                            borderColor: opt._is_zone ? 'rgba(255,255,255,0.7)' : '#60a5fa',
                                            clipPath: getShapeClipPath(opt.shape)
                                        }">
                                            <div class="absolute inset-0 pointer-events-none"
                                                :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                                            </div>

                                            <img v-if="opt.zone_image_preview" :src="opt.zone_image_preview"
                                                class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                                :style="{ opacity: opt.background_opacity ?? 1 }" />
                                            <img v-else-if="opt.image_preview" :src="opt.image_preview"
                                                class="absolute inset-0 object-contain w-full h-full pointer-events-none"
                                                :style="{ opacity: opt.background_opacity ?? 1 }" />
                                        </div>

                                        <div class="absolute px-2 py-0.5 mb-1 text-[10px] text-white rounded pointer-events-none top-1 left-1 z-30"
                                            :class="opt._is_zone ? 'bg-black/80' : 'bg-blue-900/80'">
                                            {{ opt._is_zone ? 'Zona ' + opt.group_id : (form.type === 'drag_and_drop' ?
                                                'Peça ' + opt.group_id : 'Opção ' + (idx + 1)) }}
                                        </div>

                                        <div class="absolute inset-0 z-20 flex p-2 pointer-events-none" :style="{
                                            justifyContent: opt.text_align === 'left' ? 'flex-start' : (opt.text_align === 'right' ? 'flex-end' : 'center'),
                                            alignItems: opt.vertical_align === 'top' ? 'flex-start' : (opt.vertical_align === 'bottom' ? 'flex-end' : 'center'),
                                            clipPath: getShapeClipPath(opt.shape)
                                        }">
                                            <span v-if="opt.show_text"
                                                class="font-bold text-center break-words drop-shadow-md"
                                                :style="{ color: opt.text_color || '#ffffff', fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">
                                                {{ opt.text || (opt._is_zone ? "(Zona)" : "(Opção)") }}
                                            </span>
                                        </div>

                                        <div class="absolute bottom-0 right-0 z-30 hidden w-4 h-4 bg-amber-500 cursor-nwse-resize group-hover:block"
                                            @mousedown.stop="startResize($event, idx)"></div>
                                    </div>
                                </template>
                            </template>

                            <TitleHotspot
                                v-if="activePreviewQuestion?.show_title && activePreviewQuestion.type !== 'automatic'"
                                :data="{ coordinates: activePreviewQuestion.question_coordinates, background_color: activePreviewQuestion.background_color, background_opacity: activePreviewQuestion.background_opacity, background_image: activePreviewQuestion.title_image || activePreviewQuestion.title_image_url || null, text: activePreviewQuestion.title, text_style: { color: activePreviewQuestion.title_color, size: activePreviewQuestion.title_font_size, family: activePreviewQuestion.title_font_family, align: activePreviewQuestion.title_text_align ?? 'center', vertical: activePreviewQuestion.title_vertical_align ?? 'center' } }" />
                            <div v-if="isPreviewMode && activePreviewQuestion && activePreviewQuestion.type === 'info' && (!activePreviewQuestion.options || activePreviewQuestion.options.length === 0)"
                                class="absolute inset-0 z-40 cursor-pointer" @click="submitPreviewAnswer()">
                            </div>
                            <template
                                v-if="activePreviewQuestion && ['single_choice', 'multiple_choice', 'info'].includes(activePreviewQuestion.type)">
                                <template v-for="(opt, idx) in activePreviewQuestion.options" :key="'zone-prev-' + idx">
                                    <div v-if="opt.coordinates && revealedOptions.includes(idx)"
                                        @click="toggleSelection(idx)"
                                        class="absolute z-50 overflow-hidden transition-all cursor-pointer group"
                                        :class="[
                                            selectedIndices.includes(idx) ? 'ring-4 ring-amber-500' : 'hover:ring-2 hover:ring-white/50'
                                        ]"
                                        :style="{ top: opt.coordinates.top + '%', left: opt.coordinates.left + '%', width: opt.coordinates.width + '%', height: opt.coordinates.height + '%', borderRadius: (opt.coordinates.radius ?? 8) + 'px', clipPath: getShapeClipPath(opt.shape) }">
                                        <div class="absolute inset-0 transition-colors pointer-events-none group-hover:bg-white/10"
                                            :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                                        </div>
                                        <img v-if="opt.zone_image" :src="opt.zone_image"
                                            class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                            :style="{ opacity: opt.background_opacity ?? 1 }" />
                                        <div class="absolute z-30 flex items-center justify-center w-5 h-5 border-2 top-2 left-2"
                                            :class="[selectedIndices.includes(idx) ? 'border-amber-500 bg-amber-500' : 'border-slate-400 bg-black/30', ['single_choice', 'info'].includes(activePreviewQuestion.type) ? 'rounded-full' : 'rounded-md']">
                                            <div v-if="selectedIndices.includes(idx)"
                                                class="w-2 h-2 bg-white rounded-full" />
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

                            <template v-if="activePreviewQuestion && activePreviewQuestion.type === 'drag_and_drop'">
                                <div v-for="(opt, idx) in activePreviewQuestion.options" :key="'z' + idx">
                                    <div v-if="opt.coordinates && opt._is_zone"
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
                                                :style="{ color: opt.text_color || '#ffffff', fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">
                                                {{ opt.text }}
                                            </span>
                                        </div>

                                        <div v-if="droppedItems[idx]?.length"
                                            class="relative z-20 flex flex-wrap items-center justify-center w-full h-full gap-1 p-2 overflow-y-auto">
                                            <div v-for="i in droppedItems[idx]" :key="i"
                                                class="flex flex-col items-center justify-center gap-1 p-1 text-xs border rounded shadow-md cursor-pointer"
                                                :style="{ backgroundColor: activePreviewQuestion.options[i].background_color === 'transparent' ? '#3b82f6' : activePreviewQuestion.options[i].background_color, borderColor: activePreviewQuestion.options[i].text_color }"
                                                @click="resetDrop(idx, i)">
                                                <img v-if="activePreviewQuestion.options[i].image_url"
                                                    :src="activePreviewQuestion.options[i].image_url"
                                                    class="object-contain w-10 h-10 rounded pointer-events-none" />
                                                <span v-if="activePreviewQuestion.options[i].show_text"
                                                    class="text-[10px] text-center font-bold drop-shadow-md"
                                                    :style="{ color: activePreviewQuestion.options[i].text_color, fontFamily: activePreviewQuestion.options[i].text_font_family }">
                                                    {{ activePreviewQuestion.options[i].text }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-for="(opt, idx) in activePreviewQuestion.options" :key="'item-abs-' + idx">
                                    <div v-if="!opt._is_zone && opt.coordinates && revealedOptions.includes(idx)"
                                        v-show="!isItemDropped(idx)" draggable="true"
                                        @dragstart="onDragStart($event, idx)"
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
                                                :style="{ color: opt.text_color || '#000000', fontSize: opt.text_font_size + 'px', fontFamily: opt.text_font_family }">
                                                {{ opt.text }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="absolute left-0 right-0 z-[60] flex justify-center gap-2 bottom-10 pointer-events-none">
                                    <div v-for="(opt, idx) in activePreviewQuestion.options" :key="'d-bottom-' + idx"
                                        v-show="!opt._is_zone && !opt.coordinates && !isItemDropped(idx) && revealedOptions.includes(idx)"
                                        draggable="true" @dragstart="onDragStart($event, idx)"
                                        class="relative flex items-center gap-2 px-4 py-2 overflow-hidden font-bold shadow-xl pointer-events-auto cursor-grab hover:ring-2 hover:ring-amber-400"
                                        :style="{ backgroundColor: opt.background_color === 'transparent' ? '#ffffff' : opt.background_color, borderRadius: (opt.coordinates?.radius ?? 8) + 'px', clipPath: getShapeClipPath(opt.shape) }">

                                        <div class="absolute inset-0 pointer-events-none"
                                            :style="{ opacity: opt.background_opacity ?? 1, backgroundColor: opt.background_color === 'transparent' ? '#ffffff' : opt.background_color }">
                                        </div>
                                        <img v-if="opt.image_url" :src="opt.image_url"
                                            class="relative z-10 object-cover w-10 h-10 rounded pointer-events-none" />
                                        <span v-if="opt.show_text" class="relative z-10 drop-shadow-md"
                                            :style="{ color: opt.text_color || '#000000', fontFamily: opt.text_font_family }">
                                            {{ opt.text }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <div v-if="shouldShowQuestionModal" class="absolute inset-0 z-40 pointer-events-none">
                                <div class="absolute z-50 p-6 overflow-y-auto shadow-2xl pointer-events-auto"
                                    :style="{ top: (activePreviewQuestion.modal_coordinates?.top || 50) + '%', left: (activePreviewQuestion.modal_coordinates?.left || 50) + '%', width: (activePreviewQuestion.modal_coordinates?.width || 40) + '%', height: (activePreviewQuestion.modal_coordinates?.height || 50) + '%', transform: 'translate(-50%, -50%)', borderRadius: (activePreviewQuestion.modal_coordinates?.radius ?? 12) + 'px' }">
                                    <div class="absolute inset-0 pointer-events-none -z-10"
                                        :style="{ backgroundColor: activePreviewQuestion.modal_background_color || '#ffffff', opacity: activePreviewQuestion.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                                    </div>
                                    <div v-if="activePreviewQuestion.modal_image"
                                        class="absolute inset-0 pointer-events-none -z-10"
                                        :style="{ backgroundImage: `url(${activePreviewQuestion.modal_image})`, backgroundSize: 'cover', backgroundPosition: 'center', opacity: activePreviewQuestion.modal_background_opacity ?? 1, borderRadius: 'inherit' }">
                                    </div>
                                    <!--
                                    <div v-if="activePreviewQuestion.type === 'info' && (!activePreviewQuestion.options || activePreviewQuestion.options.length === 0)"
                                        class="flex flex-col items-center justify-center h-full text-center transition-colors cursor-pointer hover:bg-white/10"
                                        @click="submitPreviewAnswer()">
                                        <p class="text-lg font-bold text-slate-800 drop-shadow-sm">{{
                                            activePreviewQuestion.correct_feedback ||
                                            "Atenção." }}</p>
                                    </div> -->
                                    <div class="relative z-10 flex flex-col h-full gap-3">
                                        <template v-for="(opt, idx) in activePreviewQuestion.options" :key="idx">
                                            <div v-if="!opt.coordinates && revealedOptions.includes(idx)"
                                                @click="toggleSelection(idx)"
                                                class="relative flex items-center gap-3 p-3 overflow-hidden transition-all border-2 cursor-pointer group"
                                                :class="selectedIndices.includes(idx) ? 'ring-2 ring-amber-500 border-amber-500' : 'border-transparent hover:ring-2 hover:ring-white/50'"
                                                :style="{ borderColor: (opt.text_color || '#000') + '40', borderRadius: (opt.coordinates?.radius ?? 8) + 'px' }">
                                                <div class="absolute inset-0 transition-colors pointer-events-none group-hover:bg-white/10"
                                                    :style="{ backgroundColor: opt.background_color || 'transparent', opacity: opt.background_opacity ?? 1 }">
                                                </div>
                                                <img v-if="opt.image_url" :src="opt.image_url"
                                                    class="absolute inset-0 object-cover w-full h-full pointer-events-none"
                                                    :style="{ opacity: opt.background_opacity ?? 1 }" />
                                                <div class="z-20 flex items-center justify-center w-5 h-5 border-2 shrink-0 bg-black/20"
                                                    :class="[selectedIndices.includes(idx) ? 'border-amber-500 bg-amber-500' : 'border-slate-400', ['single_choice', 'info'].includes(activePreviewQuestion.type) ? 'rounded-full' : 'rounded-md',]"
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
                        </div>

                        <div class="flex flex-col gap-2 p-3 mt-4 border rounded-lg bg-slate-900 border-slate-800">
                            <div class="relative h-8 rounded cursor-pointer bg-slate-800 group"
                                @click="handleTimelineClick">
                                <div class="absolute top-0 left-0 h-full transition-all duration-100 ease-linear rounded pointer-events-none bg-amber-600/50"
                                    :style="{ width: progressPercentage + '%' }"></div>
                                <div v-for="q in props.lesson.questions" :key="q.id"
                                    class="absolute z-10 w-2 h-6 transition-transform border rounded-sm top-1 bg-amber-500 hover:scale-125 border-amber-600"
                                    :style="{ left: (q.timestamp_seconds / (duration || 1)) * 100 + '%', }"
                                    @click.stop="editInteraction(q)">
                                    <Tooltip>
                                        <TooltipTrigger class="block w-full h-full"></TooltipTrigger>
                                        <TooltipContent class="shadow-xl bg-slate-900 border-slate-700 text-slate-200">
                                            <div class="flex flex-col gap-1.5 p-1 max-w-[250px]">
                                                <!-- Cabeçalho do Tooltip -->
                                                <div
                                                    class="flex items-center justify-between border-b border-slate-700 pb-1.5 mb-1">
                                                    <span class="font-mono text-xs font-bold text-amber-400">{{
                                                        formatTimeHMSms(q.timestamp_seconds) }}</span>
                                                    <span
                                                        class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-wider rounded bg-slate-800 text-slate-400">
                                                        {{ getInteractionDetails(q).typeLabel }}
                                                    </span>
                                                </div>

                                                <!-- Título -->
                                                <p class="text-sm font-bold text-white break-words">
                                                    {{ q.title || 'Sem título'
                                                    }}
                                                </p>

                                                <!-- Desvios -->
                                                <div v-if="getInteractionDetails(q).details.length > 0"
                                                    class="flex flex-col gap-1 mt-1 text-[10px]">
                                                    <span v-for="(detail, i) in getInteractionDetails(q).details"
                                                        :key="'tt-det-' + i" :class="detail.colorClass"
                                                        class="font-medium leading-tight">
                                                        {{ detail.text }}
                                                    </span>
                                                </div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Button variant="ghost" size="icon" @click="togglePlay">
                                        <Pause v-if="isPlaying" />
                                        <Play v-else />
                                    </Button>

                                    <template v-if="isPreviewMode">
                                        <Button variant="secondary" size="sm" @click="relativeSeek(-10)" class="h-8">
                                            <SkipBack class="w-3 h-3 mr-1" /> 10s
                                        </Button>
                                        <Button variant="secondary" size="sm" @click="relativeSeek(10)" class="h-8">
                                            10s
                                            <SkipForward class="w-3 h-3 ml-1" />
                                        </Button>
                                    </template>

                                    <template v-else>
                                        <Button variant="ghost" size="icon" @click="relativeSeek(-1)">
                                            -1
                                            <SkipBack class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(-0.1)">-0.1</Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(-0.01)">-0.01</Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(-0.001)">-0.001</Button>

                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(0.001)">+0.001</Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(0.01)">+0.01</Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(0.1)">+0.1</Button>
                                        <Button variant="ghost" size="sm" class="font-mono text-xs"
                                            @click="relativeSeek(1)">
                                            <SkipForward class="w-4 h-4" /> +1
                                        </Button>
                                    </template>

                                    <span class="ml-2 font-mono text-sm text-amber-500">
                                        {{ currentTimeDisplay }} / {{ formatTimeHMSms(duration) }}
                                    </span>
                                    <div class="relative flex items-center h-8 gap-2 ml-2 group/volume">
                                        <Button variant="ghost" size="icon" @click="toggleMute"
                                            class="w-8 h-8 rounded-full text-slate-400 hover:text-white hover:bg-slate-800">
                                            <VolumeX v-if="isMuted" class="w-4 h-4 text-red-400" />
                                            <Volume2 v-else class="w-4 h-4" />
                                        </Button>
                                        <div
                                            class="flex items-center w-0 overflow-hidden transition-all duration-300 ease-in-out opacity-0 group-hover/volume:w-20 group-hover/volume:opacity-100">
                                            <input type="range" min="0" max="1" step="0.05"
                                                v-model.number="currentVolume" @input="setGlobalVolume(currentVolume)"
                                                class="w-full h-1.5 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-amber-500" />
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <div v-if="showSpeedMenu" @click="showSpeedMenu = false"
                                            class="fixed inset-0 z-40"></div>
                                        <Button variant="ghost" size="sm" @click="showSpeedMenu = !showSpeedMenu"
                                            class="relative z-50 h-8 font-mono text-xs rounded-full text-slate-400 hover:text-white hover:bg-slate-800">
                                            {{ currentPlaybackRate }}x
                                        </Button>
                                        <div v-if="showSpeedMenu"
                                            class="absolute right-0 z-50 flex flex-col w-24 py-1 mb-1 overflow-hidden origin-bottom-right border shadow-xl bottom-full rounded-xl bg-slate-900 border-slate-700">
                                            <button v-for="rate in availableSpeeds" :key="rate"
                                                @click="setPlaybackSpeed(rate)"
                                                class="px-3 py-1.5 text-xs text-left transition-colors hover:bg-slate-800"
                                                :class="currentPlaybackRate === rate ? 'text-amber-500 font-bold' : 'text-slate-300'">
                                                {{ rate === 1 ? 'Normal' : rate + 'x' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <Button size="sm" class="font-bold bg-amber-500 text-slate-900 hover:bg-amber-700"
                                    @click="openNewInteractionPanel" :disabled="isPreviewMode || isInteractionPanelOpen"
                                    :class="{ 'opacity-50 cursor-not-allowed': isPreviewMode || isInteractionPanelOpen }">
                                    <Zap class="w-4 h-4 mr-2" /> Adicionar Interação
                                </Button>
                            </div>
                        </div>

                        <div class="mt-6 space-y-2">
                            <div v-for="q in props.lesson.questions" :key="q.id"
                                class="flex items-start justify-between p-3 transition-colors border rounded-lg cursor-pointer bg-slate-900 border-slate-800 hover:border-slate-600"
                                @click="editInteraction(q)">
                                <div class="flex items-start gap-4">
                                    <!-- Tempo -->
                                    <div
                                        class="w-20 py-1 font-mono text-xs text-center rounded bg-slate-800 text-amber-400 mt-0.5 shrink-0">
                                        {{ formatTimeHMSms(q.timestamp_seconds) }}
                                    </div>

                                    <!-- Informações -->
                                    <div class="flex flex-col gap-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-white">{{ q.title || 'Sem título' }}</span>
                                            <span
                                                class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-wider rounded bg-slate-800 text-slate-400">
                                                {{ getInteractionDetails(q).typeLabel }}
                                            </span>
                                        </div>

                                        <!-- Desvios (Acerto/Erro) -->
                                        <div v-if="getInteractionDetails(q).details.length > 0"
                                            class="flex flex-col text-[10px] mt-1.5">
                                            <span v-for="(detail, i) in getInteractionDetails(q).details"
                                                :key="'det-' + i" :class="detail.colorClass"
                                                class="font-medium drop-shadow-sm">
                                                {{ detail.text }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <Button variant="ghost" size="icon" class="text-red-500 hover:bg-red-950/30 shrink-0"
                                    @click.stop="deleteInteraction(q.id)">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <transition enter-active-class="transition duration-300 ease-in-out transform"
                    enter-from-class="translate-x-full" enter-to-class="translate-x-0"
                    leave-active-class="transition duration-300 ease-in-out transform" leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full">
                    <aside v-if="isInteractionPanelOpen"
                        class="z-50 flex flex-col w-1/3 h-full bg-white border-l shadow-2xl dark:bg-slate-950 dark:border-slate-800 text-slate-900 dark:text-slate-200">
                        <header
                            class="flex items-center justify-between px-5 py-4 border-b dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                    {{ editingQuestionId ? "Editar Interação" : "Nova Interação" }}
                                </h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span
                                        class="text-xs font-bold uppercase text-slate-400 dark:text-slate-500">Tempo</span>
                                    <TimeInput v-model="form.timestamp_seconds"
                                        class="font-mono text-xs bg-white dark:bg-slate-950 dark:text-slate-200 h-7 border-slate-300 dark:border-slate-700" />
                                </div>
                            </div>
                            <Button variant="ghost" size="icon" @click="isInteractionPanelOpen = false"
                                class="dark:text-slate-400 dark:hover:text-slate-100">
                                <X />
                            </Button>
                        </header>
                        <section ref="panelScrollContainer"
                            class="flex-1 px-6 py-6 space-y-6 overflow-y-auto custom-scrollbar">
                            <form @submit.prevent="submitInteraction" class="flex-1 space-y-6">

                                <details :open="getSectionState('global_background', true)"
                                    @toggle="handleDetailsToggle('global_background', $event)"
                                    data-section="global_background"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        🌅 Fundo Geral da Interação
                                    </summary>
                                    <div class="p-4 space-y-4 dark:bg-slate-950">
                                        <div
                                            class="p-3 border rounded-lg shadow-sm bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Texto
                                                de Fundo
                                                (Opcional)</Label>
                                            <Input v-model="form.global_background_text"
                                                placeholder="Ex: Aviso Importante"
                                                class="h-8 mb-3 text-xs bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 dark:text-slate-200" />
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Cores
                                                e
                                                Opacidade</Label>
                                            <div class="flex items-center gap-1 mb-3">
                                                <input type="color" v-model="form.global_background_color"
                                                    class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                    title="Cor do Fundo" />
                                                <Input type="number" step="0.1" min="0" max="1"
                                                    v-model.number="form.global_background_opacity"
                                                    class="w-16 h-8 px-2 text-xs text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                    title="Opacidade Fundo" />
                                                <div class="w-px h-5 mx-1 bg-slate-300 dark:bg-slate-700"></div>
                                                <input type="color" v-model="form.global_background_text_color"
                                                    class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                    title="Cor do Texto" />
                                                <Input type="number"
                                                    v-model.number="form.global_background_text_font_size"
                                                    class="w-16 h-8 px-2 text-xs text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                    title="Tamanho Fonte" />
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 mt-2 mb-3">
                                                <div>
                                                    <Label
                                                        class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                        (H/V)</Label>
                                                    <div class="flex gap-1 mt-1">
                                                        <Select v-model="form.global_background_text_align">
                                                            <SelectTrigger
                                                                class="h-6 px-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                                <SelectValue placeholder="H" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem value="left">Esq</SelectItem>
                                                                <SelectItem value="center">Meio</SelectItem>
                                                                <SelectItem value="right">Dir</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                        <Select v-model="form.global_background_vertical_align">
                                                            <SelectTrigger
                                                                class="h-6 px-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                                <SelectValue placeholder="V" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem value="top">Cima</SelectItem>
                                                                <SelectItem value="center">Meio</SelectItem>
                                                                <SelectItem value="bottom">Baixo</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Label
                                                        class="text-[8px] text-slate-500 font-bold uppercase">Fonte</Label>
                                                    <Select v-model="form.global_background_text_font_family">
                                                        <SelectTrigger
                                                            class="h-6 px-1 mt-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                            <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                :value="f.value" class="text-xs">{{ f.label }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Posição
                                                e Tamanho
                                                (Manual)</Label>
                                            <div
                                                class="grid grid-cols-5 gap-2 px-2 py-2 mb-3 bg-white border rounded dark:bg-slate-950 border-slate-200 dark:border-slate-700">
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Top</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.global_background_coordinates.top"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Left</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.global_background_coordinates.left"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Width</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.global_background_coordinates.width"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Height</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.global_background_coordinates.height"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                        (px)</Label><Input type="number" step="1"
                                                        v-model.number="form.global_background_coordinates.radius"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Imagem
                                                de
                                                Fundo</Label>
                                            <div class="mb-1">
                                                <input type="file" accept="image/*"
                                                    @change="onImageSelect($event, 'global_background')"
                                                    class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300 hover:file:bg-slate-300 dark:hover:file:bg-slate-700" />
                                                <div v-if="form.global_background_image_preview"
                                                    class="relative inline-block mt-2">
                                                    <img :src="form.global_background_image_preview"
                                                        class="object-cover bg-white border rounded dark:bg-slate-900 max-h-24 border-slate-200 dark:border-slate-700" />
                                                    <button type="button"
                                                        class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-1 right-1 shadow-sm"
                                                        @click="removeGlobalBackgroundImage">X</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details :open="getSectionState('basic', true)"
                                    @toggle="handleDetailsToggle('basic', $event)" data-section="basic"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        🧩 Básico</summary>
                                    <div class="p-4 space-y-6 dark:bg-slate-950">
                                        <div>
                                            <Label class="block mb-2 text-slate-900 dark:text-slate-200">Tipo</Label>
                                            <RadioGroup v-model="form.type" class="flex flex-wrap gap-2">
                                                <div class="flex items-center p-2 border rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700"
                                                    :class="{ 'border-amber-500 bg-amber-50 dark:bg-amber-950/30': form.type === 'single_choice' }">
                                                    <RadioGroupItem id="s" value="single_choice" />
                                                    <Label for="s"
                                                        class="ml-4 cursor-pointer text-slate-900 dark:text-slate-200">Única</Label>
                                                </div>
                                                <div class="flex items-center p-2 border rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700"
                                                    :class="{ 'border-amber-500 bg-amber-50 dark:bg-amber-950/30': form.type === 'multiple_choice' }">
                                                    <RadioGroupItem id="m" value="multiple_choice" />
                                                    <Label for="m"
                                                        class="ml-4 cursor-pointer text-slate-900 dark:text-slate-200">Multi</Label>
                                                </div>
                                                <div class="flex items-center p-2 border rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700"
                                                    :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-950/30': form.type === 'info' }">
                                                    <RadioGroupItem id="i" value="info" />
                                                    <Label for="i"
                                                        class="ml-4 cursor-pointer text-slate-900 dark:text-slate-200">Info</Label>
                                                </div>
                                                <div class="flex items-center p-2 border rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700"
                                                    :class="{ 'border-purple-500 bg-purple-50 dark:bg-purple-950/30': form.type === 'drag_and_drop' }">
                                                    <RadioGroupItem id="d" value="drag_and_drop" />
                                                    <Label for="d"
                                                        class="ml-4 cursor-pointer text-slate-900 dark:text-slate-200">Drag</Label>
                                                </div>
                                                <div class="flex items-center p-2 border rounded cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700"
                                                    :class="{ 'border-green-500 bg-green-50 dark:bg-green-950/30': form.type === 'automatic' }">
                                                    <RadioGroupItem id="a" value="automatic" />
                                                    <Label for="a"
                                                        class="ml-4 cursor-pointer text-slate-900 dark:text-slate-200">Auto</Label>
                                                </div>
                                            </RadioGroup>
                                        </div>
                                        <div>
                                            <div class="flex items-end justify-between mb-1">
                                                <Label class="font-bold text-slate-900 dark:text-slate-200">Título
                                                    (Pergunta)</Label>
                                                <div class="flex items-center gap-2">
                                                    <Label
                                                        class="text-[10px] uppercase font-bold text-slate-400">Mostrar</Label>
                                                    <NativeSwitch v-model="form.show_title" />
                                                </div>
                                            </div>
                                            <Input v-model="form.title" required
                                                class="mt-1 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-200 border-slate-300 dark:border-slate-700"
                                                placeholder="Digite a pergunta principal" />
                                        </div>
                                    </div>
                                </details>

                                <details :open="getSectionState('style', true)"
                                    @toggle="handleDetailsToggle('style', $event)" data-section="style"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        🎨 Estilo e Modal
                                    </summary>
                                    <div class="p-4 space-y-4 dark:bg-slate-950">
                                        <div
                                            class="p-3 border rounded-lg shadow-sm bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                                            <Label
                                                class="block mb-2 text-xs font-bold uppercase text-slate-800 dark:text-slate-300">▶
                                                Título e
                                                Fundo</Label>
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Fundo
                                                e
                                                Opacidade</Label>
                                            <div class="flex items-center gap-1 mb-3">
                                                <input type="color" v-model="form.background_color"
                                                    class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                    title="Cor do Fundo" />
                                                <Input type="number" step="0.1" min="0" max="1"
                                                    v-model.number="form.background_opacity"
                                                    class="w-16 h-8 px-2 text-xs text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                    title="Opacidade Fundo (0 a 1)" />
                                            </div>
                                            <Label class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Cor
                                                e Fonte do
                                                Texto</Label>
                                            <div class="flex items-center gap-1 mb-3">
                                                <input type="color" v-model="form.title_color"
                                                    class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                    title="Cor do Texto" />
                                                <Input type="number" v-model.number="form.title_font_size"
                                                    class="w-16 h-8 px-2 text-xs text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                    title="Tamanho da Fonte" />
                                            </div>
                                            <div class="grid grid-cols-2 gap-2 mt-2">
                                                <div>
                                                    <Label
                                                        class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                        (H/V)</Label>
                                                    <div class="flex gap-1 mt-1">
                                                        <Select v-model="form.title_text_align">
                                                            <SelectTrigger
                                                                class="h-6 px-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                                <SelectValue placeholder="H" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem value="left">Esq</SelectItem>
                                                                <SelectItem value="center">Meio</SelectItem>
                                                                <SelectItem value="right">Dir</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                        <Select v-model="form.title_vertical_align">
                                                            <SelectTrigger
                                                                class="h-6 px-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                                <SelectValue placeholder="V" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem value="top">Cima</SelectItem>
                                                                <SelectItem value="center">Meio</SelectItem>
                                                                <SelectItem value="bottom">Baixo</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Label
                                                        class="text-[8px] text-slate-500 font-bold uppercase">Fonte</Label>
                                                    <Select v-model="form.title_font_family">
                                                        <SelectTrigger
                                                            class="h-6 px-1 mt-1 text-[9px] bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                            <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                :value="f.value" class="text-xs">{{ f.label }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mt-3 mb-1 block">Posição
                                                e
                                                Tamanho (Vídeo)</Label>
                                            <div
                                                class="grid grid-cols-5 gap-2 px-2 py-2 mb-3 bg-white border rounded dark:bg-slate-950 border-slate-200 dark:border-slate-700">
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Top</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.question_coordinates.top"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Left</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.question_coordinates.left"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Width</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.question_coordinates.width"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Height</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.question_coordinates.height"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                        (px)</Label><Input type="number" step="1"
                                                        v-model.number="form.question_coordinates.radius"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-slate-500 font-bold uppercase mb-1 block">Imagem
                                                do
                                                Título</Label>
                                            <input type="file" accept="image/*" @change="onImageSelect($event, 'title')"
                                                class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:bg-slate-200 dark:file:bg-slate-800 file:text-slate-700 dark:file:text-slate-300" />
                                            <div v-if="form.title_image_preview" class="relative inline-block mt-2">
                                                <img :src="form.title_image_preview"
                                                    class="object-cover bg-white border rounded dark:bg-slate-900 max-h-16 border-slate-200 dark:border-slate-700" />
                                                <button type="button"
                                                    class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 rounded top-1 right-1 shadow-sm"
                                                    @click="removeTitleImage">X</button>
                                            </div>
                                        </div>

                                        <div v-if="shouldShowEditorModal"
                                            class="p-3 border border-blue-200 rounded-lg shadow-sm dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20">
                                            <Label
                                                class="block mb-2 text-xs font-bold text-blue-800 uppercase dark:text-blue-400">▶
                                                Caixa
                                                do Modal</Label>
                                            <Label
                                                class="text-[10px] text-blue-700 dark:text-blue-500 font-bold uppercase mb-1 block">Cor
                                                de Fundo e
                                                Opacidade</Label>
                                            <div class="flex items-center gap-1 mb-3">
                                                <input type="color" v-model="form.modal_background_color"
                                                    class="w-8 h-8 p-0 bg-transparent border border-blue-200 rounded cursor-pointer dark:border-blue-800"
                                                    title="Cor do Fundo do Modal" />
                                                <Input type="number" step="0.1" min="0" max="1"
                                                    v-model.number="form.modal_background_opacity"
                                                    class="w-16 h-8 px-2 text-xs text-center bg-white border-blue-200 dark:bg-slate-950 dark:text-slate-200 dark:border-blue-800"
                                                    title="Opacidade Fundo (0 a 1)" />
                                            </div>
                                            <Label
                                                class="text-[10px] text-blue-700 dark:text-blue-500 uppercase font-bold mb-1 block">Posição
                                                e Tamanho
                                                (Manual)</Label>
                                            <div
                                                class="grid grid-cols-5 gap-2 px-2 py-2 mb-3 bg-white border border-blue-200 rounded dark:bg-slate-950 dark:border-blue-800">
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Top</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.modal_coordinates.top"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Left</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.modal_coordinates.left"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Width</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.modal_coordinates.width"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Height</Label><Input
                                                        type="number" step="0.1"
                                                        v-model.number="form.modal_coordinates.height"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                        (px)</Label><Input type="number" step="1"
                                                        v-model.number="form.modal_coordinates.radius"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800" />
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-blue-700 dark:text-blue-500 font-bold uppercase mb-1 block">Imagem
                                                de Fundo do
                                                Modal</Label>
                                            <input type="file" accept="image/*" @change="onImageSelect($event, 'modal')"
                                                class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:bg-blue-100 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-200 dark:hover:file:bg-blue-800" />
                                            <div v-if="form.modal_image_preview" class="relative inline-block mt-2">
                                                <img :src="form.modal_image_preview"
                                                    class="object-cover bg-white border border-blue-200 rounded dark:bg-slate-900 dark:border-blue-800 max-h-16" />
                                                <button type="button"
                                                    class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 rounded top-1 right-1 shadow-sm"
                                                    @click="removeModalImage">X</button>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details v-if="form.type !== 'automatic'" :open="getSectionState('loop', true)"
                                    @toggle="handleDetailsToggle('loop', $event)" data-section="loop"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        🔁 Intervalo de
                                        Loop</summary>
                                    <div class="p-4 space-y-3 dark:bg-slate-950">
                                        <Label
                                            class="flex items-center gap-2 text-xs font-bold tracking-wider uppercase text-slate-900 dark:text-slate-200">
                                            <Repeat class="w-3.5 h-3.5 text-amber-500" /> Intervalo Loop
                                        </Label>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1">
                                                <Label class="text-[10px] text-slate-500 mb-1">Início</Label>
                                                <TimeInput v-model="form.loop_start_seconds"
                                                    class="h-8 font-mono text-xs bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-200 border-slate-300 dark:border-slate-700" />
                                            </div>
                                            <div class="pt-5 text-slate-400 dark:text-slate-600">
                                                <ArrowRightLeft class="w-4 h-4" />
                                            </div>
                                            <div class="flex-1">
                                                <Label class="text-[10px] text-slate-500 mb-1">Fim</Label>
                                                <TimeInput v-model="form.loop_end_seconds"
                                                    class="h-8 font-mono text-xs font-bold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-200 border-slate-300 dark:border-slate-700" />
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex justify-between px-1">
                                            <span>Duração: {{ Math.max(0, (form.loop_end_seconds || 0) -
                                                (form.loop_start_seconds ||
                                                    0)).toFixed(3) }}s</span>

                                            <span
                                                v-if="form.loop_start_seconds != null && form.loop_end_seconds != null && form.loop_end_seconds > form.loop_start_seconds"
                                                class="font-bold text-amber-600">Loop Ativo</span>
                                            <span v-else class="text-slate-400">Pausa Estática</span>
                                        </div>
                                    </div>
                                </details>

                                <details v-if="!['automatic'].includes(form.type)"
                                    :open="getSectionState('options', true)"
                                    @toggle="handleDetailsToggle('options', $event)" data-section="options"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        🧲 Opções / Zonas
                                    </summary>
                                    <div class="p-4 space-y-4 dark:bg-slate-950">
                                        <div class="flex items-center justify-between">
                                            <Label class="font-bold text-slate-900 dark:text-slate-200">
                                                {{ form.type === 'drag_and_drop' ? "Itens e Zonas" : "Opções" }}
                                            </Label>
                                        </div>

                                        <template v-if="form.type === 'drag_and_drop'">
                                            <div v-for="(zoneOpt, zoneIdx) in form.options" :key="'zone-' + zoneIdx">
                                                <div v-if="zoneOpt._is_zone"
                                                    class="flex flex-col gap-2 p-3 mb-4 border-l-4 border-purple-500 rounded-r-lg shadow-sm bg-purple-50 dark:bg-purple-950/20">

                                                    <div class="flex items-center justify-between mb-1">
                                                        <span
                                                            class="text-xs font-bold text-purple-700 uppercase dark:text-purple-400">Zona
                                                            {{ zoneOpt.group_id }}</span>
                                                        <button type="button"
                                                            @click="removeZoneAndItems(zoneOpt.group_id)"
                                                            class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">Remover
                                                            Zona</button>
                                                    </div>

                                                    <Input v-model="zoneOpt.text"
                                                        placeholder="Rótulo da Zona (Opcional)"
                                                        class="h-8 text-sm bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-200 border-slate-300 dark:border-purple-800/50" />

                                                    <div class="flex items-center justify-between mt-1">
                                                        <Button type="button" variant="outline" size="sm"
                                                            class="flex-1 mr-2 border-dashed dark:bg-transparent"
                                                            :class="zoneOpt.coordinates ? 'text-green-600 dark:text-green-400 border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-950/30 hover:bg-green-100 dark:hover:bg-green-900/50' : 'text-purple-600 dark:text-purple-400 border-purple-300 dark:border-purple-700 bg-white hover:bg-purple-100 dark:hover:bg-purple-900/50'"
                                                            @click.prevent.stop="enableDrawing(zoneIdx)">
                                                            <Scan class="w-3 h-3 mr-2" /> {{ zoneOpt.coordinates ?
                                                                "Redefinir Área" : "Definir Área no Vídeo" }}
                                                        </Button>
                                                        <div class="flex items-center gap-2">
                                                            <Label
                                                                class="text-[10px] uppercase font-bold text-slate-400">Texto</Label>
                                                            <NativeSwitch v-model="zoneOpt.show_text" />
                                                        </div>
                                                    </div>

                                                    <div v-if="zoneOpt.coordinates"
                                                        class="p-2 mt-2 bg-white border border-purple-100 rounded dark:bg-slate-900 dark:border-purple-800/50">
                                                        <div class="grid grid-cols-5 gap-2 mb-3">
                                                            <div class="flex flex-col gap-1"><Label
                                                                    class="text-[9px] text-slate-500 text-center font-bold">Top
                                                                    (%)</Label><Input type="number" step="0.1"
                                                                    v-model.number="zoneOpt.coordinates.top"
                                                                    class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-200" />
                                                            </div>
                                                            <div class="flex flex-col gap-1"><Label
                                                                    class="text-[9px] text-slate-500 text-center font-bold">Left
                                                                    (%)</Label><Input type="number" step="0.1"
                                                                    v-model.number="zoneOpt.coordinates.left"
                                                                    class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-200" />
                                                            </div>
                                                            <div class="flex flex-col gap-1"><Label
                                                                    class="text-[9px] text-slate-500 text-center font-bold">Width
                                                                    (%)</Label><Input type="number" step="0.1"
                                                                    v-model.number="zoneOpt.coordinates.width"
                                                                    class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-200" />
                                                            </div>
                                                            <div class="flex flex-col gap-1"><Label
                                                                    class="text-[9px] text-slate-500 text-center font-bold">Height
                                                                    (%)</Label><Input type="number" step="0.1"
                                                                    v-model.number="zoneOpt.coordinates.height"
                                                                    class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-200" />
                                                            </div>
                                                            <div class="flex flex-col gap-1"><Label
                                                                    class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                                    (px)</Label><Input type="number" step="1"
                                                                    v-model.number="zoneOpt.coordinates.radius"
                                                                    class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-200" />
                                                            </div>
                                                        </div>
                                                        <Label class="text-[9px] text-slate-500 block">Formato da
                                                            Zona</Label>
                                                        <Select v-model="zoneOpt.shape">
                                                            <SelectTrigger
                                                                class="h-6 px-2 mt-1 text-xs border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 dark:text-slate-200">
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem v-for="s in shapeOptions" :key="s.value"
                                                                    :value="s.value" class="text-xs">
                                                                    {{ s.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>

                                                        <Label class="text-[9px] text-slate-500 mt-2 block">Imagem da
                                                            Zona</Label>
                                                        <input type="file" accept="image/*"
                                                            class="block w-full text-[9px] mt-1 text-slate-500 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-purple-50 dark:file:bg-purple-900/30 file:text-purple-700 dark:file:text-purple-400"
                                                            @change="onZoneImageSelect($event, zoneIdx)" />
                                                        <div v-if="zoneOpt.zone_image_preview" class="relative mt-1">
                                                            <img :src="zoneOpt.zone_image_preview"
                                                                class="object-cover bg-white border rounded max-h-16 border-slate-300 dark:border-slate-700 dark:bg-slate-950" />
                                                            <button type="button"
                                                                class="absolute px-1 py-0 text-[9px] text-white bg-red-600 rounded top-0 right-0"
                                                                @click="removeZoneImage(zoneIdx)">X</button>
                                                        </div>
                                                        <Label class="text-[9px] text-slate-500 mt-2 block">Cores, Fundo
                                                            e Opacidade</Label>
                                                        <div class="flex items-center gap-1 mt-1">
                                                            <input type="color" v-model="zoneOpt.background_color"
                                                                class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer dark:border-slate-700"
                                                                title="Cor do Fundo" />
                                                            <Input type="number" step="0.1" min="0" max="1"
                                                                v-model.number="zoneOpt.background_opacity"
                                                                class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200"
                                                                title="Opacidade" />
                                                            <div class="w-px h-4 mx-1 bg-slate-200 dark:bg-slate-700">
                                                            </div>
                                                            <input type="color" v-model="zoneOpt.text_color"
                                                                class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer dark:border-slate-700"
                                                                title="Cor do Texto" />
                                                            <Input type="number" v-model.number="zoneOpt.text_font_size"
                                                                class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200"
                                                                title="Tamanho da Fonte" />
                                                        </div>
                                                        <div class="grid grid-cols-2 gap-2 mt-2">
                                                            <div>
                                                                <Label class="text-[9px] text-slate-500">Alinhamento (H
                                                                    / V)</Label>
                                                                <div class="flex gap-1 mt-1">
                                                                    <Select v-model="zoneOpt.text_align">
                                                                        <SelectTrigger
                                                                            class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                                                            <SelectValue placeholder="H" />
                                                                        </SelectTrigger>
                                                                        <SelectContent
                                                                            class="dark:bg-slate-900 dark:border-slate-800">
                                                                            <SelectItem value="left">Esq</SelectItem>
                                                                            <SelectItem value="center">Meio</SelectItem>
                                                                            <SelectItem value="right">Dir</SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                    <Select v-model="zoneOpt.vertical_align">
                                                                        <SelectTrigger
                                                                            class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                                                            <SelectValue placeholder="V" />
                                                                        </SelectTrigger>
                                                                        <SelectContent
                                                                            class="dark:bg-slate-900 dark:border-slate-800">
                                                                            <SelectItem value="top">Cima</SelectItem>
                                                                            <SelectItem value="center">Meio</SelectItem>
                                                                            <SelectItem value="bottom">Baixo
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <Label class="text-[9px] text-slate-500">Fonte</Label>
                                                                <Select v-model="zoneOpt.text_font_family">
                                                                    <SelectTrigger
                                                                        class="h-6 px-1 mt-1 text-[9px] bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent
                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                        <SelectItem v-for="f in fontOptions"
                                                                            :key="f.value" :value="f.value"
                                                                            class="text-xs">{{ f.label }}</SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <Separator
                                                        class="my-3 border-purple-200 dark:border-purple-900/50" />
                                                    <div
                                                        class="pl-3 space-y-2 border-l-2 border-purple-300 dark:border-purple-700">
                                                        <Label
                                                            class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-400">Peças
                                                            desta zona:</Label>

                                                        <template v-for="(itemOpt, itemIdx) in form.options"
                                                            :key="'item-' + itemIdx">
                                                            <div v-if="!itemOpt._is_zone && itemOpt.group_id === zoneOpt.group_id"
                                                                class="flex flex-col gap-2 p-2 bg-white border border-purple-100 rounded-lg shadow-sm dark:bg-slate-900 dark:border-purple-900/50">
                                                                <div class="flex items-center gap-2">
                                                                    <GripHorizontal class="w-3 h-3 text-slate-400" />
                                                                    <div class="flex flex-col gap-1 shrink-0">
                                                                        <Label
                                                                            class="text-[8px] text-slate-500 text-center font-bold">Texto</Label>
                                                                        <Input v-model="itemOpt.text"
                                                                            placeholder="Texto da Peça"
                                                                            class="text-xs h-7 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-200 border-slate-200 dark:border-slate-800" />
                                                                    </div>
                                                                    <div class="flex flex-col gap-1 w-36 shrink-0">
                                                                        <Label
                                                                            class="text-[8px] text-slate-500 text-center font-bold">Mostrar
                                                                            Em</Label>
                                                                        <TimeInput v-model="itemOpt.delay_seconds"
                                                                            class=" h-7 text-[10px] px-1 text-center bg-white dark:bg-slate-950 dark:text-slate-200 font-mono dark:border-slate-800"
                                                                            title="Atraso para aparecer" />
                                                                    </div>
                                                                    <Button type="button" variant="ghost" size="icon"
                                                                        @click="removeOption(itemIdx)"
                                                                        class="text-red-500 w-7 h-7 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                                        <Trash2 class="w-3 h-3" />
                                                                    </Button>
                                                                </div>
                                                                <div class="flex items-center justify-between mt-1">
                                                                    <Button type="button" variant="outline" size="sm"
                                                                        class="flex-1 mr-2 border-dashed dark:bg-transparent"
                                                                        :class="itemOpt.coordinates ? 'text-green-600 dark:text-green-400 border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-950/30 hover:bg-green-100 dark:hover:bg-green-900/50' : 'text-blue-600 dark:text-blue-400 border-blue-300 dark:border-blue-700 bg-slate-50 dark:bg-slate-900 hover:bg-blue-100 dark:hover:bg-blue-900/50'"
                                                                        @click.prevent.stop="enableDrawing(itemIdx)">
                                                                        <Scan class="w-3 h-3 mr-2" /> {{
                                                                            itemOpt.coordinates ? "Reposicionar Peça" :
                                                                                "Posicionar no Vídeo" }}
                                                                    </Button>
                                                                    <div class="flex items-center gap-2">
                                                                        <Label
                                                                            class="text-[9px] uppercase font-bold text-slate-400">Texto</Label>
                                                                        <NativeSwitch v-model="itemOpt.show_text" />
                                                                    </div>
                                                                </div>
                                                                <div v-if="itemOpt.coordinates"
                                                                    class="p-2 mt-1 border rounded bg-slate-50 dark:bg-slate-950 border-slate-100 dark:border-slate-800">
                                                                    <div class="grid grid-cols-5 gap-1 mb-2">
                                                                        <div class="flex flex-col gap-1"><Label
                                                                                class="text-[8px] text-slate-500 text-center">Top</Label><Input
                                                                                type="number" step="0.1"
                                                                                v-model.number="itemOpt.coordinates.top"
                                                                                class="h-5 px-1 text-[9px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" />
                                                                        </div>
                                                                        <div class="flex flex-col gap-1"><Label
                                                                                class="text-[8px] text-slate-500 text-center">Left</Label><Input
                                                                                type="number" step="0.1"
                                                                                v-model.number="itemOpt.coordinates.left"
                                                                                class="h-5 px-1 text-[9px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" />
                                                                        </div>
                                                                        <div class="flex flex-col gap-1"><Label
                                                                                class="text-[8px] text-slate-500 text-center">Width</Label><Input
                                                                                type="number" step="0.1"
                                                                                v-model.number="itemOpt.coordinates.width"
                                                                                class="h-5 px-1 text-[9px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" />
                                                                        </div>
                                                                        <div class="flex flex-col gap-1"><Label
                                                                                class="text-[8px] text-slate-500 text-center">Height</Label><Input
                                                                                type="number" step="0.1"
                                                                                v-model.number="itemOpt.coordinates.height"
                                                                                class="h-5 px-1 text-[9px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" />
                                                                        </div>
                                                                        <div class="flex flex-col gap-1"><Label
                                                                                class="text-[8px] text-slate-500 text-center">Raio</Label><Input
                                                                                type="number" step="1"
                                                                                v-model.number="itemOpt.coordinates.radius"
                                                                                class="h-5 px-1 text-[9px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200" />
                                                                        </div>
                                                                    </div>

                                                                    <Label
                                                                        class="text-[9px] text-slate-500 block">Formato
                                                                        da Peça</Label>
                                                                    <Select v-model="itemOpt.shape">
                                                                        <SelectTrigger
                                                                            class="h-6 px-2 mt-1 text-xs bg-white dark:bg-slate-900 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                            <SelectValue />
                                                                        </SelectTrigger>
                                                                        <SelectContent
                                                                            class="dark:bg-slate-900 dark:border-slate-800">
                                                                            <SelectItem v-for="s in shapeOptions"
                                                                                :key="s.value" :value="s.value"
                                                                                class="text-xs">
                                                                                {{ s.label }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>

                                                                    <Label
                                                                        class="text-[9px] text-slate-500 mt-2 block">Imagem
                                                                        da Peça</Label>
                                                                    <input type="file" accept="image/*"
                                                                        @change="onOptionImageSelect($event, itemIdx)"
                                                                        class="block w-full text-[9px] mt-1 text-slate-500 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:bg-blue-50 dark:file:bg-blue-900/30 file:text-blue-700 dark:file:text-blue-400" />
                                                                    <div v-if="itemOpt.image_preview"
                                                                        class="relative mt-1">
                                                                        <img :src="itemOpt.image_preview"
                                                                            class="object-contain bg-white border rounded max-h-12 border-slate-200 dark:border-slate-700 dark:bg-slate-950" />
                                                                        <button type="button"
                                                                            class="absolute px-1 py-0 text-[9px] text-white bg-red-600 rounded top-0 right-0"
                                                                            @click="removeOptionImage(itemIdx)">X</button>
                                                                    </div>
                                                                    <Label
                                                                        class="text-[9px] text-slate-500 mt-2 block">Cores,
                                                                        Fundo e Opacidade</Label>
                                                                    <div class="flex items-center gap-1 mt-1">
                                                                        <input type="color"
                                                                            v-model="itemOpt.background_color"
                                                                            class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer dark:border-slate-700"
                                                                            title="Cor do Fundo" />
                                                                        <Input type="number" step="0.1" min="0" max="1"
                                                                            v-model.number="itemOpt.background_opacity"
                                                                            class="w-12 h-6 px-1 text-[10px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                                                                            title="Opacidade" />
                                                                        <div
                                                                            class="w-px h-4 mx-1 bg-slate-200 dark:bg-slate-700">
                                                                        </div>
                                                                        <input type="color" v-model="itemOpt.text_color"
                                                                            class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer dark:border-slate-700"
                                                                            title="Cor do Texto" />
                                                                        <Input type="number"
                                                                            v-model.number="itemOpt.text_font_size"
                                                                            class="w-12 h-6 px-1 text-[10px] text-center dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200"
                                                                            title="Tamanho Fonte" />
                                                                    </div>
                                                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                                                        <div>
                                                                            <Label
                                                                                class="text-[8px] text-slate-500">Alinhamento
                                                                                (H/V)</Label>
                                                                            <div class="flex gap-1 mt-1">
                                                                                <Select v-model="itemOpt.text_align">
                                                                                    <SelectTrigger
                                                                                        class="h-5 px-1 text-[9px] dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200">
                                                                                        <SelectValue placeholder="H" />
                                                                                    </SelectTrigger>
                                                                                    <SelectContent
                                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                                        <SelectItem value="left">Esq
                                                                                        </SelectItem>
                                                                                        <SelectItem value="center">Meio
                                                                                        </SelectItem>
                                                                                        <SelectItem value="right">Dir
                                                                                        </SelectItem>
                                                                                    </SelectContent>
                                                                                </Select>
                                                                                <Select
                                                                                    v-model="itemOpt.vertical_align">
                                                                                    <SelectTrigger
                                                                                        class="h-5 px-1 text-[9px] dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200">
                                                                                        <SelectValue placeholder="V" />
                                                                                    </SelectTrigger>
                                                                                    <SelectContent
                                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                                        <SelectItem value="top">Cima
                                                                                        </SelectItem>
                                                                                        <SelectItem value="center">Meio
                                                                                        </SelectItem>
                                                                                        <SelectItem value="bottom">Baixo
                                                                                        </SelectItem>
                                                                                    </SelectContent>
                                                                                </Select>
                                                                            </div>
                                                                        </div>
                                                                        <div>
                                                                            <Label
                                                                                class="text-[8px] text-slate-500">Fonte</Label>
                                                                            <Select v-model="itemOpt.text_font_family">
                                                                                <SelectTrigger
                                                                                    class="h-5 px-1 mt-1 text-[9px] dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200">
                                                                                    <SelectValue />
                                                                                </SelectTrigger>
                                                                                <SelectContent
                                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                                    <SelectItem v-for="f in fontOptions"
                                                                                        :key="f.value" :value="f.value"
                                                                                        class="text-xs">{{ f.label }}
                                                                                    </SelectItem>
                                                                                </SelectContent>
                                                                            </Select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <Button type="button" variant="outline" size="sm"
                                                            class="w-full h-8 mt-2 text-xs text-purple-700 bg-white border-purple-300 border-dashed dark:text-purple-400 dark:bg-transparent dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30"
                                                            @click="addItemToZone(zoneOpt.group_id)">
                                                            <Plus class="w-3 h-3 mr-1" /> Adicionar Peça nesta Zona
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                            <Button type="button" variant="default" size="sm"
                                                class="w-full font-bold text-white bg-purple-600 shadow-md hover:bg-purple-700"
                                                @click="addNewZoneGroup()">
                                                <Plus class="w-4 h-4 mr-2" /> Nova Zona de Arrasto
                                            </Button>
                                        </template>

                                        <template v-else>
                                            <div v-for="(opt, idx) in form.options" :key="idx" v-show="!opt._is_zone"
                                                class="flex flex-col gap-2 p-3 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800">
                                                <div class="flex items-end gap-2">
                                                    <div class="pb-2" v-if="form.type !== 'info'">
                                                        <input v-if="form.type === 'single_choice'" type="radio"
                                                            name="c" :checked="opt.is_correct"
                                                            @change="setCorrectOption(idx)"
                                                            class="w-4 h-4 text-amber-600 border-slate-300 dark:border-slate-700 dark:bg-slate-800" />
                                                        <NativeSwitch v-else v-model="opt.is_correct" />
                                                    </div>

                                                    <div class="flex flex-col flex-1 gap-1">
                                                        <Label
                                                            class="text-[8px] text-slate-500 text-left font-bold uppercase">Texto
                                                            da Opção</Label>
                                                        <Input v-model="opt.text" placeholder="Rótulo da Opção"
                                                            class="w-full h-8 bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-200 border-slate-300 dark:border-slate-700" />
                                                    </div>

                                                    <div class="flex flex-col gap-1 w-36 shrink-0">
                                                        <Label
                                                            class="text-[8px] text-slate-500 text-center font-bold uppercase">Mostrar
                                                            em</Label>
                                                        <TimeInput v-model="opt.delay_seconds"
                                                            class="h-8 text-[10px] px-1 text-center bg-white dark:bg-slate-950 font-mono dark:border-slate-700 dark:text-slate-200"
                                                            title="Tempo para esta opção aparecer no vídeo" />
                                                    </div>

                                                    <Button type="button" variant="ghost" size="icon"
                                                        @click="removeOption(idx)"
                                                        class="w-8 h-8 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30">
                                                        <Trash2 class="w-4 h-4" />
                                                    </Button>
                                                </div>

                                                <div class="flex flex-col w-full gap-1 mt-1">
                                                    <Label
                                                        class="text-[8px] text-amber-600 dark:text-amber-500 text-left font-bold uppercase">Feedback
                                                        Específico ao Selecionar (Opcional)</Label>
                                                    <textarea v-model="opt.feedback_text"
                                                        placeholder="Ex: Essa alternativa é incorreta porque..."
                                                        class="w-full min-h-[50px] p-2 text-xs bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-md focus:border-amber-400 dark:focus:border-amber-500 resize-y shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-400 dark:focus:ring-amber-500"></textarea>
                                                </div>

                                                <div class="flex flex-col gap-2 mt-1">
                                                    <div class="flex items-center justify-between">
                                                        <Button type="button" variant="outline" size="sm"
                                                            class="flex-1 mr-2 border-dashed dark:bg-transparent"
                                                            :class="opt.coordinates ? 'text-green-600 dark:text-green-400 border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-950/30 hover:bg-green-100 dark:hover:bg-green-900/50' : 'text-slate-500 dark:text-slate-400 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                                            @click.prevent.stop="enableDrawing(idx)">
                                                            <Scan class="w-3 h-3 mr-2" /> {{ opt.coordinates ?
                                                                "Redefinir Área" : "Definir Área no Vídeo" }}
                                                        </Button>
                                                        <div class="flex items-center gap-2">
                                                            <Label
                                                                class="text-[10px] uppercase font-bold text-slate-400">Texto</Label>
                                                            <NativeSwitch v-model="opt.show_text" />
                                                        </div>
                                                    </div>
                                                    <div v-if="opt.coordinates"
                                                        class="grid grid-cols-5 gap-2 px-2 py-2 border rounded bg-slate-100 dark:bg-slate-950 border-slate-200 dark:border-slate-800">
                                                        <div class="flex flex-col gap-1"><Label
                                                                class="text-[9px] text-slate-500 text-center font-bold">Top
                                                                (%)</Label><Input type="number" step="0.1"
                                                                v-model.number="opt.coordinates.top"
                                                                class="h-6 px-1 text-xs text-center bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                        </div>
                                                        <div class="flex flex-col gap-1"><Label
                                                                class="text-[9px] text-slate-500 text-center font-bold">Left
                                                                (%)</Label><Input type="number" step="0.1"
                                                                v-model.number="opt.coordinates.left"
                                                                class="h-6 px-1 text-xs text-center bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                        </div>
                                                        <div class="flex flex-col gap-1"><Label
                                                                class="text-[9px] text-slate-500 text-center font-bold">Width
                                                                (%)</Label><Input type="number" step="0.1"
                                                                v-model.number="opt.coordinates.width"
                                                                class="h-6 px-1 text-xs text-center bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                        </div>
                                                        <div class="flex flex-col gap-1"><Label
                                                                class="text-[9px] text-slate-500 text-center font-bold">Height
                                                                (%)</Label><Input type="number" step="0.1"
                                                                v-model.number="opt.coordinates.height"
                                                                class="h-6 px-1 text-xs text-center bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                        </div>
                                                        <div class="flex flex-col gap-1"><Label
                                                                class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                                (px)</Label><Input type="number" step="1"
                                                                v-model.number="opt.coordinates.radius"
                                                                class="h-6 px-1 text-[10px] text-center bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700" />
                                                        </div>
                                                    </div>
                                                    <div v-if="opt.coordinates" class="mt-2">
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <div>
                                                                <Label class="text-[9px] text-slate-500 block">Formato
                                                                    da Área</Label>
                                                                <Select v-model="opt.shape">
                                                                    <SelectTrigger
                                                                        class="h-6 px-2 mt-1 text-xs bg-white dark:bg-slate-950 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent
                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                        <SelectItem v-for="s in shapeOptions"
                                                                            :key="s.value" :value="s.value"
                                                                            class="text-xs">
                                                                            {{ s.label }}
                                                                        </SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                                <Label
                                                                    class="text-[9px] text-slate-500 mt-2 block">Imagem
                                                                    de
                                                                    Fundo</Label>
                                                                <input type="file" accept="image/*"
                                                                    @change="onZoneImageSelect($event, idx)"
                                                                    class="block w-full text-[9px] mt-1 text-slate-500 dark:text-slate-400 file:mr-2 file:py-0.5 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-purple-50 dark:file:bg-purple-900/30 file:text-purple-700 dark:file:text-purple-400 hover:file:bg-purple-100 dark:hover:file:bg-purple-800" />
                                                                <div v-if="opt.zone_image_preview"
                                                                    class="relative mt-1">
                                                                    <img :src="opt.zone_image_preview"
                                                                        class="object-contain bg-white border rounded max-h-12 border-slate-200 dark:border-slate-700 dark:bg-slate-950" />
                                                                    <button type="button"
                                                                        class="absolute px-1 py-0 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-0 right-0"
                                                                        @click="removeZoneImage(idx)">X</button>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <Label class="text-[9px] text-slate-500">Fonte do
                                                                    Texto</Label>
                                                                <Select v-model="opt.text_font_family">
                                                                    <SelectTrigger
                                                                        class="h-6 px-2 mt-1 text-[9px] border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 dark:text-slate-200">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent
                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                        <SelectItem v-for="f in fontOptions"
                                                                            :key="f.value" :value="f.value"
                                                                            class="text-xs">{{ f.label }}
                                                                        </SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <Label class="text-[9px] text-slate-500">Cores, Fundo e
                                                            Opacidade</Label>
                                                        <div class="flex items-center gap-1 mt-1">
                                                            <input type="color" v-model="opt.background_color"
                                                                class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                                title="Cor do Fundo" />
                                                            <Input type="number" step="0.1" min="0" max="1"
                                                                v-model.number="opt.background_opacity"
                                                                class="w-12 h-6 px-1 text-[10px] text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                                title="Opacidade Fundo (0 a 1)" />
                                                            <div class="w-px h-4 mx-1 bg-slate-300 dark:bg-slate-700">
                                                            </div>
                                                            <input type="color" v-model="opt.text_color"
                                                                class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer border-slate-200 dark:border-slate-700"
                                                                title="Cor do Texto" />
                                                            <Input type="number" v-model.number="opt.text_font_size"
                                                                class="w-12 h-6 px-1 text-[10px] text-center bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700"
                                                                title="Tamanho da Fonte" />
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                                        <div>
                                                            <Label class="text-[8px] text-slate-500">Alinhamento
                                                                (H/V)</Label>
                                                            <div class="flex gap-1 mt-1">
                                                                <Select v-model="opt.text_align">
                                                                    <SelectTrigger
                                                                        class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                                                        <SelectValue placeholder="H" />
                                                                    </SelectTrigger>
                                                                    <SelectContent
                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                        <SelectItem value="left">Esq</SelectItem>
                                                                        <SelectItem value="center">Meio</SelectItem>
                                                                        <SelectItem value="right">Dir</SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                                <Select v-model="opt.vertical_align">
                                                                    <SelectTrigger
                                                                        class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                                                        <SelectValue placeholder="V" />
                                                                    </SelectTrigger>
                                                                    <SelectContent
                                                                        class="dark:bg-slate-900 dark:border-slate-800">
                                                                        <SelectItem value="top">Cima</SelectItem>
                                                                        <SelectItem value="center">Meio</SelectItem>
                                                                        <SelectItem value="bottom">Baixo</SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <details
                                                    class="mt-2 border rounded dark:border-slate-800 bg-slate-50 dark:bg-slate-900">
                                                    <summary
                                                        class="p-2 text-xs font-bold cursor-pointer text-slate-500 dark:text-slate-400">
                                                        Comportamento avançado</summary>
                                                    <div v-if="optionOverrideUnused(opt)"
                                                        class="p-2 text-[10px] rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800 m-2">
                                                        ⚠️ Em questões múltiplas, regras por opção não são usadas.</div>
                                                    <div class="p-2 space-y-4 text-xs">
                                                        <div
                                                            :class="{ 'opacity-50 pointer-events-none': optionIsWrong(opt) && form.type !== 'info' }">
                                                            <Label
                                                                class="font-bold text-green-700 dark:text-green-500">{{
                                                                    form.type ===
                                                                        'info' ? 'Ação ao Clicar' : 'Ao Acertar (Ação)'
                                                                }}</Label>
                                                            <Select v-model="opt.correct_action">
                                                                <SelectTrigger
                                                                    class="mt-1 text-xs bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7">
                                                                    <SelectValue placeholder="Usar regra da pergunta" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem :value="null">Usar regra da pergunta
                                                                    </SelectItem>
                                                                    <SelectItem value="continue">Continuar</SelectItem>
                                                                    <SelectItem value="seek">Ir para tempo</SelectItem>
                                                                </SelectContent>
                                                            </Select>

                                                            <div v-if="opt.correct_action === 'seek'"
                                                                class="p-2 mt-2 border rounded dark:border-slate-700 bg-slate-100 dark:bg-slate-950">
                                                                <div class="mb-2">
                                                                    <Label
                                                                        class="text-[10px] text-slate-500 uppercase font-bold">Pular
                                                                        para Vídeo:</Label>
                                                                    <Select v-model="opt.correct_target_lesson_id">
                                                                        <SelectTrigger
                                                                            class="h-6 text-[10px] bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                                                                            <SelectValue
                                                                                placeholder="Neste vídeo (Atual)" />
                                                                        </SelectTrigger>
                                                                        <SelectContent
                                                                            class="dark:bg-slate-900 dark:border-slate-800">
                                                                            <!-- 🔥 CORREÇÃO: "current" em vez de null -->
                                                                            <SelectItem value="current"
                                                                                class="text-[10px]">
                                                                                Neste vídeo (Atual)
                                                                            </SelectItem>
                                                                            <!-- 🔥 CORREÇÃO: les.id.toString() obrigatório para o Shadcn -->
                                                                            <SelectItem v-for="les in props.allLessons"
                                                                                :key="les.id" :value="les.id.toString()"
                                                                                class="text-[10px]">
                                                                                {{ les.module.title }} - {{ les.title }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                                <div>
                                                                    <Label
                                                                        class="text-[10px] text-slate-500 uppercase font-bold">No
                                                                        tempo:</Label>
                                                                    <TimeInput v-model="opt.correct_seek_to"
                                                                        class="bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700 h-6 text-[10px]" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div v-if="form.type !== 'info'"
                                                            :class="{ 'opacity-50 pointer-events-none': optionIsCorrect(opt) }">
                                                            <Label class="font-bold text-red-700 dark:text-red-500">Ao
                                                                Errar
                                                                (Ação)</Label>
                                                            <Select v-model="opt.wrong_action">
                                                                <SelectTrigger
                                                                    class="mt-1 text-xs bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7">
                                                                    <SelectValue placeholder="Usar regra da pergunta" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem :value="null">Usar regra da pergunta
                                                                    </SelectItem>
                                                                    <SelectItem value="continue">Continuar</SelectItem>
                                                                    <SelectItem value="seek">Ir para tempo</SelectItem>
                                                                </SelectContent>
                                                            </Select>

                                                            <div v-if="opt.wrong_action === 'seek'"
                                                                class="p-2 mt-2 border rounded dark:border-slate-700 bg-slate-100 dark:bg-slate-950">
                                                                <div class="mb-2">
                                                                    <Label
                                                                        class="text-[10px] text-slate-500 uppercase font-bold">Pular
                                                                        para Vídeo:</Label>
                                                                    <Select v-model="opt.wrong_target_lesson_id">
                                                                        <SelectTrigger
                                                                            class="h-6 text-[10px] bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700">
                                                                            <SelectValue
                                                                                placeholder="Neste vídeo (Atual)" />
                                                                        </SelectTrigger>
                                                                        <SelectContent
                                                                            class="dark:bg-slate-900 dark:border-slate-800">
                                                                            <!-- 🔥 CORREÇÃO: "current" em vez de null -->
                                                                            <SelectItem value="current"
                                                                                class="text-[10px]">
                                                                                Neste vídeo (Atual)
                                                                            </SelectItem>
                                                                            <!-- 🔥 CORREÇÃO: les.id.toString() obrigatório para o Shadcn -->
                                                                            <SelectItem v-for="les in props.allLessons"
                                                                                :key="les.id" :value="les.id.toString()"
                                                                                class="text-[10px]">
                                                                                {{ les.module.title }} - {{ les.title }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                                <div>
                                                                    <Label
                                                                        class="text-[10px] text-slate-500 uppercase font-bold">No
                                                                        tempo:</Label>
                                                                    <TimeInput v-model="opt.wrong_seek_to"
                                                                        class="bg-white dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700 h-6 text-[10px]" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                            <Button type="button" variant="outline" size="sm"
                                                class="w-full bg-white border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800"
                                                @click="addOption(false)">
                                                <Plus class="w-4 h-4 mr-2" /> Adicionar Opção
                                            </Button>
                                        </template>
                                    </div>
                                </details>

                                <details :open="getSectionState('rules', true)"
                                    @toggle="handleDetailsToggle('rules', $event)" data-section="rules"
                                    class="overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                                        ⚙️ Regras Globais
                                    </summary>
                                    <div class="p-4 space-y-4 dark:bg-slate-950">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <Label class="dark:text-slate-300">
                                                    {{ form.type === 'info' ?
                                                        'Ação Padrão' : 'Acerto'
                                                    }}
                                                </Label>
                                                <Select v-model="form.correct_action">
                                                    <SelectTrigger
                                                        class="dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                        <SelectItem value="continue">Seguir</SelectItem>
                                                        <SelectItem value="seek">Ir para...</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <div v-if="form.correct_action === 'seek'"
                                                    class="p-2 mt-2 text-xs border rounded dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                                                    <div class="mb-2">
                                                        <Label
                                                            class="text-[10px] text-slate-500 uppercase font-bold">Pular
                                                            para
                                                            Vídeo:</Label>
                                                        <Select v-model="form.correct_target_lesson_id">
                                                            <SelectTrigger
                                                                class="text-xs bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7">
                                                                <SelectValue placeholder="Neste vídeo (Atual)" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <!-- 🔥 CORREÇÃO: "current" em vez de null -->
                                                                <SelectItem value="current" class="text-[10px]">
                                                                    Neste vídeo (Atual)
                                                                </SelectItem>
                                                                <!-- 🔥 CORREÇÃO: les.id.toString() obrigatório para o Shadcn -->
                                                                <SelectItem v-for="les in props.allLessons"
                                                                    :key="les.id" :value="les.id.toString()"
                                                                    class="text-[10px]">
                                                                    {{ les.module.title }} - {{ les.title }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div>
                                                        <Label
                                                            class="text-[10px] text-slate-500 uppercase font-bold">Iniciar
                                                            no
                                                            tempo:</Label>
                                                        <TimeInput v-model="form.correct_seek_to"
                                                            class="bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="!['info', 'automatic'].includes(form.type)">
                                                <Label class="dark:text-slate-300">Erro</Label>
                                                <Select v-model="form.wrong_action">
                                                    <SelectTrigger
                                                        class="dark:bg-slate-900 dark:border-slate-700 dark:text-slate-200">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                        <SelectItem value="continue">Seguir</SelectItem>
                                                        <SelectItem value="seek">Ir para...</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <div v-if="form.wrong_action === 'seek'"
                                                    class="p-2 mt-2 text-xs border rounded dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                                                    <div class="mb-2">
                                                        <Label
                                                            class="text-[10px] text-slate-500 uppercase font-bold">Pular
                                                            para
                                                            Vídeo:</Label>
                                                        <Select v-model="form.wrong_target_lesson_id">
                                                            <SelectTrigger
                                                                class="text-xs bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7">
                                                                <SelectValue placeholder="Neste vídeo (Atual)" />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <!-- 🔥 CORREÇÃO: "current" em vez de null -->
                                                                <SelectItem value="current" class="text-[10px]">
                                                                    Neste vídeo (Atual)
                                                                </SelectItem>
                                                                <!-- 🔥 CORREÇÃO: les.id.toString() obrigatório para o Shadcn -->
                                                                <SelectItem v-for="les in props.allLessons"
                                                                    :key="les.id" :value="les.id.toString()"
                                                                    class="text-[10px]">
                                                                    {{ les.module.title }} - {{ les.title }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <div>
                                                        <Label class="text-[10px] text-slate-500 uppercase font-bold">No
                                                            tempo:</Label>
                                                        <TimeInput v-model="form.wrong_seek_to"
                                                            class="bg-white dark:bg-slate-950 dark:text-slate-200 dark:border-slate-700 h-7" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details v-if="needsConfirmation" :open="getSectionState('confirmation', true)"
                                    @toggle="handleDetailsToggle('confirmation', $event)" data-section="confirmation"
                                    class="mb-4 overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold cursor-pointer bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-500">
                                        🟡
                                        Confirmação (Caixa e Botão)
                                    </summary>
                                    <div class="p-4 dark:bg-slate-950">
                                        <div
                                            class="p-3 border rounded-lg shadow-sm border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-slate-900">

                                            <Label
                                                class="block mb-2 text-xs font-bold uppercase text-amber-800 dark:text-amber-500">🔲
                                                Caixa
                                                (Fundo)</Label>
                                            <div
                                                class="p-2 mb-4 bg-white border rounded dark:bg-slate-950 border-amber-200 dark:border-amber-800">
                                                <div>
                                                    <Label class="text-[9px] text-slate-500 font-bold uppercase">Texto
                                                        de Aviso
                                                        (Opcional)</Label>
                                                    <Input v-model="form.confirmation_config.text"
                                                        placeholder="Ex: Tem certeza?"
                                                        class="h-8 mt-1 text-xs bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mt-3 mb-1 block">Estilo
                                                    do
                                                    Texto</Label>
                                                <div class="grid grid-cols-2 gap-2 mb-3">
                                                    <div class="flex items-center gap-1">
                                                        <input type="color"
                                                            v-model="form.confirmation_config.text_color"
                                                            class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer border-amber-200 dark:border-amber-800"
                                                            title="Cor do Texto" />
                                                        <Input type="number" min="8"
                                                            v-model.number="form.confirmation_config.text_font_size"
                                                            class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800"
                                                            title="Tamanho da Fonte" />
                                                    </div>
                                                    <Select v-model="form.confirmation_config.text_font_family">
                                                        <SelectTrigger
                                                            class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                            <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                :value="f.value" class="text-xs">{{ f.label }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mb-3">
                                                    <div>
                                                        <Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                            (H/V)</Label>
                                                        <div class="flex gap-1 mt-1">
                                                            <Select v-model="form.confirmation_config.text_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                                    <SelectValue placeholder="H" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="left">Esq</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="right">Dir</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                            <Select v-model="form.confirmation_config.vertical_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                                    <SelectValue placeholder="V" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="top">Cima</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="bottom">Baixo</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mb-1 block">Cor
                                                    Fundo e
                                                    Opacidade</Label>
                                                <div class="flex items-center gap-1 mb-3">
                                                    <input type="color"
                                                        v-model="form.confirmation_config.background_color"
                                                        class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-amber-200 dark:border-amber-800"
                                                        title="Cor do Fundo da Caixa" />
                                                    <Input type="number" step="0.1" min="0" max="1"
                                                        v-model.number="form.confirmation_config.background_opacity"
                                                        class="w-16 h-8 px-2 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800"
                                                        title="Opacidade Fundo (0 a 1)" />
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mb-1 block">Imagem
                                                    de
                                                    Fundo</Label>
                                                <input type="file" accept="image/*"
                                                    class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-amber-100 dark:file:bg-amber-900/30 file:text-amber-700 dark:file:text-amber-500 hover:file:bg-amber-200 dark:hover:file:bg-amber-800/50"
                                                    @change="onConfirmationImageSelect" />
                                                <div v-if="form.confirmation_image_preview"
                                                    class="relative inline-block mt-2">
                                                    <img :src="form.confirmation_image_preview"
                                                        class="object-cover bg-white border rounded max-h-16 border-amber-200 dark:border-amber-800 dark:bg-slate-950" />
                                                    <button type="button"
                                                        class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-1 right-1 shadow-sm"
                                                        @click="() => { form.confirmation_image = null; form.confirmation_image_preview = null; form.remove_confirmation_image = true; }">X</button>
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mt-3 mb-1 block">Posição
                                                    e
                                                    Tamanho (%)</Label>
                                                <div class="grid grid-cols-5 gap-2">
                                                    <div class="flex flex-col gap-1"><Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Top</Label><Input
                                                            type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.coordinates.top"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1"><Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Left</Label><Input
                                                            type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.coordinates.left"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1"><Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Width</Label><Input
                                                            type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.coordinates.width"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1"><Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Height</Label><Input
                                                            type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.coordinates.height"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1"><Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                            (px)</Label><Input type="number" step="1"
                                                            v-model.number="form.confirmation_config.coordinates.radius"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                </div>
                                            </div>

                                            <Label
                                                class="block mb-2 text-xs font-bold uppercase text-amber-800 dark:text-amber-500">🔘
                                                Estilo do
                                                Botão</Label>
                                            <div
                                                class="p-2 bg-white border rounded dark:bg-slate-950 border-amber-200 dark:border-amber-800">
                                                <div>
                                                    <Label class="text-[9px] text-slate-500 font-bold uppercase">Texto
                                                        do Botão</Label>
                                                    <Input v-model="form.confirmation_config.button.text"
                                                        placeholder="Ex: Confirmar"
                                                        class="h-8 mt-1 text-xs bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mt-3 mb-1 block">Estilo
                                                    do
                                                    Texto</Label>
                                                <div class="grid grid-cols-2 gap-2 mb-3">
                                                    <div class="flex items-center gap-1">
                                                        <input type="color"
                                                            v-model="form.confirmation_config.button.text_color"
                                                            class="w-6 h-6 p-0 bg-transparent border rounded cursor-pointer border-amber-200 dark:border-amber-800"
                                                            title="Cor do Texto" />
                                                        <Input type="number" min="8"
                                                            v-model.number="form.confirmation_config.button.text_font_size"
                                                            class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800"
                                                            title="Tamanho da Fonte" />
                                                    </div>
                                                    <Select v-model="form.confirmation_config.button.text_font_family">
                                                        <SelectTrigger
                                                            class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                                            <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                :value="f.value" class="text-xs">{{ f.label }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mb-3">
                                                    <div>
                                                        <Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                            (H/V)</Label>
                                                        <div class="flex gap-1 mt-1">
                                                            <Select
                                                                v-model="form.confirmation_config.button.text_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                                    <SelectValue placeholder="H" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="left">Esq</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="right">Dir</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                            <Select
                                                                v-model="form.confirmation_config.button.vertical_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800">
                                                                    <SelectValue placeholder="V" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="top">Cima</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="bottom">Baixo</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mb-1 block">Cor
                                                    Fundo e
                                                    Opacidade</Label>
                                                <div class="flex items-center gap-1 mb-3">
                                                    <input type="color"
                                                        v-model="form.confirmation_config.button.background_color"
                                                        class="w-8 h-8 p-0 bg-transparent border rounded cursor-pointer border-amber-200 dark:border-amber-800"
                                                        title="Cor do Fundo do Botão" />
                                                    <Input type="number" step="0.1" min="0" max="1"
                                                        v-model.number="form.confirmation_config.button.background_opacity"
                                                        class="w-16 h-8 px-2 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800"
                                                        title="Opacidade do Botão (0 a 1)" />
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mb-1 block">Imagem
                                                    do
                                                    Botão</Label>
                                                <input type="file" accept="image/*"
                                                    class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-amber-100 dark:file:bg-amber-900/30 file:text-amber-700 dark:file:text-amber-500 hover:file:bg-amber-200 dark:hover:file:bg-amber-800/50"
                                                    @change="onConfirmationButtonImageSelect" />
                                                <div v-if="form.confirmation_button_image_preview"
                                                    class="relative inline-block mt-2">
                                                    <img :src="form.confirmation_button_image_preview"
                                                        class="object-cover bg-white border rounded max-h-12 border-amber-200 dark:border-amber-800 dark:bg-slate-950" />
                                                    <button type="button"
                                                        class="absolute px-1.5 py-0 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-0 right-0 shadow-sm"
                                                        @click="() => { form.confirmation_button_image = null; form.confirmation_button_image_preview = null; form.remove_confirmation_button_image = true; }">X</button>
                                                </div>

                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase mt-3 block mb-1">Posição
                                                    e
                                                    Tamanho do Botão (%)</Label>
                                                <div class="grid grid-cols-5 gap-2">
                                                    <div class="flex flex-col gap-1">
                                                        <Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Top</Label>
                                                        <Input type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.button.coordinates.top"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Left</Label>
                                                        <Input type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.button.coordinates.left"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Width</Label>
                                                        <Input type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.button.coordinates.width"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Height</Label>
                                                        <Input type="number" step="0.1"
                                                            v-model.number="form.confirmation_config.button.coordinates.height"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <Label
                                                            class="text-[9px] text-slate-500 text-center font-bold">Raio</Label>
                                                        <Input type="number" step="1"
                                                            v-model.number="form.confirmation_config.button.coordinates.radius"
                                                            class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-amber-200 dark:border-amber-800" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details v-if="!['automatic', 'info'].includes(form.type)"
                                    :open="getSectionState('feedback_correct', true)"
                                    @toggle="handleDetailsToggle('feedback_correct', $event)"
                                    data-section="feedback_correct"
                                    class="mb-4 overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold text-green-800 bg-green-100 cursor-pointer dark:text-green-500 dark:bg-green-900/30">
                                        🟢
                                        Feedback de
                                        Acerto</summary>
                                    <div class="p-4 dark:bg-slate-950">
                                        <div
                                            class="p-3 border border-green-300 rounded-lg shadow-sm dark:border-green-800 bg-green-50 dark:bg-slate-900">
                                            <div class="flex items-start gap-2 mb-3">
                                                <div class="flex-1"><Label
                                                        class="text-[10px] text-green-700 dark:text-green-500 font-bold uppercase">Mensagem</Label>
                                                    <textarea v-model="form.correct_feedback_config.text"
                                                        placeholder="Ex: Muito bem!"
                                                        class="w-full min-h-[60px] mt-1 p-2 text-xs bg-white dark:bg-slate-950 dark:text-slate-200 border border-green-300 dark:border-green-800 rounded-md focus:outline-none focus:ring-1 focus:ring-green-400 dark:focus:ring-green-600 resize-y shadow-sm"></textarea>
                                                </div>
                                                <div class="w-20"><Label
                                                        class="text-[10px] text-green-700 dark:text-green-500 font-bold uppercase">Dur.
                                                        (s)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.correct_feedback_config.duration_seconds"
                                                        class="h-8 mt-1 text-xs text-center bg-white border-green-300 dark:bg-slate-950 dark:text-slate-200 dark:border-green-800" />
                                                </div>
                                            </div>
                                            <div
                                                class="p-2 mb-3 bg-white border border-green-200 rounded dark:bg-slate-950 dark:border-green-800">
                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase block">Cores,
                                                    Fundo e
                                                    Opacidade</Label>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <input type="color"
                                                        v-model="form.correct_feedback_config.background_color"
                                                        class="w-6 h-6 p-0 bg-transparent border border-green-200 rounded cursor-pointer dark:border-green-800"
                                                        title="Cor do Fundo" /><Input type="number" step="0.1" min="0"
                                                        max="1"
                                                        v-model.number="form.correct_feedback_config.background_opacity"
                                                        class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-green-200 dark:border-green-800"
                                                        title="Opacidade Fundo" />
                                                    <div class="w-px h-4 mx-1 bg-green-200 dark:bg-green-800"></div>
                                                    <input type="color"
                                                        v-model="form.correct_feedback_config.text_color"
                                                        class="w-6 h-6 p-0 bg-transparent border border-green-200 rounded cursor-pointer dark:border-green-800"
                                                        title="Cor do Texto" /><Input type="number"
                                                        v-model.number="form.correct_feedback_config.text_font_size"
                                                        class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-green-200 dark:border-green-800"
                                                        title="Tamanho da Fonte" />
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mt-2">
                                                    <div><Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                            (H/V)</Label>
                                                        <div class="flex gap-1 mt-1"><Select
                                                                v-model="form.correct_feedback_config.text_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-green-200 dark:border-green-800">
                                                                    <SelectValue placeholder="H" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="left">Esq</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="right">Dir</SelectItem>
                                                                </SelectContent>
                                                            </Select><Select
                                                                v-model="form.correct_feedback_config.vertical_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-green-200 dark:border-green-800">
                                                                    <SelectValue placeholder="V" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="top">Cima</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="bottom">Baixo</SelectItem>
                                                                </SelectContent>
                                                            </Select></div>
                                                    </div>
                                                    <div><Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Fonte</Label><Select
                                                            v-model="form.correct_feedback_config.text_font_family">
                                                            <SelectTrigger
                                                                class="h-6 px-1 mt-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-green-200 dark:border-green-800">
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                    :value="f.value" class="text-xs">{{ f.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select></div>
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-green-700 dark:text-green-500 font-bold uppercase mb-1 block">Posição
                                                e Tamanho
                                                no Vídeo</Label>
                                            <div
                                                class="grid grid-cols-5 gap-2 p-2 mb-3 bg-white border border-green-200 rounded dark:bg-slate-950 dark:border-green-800">
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Top
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.correct_feedback_config.coordinates.top"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Left
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.correct_feedback_config.coordinates.left"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Width
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.correct_feedback_config.coordinates.width"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Height
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.correct_feedback_config.coordinates.height"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                        (px)</Label><Input type="number" step="1"
                                                        v-model.number="form.correct_feedback_config.coordinates.radius"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                            </div>
                                            <div><Label
                                                    class="text-[10px] text-green-700 dark:text-green-500 font-bold uppercase mb-1 block">Imagem
                                                    de
                                                    Fundo</Label><input type="file" accept="image/*"
                                                    class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-green-100 dark:file:bg-green-900/30 file:text-green-700 dark:file:text-green-400 hover:file:bg-green-200 dark:hover:file:bg-green-800/50"
                                                    @change="onFeedbackImageSelect($event, 'correct')" />
                                                <div v-if="form.correct_feedback_image_preview"
                                                    class="relative inline-block mt-2"><img
                                                        :src="form.correct_feedback_image_preview"
                                                        class="object-cover bg-white border border-green-200 rounded dark:border-green-800 max-h-16 dark:bg-slate-950" /><button
                                                        type="button"
                                                        class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-1 right-1 shadow-sm"
                                                        @click="removeCorrectFeedbackImage">X</button></div>
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <details v-if="!['automatic', 'info'].includes(form.type)"
                                    :open="getSectionState('feedback_wrong', true)"
                                    @toggle="handleDetailsToggle('feedback_wrong', $event)"
                                    data-section="feedback_wrong"
                                    class="mb-4 overflow-hidden border rounded-lg dark:border-slate-800">
                                    <summary
                                        class="px-4 py-3 font-bold text-red-800 bg-red-100 cursor-pointer dark:text-red-500 dark:bg-red-900/30">
                                        🔴
                                        Feedback de Erro
                                    </summary>
                                    <div class="p-4 dark:bg-slate-950">
                                        <div
                                            class="p-3 border border-red-300 rounded-lg shadow-sm dark:border-red-800 bg-red-50 dark:bg-slate-900">
                                            <div class="flex items-start gap-2 mb-3">
                                                <div class="flex-1"><Label
                                                        class="text-[10px] text-red-700 dark:text-red-500 font-bold uppercase">Mensagem</Label>
                                                    <textarea v-model="form.wrong_feedback_config.text"
                                                        placeholder="Ex: Tente novamente!"
                                                        class="w-full min-h-[60px] mt-1 p-2 text-xs bg-white dark:bg-slate-950 dark:text-slate-200 border border-red-300 dark:border-red-800 rounded-md focus:outline-none focus:ring-1 focus:ring-red-400 dark:focus:ring-red-600 resize-y shadow-sm"></textarea>
                                                </div>
                                                <div class="w-20"><Label
                                                        class="text-[10px] text-red-700 dark:text-red-500 font-bold uppercase">Dur.
                                                        (s)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.wrong_feedback_config.duration_seconds"
                                                        class="h-8 mt-1 text-xs text-center bg-white border-red-300 dark:bg-slate-950 dark:text-slate-200 dark:border-red-800" />
                                                </div>
                                            </div>
                                            <div
                                                class="p-2 mb-3 bg-white border border-red-200 rounded dark:bg-slate-950 dark:border-red-800">
                                                <Label
                                                    class="text-[9px] text-slate-500 font-bold uppercase block">Cores,
                                                    Fundo e
                                                    Opacidade</Label>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <input type="color"
                                                        v-model="form.wrong_feedback_config.background_color"
                                                        class="w-6 h-6 p-0 bg-transparent border border-red-200 rounded cursor-pointer dark:border-red-800"
                                                        title="Cor do Fundo" /><Input type="number" step="0.1" min="0"
                                                        max="1"
                                                        v-model.number="form.wrong_feedback_config.background_opacity"
                                                        class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-red-200 dark:border-red-800"
                                                        title="Opacidade Fundo" />
                                                    <div class="w-px h-4 mx-1 bg-red-200 dark:bg-red-800"></div><input
                                                        type="color" v-model="form.wrong_feedback_config.text_color"
                                                        class="w-6 h-6 p-0 bg-transparent border border-red-200 rounded cursor-pointer dark:border-red-800"
                                                        title="Cor do Texto" /><Input type="number"
                                                        v-model.number="form.wrong_feedback_config.text_font_size"
                                                        class="w-12 h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-red-200 dark:border-red-800"
                                                        title="Tamanho da Fonte" />
                                                </div>
                                                <div class="grid grid-cols-2 gap-2 mt-2">
                                                    <div><Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Alinhamento
                                                            (H/V)</Label>
                                                        <div class="flex gap-1 mt-1"><Select
                                                                v-model="form.wrong_feedback_config.text_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-red-200 dark:border-red-800">
                                                                    <SelectValue placeholder="H" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="left">Esq</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="right">Dir</SelectItem>
                                                                </SelectContent>
                                                            </Select><Select
                                                                v-model="form.wrong_feedback_config.vertical_align">
                                                                <SelectTrigger
                                                                    class="h-6 px-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-red-200 dark:border-red-800">
                                                                    <SelectValue placeholder="V" />
                                                                </SelectTrigger>
                                                                <SelectContent
                                                                    class="dark:bg-slate-900 dark:border-slate-800">
                                                                    <SelectItem value="top">Cima</SelectItem>
                                                                    <SelectItem value="center">Meio</SelectItem>
                                                                    <SelectItem value="bottom">Baixo</SelectItem>
                                                                </SelectContent>
                                                            </Select></div>
                                                    </div>
                                                    <div><Label
                                                            class="text-[8px] text-slate-500 font-bold uppercase">Fonte</Label><Select
                                                            v-model="form.wrong_feedback_config.text_font_family">
                                                            <SelectTrigger
                                                                class="h-6 px-1 mt-1 text-[9px] bg-slate-50 dark:bg-slate-900 dark:text-slate-200 border-red-200 dark:border-red-800">
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent
                                                                class="dark:bg-slate-900 dark:border-slate-800">
                                                                <SelectItem v-for="f in fontOptions" :key="f.value"
                                                                    :value="f.value" class="text-xs">{{ f.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select></div>
                                                </div>
                                            </div>
                                            <Label
                                                class="text-[10px] text-red-700 dark:text-red-500 font-bold uppercase mb-1 block">Posição
                                                e Tamanho no
                                                Vídeo</Label>
                                            <div
                                                class="grid grid-cols-5 gap-2 p-2 mb-3 bg-white border border-red-200 rounded dark:bg-slate-950 dark:border-red-800">
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Top
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.wrong_feedback_config.coordinates.top"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Left
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.wrong_feedback_config.coordinates.left"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Width
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.wrong_feedback_config.coordinates.width"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Height
                                                        (%)</Label><Input type="number" step="0.1"
                                                        v-model.number="form.wrong_feedback_config.coordinates.height"
                                                        class="h-6 px-1 text-xs text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                                <div class="flex flex-col gap-1"><Label
                                                        class="text-[9px] text-slate-500 text-center font-bold">Raio
                                                        (px)</Label><Input type="number" step="1"
                                                        v-model.number="form.wrong_feedback_config.coordinates.radius"
                                                        class="h-6 px-1 text-[10px] text-center bg-slate-50 dark:bg-slate-900 dark:text-slate-200" />
                                                </div>
                                            </div>
                                            <div><Label
                                                    class="text-[10px] text-red-700 dark:text-red-500 font-bold uppercase mb-1 block">Imagem
                                                    de
                                                    Fundo</Label><input type="file" accept="image/*"
                                                    class="block w-full text-[9px] text-slate-500 dark:text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[9px] file:font-semibold file:bg-red-100 dark:file:bg-red-900/30 file:text-red-700 dark:file:text-red-400 hover:file:bg-red-200 dark:hover:file:bg-red-800/50"
                                                    @change="onFeedbackImageSelect($event, 'wrong')" />
                                                <div v-if="form.wrong_feedback_image_preview"
                                                    class="relative inline-block mt-2"><img
                                                        :src="form.wrong_feedback_image_preview"
                                                        class="object-cover bg-white border border-red-200 rounded dark:border-red-800 max-h-16 dark:bg-slate-950" /><button
                                                        type="button"
                                                        class="absolute px-1.5 py-0.5 text-[9px] font-bold text-white bg-red-600 hover:bg-red-700 rounded top-1 right-1 shadow-sm"
                                                        @click="removeWrongFeedbackImage">X</button></div>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </form>
                        </section>
                        <footer class="px-5 py-4 border-t dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
                            <Button class="w-full font-bold" type="button" @click="submitInteraction">
                                Salvar Interação
                            </Button>
                        </footer>
                    </aside>
                </transition>
            </div>
        </TooltipProvider>

        <!-- 🔥 MODAL DE REDIRECIONAMENTO DO MODO TESTE -->
        <div v-if="showRedirectModal"
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="w-full max-w-md p-6 border shadow-2xl bg-slate-900 border-slate-700 rounded-xl">
                <h3 class="flex items-center gap-2 mb-2 text-lg font-bold text-amber-500">
                    <ExternalLink class="w-5 h-5" /> Modo de Teste
                </h3>
                <p class="mb-4 text-sm text-slate-300">
                    Você está em modo de teste, mas na visão do aluno esta interação redirecionaria para outro vídeo:
                </p>

                <div class="p-4 mb-6 border rounded-lg bg-slate-950 border-slate-800 text-slate-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase text-slate-500 w-14">Vídeo:</span>
                        <span class="text-sm font-bold truncate text-slate-100">{{ redirectModalData.videoName }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase text-slate-500 w-14">Tempo:</span>
                        <span class="font-mono text-sm font-bold text-amber-400">{{ redirectModalData.timeFormatted
                            }}</span>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button @click="confirmRedirectModal"
                        class="font-bold text-slate-900 bg-amber-500 hover:bg-amber-600">
                        Continuar Teste
                    </Button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
