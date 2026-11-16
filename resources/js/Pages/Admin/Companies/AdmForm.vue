<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props = defineProps<{ company: { id: number; name: string } }>();
const form = useForm({ name: '', email: '' });
const submit = () => form.post(route('admin.companies.adms.store', props.company.id));
</script>
<template>
    <Head title="Cadastrar ADM Cliente PJ" />

    <AuthenticatedLayout>
        <form class="max-w-2xl mx-auto space-y-6" @submit.prevent="submit">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Cadastrar ADM Cliente PJ</h1>
                    <p class="text-muted-foreground">Empresa: {{ company.name }}</p>
                </div>
                <div class="flex gap-2">
                    <Button as-child type="button" variant="outline">
                        <Link :href="route('admin.companies.show', company.id)">Cancelar</Link>
                    </Button>
                    <Button :disabled="form.processing">Enviar Convite</Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Dados do Administrador</CardTitle>
                    <CardDescription>O administrador receberá um convite seguro para concluir o cadastro.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="name">Nome</Label>
                        <Input id="name" v-model="form.name" autofocus />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="email">E-mail</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AuthenticatedLayout>
</template>
