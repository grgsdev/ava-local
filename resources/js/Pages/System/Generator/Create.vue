<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModelForm from './Partials/ModelForm.vue'; // <-- O coração da página
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

// Usamos o useForm para inicializar a estrutura do nosso JSON
const form = useForm({
    name: '',
    version: 'V1',
    softDeletes: true,
    timestamps: true,
    useIsActive: false,
    useApprovedStatus: false,
    useScribe: true,
    authorize: false,
    logsActivity: true,
    clearsResponseCache: true,
    attributes: [
        {
            name: 'name',
            type: 'string',
            length: 255,
            max: 255,
            min: 0,
            precision: 0,
            scale: 0,
            validate: true,
            required: true,
            nullable: false,
            unique: true,
            translated: false,
            sortAble: true,
            filterAble: true,
            exactFilter: false,
            searchAble: true,
            description: 'Nome do item.',
            example: 'Exemplo'
        }
    ],
    relations: []
});

const submit = () => {
    form.post(route('system.generator.store'));
};
</script>

<template>
    <Head title="Criar Modelo JSON" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto">
            <Card>
                <CardHeader>
                    <CardTitle>Criar Novo Modelo JSON</CardTitle>
                    <CardDescription>
                        Defina a estrutura de dados e o comportamento do seu novo módulo.
                        Isso irá gerar um arquivo .json que será a base para o scaffolding.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ModelForm v-model="form" :errors="form.errors" :is-editing="false" @submit="submit" />
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
