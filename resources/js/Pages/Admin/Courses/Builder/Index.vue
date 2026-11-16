<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Plus, Video, Edit, GripVertical, MoreVertical,
    Trash2, PlayCircle, Network, List, RefreshCcw, Maximize, Minimize,
    ZoomIn, ZoomOut,
    EyeOff,
    Eye,
    Circle
} from 'lucide-vue-next';

const props = defineProps<{ course: any }>();
const page = usePage();
// Presumindo a estrutura padrão do Laravel/Inertia para utilizadores autenticados:
const currentUserId = computed(() => page.props.auth.user.id);
// --- ESTADOS DE VISUALIZAÇÃO ---
const viewMode = ref<'list' | 'map'>('list');
// 🔥 NOVO: Controlo de Zoom
const zoomLevel = ref(1);

const zoomIn = () => { zoomLevel.value = Math.min(zoomLevel.value + 0.1, 2); }; // Máximo 200%
const zoomOut = () => { zoomLevel.value = Math.max(zoomLevel.value - 0.1, 0.3); }; // Mínimo 30%
const resetZoom = () => { zoomLevel.value = 1; };
const isMapFullscreen = ref(false);

watch(isMapFullscreen, (isFull) => {
    if (isFull) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

// --- ESTADOS DOS MODAIS ---
const isModuleModalOpen = ref(false);
const isLessonModalOpen = ref(false);
const editingModuleId = ref<number | null>(null);
const editingLessonId = ref<number | null>(null);
const activeModuleId = ref<number | null>(null);

const moduleForm = useForm({ title: '' });
const lessonForm = useForm({ title: '', video_url: '' });

// --- LÓGICA DO MAPA VISUAL (GRAFO) ---
interface GraphNode {
    id: number;
    title: string;
    moduleTitle: string;
    x: number;
    y: number;
    moduleId: number;
}

interface GraphEdge {
    source: number;
    target: number;
    colorClass: string;
    marker: string;
    label: string;
    time: string;
    badgeClass: string;
    questionTitle: string;
    overlapIndex: number;
    visible: boolean;
    path?: string;
    midpoint?: { x: number; y: number } | null;
    opacity?: number;
    strokeDash?: string;
    customOffsetX?: number;
    customOffsetY?: number; // Para o arrasto das curvas
}
// 🔥 NOVO: Estado de conexões ocultas
const hiddenEdges = ref<string[]>([]);
const showAllEdges = () => {
    hiddenEdges.value = [];
    saveMapPositions();
};
// Helper para criar uma chave única para cada edge
const getEdgeKey = (e: GraphEdge) => `${e.source}-${e.target}-${e.questionTitle}`;
const nodes = ref<GraphNode[]>([]);
const edges = ref<GraphEdge[]>([]);
const mapContainer = ref<HTMLElement | null>(null);
const toggleEdgeVisibility = (edge: GraphEdge) => {
    const key = getEdgeKey(edge);
    const index = hiddenEdges.value.indexOf(key);

    if (index > -1) {
        hiddenEdges.value.splice(index, 1);
    } else {
        hiddenEdges.value.push(key);
    }

    saveMapPositions(); // 🔥 Salva o mapa ao esconder/mostrar a linha
};
const formatTimeMap = (seconds: number) => {
    if (isNaN(seconds) || seconds < 0) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}`;
};

const saveMapPositions = () => {
    if (!props.course) return;

    // 1. Salvar Posições das Caixas (Nós)
    const positions: Record<number, { x: number, y: number }> = {};
    nodes.value.forEach(node => {
        positions[node.id] = { x: node.x, y: node.y };
    });
    const storageKeyNodes = `user_${currentUserId.value}_course_${props.course.id}_map_positions`;
    localStorage.setItem(storageKeyNodes, JSON.stringify(positions));

    // 🔥 2. NOVO: Salvar Estado das Linhas (Desvios e Visibilidade)
    const edgesState = {
        offsets: {} as Record<string, { x: number, y: number }>, // Agora é um objeto
        hidden: hiddenEdges.value
    };
    edges.value.forEach(edge => {
        if (edge.customOffsetX || edge.customOffsetY) {
            edgesState.offsets[getEdgeKey(edge)] = {
                x: edge.customOffsetX || 0,
                y: edge.customOffsetY || 0
            };
        }
    });
    const storageKeyEdges = `user_${currentUserId.value}_course_${props.course.id}_map_edges`;
    localStorage.setItem(storageKeyEdges, JSON.stringify(edgesState));
};

const resetMapPositions = () => {
    if (!props.course) return;
    if (confirm('Deseja reorganizar todas as caixas e linhas para as posições padrão?')) {
        const storageKeyNodes = `user_${currentUserId.value}_course_${props.course.id}_map_positions`;
        const storageKeyEdges = `user_${currentUserId.value}_course_${props.course.id}_map_edges`;

        localStorage.removeItem(storageKeyNodes);
        localStorage.removeItem(storageKeyEdges); // 🔥 Limpa as linhas também

        hiddenEdges.value = []; // Reseta o olho em memória
        buildGraph();
    }
};

const buildGraph = () => {
    const newNodes: GraphNode[] = [];
    const newEdges: GraphEdge[] = [];

    if (!props.course || !props.course.modules) return;
    const getLessonTitle = (lessonId: number) => {
        for (const module of props.course.modules) {
            const lesson = module.lessons.find((l: any) => l.id === lessonId);
            if (lesson) return lesson.title;
        }
        return 'Vídeo Desconhecido';
    };
    const storageKey = `user_${currentUserId.value}_course_${props.course.id}_map_positions`;
    const savedRaw = localStorage.getItem(storageKey);
    const savedPositions = savedRaw ? JSON.parse(savedRaw) : {};

    const storageKeyEdges = `user_${currentUserId.value}_course_${props.course.id}_map_edges`;
    const savedEdgesRaw = localStorage.getItem(storageKeyEdges);
    const savedEdgesState = savedEdgesRaw ? JSON.parse(savedEdgesRaw) : { offsets: {}, hidden: [] };

    // Restaura as linhas ocultas logo na inicialização
    hiddenEdges.value = savedEdgesState.hidden || [];
    props.course.modules.forEach((m: any, mIdx: number) => {
        let currentX = 50 + (mIdx * 350);
        let currentY = 50;

        if (!m.lessons) return;

        m.lessons.forEach((l: any) => {
            let nodeX = currentX;
            let nodeY = currentY;

            if (savedPositions[l.id]) {
                nodeX = savedPositions[l.id].x;
                nodeY = savedPositions[l.id].y;
            }

            newNodes.push({
                id: l.id, title: l.title, moduleTitle: m.title, moduleId: m.id,
                x: nodeX, y: nodeY
            });
            currentY += 150;

            if (l.questions && l.questions.length > 0) {
                l.questions.forEach((q: any) => {

                    // Define o Tipo Base da Interação
                    let typeLabel = 'Quiz';
                    if (q.type === 'automatic') typeLabel = 'Auto';
                    else if (q.type === 'info') typeLabel = 'Info';
                    else if (q.type === 'drag_and_drop') typeLabel = 'Drag';

                    const sourceTimeStr = formatTimeMap(Number(q.timestamp_seconds));
                    const baseTitle = q.title ? q.title : 'Sem Título';

                    // Função Helper para desenhar a linha
                    const addEdge = (targetId: any, seekTo: any, theme: 'green' | 'red' | 'blue', icon: string, contextDesc: string) => {
                        if (!targetId) return;
                        const parsedId = Number(targetId);
                        if (parsedId === l.id) return; // Ignora links internos no mesmo vídeo

                        let colorClass = '';
                        let marker = '';
                        let badgeClass = 'bg-white dark:bg-slate-950 border shadow-sm transition-all duration-300 max-w-[800px] ';

                        // Design system de cores
                        if (theme === 'green') {
                            colorClass = 'stroke-green-500/80'; marker = 'green';
                            badgeClass += 'border-green-200 text-green-700 dark:border-green-800 dark:text-green-400';
                        } else if (theme === 'red') {
                            colorClass = 'stroke-red-500/80'; marker = 'red';
                            badgeClass += 'border-red-200 text-red-700 dark:border-red-800 dark:text-red-400';
                        } else {
                            colorClass = 'stroke-blue-500/80'; marker = 'blue';
                            badgeClass += 'border-blue-200 text-blue-700 dark:border-blue-800 dark:text-blue-400';
                        }

                        const targetTimeStr = (seekTo != null && seekTo !== '') ? formatTimeMap(Number(seekTo)) : '00:00';

                        // 🔥 NOVO: Construção da string com os nomes dos vídeos
                        const targetLessonTitle = getLessonTitle(parsedId);
                        const combinedTimeStr = `${l.title} [${sourceTimeStr}] ➔ ${targetLessonTitle} [${targetTimeStr}]`;

                        const overlapCount = newEdges.filter(e => e.source === l.id && e.target === parsedId).length;
                        const finalLabel = `${icon} ${typeLabel}`;
                        const finalTitle = contextDesc ? `${baseTitle} (${contextDesc})` : baseTitle;
                        // 🔥 A MÁGICA DA PRESERVAÇÃO: Recupera o offset caso o gráfico seja redesenhado
                        const oldEdge = edges.value.find(e => e.source === l.id && e.target === parsedId && e.questionTitle === finalTitle);
                        const edgeKey = `${l.id}-${parsedId}-${finalTitle}`;
                        const preservedOffset = savedEdgesState.offsets[edgeKey] || 0;
                        newEdges.push({
                            source: l.id,
                            target: parsedId,
                            colorClass,
                            marker,
                            label: finalLabel,
                            time: combinedTimeStr,
                            badgeClass,
                            questionTitle: finalTitle,
                            overlapIndex: overlapCount,
                            visible: true,
                            customOffsetX: preservedOffset.x, // Eixo X
                            customOffsetY: preservedOffset.y
                        });
                    };

                    // 🔥 LÓGICA DE AVALIAÇÃO INTELIGENTE (Global vs Individual)

                    const isQuestionTypeWithOptions = ['single_choice', 'multiple_choice', 'info'].includes(q.type);

                    // Flags para saber se devemos usar a regra global
                    let useGlobalCorrect = true;
                    let useGlobalWrong = true;

                    // 1. Verifica as Regras Individuais (Sobreposições) PRIMEIRO
                    if (q.options && q.options.length > 0 && isQuestionTypeWithOptions) {
                        q.options.forEach((opt: any, index: number) => {
                            const optName = opt.text ? opt.text : `Opção ${index + 1}`;

                            // Acerto / Avanço da Opção
                            if (opt.correct_action === 'seek') {
                                // Se a opção tem uma ação específica de sucesso/avanço, a global de sucesso é cancelada
                                useGlobalCorrect = false;

                                const isInfo = q.type === 'info';
                                const theme = isInfo ? 'blue' : (opt.is_correct ? 'green' : 'blue');
                                const icon = isInfo ? '➡️' : (opt.is_correct ? '✅' : '➡️');
                                addEdge(opt.correct_target_lesson_id, opt.correct_seek_to, theme, icon, optName);
                            }

                            // Erro da Opção
                            if (q.type !== 'info' && opt.wrong_action === 'seek' && !opt.is_correct) {
                                // Se a opção tem um erro específico configurado, o erro global é cancelado
                                useGlobalWrong = false;

                                addEdge(opt.wrong_target_lesson_id, opt.wrong_seek_to, 'red', '❌', `${optName} - Erro`);
                            }
                        });
                    }

                    // 2. Aplica as Regras Globais APENAS se as opções não as tiverem substituído

                    if (useGlobalCorrect && q.correct_action === 'seek') {
                        const isContinue = (q.type === 'info' || q.type === 'automatic');
                        const theme = isContinue ? 'blue' : 'green';
                        const icon = isContinue ? '➡️' : '✅';
                        const contextDesc = isContinue ? '' : 'Acerto Global';
                        addEdge(q.correct_target_lesson_id, q.correct_seek_to, theme, icon, contextDesc);
                    }

                    if (useGlobalWrong && !['info', 'automatic'].includes(q.type) && q.wrong_action === 'seek') {
                        addEdge(q.wrong_target_lesson_id, q.wrong_seek_to, 'red', '❌', 'Erro Global');
                    }
                });
            }
        });
    });

    nodes.value = newNodes;
    edges.value = newEdges;
};
// 🔥 NOVO: Lógica de Panning (Clicar e arrastar o fundo para rolar)
const scrollWrapper = ref<HTMLElement | null>(null);
const isPanning = ref(false);
let panStartX = 0;
let panStartY = 0;
let panScrollLeft = 0;
let panScrollTop = 0;

const startPan = (e: MouseEvent) => {
    if (draggingNodeId.value !== null || isConnecting.value) return;

    const target = e.target as HTMLElement;

    // 🔥 CORREÇÃO: Ensinar o Panning a ignorar botões e elementos clicáveis!
    if (target.closest('.pointer-events-auto') || target.closest('button') || target.closest('.cursor-pointer')) {
        return;
    }

    if (!scrollWrapper.value) return;

    isPanning.value = true;
    panStartX = e.pageX - scrollWrapper.value.offsetLeft;
    panStartY = e.pageY - scrollWrapper.value.offsetTop;
    panScrollLeft = scrollWrapper.value.scrollLeft;
    panScrollTop = scrollWrapper.value.scrollTop;
};

const doPan = (e: MouseEvent) => {
    // Se não estiver com o rato pressionado no fundo, ignora
    if (!isPanning.value || !scrollWrapper.value) return;

    e.preventDefault(); // Evita que o browser tente selecionar texto acidentalmente

    // Calcula onde o rato está agora
    const x = e.pageX - scrollWrapper.value.offsetLeft;
    const y = e.pageY - scrollWrapper.value.offsetTop;

    // Calcula a distância percorrida (Multiplicador de 1.5 dá uma sensação de leveza/velocidade)
    const walkX = (x - panStartX) * 1.5;
    const walkY = (y - panStartY) * 1.5;

    // Move as barras de scroll na direção oposta ao movimento
    scrollWrapper.value.scrollLeft = panScrollLeft - walkX;
    scrollWrapper.value.scrollTop = panScrollTop - walkY;
};

const stopPan = () => {
    isPanning.value = false;
};


const focusedNodeId = ref<string | null>(null);

const activeEdges = computed(() => {
    const hidden = hiddenEdges.value;

    let filtered = edges.value;

    if (focusedNodeId.value) {
        filtered = filtered.filter(e =>
            Number(e.source) === Number(focusedNodeId.value) ||
            Number(e.target) === Number(focusedNodeId.value)
        );
    }
    const outCount: Record<number, number> = {};
    const inCount: Record<number, number> = {};

    const edgeIndices = edges.value.map(edge => {
        const sIdx = outCount[edge.source] || 0;
        outCount[edge.source] = sIdx + 1;

        const tIdx = inCount[edge.target] || 0;
        inCount[edge.target] = tIdx + 1;

        return { sIdx, tIdx };
    });

    return edges.value.map((edge, index) => {
        const sourceNode = nodes.value.find(n => n.id === edge.source);
        const targetNode = nodes.value.find(n => n.id === edge.target);

        if (!sourceNode || !targetNode) {
            return { ...edge, path: '', midpoint: null };
        }

        const { sIdx, tIdx } = edgeIndices[index];
        const totalOut = outCount[edge.source];
        const totalIn = inCount[edge.target];

        const sOffsetY = totalOut > 1 ? 30 + (70 / (totalOut - 1)) * sIdx : 65;
        const tOffsetY = totalIn > 1 ? 30 + (70 / (totalIn - 1)) * tIdx : 65;

        const sx = sourceNode.x + 220;
        const sy = sourceNode.y + sOffsetY;

        let tx = targetNode.x;
        let ty = targetNode.y + tOffsetY;

        const overlap = edge.overlapIndex || 0;
        // Ajuste fino na propagação da curva

        const curveSpreadY = overlap === 0 ? 0 : Math.ceil(overlap / 2) * 50 * (overlap % 2 === 0 ? 1 : -1);

        const offsetX = edge.customOffsetX || 0;
        const offsetY = edge.customOffsetY || 0;
        let pathStr = '';
        let p0, p1, p2, p3;

        // Construção dos Pontos da Curva de Bezier Cúbica
        if (targetNode.x <= sourceNode.x + 240) {
            tx = targetNode.x + 220;
            const controlX = Math.max(sx, tx) + 120 + (overlap * 40);
            p0 = { x: sx, y: sy };
            // 🔥 Adiciona offsetX no Eixo X dos pontos de controle
            p1 = { x: controlX + offsetX, y: sy + curveSpreadY + offsetY };
            p2 = { x: controlX + offsetX, y: ty + curveSpreadY + offsetY };
            p3 = { x: tx, y: ty };
        } else {
            const controlOffset = Math.max(Math.abs(tx - sx) / 2.5, 50);
            p0 = { x: sx, y: sy };
            // 🔥 Adiciona offsetX no Eixo X dos pontos de controle
            p1 = { x: sx + controlOffset + offsetX, y: sy + curveSpreadY + offsetY };
            p2 = { x: tx - controlOffset + offsetX, y: ty + curveSpreadY + offsetY };
            p3 = { x: tx, y: ty };
        }

        pathStr = `M ${p0.x} ${p0.y} C ${p1.x} ${p1.y}, ${p2.x} ${p2.y}, ${p3.x} ${p3.y}`;

        // 🔥 A MÁGICA 1: Distribuição ao longo da linha
        // Em vez de todas as badges ficarem no centro, nós espalhamo-las pela curva (t).
        const tValues = [0.5, 0.35, 0.65, 0.25, 0.75, 0.15, 0.85];
        const t = tValues[overlap % tValues.length];

        // Cálculo matemático exato da Curva de Bezier para encontrar a posição da Badge
        const getBezierPoint = (t: number, p0: any, p1: any, p2: any, p3: any) => {
            const u = 1 - t;
            return {
                x: (u * u * u * p0.x) + (3 * u * u * t * p1.x) + (3 * u * t * t * p2.x) + (t * t * t * p3.x),
                y: (u * u * u * p0.y) + (3 * u * u * t * p1.y) + (3 * u * t * t * p2.y) + (t * t * t * p3.y)
            };
        };

        const mid = getBezierPoint(t, p0, p1, p2, p3);
        const isHidden = hiddenEdges.value.includes(getEdgeKey(edge));
        return {
            ...edge,
            path: pathStr,
            midpoint: mid,
            customOffsetX: edge.customOffsetX || 0,
            customOffsetY: edge.customOffsetY || 0,
            colorClass: isHidden ? "stroke-slate-300 dark:stroke-slate-700" : edge.colorClass,
            opacity: isHidden ? 0.3 : 1,
            strokeDash: isHidden ? "4,4" : "6,4"
        };
    });
});

const draggingCurveEdge = ref<GraphEdge | null>(null);
let curveDragStartX = 0;
let curveDragStartY = 0;
let curveInitialOffsetX = 0;
let curveInitialOffsetY = 0;

const startDraggingCurve = (e: MouseEvent, edge: GraphEdge) => {
    e.preventDefault();

    const originalEdge = edges.value.find(orig =>
        orig.source === edge.source &&
        orig.target === edge.target &&
        orig.questionTitle === edge.questionTitle
    );

    if (originalEdge) {
        draggingCurveEdge.value = originalEdge;
        curveDragStartX = e.clientX;
        curveDragStartY = e.clientY;

        // Agora lemos os dois eixos
        curveInitialOffsetX = originalEdge.customOffsetX || 0;
        curveInitialOffsetY = originalEdge.customOffsetY || 0;

        window.addEventListener('mousemove', onDraggingCurve);
        window.addEventListener('mouseup', stopDraggingCurve);
    }
};

const onDraggingCurve = (e: MouseEvent) => {
    if (!draggingCurveEdge.value) return;

    // Calcula o desvio 2D em relação ao nível de Zoom atual
    const deltaX = (e.clientX - curveDragStartX) / zoomLevel.value;
    const deltaY = (e.clientY - curveDragStartY) / zoomLevel.value;

    // Aplica a deformação 2D na linha original
    draggingCurveEdge.value.customOffsetX = curveInitialOffsetX + deltaX;
    draggingCurveEdge.value.customOffsetY = curveInitialOffsetY + deltaY;
};

const stopDraggingCurve = () => {
    draggingCurveEdge.value = null;
    window.removeEventListener('mousemove', onDraggingCurve);
    window.removeEventListener('mouseup', stopDraggingCurve);

    saveMapPositions();
};
// Lógica de Arrasto 100% Reativa
const draggingNodeId = ref<number | null>(null);
const dragOffset = ref({ x: 0, y: 0 });

const startDrag = (event: MouseEvent, node: GraphNode) => {
    const target = event.target as HTMLElement;
    if (target.closest('button') || target.closest('a')) return;

    event.preventDefault();
    draggingNodeId.value = node.id;
    // 🔥 AJUSTE MATEMÁTICO: Sincroniza o rato com a escala atual
    dragOffset.value.x = event.clientX - (node.x * zoomLevel.value);
    dragOffset.value.y = event.clientY - (node.y * zoomLevel.value);
};

const isConnecting = ref(false);
const connectionSourceNode = ref<GraphNode | null>(null);
const mousePos = ref({ x: 0, y: 0 });
const potentialTargetId = ref<number | null>(null);

const startConnection = (event: MouseEvent, node: GraphNode) => {
    event.stopPropagation();
    event.preventDefault();
    isConnecting.value = true;
    connectionSourceNode.value = node;
    updateMousePos(event);
};

const updateMousePos = (event: MouseEvent) => {
    if (!mapContainer.value) return;
    const rect = mapContainer.value.getBoundingClientRect();
    mousePos.value = {
        // 🔥 AJUSTE MATEMÁTICO: A posição do rato respeita a escala para desenhar a linha fantasma
        x: (event.clientX - rect.left) / zoomLevel.value,
        y: (event.clientY - rect.top) / zoomLevel.value
    };
};

const onDrag = (event: MouseEvent) => {
    if (draggingNodeId.value !== null) {
        const nodeIndex = nodes.value.findIndex(n => n.id === draggingNodeId.value);
        if (nodeIndex !== -1) {
            // 🔥 AJUSTE MATEMÁTICO: Converte o movimento físico do rato para as coordenadas virtuais
            let newX = (event.clientX - dragOffset.value.x) / zoomLevel.value;
            let newY = (event.clientY - dragOffset.value.y) / zoomLevel.value;

            newX = Math.max(20, newX);
            newY = Math.max(20, newY);

            nodes.value[nodeIndex] = {
                ...nodes.value[nodeIndex],
                x: newX,
                y: newY
            };
        }
    }
    if (isConnecting.value) {
        updateMousePos(event);
    }
};

const endDrag = () => {
    if (isConnecting.value && connectionSourceNode.value && potentialTargetId.value) {
        const sourceId = connectionSourceNode.value.id;
        const targetId = potentialTargetId.value;
        const moduleId = connectionSourceNode.value.moduleId;

        if (sourceId !== targetId) {
            router.visit(route('admin.courses.builder.lessons.edit', {
                course: props.course.id,
                module: moduleId,
                lesson: sourceId
            }) + `?auto_target=${targetId}`);
        }
    }

    if (draggingNodeId.value !== null && !isConnecting.value) {
        saveMapPositions();
    }

    draggingNodeId.value = null;
    isConnecting.value = false;
    connectionSourceNode.value = null;
    potentialTargetId.value = null;
};

const phantomPath = computed(() => {
    if (!isConnecting.value || !connectionSourceNode.value) return '';

    const sx = connectionSourceNode.value.x + 220;
    const sy = connectionSourceNode.value.y + 40;
    const tx = mousePos.value.x;
    const ty = mousePos.value.y;

    const controlOffset = Math.max(Math.abs(tx - sx) / 2.5, 40);
    return `M ${sx} ${sy} C ${sx + controlOffset} ${sy}, ${tx - controlOffset} ${ty}, ${tx} ${ty}`;
});

watch(() => props.course, () => {
    buildGraph();
}, { deep: true, immediate: true });

// --- AÇÕES DE MÓDULO E AULA ---
const openCreateModule = () => {
    editingModuleId.value = null;
    moduleForm.reset();
    isModuleModalOpen.value = true;
};

const openEditModule = (module: any) => {
    editingModuleId.value = module.id;
    moduleForm.title = module.title;
    isModuleModalOpen.value = true;
};

const submitModule = () => {
    if (editingModuleId.value) {
        moduleForm.put(route('admin.courses.builder.modules.update', {
            course: props.course.id, module: editingModuleId.value
        }), { onSuccess: () => isModuleModalOpen.value = false });
    } else {
        moduleForm.post(route('admin.courses.builder.modules.store', props.course.id), {
            onSuccess: () => { moduleForm.reset(); isModuleModalOpen.value = false; }
        });
    }
};

const deleteModule = (module: any) => {
    if (confirm(`Excluir a categoria "${module.title}" e todas os seus vídeos?`)) {
        router.delete(route('admin.courses.builder.modules.destroy', { course: props.course.id, module: module.id }));
    }
};

const openCreateLesson = (moduleId: number) => {
    activeModuleId.value = moduleId;
    editingLessonId.value = null;
    lessonForm.reset();
    isLessonModalOpen.value = true;
};

const openEditLesson = (moduleId: number, lesson: any) => {
    activeModuleId.value = moduleId;
    editingLessonId.value = lesson.id;
    lessonForm.title = lesson.title;
    lessonForm.video_url = lesson.video_url;
    isLessonModalOpen.value = true;
};

const submitLesson = () => {
    if (!activeModuleId.value) return;
    const params = { course: props.course.id, module: activeModuleId.value };

    if (editingLessonId.value) {
        lessonForm.put(route('admin.courses.builder.lessons.update', { ...params, lesson: editingLessonId.value }), {
            onSuccess: () => isLessonModalOpen.value = false
        });
    } else {
        lessonForm.post(route('admin.courses.builder.lessons.store', params), {
            onSuccess: () => { lessonForm.reset(); isLessonModalOpen.value = false; }
        });
    }
};

const deleteLesson = (moduleId: number, lessonId: number) => {
    if (confirm('Excluir esta aula?')) {
        router.delete(route('admin.courses.builder.lessons.destroy', { course: props.course.id, module: moduleId, lesson: lessonId }));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold tracking-tight">Estrutura do projeto</h2>
                    <p class="text-sm text-muted-foreground">{{ course.title }}</p>
                </div>
            </div>
        </template>

        <div class="w-full h-full px-4 pb-20 mx-auto mt-6 space-y-6 max-w-7xl sm:px-6">

            <div
                class="flex items-center justify-between p-4 bg-white border rounded-lg shadow-sm dark:bg-slate-900 dark:border-slate-800">

                <div class="flex items-center gap-4">
                    <div class="flex p-1 border rounded-lg bg-muted">
                        <button @click="viewMode = 'list'" title="Ver como Lista"
                            :class="['px-3 py-1.5 rounded-md flex items-center justify-center transition-all text-sm font-bold', viewMode === 'list' ? 'bg-background shadow-sm text-amber-600' : 'text-muted-foreground hover:text-foreground']">
                            <List class="w-4 h-4 mr-2" /> Lista
                        </button>
                        <button @click="viewMode = 'map'" title="Ver Mapa Visual (Network)"
                            :class="['px-3 py-1.5 rounded-md flex items-center justify-center transition-all text-sm font-bold', viewMode === 'map' ? 'bg-background shadow-sm text-amber-600' : 'text-muted-foreground hover:text-foreground']">
                            <Network class="w-4 h-4 mr-2" /> Mapa Interativo
                        </button>
                    </div>

                    <Button v-if="viewMode === 'map'" @click="resetMapPositions" variant="outline" size="sm"
                        class="h-9">
                        <RefreshCcw class="w-4 h-4 mr-2" /> Organizar Caixas
                    </Button>
                </div>

                <Button @click="openCreateModule">
                    <Plus class="w-4 h-4 mr-2" /> Nova Categoria
                </Button>
            </div>

            <div v-if="viewMode === 'list'" class="w-full mx-auto space-y-6">
                <Accordion type="multiple" class="w-full space-y-4"
                    :default-value="course.modules.map((m: any) => String(m.id))">

                    <AccordionItem v-for="module in course.modules" :key="module.id" :value="String(module.id)"
                        class="px-2 border rounded-lg shadow-sm bg-card text-card-foreground">

                        <div class="flex items-center w-full py-2">
                            <div class="p-2 text-muted-foreground cursor-grab active:cursor-grabbing">
                                <GripVertical class="w-5 h-5" />
                            </div>

                            <AccordionTrigger class="flex-1 px-2 py-2 hover:no-underline">
                                <div class="flex items-center gap-3 text-left">
                                    <span class="text-lg font-bold">{{ module.title }}</span>
                                    <span
                                        class="text-xs text-muted-foreground font-normal bg-muted px-2 py-0.5 rounded-full">
                                        {{ module.lessons.length }} vídeos
                                    </span>
                                </div>
                            </AccordionTrigger>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="mr-2">
                                        <MoreVertical class="w-4 h-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="openEditModule(module)">
                                        <Edit class="w-4 h-4 mr-2" /> Renomear
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="deleteModule(module)"
                                        class="text-red-600 focus:text-red-700 focus:bg-red-50">
                                        <Trash2 class="w-4 h-4 mr-2" /> Excluir Categoria
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <AccordionContent class="px-4 pt-0 pb-4 pl-10">
                            <div class="space-y-2">
                                <div v-for="lesson in module.lessons" :key="lesson.id"
                                    class="flex items-center justify-between p-3 transition-all border border-transparent rounded-md group bg-muted/30 hover:border-border hover:bg-muted/50">

                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <Video class="w-4 h-4 text-blue-500 shrink-0" />
                                        <span class="text-sm font-medium truncate">{{ lesson.title }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="route('admin.courses.builder.lessons.edit', { course: course.id, module: module.id, lesson: lesson.id })">
                                            <Button size="sm" variant="outline"
                                                class="h-8 gap-2 text-xs bg-white dark:bg-slate-950 hover:text-blue-600 hover:border-blue-200">
                                                <PlayCircle class="w-3 h-3" />
                                                Editar Interações
                                            </Button>
                                        </Link>

                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="w-8 h-8">
                                                    <MoreVertical class="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem @click="openEditLesson(module.id, lesson)">
                                                    <Edit class="w-4 h-4 mr-2" /> Editar Título/URL
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="deleteLesson(module.id, lesson.id)"
                                                    class="text-red-600 focus:text-red-700 focus:bg-red-50">
                                                    <Trash2 class="w-4 h-4 mr-2" /> Excluir Vídeo
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <Button variant="ghost" size="sm"
                                    class="w-full border border-dashed border-muted-foreground/30 text-muted-foreground hover:text-primary hover:border-primary/50 hover:bg-primary/5"
                                    @click="openCreateLesson(module.id)">
                                    <Plus class="w-4 h-4 mr-2" /> Adicionar Vídeo nesta categoria
                                </Button>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>

                <div v-if="course.modules.length === 0"
                    class="flex flex-col items-center justify-center py-16 border-2 border-dashed rounded-lg bg-muted/10">
                    <div class="p-4 mb-4 rounded-full bg-muted/50">
                        <Plus class="w-8 h-8 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-semibold">Comece a Categoria</h3>
                    <p class="mb-6 text-sm text-muted-foreground">Crie a categoria para organizar os vídeos.</p>
                    <Button @click="openCreateModule">Criar Categoria</Button>
                </div>
            </div>

            <div v-if="viewMode === 'map'" :class="[
                'transition-all duration-300 ease-in-out flex flex-col',
                isMapFullscreen ? 'fixed inset-0 z-[100] bg-slate-100 dark:bg-slate-900 p-4 space-y-4' : 'space-y-3'
            ]">
                <div
                    class="flex items-center justify-between p-2 bg-white border rounded-lg shadow-sm shrink-0 border-slate-200 dark:bg-slate-900 dark:border-slate-800">

                    <div class="flex items-center">
                        <div v-if="hiddenEdges.length > 0">
                            <Button variant="ghost" size="sm" @click="showAllEdges"
                                class="h-8 text-amber-600 dark:text-amber-400 hover:text-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/40"
                                title="Restaurar linhas ocultas">
                                <Eye class="w-4 h-4 mr-2" />
                                <span class="text-xs font-bold">Mostrar Todas ({{ hiddenEdges.length }})</span>
                            </Button>
                        </div>
                        <div v-else
                            class="flex items-center px-3 text-xs font-bold tracking-wider uppercase text-slate-400">
                            <Network class="w-4 h-4 mr-2 opacity-50" />
                            Painel de Edição
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center h-8 overflow-hidden border rounded-md bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                            <button
                                class="flex items-center justify-center w-8 h-full transition-colors text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
                                @click="zoomOut" title="Reduzir Zoom">
                                <ZoomOut class="w-3.5 h-3.5" />
                            </button>
                            <span
                                class="w-12 text-[11px] font-bold text-center cursor-pointer select-none text-slate-700 dark:text-slate-200 hover:text-amber-600 transition-colors"
                                @click="resetZoom" title="Resetar Zoom">
                                {{ Math.round(zoomLevel * 100) }}%
                            </span>
                            <button
                                class="flex items-center justify-center w-8 h-full transition-colors text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700"
                                @click="zoomIn" title="Aumentar Zoom">
                                <ZoomIn class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <Button variant="secondary" size="sm" class="h-8 shadow-sm"
                            @click="isMapFullscreen = !isMapFullscreen">
                            <Maximize v-if="!isMapFullscreen" class="w-4 h-4 mr-2" />
                            <Minimize v-else class="w-4 h-4 mr-2 text-amber-600" />
                            {{ isMapFullscreen ? 'Sair da Tela Cheia' : 'Tela Cheia' }}
                        </Button>
                    </div>
                </div>
                <div ref="scrollWrapper"
                    class="relative w-full overflow-auto border shadow-inner select-none border-slate-300 dark:border-slate-800 rounded-xl bg-slate-50 dark:bg-slate-950"
                    :class="[
                        isMapFullscreen ? 'flex-1' : 'h-[600px]',
                        isPanning ? 'cursor-grabbing' : 'cursor-grab'
                    ]" @mousedown="startPan" @mousemove="doPan" @mouseup="stopPan" @mouseleave="stopPan">

                    <div ref="mapContainer"
                        class="relative min-w-[3000px] min-h-[2000px] origin-top-left transition-transform duration-75 ease-linear"
                        :style="{ transform: `scale(${zoomLevel})` }" @mousemove="onDrag" @mouseup="endDrag"
                        @mouseleave="endDrag">

                        <svg class="absolute inset-0 z-0 w-full h-full pointer-events-none">
                            <defs>
                                <!-- 🔥 AJUSTE: Setas reduzidas de 10x7 para 7x5 -->
                                <marker id="arrowhead-amber" markerWidth="7" markerHeight="5" refX="6" refY="2.5"
                                    orient="auto">
                                    <polygon points="0 0, 7 2.5, 0 5" class="fill-amber-500" />
                                </marker>
                                <marker id="arrowhead-green" markerWidth="7" markerHeight="5" refX="6" refY="2.5"
                                    orient="auto">
                                    <polygon points="0 0, 7 2.5, 0 5" class="fill-green-500" />
                                </marker>
                                <marker id="arrowhead-blue" markerWidth="7" markerHeight="5" refX="6" refY="2.5"
                                    orient="auto">
                                    <polygon points="0 0, 7 2.5, 0 5" class="fill-blue-500" />
                                </marker>
                                <marker id="arrowhead-purple" markerWidth="7" markerHeight="5" refX="6" refY="2.5"
                                    orient="auto">
                                    <polygon points="0 0, 7 2.5, 0 5" class="fill-purple-500" />
                                </marker>
                                <marker id="arrowhead-red" markerWidth="7" markerHeight="5" refX="6" refY="2.5"
                                    orient="auto">
                                    <polygon points="0 0, 7 2.5, 0 5" class="fill-red-500" />
                                </marker>
                            </defs>
                            <!-- 🔥 AGORA USAMOS activeEdges QUE É COMPUTADO EM TEMPO REAL -->
                            <!-- 🔥 AJUSTE: Grossura da linha (stroke-width) reduzida para 2 para harmonizar com a seta menor -->
                            <path v-for="(edge, i) in activeEdges" :key="i" :d="edge.path || ''"
                                :class="edge.colorClass" :style="{ opacity: edge.opacity }"
                                :stroke-dasharray="edge.strokeDash" stroke-width="2" fill="none"
                                :marker-end="'url(#arrowhead-' + (edge.opacity === 0.3 ? 'gray' : edge.marker) + ')'" />

                            <circle v-for="edge in activeEdges" :key="'handle-' + edge.source" :cx="edge.midpoint?.x"
                                :cy="edge.midpoint?.y" r="8" class="cursor-move fill-amber-500"
                                @mousedown.stop="startDraggingCurve($event, edge)" />
                            <!-- Linha fantasma (quando estamos a arrastar para ligar caixas) -->
                            <path v-if="isConnecting" :d="phantomPath" class="stroke-blue-500" stroke-width="2"
                                fill="none" stroke-dasharray="8,4" />
                        </svg>

                        <template v-for="(edge, i) in activeEdges"
                            :key="'label-' + i + '-' + edge.source + '-' + edge.target">
                            <div v-if="edge.midpoint"
                                class="absolute flex items-center justify-center transition-transform -translate-x-1/2 -translate-y-1/2 pointer-events-auto group"
                                :class="draggingCurveEdge && draggingCurveEdge.source === edge.source && draggingCurveEdge.target === edge.target && draggingCurveEdge.questionTitle === edge.questionTitle ? 'cursor-grabbing scale-105 shadow-xl z-50' : 'cursor-grab z-20'"
                                :style="{ left: edge.midpoint.x + 'px', top: edge.midpoint.y + 'px' }"
                                @mousedown.stop="startDraggingCurve($event, edge)">

                                <span
                                    class="flex items-center px-2 py-0.5 text-[9px] font-bold tracking-wider rounded-full ring-4 ring-slate-50 dark:ring-slate-950 shadow-md transition-all"
                                    :class="edge.badgeClass">
                                    <button type="button" @mousedown.stop @click.stop="toggleEdgeVisibility(edge)"
                                        class="ml-2 p-1.5 -mr-1.5 rounded-full transition-all hover:bg-slate-200 dark:hover:bg-slate-800 hover:opacity-100 cursor-pointer"
                                        :class="hiddenEdges.includes(getEdgeKey(edge)) ? 'opacity-100 text-amber-500' : 'opacity-40'">

                                        <EyeOff v-if="!hiddenEdges.includes(getEdgeKey(edge))" class="w-3.5 h-3.5" />
                                        <Eye v-else class="w-3.5 h-3.5" />
                                    </button>
                                    <span class="shrink-0">{{ edge.time }} • {{ edge.label }}</span>
                                    <span class="mx-1.5 opacity-40 shrink-0">|</span>

                                    <span
                                        class="truncate transition-all duration-300 ease-in-out max-w-[130px] group-hover:max-w-[300px] opacity-80"
                                        :title="edge.questionTitle">
                                        {{ edge.questionTitle }}
                                    </span>
                                </span>
                            </div>
                        </template>

                        <div v-for="node in nodes" :key="'node-' + node.id"
                            :style="{ left: node.x + 'px', top: node.y + 'px' }"
                            class="absolute w-[220px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg hover:shadow-xl cursor-grab active:cursor-grabbing pointer-events-auto flex flex-col overflow-hidden z-10 transition-shadow"
                            @mousedown="startDrag($event, node)"
                            @mouseenter="isConnecting ? potentialTargetId = node.id : null"
                            @mouseleave="potentialTargetId = null" @dragstart.prevent>
                            <div v-if="isConnecting && potentialTargetId === node.id"
                                class="absolute inset-0 rounded-lg pointer-events-none bg-blue-500/10 ring-2 ring-blue-500">
                            </div>
                            <div
                                class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center pointer-events-none">
                                <span class="truncate">{{ node.moduleTitle }}</span>
                                <Video class="w-3 h-3 text-blue-500" />
                            </div>

                            <div class="p-3 pointer-events-none">
                                <p class="text-sm font-bold leading-tight text-slate-800 dark:text-slate-200">{{
                                    node.title }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between px-3 pb-3 pointer-events-auto">
                                <Link
                                    :href="route('admin.courses.builder.lessons.edit', { course: course.id, module: node.moduleId, lesson: node.id })"
                                    class="z-50" @mousedown.stop>
                                    <Button size="sm" variant="secondary"
                                        class="h-6 px-2 text-[10px] gap-1 cursor-pointer">
                                        <Edit class="w-3 h-3" /> Interações
                                    </Button>
                                </Link>

                                <Button size="sm" variant="ghost"
                                    class="z-50 w-6 h-6 p-0 text-red-500 cursor-pointer hover:bg-red-50 dark:hover:bg-red-950/30"
                                    @click.stop="deleteLesson(node.moduleId, node.id)" @mousedown.stop>
                                    <Trash2 class="w-3 h-3" />
                                </Button>
                            </div>

                            <!-- 🔥 NOVA ÂNCORA DE CONEXÃO (Mais visível, animada e com tooltip) -->
                            <div v-if="typeof startConnection !== 'undefined'"
                                class="absolute right-[-14px] top-1/2 -translate-y-1/2 flex items-center justify-center z-50 group/anchor cursor-crosshair"
                                @mousedown.stop="startConnection($event, node)">

                                <!-- Efeito de pulso (Radar) para atrair o olhar -->
                                <div class="absolute w-8 h-8 bg-blue-500 rounded-full opacity-40 animate-ping"></div>

                                <!-- O botão principal (Maior) -->
                                <div
                                    class="relative flex items-center justify-center transition-all duration-200 bg-blue-600 border-2 border-white rounded-full shadow-lg w-7 h-7 group-hover/anchor:bg-blue-500 group-hover/anchor:scale-110 dark:border-slate-800">
                                    <Plus class="w-4 h-4 text-white" />
                                </div>

                                <!-- Tooltip explicativa ao passar o rato -->
                                <div
                                    class="absolute right-[-10px] translate-x-full px-2 py-1 text-[10px] font-bold text-white bg-slate-800 rounded opacity-0 pointer-events-none group-hover/anchor:opacity-100 transition-opacity whitespace-nowrap shadow-md border border-slate-700 z-50">
                                    Puxar Conexão
                                </div>
                            </div>
                        </div>

                        <div v-if="nodes.length === 0"
                            class="absolute inset-0 z-0 flex items-center justify-center pointer-events-none">
                            <p class="font-medium text-slate-400">Adicione categorias e vídeos para visualizar a rede.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center gap-4 px-5 py-3 text-xs bg-white border rounded-lg shadow-sm shrink-0 border-slate-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300">
                    <span class="font-bold text-[10px] text-slate-500 uppercase tracking-wider mr-2">Legenda de
                        Conexões:</span>

                    <!-- 🔥 ATUALIZADO: Legendas Consistentes (Verde/Azul/Vermelho) -->
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-1.5 rounded-full bg-green-500"></div>
                        <span>Acerto / Correto</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-1.5 rounded-full bg-blue-500"></div>
                        <span>Segue / Info / Automático</span>
                    </div>

                    <div class="flex items-center gap-1.5 border-l border-slate-300 dark:border-slate-700 pl-4 ml-2">
                        <div class="w-4 h-1.5 rounded-full bg-red-500"></div>
                        <span class="font-medium text-red-600 dark:text-red-400">Erro / Incorreto</span>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isModuleModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingModuleId ? 'Renomear Categoria' : 'Nova Categoria' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitModule" class="py-4 space-y-4">
                    <div class="space-y-2">
                        <Label>Título da Categoria</Label>
                        <Input v-model="moduleForm.title" placeholder="Ex: NR1" autoFocus />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isModuleModalOpen = false">Cancelar</Button>
                        <Button type="submit">{{ editingModuleId ? 'Salvar' : 'Criar' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isLessonModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingLessonId ? 'Editar Vídeo' : 'Novo Vídeo' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitLesson" class="py-4 space-y-4">
                    <div class="space-y-2">
                        <Label>Título do Vídeo</Label>
                        <Input v-model="lessonForm.title" placeholder="Ex: Entendendo as normas" />
                    </div>
                    <div class="space-y-2">
                        <Label>URL do Vídeo (MP4 Direto)</Label>
                        <Input v-model="lessonForm.video_url" placeholder="https://..." />
                        <p class="text-[10px] text-muted-foreground">
                            Link externo em Panda, Vimeo ou Youtube.
                        </p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isLessonModalOpen = false">Cancelar</Button>
                        <Button type="submit">{{ editingLessonId ? 'Salvar Alterações' : 'Adicionar Vídeo' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AuthenticatedLayout>
</template>
