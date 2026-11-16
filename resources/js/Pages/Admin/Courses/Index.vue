<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PageProps } from '@/types';
import Pagination from '@/Components/Pagination.vue';
import SortableHeader from '@/Components/Admin/SortableHeader.vue'; // <--- Padrão de Sort
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Plus, Edit, Trash, Layers, Search, Send, FileText
} from 'lucide-vue-next';

// --- IMPORTS DO ALERT DIALOG ---
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const props = defineProps<{
    courses: { data: any[], links: any[], meta?: any };
    filters?: { filter?: { title?: string } };
}>();
const page = usePage<PageProps>();

const canDeleteCourse = computed(() => {
    if (page.props.auth.user.is_master ?? false) {
        return true;
    }

    const companyCan = (page.props.auth as typeof page.props.auth & {
        company_can?: string[];
    }).company_can;

    return companyCan?.includes('delete projects') ?? false;
});

const canSubmitCourse = computed(() => {
    const companyCan = (page.props.auth as typeof page.props.auth & {
        company_can?: string[];
    }).company_can;

    return companyCan?.includes('edit projects') ?? false;
});

const canReviewPendingCourse = computed(() => {
    if (page.props.auth.user.is_master ?? false) {
        return true;
    }

    const companyCan = (page.props.auth as typeof page.props.auth & {
        company_can?: string[];
    }).company_can;

    return companyCan?.includes('approve projects') ?? false;
});

const canSubmit = (course: any) => canSubmitCourse.value
    && (!course.project || ['Em Edição', 'Reprovado'].includes(course.project.status));

const submitCourse = (course: any) => {
    router.post(route('admin.courses.submit', course.id));
};

const workflowStatus = (course: any) => course.project?.status;
const rejectionFeedback = (course: any) => course.project?.rejection_feedback;
const canEditCourse = (course: any) => workflowStatus(course) !== 'Aguardando aprovação'
    || canReviewPendingCourse.value;

const publication = (course: any) => course.project?.publication;
const publish = (course: any) => router.post(route('projects.publish', course.project.id), {}, { preserveScroll: true });
const revoke = (course: any) => router.post(route('projects.revoke', course.project.id), {}, { preserveScroll: true });
const copiedCourseId = ref<number | null>(null);
const copyPublicLink = async (course: any) => {
    const publicUrl = publication(course)?.public_url;

    if (!publicUrl || !navigator.clipboard) return;

    await navigator.clipboard.writeText(publicUrl);
    copiedCourseId.value = course.id;
};

// --- BUSCA (Debounce) ---
const search = ref(props.filters?.filter?.title || '');
let timeout: any = null;

watch(search, (val) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.courses.index'), {
            'filter[title]': val
        }, { preserveState: true, replace: true });
    }, 500);
});

// --- LÓGICA DE EXCLUSÃO (ALERT DIALOG) ---
const isDeleteOpen = ref(false);
const courseToDelete = ref<any>(null);
const isDeleting = ref(false);

const openDelete = (course: any) => {
    courseToDelete.value = course;
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    if (!courseToDelete.value) return;
    isDeleting.value = true;

    router.delete(route('admin.courses.destroy', courseToDelete.value.id), {
        onFinish: () => {
            isDeleting.value = false;
            isDeleteOpen.value = false;
            courseToDelete.value = null;
        }
    });
};

</script>

<template>
    <Head title="Projetos" />

    <AuthenticatedLayout>
        <div class="space-y-4">
            <Card class="overflow-hidden">
                <CardHeader class="gap-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <div>
                                <CardTitle>Projetos</CardTitle>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Crie, organize e envie os projetos da empresa para aprovação.
                                </p>
                            </div>
                        </div>
                        <Button as-child class="shrink-0">
                            <Link :href="route('admin.courses.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Novo Projeto
                            </Link>
                        </Button>
                    </div>

                    <div class="relative w-full sm:max-w-sm">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Buscar projetos..." class="pl-9" />
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                <SortableHeader label="Título" name="title" />
                            </TableHead>

                            <!-- <TableHead>
                                <SortableHeader label="Preço" name="price" />
                            </TableHead> -->

                            <TableHead>Conteúdo</TableHead>

                            <TableHead>Status</TableHead>

                            <TableHead>Publicação</TableHead>

                            <TableHead class="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="course in courses.data" :key="course.id">
                            <TableCell class="font-medium">
                                <div class="min-w-48">
                                    {{ course.title }}
                                    <div class="max-w-[300px] truncate text-xs text-muted-foreground">
                                        {{ course.headline }}
                                    </div>
                                    <p v-if="workflowStatus(course) === 'Reprovado' && rejectionFeedback(course)"
                                        class="mt-2 max-w-md text-xs text-destructive">
                                        Ajuste solicitado: {{ rejectionFeedback(course) }}
                                    </p>
                                </div>
                            </TableCell>

                            <!-- <TableCell>
                                {{ formatMoney(course.price) }}
                            </TableCell> -->

                            <TableCell>
                                <div class="flex flex-wrap gap-2">
                                    <Badge variant="secondary">{{ course.modules_count }} Módulos</Badge>
                                    <Badge variant="outline">{{ course.lessons_count }} Vídeos</Badge>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="flex flex-wrap gap-2">
                                    <Badge :variant="course.is_published ? 'default' : 'secondary'"
                                        :class="course.is_published ? 'bg-green-600 hover:bg-green-700' : ''">
                                        {{ course.is_published ? 'Publicado' : 'Rascunho' }}
                                    </Badge>
                                    <Badge v-if="workflowStatus(course)" variant="secondary"
                                        :class="workflowStatus(course) === 'Reprovado'
                                            ? 'bg-red-100 text-red-800 hover:bg-red-100'
                                            : workflowStatus(course) === 'Aprovado'
                                                ? 'bg-green-100 text-green-800 hover:bg-green-100'
                                                : 'bg-amber-100 text-amber-800 hover:bg-amber-100'">
                                        {{ workflowStatus(course) }}
                                    </Badge>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div v-if="publication(course)" class="flex min-w-52 flex-col gap-2">
                                    <span class="text-sm font-medium">{{ publication(course).state }}</span>
                                    <div v-if="publication(course).public_url" class="flex items-center gap-2">
                                        <a :href="publication(course).public_url" target="_blank" rel="noopener"
                                            class="truncate text-xs text-primary underline">
                                            {{ publication(course).public_url }}
                                        </a>
                                        <Button size="sm" variant="outline" @click="copyPublicLink(course)">
                                            {{ copiedCourseId === course.id ? 'Copiado' : 'Copiar link' }}
                                        </Button>
                                    </div>
                                </div>
                            </TableCell>

                            <TableCell>
                                <div class="flex min-w-max flex-wrap justify-end gap-2">
                                    <Button v-if="canEditCourse(course)" as-child size="sm" variant="secondary">
                                        <Link :href="route('admin.courses.builder.index', course.id)">
                                            <Layers class="mr-2 h-4 w-4" />
                                            Builder
                                        </Link>
                                    </Button>
                                    <Button v-if="canEditCourse(course)" as-child size="icon" variant="ghost">
                                        <Link :href="route('admin.courses.edit', course.id)" aria-label="Editar projeto">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button v-if="canSubmit(course)" size="sm" variant="outline" @click="submitCourse(course)">
                                        <Send class="mr-2 h-4 w-4" />
                                        Enviar para aprovação
                                    </Button>
                                    <Button v-if="publication(course)?.can_publish && !publication(course).active && workflowStatus(course) === 'Aprovado'"
                                        size="sm" @click="publish(course)">
                                        Publicar
                                    </Button>
                                    <Button v-if="publication(course)?.can_publish && publication(course).republish_ready"
                                        size="sm" @click="publish(course)">
                                        Republicar
                                    </Button>
                                    <Button v-if="publication(course)?.can_revoke && publication(course).active"
                                        variant="destructive" size="sm" @click="revoke(course)">
                                        Revogar
                                    </Button>
                                    <Button v-if="canDeleteCourse" size="icon" variant="ghost"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        aria-label="Excluir projeto" @click="openDelete(course)">
                                        <Trash class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="courses.data.length === 0">
                            <TableCell colspan="5" class="py-12 text-center text-muted-foreground">
                                Nenhum projeto encontrado. Crie um novo projeto para começar.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                    </div>
                </CardContent>
            </Card>

            <Pagination :links="courses.links || courses.meta.links" />
        </div>

        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Excluir projeto?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Esta ação não pode ser desfeita. O projeto
                        <span class="font-bold text-foreground">{{ courseToDelete?.title }}</span>
                        e todas os vídeos, módulos e interações serão apagados permanentemente.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDelete"
                        class="text-white bg-red-600 hover:bg-red-700 focus:ring-red-600" :disabled="isDeleting">
                        {{ isDeleting ? 'Excluindo...' : 'Sim, excluir' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AuthenticatedLayout>
</template>
