<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import type { LogFile } from '@/types';
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { MoreHorizontal, Trash2 } from "lucide-vue-next";
import { Badge } from "@/components/ui/badge";

const props = defineProps<{
    logFiles: LogFile[];
}>();

// Função para colorir os emblemas de nível
const getLevelColor = (level: string) => {
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

const formatBytes = (bytes: number, decimals = 2) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

// Lógica de exclusão
const isDeleteDialogOpen = ref(false);
const logToDelete = ref<LogFile | null>(null);

const confirmDelete = (log: LogFile) => {
    logToDelete.value = log;
    isDeleteDialogOpen.value = true;
};

const deleteLog = () => {
    if (logToDelete.value) {
        router.delete(route("system.logs.destroy", logToDelete.value.date), {
            preserveScroll: true,
            onFinish: () => {
                isDeleteDialogOpen.value = false;
                logToDelete.value = null;
            }
        });
    }
};
</script>

<template>

    <Head title="Logs do Sistema" />

    <AuthenticatedLayout>
        <IndexPageLayout>
            <template #title>Logs do Sistema</template>
            <template #description>
                Visualize e gerencie os ficheiros de log diários gerados pelo Laravel.
            </template>

            <template #table-header>
                <TableRow>
                    <TableHead>Data</TableHead>
                    <TableHead>Tamanho do Ficheiro</TableHead>
                    <TableHead>Resumo de Níveis</TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </template>

            <template #table-body>
                <TableRow v-for="log in logFiles" :key="log.date">
                    <TableCell class="font-medium">
                        <Link :href="route('system.logs.show', log.date)" class="text-primary hover:underline">
                        {{ log.date }}
                        </Link>
                    </TableCell>
                    <TableCell>{{ formatBytes(log.size) }}</TableCell>
                    <TableCell>
                        <div class="flex flex-wrap items-center gap-1">
                            <Badge v-for="(count, level) in log.levels" :key="level" :class="getLevelColor(level)"
                                class="capitalize">
                                {{ level }}: {{ count }}
                            </Badge>
                        </div>
                    </TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" class="w-8 h-8 p-0">
                                    <MoreHorizontal class="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @select.prevent="confirmDelete(log)"
                                    class="text-red-600 focus:text-red-600">
                                    <Trash2 class="w-4 h-4 mr-2" />
                                    Excluir
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                </TableRow>
                <TableRow v-if="logFiles.length === 0">
                    <TableCell colspan="4" class="py-10 text-center text-muted-foreground">
                        Nenhum ficheiro de log encontrado.
                    </TableCell>
                </TableRow>
            </template>
        </IndexPageLayout>

        <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Você tem a certeza?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. O ficheiro de log de
                        <span v-if="logToDelete" class="font-semibold">{{ logToDelete.date }}</span>
                        será apagado permanentemente.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="deleteLog" class="bg-destructive hover:bg-destructive/90">
                        Apagar
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
