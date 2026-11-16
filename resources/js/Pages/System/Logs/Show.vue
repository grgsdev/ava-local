<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import type { LogEntry } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { computed, ref } from "vue";

const props = defineProps<{
    log: {
        date: string;
        content: LogEntry[];
    };
}>();

// --- LÓGICA DE FILTROS ADICIONADA ---
const selectedLevels = ref<string[]>([]);
const searchQuery = ref('');

// Calcula um resumo com a contagem de cada nível de log
const logLevelsSummary = computed(() => {
    const summary: Record<string, number> = {};
    props.log.content.forEach(entry => {
        summary[entry.level] = (summary[entry.level] || 0) + 1;
    });
    // Ordena os níveis para uma exibição consistente
    const sortedSummary: Record<string, number> = {};
    Object.keys(summary).sort().forEach(key => {
        sortedSummary[key] = summary[key];
    });
    return sortedSummary;
});

// Filtra o conteúdo do log com base nos níveis selecionados e na busca por texto
const filteredContent = computed(() => {
    const levels = selectedLevels.value;
    const search = searchQuery.value.toLowerCase();

    // Se não houver filtros, retorna tudo
    if (levels.length === 0 && !search) {
        return props.log.content;
    }

    return props.log.content.filter(entry => {
        const levelMatch = levels.length === 0 || levels.includes(entry.level);
        const searchMatch = !search || entry.message.toLowerCase().includes(search);
        return levelMatch && searchMatch;
    });
});

// Função para adicionar ou remover um nível do filtro
const toggleLevelFilter = (level: string) => {
    const index = selectedLevels.value.indexOf(level);
    if (index > -1) {
        selectedLevels.value.splice(index, 1);
    } else {
        selectedLevels.value.push(level);
    }
};


// --- FUNÇÕES DE ESTILO (EXISTENTES) ---
const getLevelColor = (level: LogEntry['level']) => {
    const colors: Record<string, string> = {
        emergency: 'bg-red-700',
        alert: 'bg-red-600',
        critical: 'bg-red-500',
        error: 'bg-red-400 text-white',
        warning: 'bg-yellow-400 text-black',
        notice: 'bg-blue-400 text-white',
        info: 'bg-green-400 text-white',
        debug: 'bg-gray-400 text-white',
    };
    return colors[level] || 'bg-gray-500 text-white';
};
</script>

<template>

    <Head :title="`Log de ${log.date}`" />

    <AuthenticatedLayout>
        <div class="mx-auto space-y-6 max-w-7xl">
            <div>
                <Link :href="route('system.logs.index')" class="text-sm text-primary hover:underline">
                    &larr; Voltar para a lista de logs
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">Logs do Sistema - {{ log.date }}</h1>
                <p class="text-muted-foreground">
                    Exibindo {{ filteredContent.length }} de {{ log.content.length }} entradas encontradas no
                    arquivo.
                </p>
            </div>

            <!-- PAINEL DE FILTROS -->
            <Card>
                <CardContent class="pt-6 space-y-4">
                    <div class="space-y-1.5">
                        <Label for="search">Buscar no conteúdo</Label>
                        <Input id="search" v-model="searchQuery" placeholder="Digite para filtrar as mensagens..." />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Filtrar por Nível</Label>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button v-for="(count, level) in logLevelsSummary" :key="level"
                                :variant="selectedLevels.includes(level) ? 'default' : 'outline'"
                                @click="toggleLevelFilter(level)"
                                class="flex items-center gap-2 capitalize transition-all duration-200">
                                <span :class="getLevelColor(level as LogEntry['level'])"
                                    class="w-3 h-3 rounded-full"></span>
                                {{ level }}
                                <Badge variant="secondary" class="rounded-full">{{ count }}</Badge>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>


            <!-- LISTA DE LOGS -->
            <div class="space-y-4 font-mono text-xs">
                <div v-if="filteredContent.length === 0" class="py-10 text-center text-muted-foreground">
                    Nenhuma entrada de log corresponde aos seus filtros.
                </div>
                <details v-for="(entry, index) in filteredContent" :key="index" class="p-4 border rounded-lg bg-card">
                    <summary class="flex items-center gap-4 cursor-pointer">
                        <span :class="getLevelColor(entry.level)" class="px-2 py-1 text-white rounded">
                            {{ entry.level.toUpperCase() }}
                        </span>
                        <span class="text-muted-foreground">{{ entry.timestamp }}</span>
                        <span class="flex-1 font-semibold truncate">{{ entry.message.split('\n')[0] }}</span>
                    </summary>
                    <div class="pt-4 mt-4 whitespace-pre-wrap border-t text-muted-foreground">
                        {{ entry.message }}
                    </div>
                </details>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
