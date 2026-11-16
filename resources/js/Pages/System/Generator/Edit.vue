<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModelForm from './Partials/ModelForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

// Recebemos o conteúdo do JSON como a prop 'model'
const props = defineProps<{
    model: any; // A estrutura do JSON é dinâmica
}>();

// O useForm é inicializado com os dados recebidos
const form = useForm({
    name: props.model.name,
    version: props.model.version,
    softDeletes: props.model.softDeletes,
    timestamps: props.model.timestamps,
    useIsActive: props.model.useIsActive,
    useApprovedStatus: props.model.useApprovedStatus,
    useScribe: props.model.useScribe,
    authorize: props.model.authorize,
    logsActivity: props.model.logsActivity,
    clearsResponseCache: props.model.clearsResponseCache,
    attributes: props.model.attributes,
    relations: props.model.relations,
});

const submit = () => {
    form.put(route('system.generator.update', props.model.name));
};
</script>

<template>
    <Head title="Editar Modelo JSON" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto">
            <Card>
                <CardHeader>
                    <CardTitle>Editar Modelo: {{ model.name }}</CardTitle>
                    <CardDescription>
                        Ajuste a estrutura de dados e o comportamento do seu módulo.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ModelForm v-model="form" :errors="form.errors" :is-editing="true" @submit="submit" />
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
