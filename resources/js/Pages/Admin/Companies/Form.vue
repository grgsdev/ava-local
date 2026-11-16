<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props = defineProps<{ company?: { id: number; name: string } }>();
const form = useForm({ name: props.company?.name ?? '' });
const submit = () => props.company ? form.put(route('admin.companies.update', props.company.id)) : form.post(route('admin.companies.store'));
</script>
<template>
    <Head :title="company ? 'Editar Empresa' : 'Nova Empresa'" />

    <AuthenticatedLayout>
        <form class="max-w-2xl mx-auto space-y-6" @submit.prevent="submit">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ company ? 'Editar Empresa' : 'Nova Empresa' }}</h1>
                    <p class="text-muted-foreground">Informe os dados básicos para a empresa.</p>
                </div>
                <div class="flex gap-2">
                    <Button as-child type="button" variant="outline">
                        <Link :href="company ? route('admin.companies.show', company.id) : route('admin.companies.index')">Cancelar</Link>
                    </Button>
                    <Button :disabled="form.processing">Salvar</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Dados da Empresa</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-1.5">
                        <Label for="name">Nome</Label>
                        <Input id="name" v-model="form.name" autofocus />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AuthenticatedLayout>
</template>
