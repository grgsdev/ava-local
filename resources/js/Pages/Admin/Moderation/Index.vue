<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { ChevronUp, ChevronDown } from "lucide-vue-next";
import Pagination from "@/Components/Pagination.vue";

// Nosso Composable
import { useFilters } from "@/Composables/useFilters.js";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    moderationCases: Object,
    queryParams: Object,
});

const initialFilters = {
    "comment.user.name": props.queryParams?.filter?.["comment.user.name"] ?? "",
    status: props.queryParams?.filter?.status ?? "pending", // Padrão para 'pendente'
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort } = useFilters(
    props,
    initialFilters,
    "admin.moderation.index"
);

const isSorted = (field) => {
    const asc = props.queryParams?.sort === field;
    const desc = props.queryParams?.sort === `-${field}`;
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// Helper para extrair o título do post/produto
const getCommentableTitle = (commentable) => {
    if (!commentable) return "Conteúdo removido";
    return commentable.title || commentable.name || `Item #${commentable.id}`;
};

// Helper para dar cor ao status
const statusVariant = (status) => {
    switch (status) {
        case "pending":
            return "destructive";
        case "approved":
            return "success";
        default:
            return "secondary";
    }
};
</script>

<template>
    <Head title="Fila de Moderação" />

    <IndexPageLayout :data="moderationCases">
        <template #title>Fila de Moderação</template>

        <template #description>
            Comentários sinalizados que requerem a sua atenção.
        </template>

        <template #filters>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <Input
                    type="text"
                    v-model="filters['comment.user.name']"
                    placeholder="Buscar por autor do comentário..."
                    class="md:col-span-2"
                />
                <Select v-model="filters.status">
                    <SelectTrigger>
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="pending">Pendente</SelectItem>
                        <SelectItem value="approved">Aprovado</SelectItem>
                        <SelectItem value="comment_deleted">Apagado</SelectItem>
                    </SelectContent>
                </Select>
                <Button @click="clearFilters" variant="ghost"
                    >Limpar Filtros</Button
                >
            </div>
        </template>

        <template #table-header>
            <TableRow>
                <TableHead>Autor</TableHead>
                <TableHead>Comentário</TableHead>
                <TableHead>No Conteúdo</TableHead>
                <TableHead
                    ><button
                        @click="sort('created_at')"
                        class="flex items-center gap-2 font-semibold"
                    >
                        Data
                    </button></TableHead
                >
                <TableHead>Status</TableHead>
                <TableHead>Moderador</TableHead>
                <TableHead class="text-right">Ações</TableHead>
            </TableRow>
        </template>

        <template #table-body>
            <TableRow v-if="moderationCases.data.length === 0">
                <TableCell colspan="7" class="text-center text-gray-500">
                    Nenhum caso encontrado para os filtros selecionados.
                </TableCell>
            </TableRow>
            <TableRow
                v-for="caseItem in moderationCases.data"
                :key="caseItem.id"
            >
                <TableCell class="font-medium">{{
                    caseItem.comment?.user?.name || "Usuário removido"
                }}</TableCell>
                <TableCell class="max-w-md truncate">{{
                    caseItem.comment?.body
                }}</TableCell>
                <TableCell>
                    <Link
                        v-if="caseItem.comment?.commentable"
                        :href="
                            route(
                                'posts.show',
                                caseItem.comment.commentable.slug
                            )
                        "
                        target="_blank"
                        class="text-primary hover:underline"
                    >
                        {{ getCommentableTitle(caseItem.comment.commentable) }}
                    </Link>
                    <span v-else class="text-muted-foreground">N/A</span>
                </TableCell>
                <TableCell>{{
                    new Date(caseItem.created_at).toLocaleDateString("pt-BR")
                }}</TableCell>
                <TableCell>
                    <Badge :variant="statusVariant(caseItem.status)">
                        {{ caseItem.status }}
                    </Badge>
                </TableCell>
                <TableCell>{{ caseItem.moderator?.name || "N/A" }}</TableCell>
                <TableCell class="text-right">
                    <Link :href="route('admin.moderation.show', caseItem.id)">
                        <Button variant="outline" size="sm">Revisar</Button>
                    </Link>
                </TableCell>
            </TableRow>
        </template>
    </IndexPageLayout>
</template>
