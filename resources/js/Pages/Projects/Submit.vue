<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ project: { data?: { id: number; title: string; status: string }; id?: number; title?: string; status?: string } }>();
const project = props.project.data ?? props.project;
const form = useForm({});

const submit = () => form.post(route('projects.submit', project.id));
</script>

<template>
    <Head title="Enviar projeto para aprovação" />
    <AuthenticatedLayout>
        <main class="mx-auto max-w-xl p-8">
            <h1 class="mb-4 text-2xl font-semibold">Enviar projeto para aprovação</h1>
            <p class="mb-6">{{ project.title }}</p>
            <p class="mb-6 text-sm text-muted-foreground">O projeto será alterado de Em Edição para Aguardando aprovação.</p>
            <button class="rounded bg-primary px-4 py-2 text-white" :disabled="form.processing" @click="submit">
                Enviar para aprovação
            </button>
        </main>
    </AuthenticatedLayout>
</template>
