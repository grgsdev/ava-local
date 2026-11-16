<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import type { DeletedRecord } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

// Props
const props = defineProps<{
    record: DeletedRecord;
}>();

// Transforma o nome da chave para um formato legível
const formatKey = (key: string) => {
    return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>

    <Head :title="`Backup do Registro #${record.subject_id}`" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Dados do Registro Excluído</h1>
                    <p class="text-muted-foreground">Visualizando o backup do registro #{{ record.subject_id }}</p>
                </div>
                <Button as-child variant="outline">
                    <Link :href="route('system.deleted-records.index')">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Voltar para a Lixeira
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Informações do Backup</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 text-sm md:grid-cols-3">
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Tipo do Registro</p>
                        <p class="font-mono text-xs">{{ record.subject_type.split('\\').pop() }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">ID Original</p>
                        <p>{{ record.subject_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Excluído por</p>
                        <p>{{ record.causer_name || 'Sistema' }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-medium text-muted-foreground">Data da Exclusão</p>
                        <p>{{ record.deleted_at.short_with_time }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Dados Salvos (subject_data)</CardTitle>
                    <CardDescription>
                        Estes foram os dados do registro no momento em que foi excluído.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div v-for="(value, key) in record.subject_data" :key="key"
                        class="grid grid-cols-3 gap-4 p-2 border-b last:border-b-0 hover:bg-accent">
                        <dt class="font-semibold text-muted-foreground">{{ formatKey(key) }}</dt>
                        <dd class="col-span-2 font-mono text-xs">{{ value === null ? 'null' : value }}</dd>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AuthenticatedLayout>
</template>
