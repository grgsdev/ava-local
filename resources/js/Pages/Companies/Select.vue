<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

type Company = { id: number; name: string };

const props = defineProps<{ companies: Company[] }>();
const form = useForm({ company_id: props.companies[0]?.id ?? null });

const submit = () => form.post(route('companies.select.store'));
</script>

<template>
    <Head title="Selecionar empresa" />
    <main class="mx-auto max-w-xl p-8">
        <h1 class="mb-6 text-2xl font-semibold">Selecione a empresa ativa</h1>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block">
                <span class="mb-2 block text-sm font-medium">Empresa</span>
                <select v-model="form.company_id" class="w-full rounded border p-2">
                    <option v-for="company in props.companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                    </option>
                </select>
            </label>
            <button type="submit" class="rounded bg-primary px-4 py-2 text-white" :disabled="form.processing">
                Continuar
            </button>
        </form>
    </main>
</template>
