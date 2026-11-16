<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    moderationCase: Object,
    censoredWords: Array,
});

const form = useForm({
    action: '', // Será 'approve' ou 'delete'
    notes: props.moderationCase.notes || '',
});

// Helper para pegar o título do post/produto
const getCommentableTitle = (commentable) => {
    if (!commentable) return 'Conteúdo removido';
    return commentable.title || commentable.name || `Item #${commentable.id}`;
};

// Helper para pegar a rota de edição do post/produto
const getCommentableEditRoute = (commentable) => {
    if (!commentable) return '#';
    // Adicione mais lógicas aqui se tiver comentários em outros modelos
    if (commentable.type === 'App\\Models\\Post') {
        return route('posts.edit', commentable.id);
    }
    if (commentable.type === 'App\\Models\\Product') {
        return route('admin.products.edit', commentable.id);
    }
    return '#';
};

const submit = (action) => {
    form.action = action;
    form.put(route('admin.moderation.update', props.moderationCase.id), {
        preserveScroll: true,
    });
};
const highlightedComment = computed(() => {
    if (!props.censoredWords || props.censoredWords.length === 0) {
        // Se não houver palavras na lista, retorna o texto simples
        return [{ text: props.moderationCase.comment.body, highlight: false }];
    }

    // Cria uma expressão regular (RegExp) a partir da nossa lista de palavras.
    // 'i' torna a busca case-insensitive (não diferencia maiúsculas/minúsculas).
    // 'g' garante que todas as ocorrências sejam encontradas.
    const regex = new RegExp(`(${props.censoredWords.join('|')})`, 'gi');

    const body = props.moderationCase.comment.body;

    // Divide o texto em partes, separando as palavras encontradas
    const parts = body.split(regex);

    return parts.filter(part => part).map(part => {
        // Verifica se a parte do texto é uma das palavras censuradas
        const isCensored = props.censoredWords.some(word => word.toLowerCase() === part.toLowerCase());
        return {
            text: part,
            highlight: isCensored,
        };
    });
});
</script>

<template>

    <Head :title="`Moderar Comentário #${moderationCase.comment.id}`" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-1">
            <Card>
                <CardHeader>
                    <CardTitle>Detalhes do Caso</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p><strong>ID do Caso:</strong> {{ moderationCase.id }}</p>
                    <p><strong>Status:</strong>
                        <Badge :variant="moderationCase.status === 'pending' ? 'destructive' : 'secondary'">{{
                            moderationCase.status }}</Badge>
                    </p>
                    <p><strong>Sinalizado em:</strong> {{ new Date(moderationCase.created_at).toLocaleString('pt-BR') }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Autor do Comentário</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <p><strong>Nome:</strong> {{ moderationCase.comment.user.name }}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Conteúdo Associado</CardTitle>
                </CardHeader>
                <CardContent v-if="moderationCase.comment.commentable" class="space-y-3">
                    <p class="font-semibold">{{ getCommentableTitle(moderationCase.comment.commentable) }}</p>
                    <Link :href="getCommentableEditRoute(moderationCase.comment.commentable)">
                    <Button variant="outline" class="w-full">Editar Conteúdo</Button>
                    </Link>
                </CardContent>
                <CardContent v-else>
                    <p class="text-sm text-muted-foreground">O conteúdo original foi removido.</p>
                </CardContent>
            </Card>
        </div>

        <div class="lg:col-span-2">
            <Card>
                <CardHeader>
                    <CardTitle>Ação de Moderação</CardTitle>
                    <CardDescription>Analise o comentário abaixo e tome uma decisão.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div>
                        <Label>Comentário Sinalizado</Label>
                        <blockquote class="p-4 mt-2 border-l-4 bg-muted text-muted-foreground">
                            <span v-for="(part, index) in highlightedComment" :key="index">
                                <mark v-if="part.highlight"
                                    class="px-1 py-0.5 rounded-md bg-destructive/30 text-destructive-foreground">
                                    {{ part.text }}
                                </mark>
                                <span v-else>{{ part.text }}</span>
                            </span>
                        </blockquote>
                    </div>

                    <form @submit.prevent>
                        <div>
                            <Label for="notes">Notas do Moderador (opcional)</Label>
                            <Textarea id="notes" v-model="form.notes"
                                placeholder="Ex: Aprovado, mas editado para remover linguagem imprópria." />
                            <InputError :message="form.errors.notes" class="mt-2" />
                        </div>
                    </form>
                </CardContent>
                <CardFooter class="justify-end gap-2">
                    <Button variant="destructive" @click="submit('delete')" :disabled="form.processing">
                        Apagar Comentário
                    </Button>
                    <Button variant="success" @click="submit('approve')" :disabled="form.processing">
                        Aprovar Comentário
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
