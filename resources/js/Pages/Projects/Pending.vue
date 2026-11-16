<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ClipboardCheck, ExternalLink, FileText } from 'lucide-vue-next';
import { AlertDialog, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{ projects: { data: Array<{ id: number; title: string; status: string; course?: { id: number; title: string } | null; created_at?: { short_with_time?: string } }> } }>();
const rejectingProject = ref<{ id: number; title: string } | null>(null);
const rejectionForm = useForm({ feedback: '' });

const approve = (project: { id: number }) => router.post(route('projects.approve', project.id));
const openReject = (project: { id: number; title: string }) => {
    rejectingProject.value = project;
    rejectionForm.reset();
    rejectionForm.clearErrors();
};
const reject = () => {
    if (!rejectingProject.value) return;

    rejectionForm.post(route('projects.reject', rejectingProject.value.id), {
        onSuccess: () => {
            rejectingProject.value = null;
            rejectionForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Projetos pendentes" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl">
            <Card class="overflow-hidden">
                <CardHeader>
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                            <ClipboardCheck class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <div>
                            <CardTitle>Fila de aprovação</CardTitle>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Projetos da empresa aguardando revisão.
                            </p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Projeto</TableHead>
                                    <TableHead>Course associado</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Criado em</TableHead>
                                    <TableHead class="text-right">Ações</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="project in projects.data" :key="project.id">
                                    <TableCell class="font-medium">{{ project.title }}</TableCell>
                                    <TableCell>
                                        <div v-if="project.course" class="flex items-center gap-2">
                                            <FileText class="h-4 w-4 text-muted-foreground" />
                                            <Link class="font-medium hover:underline" :href="route('admin.courses.edit', project.course.id)">
                                                {{ project.course.title }}
                                            </Link>
                                        </div>
                                        <Badge v-else variant="outline">Projeto legado</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary" class="bg-amber-100 text-amber-800 hover:bg-amber-100">
                                            {{ project.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="whitespace-nowrap text-muted-foreground">
                                        {{ project.created_at?.short_with_time }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex min-w-max flex-wrap justify-end gap-2">
                                            <Button v-if="project.course" as-child size="sm">
                                                <Link :href="route('admin.courses.edit', project.course.id)">
                                                    Revisar Course
                                                    <ExternalLink class="ml-2 h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button v-else as-child size="sm" variant="outline">
                                                <Link :href="route('projects.edit', project.id)">Abrir projeto</Link>
                                            </Button>
                                            <Button size="sm" variant="outline" @click="approve(project)">Aprovar</Button>
                                            <Button size="sm" variant="destructive" @click="openReject(project)">Rejeitar</Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="projects.data.length === 0">
                                    <TableCell colspan="5" class="py-12 text-center text-muted-foreground">
                                        Nenhum projeto aguardando aprovação.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
        <AlertDialog :open="Boolean(rejectingProject)" @update:open="(open) => !open && (rejectingProject = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Reprovar projeto?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Informe a justificativa para que o Editor possa corrigir e reenviar o mesmo projeto.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <Textarea v-model="rejectionForm.feedback" placeholder="Descreva os ajustes necessários." />
                <p v-if="rejectionForm.errors.feedback" class="text-sm text-destructive">{{ rejectionForm.errors.feedback }}</p>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="rejectingProject = null">Cancelar</AlertDialogCancel>
                    <Button type="button" :disabled="rejectionForm.processing" @click="reject">
                        Confirmar reprovação
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AuthenticatedLayout>
</template>
