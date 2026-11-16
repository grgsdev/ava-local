<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import type { CommandLog } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { ArrowLeft } from "lucide-vue-next";

// Props
const props = defineProps<{
    command: CommandLog;
}>();

// Função de Apoio para UI
const getStatusInfo = (status: boolean) => {
    if (status) {
        return { text: 'Sucesso', variant: 'default' as const };
    }

    return { text: 'Falhou', variant: 'secondary' as const };
};
</script>

<template>

    <Head :title="`Detalhes do Log: ${command.command}`" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Detalhes do Log de Comando</h1>
                    <p class="text-muted-foreground">Visualizando o log #{{ command.id }}</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="route('system.command-log.index')">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Voltar para a Lista
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Informações Gerais</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 text-sm md:grid-cols-3">
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Comando</p>
                        <p class="font-mono">{{ command.command }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Status</p>
                        <Badge :variant="getStatusInfo(command.status).variant">
                            {{ getStatusInfo(command.status).text }}
                        </Badge>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Data da Execução</p>
                        <p>{{ command.executed_at.short_with_time }} ({{ command.executed_at.human }})</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Saída do Console (Output)</CardTitle>
                    <CardDescription>
                        Este é o resultado completo retornado pelo comando durante a execução.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <pre
                        class="p-4 overflow-x-auto text-sm text-white bg-gray-900 rounded-md font-mono max-h-[60vh]"><code>{{ command.output || 'Nenhuma saída registrada.' }}</code></pre>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
