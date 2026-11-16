<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import IndexPageLayout from "@/Layouts/IndexPageLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

// Componentes de UI
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { TableCell, TableHead, TableRow } from "@/components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import InputError from "@/Components/InputError.vue";

// Ícones
import { ChevronUp, ChevronDown, Trash2 } from "lucide-vue-next";

// Nosso Composable
import { useFilters } from "@/Composables/useFilters.js";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    censoredWords: Object,
    queryParams: Object,
});

const initialFilters = {
    word: props.queryParams?.filter?.word ?? "",
    Search: props.queryParams?.filter?.search ?? "",
    per_page: props.queryParams?.per_page ?? "15",
};

const { filters, clearFilters, sort, clearFilter } = useFilters(
    props,
    initialFilters,
    "admin.censored-words.index"
);

const isSorted = (field) => {
    const asc = props.queryParams?.sort === field;
    const desc = props.queryParams?.sort === `-${field}`;
    if (!asc && !desc) return null;
    return asc ? "asc" : "desc";
};

// --- Lógica do Modal de Criação ---
const isCreateModalOpen = ref(false);
const form = useForm({
    word: "",
});

const submitNewWord = () => {
    form.post(route("admin.censored-words.store"), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateModalOpen.value = false;
            form.reset();
        },
    });
};

// --- Lógica do Alerta de Exclusão ---
const isDeleteDialogOpen = ref(false);
const wordToDelete = ref(null);

const confirmDelete = (word) => {
    wordToDelete.value = word;
    isDeleteDialogOpen.value = true;
};

const deleteWord = () => {
    if (wordToDelete.value) {
        router.delete(
            route("admin.censored-words.destroy", wordToDelete.value.id),
            {
                preserveScroll: true,
                onSuccess: () => (isDeleteDialogOpen.value = false),
            }
        );
    }
};
</script>

<template>

    <Head title="Palavras Censuradas" />

    <IndexPageLayout :data="censoredWords">
        <template #title>Palavras Censuradas</template>

        <template #actions>
            <Button @click="isCreateModalOpen = true">Nova Palavra</Button>
        </template>

        <template #filters>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div class="space-y-1 xl:col-span-2">
                    <label class="text-sm font-medium">Título</label>
                    <Input type="text" v-model="filters.word" placeholder="Pesquisar por palavra ou frase..." />
                </div>
                <div class="space-y-1">
                    <label class="text-sm">Itens por página</label>
                    <Select v-model="filters.per_page">
                        <SelectTrigger>
                            <SelectValue placeholder="Qtd" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10</SelectItem>
                            <SelectItem value="15">15</SelectItem>
                            <SelectItem value="20">20</SelectItem>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-end">
                    <Button @click="clearFilters" variant="ghost" class="w-full">{{ $t("Clear Filters") }}</Button>
                </div>
            </div>
        </template>

        <template #table-header>
            <TableRow>
                <TableHead><button @click="sort('word')" class="flex items-center gap-2 font-semibold">
                        Palavra / Frase
                    </button></TableHead>
                <TableHead><button @click="sort('created_at')" class="flex items-center gap-2 font-semibold">
                        Adicionada Em
                    </button></TableHead>
                <TableHead class="text-right">Ações</TableHead>
            </TableRow>
        </template>

        <template #table-body>
            <TableRow v-for="word in censoredWords.data" :key="word.id">
                <TableCell class="font-medium">{{ word.word }}</TableCell>
                <TableCell>{{
                    new Date(word.created_at).toLocaleDateString("pt-BR")
                    }}</TableCell>
                <TableCell class="text-right">
                    <Button @click="confirmDelete(word)" variant="ghost" size="icon"
                        class="w-8 h-8 p-0 text-destructive hover:text-destructive">
                        <Trash2 class="w-4 h-4" />
                    </Button>
                </TableCell>
            </TableRow>
            <TableRow v-if="censoredWords.data.length === 0">
                <TableCell colspan="3" class="text-center text-gray-500">Nenhuma palavra encontrada.</TableCell>
            </TableRow>
        </template>
    </IndexPageLayout>

    <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Adicionar Nova Palavra/Frase</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitNewWord" class="space-y-4">
                <div class="space-y-2">
                    <Label for="word-input">Palavra ou Frase</Label>
                    <Input id="word-input" v-model="form.word" />
                    <InputError class="mt-2" :message="form.errors.word" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="isCreateModalOpen = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Salvar</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Você tem a certeza?</AlertDialogTitle>
                <AlertDialogDescription v-if="wordToDelete">
                    Esta ação não pode ser desfeita. A palavra/frase
                    <span class="font-semibold">"{{ wordToDelete.word }}"</span>
                    será apagada permanentemente.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancelar</AlertDialogCancel>
                <AlertDialogAction @click="deleteWord" class="bg-destructive hover:bg-destructive/90">Sim, apagar
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
