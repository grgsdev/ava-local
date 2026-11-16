<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardContent, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip';
import { PencilIcon, DownloadIcon, CheckIcon } from 'lucide-vue-next';

const props = defineProps<{
    model: any; // A estrutura do JSON é dinâmica e complexa para uma tipagem estrita aqui
}>();

const formatRelationType = (type: string) => {
    const types: Record<string, string> = {
        'belongsTo': 'Belongs To',
        'hasOne': 'Has One',
        'hasMany': 'Has Many',
        'belongsToMany': 'Belongs To Many'
    };
    return types[type] || type;
};

const formatDateTime = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('pt-BR');
};
</script>

<template>

    <Head :title="`Detalhes: ${model.name}`" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ model.name }}</h1>
                    <p class="text-muted-foreground">Visualizando os detalhes do blueprint do modelo.</p>
                </div>
                <div class="flex space-x-2">
                    <Button as-child variant="outline">
                        <a :href="route('system.generator.export.pdf', model.name)">
                            <DownloadIcon class="w-4 h-4 mr-2" />
                            Exportar PDF
                        </a>
                    </Button>
                    <Button as-child variant="outline">
                        <Link :href="route('system.generator.edit', model.name)">
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Editar
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Configuração do Modelo</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Features</Label>
                            <ul class="space-y-2">
                                <li v-if="model.softDeletes" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Soft Deletes</span>
                                </li>
                                <li v-if="model.timestamps" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Timestamps</span>
                                </li>
                                <li v-if="model.useIsActive" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Status Ativo/Inativo</span>
                                </li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <Label>Integrações</Label>
                            <ul class="space-y-2">
                                <li v-if="model.useScribe" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Documentação Scribe</span>
                                </li>
                                <li v-if="model.logsActivity" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Log de Atividades</span>
                                </li>
                                <li v-if="model.clearsResponseCache" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Limpeza de Cache</span>
                                </li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <Label>Segurança</Label>
                            <ul class="space-y-2">
                                <li v-if="model.authorize" class="flex items-center space-x-2">
                                    <CheckIcon class="w-4 h-4 text-green-500" /><span>Autorização</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Atributos ({{ model.attributes.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Validação</TableHead>
                                <TableHead>Opções</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(attr, index) in model.attributes" :key="index">
                                <TableCell class="font-medium">{{ attr.name }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ attr.type }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-if="attr.required" variant="secondary">Required</Badge>
                                        <Badge v-if="attr.nullable" variant="secondary">Nullable</Badge>
                                        <Badge v-if="attr.unique" variant="secondary">Unique</Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-if="attr.sortAble" variant="outline">Sortable</Badge>
                                        <Badge v-if="attr.filterAble" variant="outline">Filterable</Badge>
                                        <Badge v-if="attr.searchAble" variant="outline">Searchable</Badge>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Card v-if="model.relations && model.relations.length > 0">
                <CardHeader>
                    <CardTitle class="text-base">Relações ({{ model.relations.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Modelo Relacionado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(rel, index) in model.relations" :key="index">
                                <TableCell class="font-medium">{{ rel.name }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ formatRelationType(rel.type) }}</Badge>
                                </TableCell>
                                <TableCell>{{ rel.related }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
