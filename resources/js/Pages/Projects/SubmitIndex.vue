<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

type Project = { id: number; title: string; status: string };

defineProps<{ projects: { data: Project[] } }>();
</script>

<template>
    <Head title="Enviar projeto" />
    <AuthenticatedLayout>
        <main class="mx-auto max-w-4xl p-8">
            <h1 class="mb-6 text-2xl font-semibold">Enviar projeto para aprovação</h1>
            <div v-if="projects.data.length === 0" class="text-muted-foreground">Nenhum projeto em edição disponível.</div>
            <ul v-else class="space-y-3">
                <li v-for="project in projects.data" :key="project.id" class="flex items-center justify-between rounded border p-4">
                    <div>
                        <p class="font-medium">{{ project.title }}</p>
                        <p class="text-sm text-muted-foreground">{{ project.status }}</p>
                    </div>
                    <Link :href="route('projects.submit.confirmation', project.id)" class="rounded bg-primary px-4 py-2 text-white">
                        Abrir submit
                    </Link>
                </li>
            </ul>
        </main>
    </AuthenticatedLayout>
</template>
