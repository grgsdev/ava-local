<script setup lang="ts">
import { ref, watch } from 'vue';
import { VueFlow, useVueFlow, Node, Edge } from '@vue-flow/core';
import { Panel } from '@vue-flow/additional-components';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Project } from '@/types';

const props = defineProps<{
    project: Project;
}>();

const emit = defineEmits(['editVideo']);
const videos = props.project.videos ?? [];
// O hook 'useVueFlow' dá-nos controlo sobre a instância do fluxograma
const { onNodeClick, fitView } = useVueFlow();

const nodes = ref<Node[]>([]);
const edges = ref<Edge[]>([]);

// Converte os dados do projeto para o formato do Vue Flow
const mapProjectToFlow = () => {
    // Mapeia cada vídeo para um "Nó" no fluxograma
    nodes.value = videos.map((video, index) => ({
        id: video.id.toString(),
        label: video.title || `Vídeo #${video.id}`,
        position: video.position || { x: (index % 4) * 250, y: Math.floor(index / 4) * 150 },
    }));

    // Mapeia interações "pular para vídeo" para "Arestas" (conexões)
    const newEdges: Edge[] = [];
    videos.forEach(video => {
        video.interactions.forEach(interaction => {
            if (interaction.data.answers) { // Garante que é uma interação de escolha
                (interaction.data.answers as any[]).forEach(answer => {
                    if (answer.action === 'jump_to_video' && answer.target) {
                        newEdges.push({
                            id: `e-${interaction.id}-${answer.target}`,
                            source: video.id.toString(),
                            target: answer.target.toString(),
                            label: answer.text.substring(0, 20) + '...',
                            animated: true,
                        });
                    }
                });
            }
        });
    });
    edges.value = newEdges;
};

// Observa por mudanças no projeto (ex: após salvar uma interação) e atualiza o fluxograma
watch(() => props.project, mapProjectToFlow, { deep: true, immediate: true });

// Ação ao clicar num nó (vídeo) -> emite um evento para o pai (Edit.vue)
onNodeClick((event) => {
    const videoId = event.node.id;
    const video = (props.project.videos ?? []).find(v => v.id.toString() === videoId);
    if (video) {
        emit('editVideo', video);
    }
});

// Adiciona um novo vídeo ao projeto
const addVideo = () => {
    const title = prompt("Digite o título do novo vídeo:", `Novo Vídeo ${(props.project.videos ?? []).length + 1}`);
    if (title) {
        router.post(route('projects.videos.store', props.project.id), {
            url: 'https://youtube.com/placeholder', // URL provisória
            title: title,
        }, {
            preserveScroll: true,
        });
    }
};
</script>

<style>
/* Importa os estilos essenciais para o Vue Flow */
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
</style>

<template>
    <div class="w-full h-[600px] border rounded-lg bg-white shadow-sm">
        <VueFlow v-model:nodes="nodes" v-model:edges="edges" :fit-view-on-init="true">
            <Panel position="topLeft">
                <div class="flex gap-2 p-2">
                    <Button @click="addVideo" size="sm">Adicionar Vídeo</Button>
                    <Button @click="fitView" size="sm" variant="outline">Centralizar</Button>
                </div>
            </Panel>
        </VueFlow>
    </div>
</template>
