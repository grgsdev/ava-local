<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import type { Paginated, Role } from '@/types';
const props = defineProps<{
    roles: Paginated<Role>; // <-- Recebemos a lista de todos os papéis
}>();
const form = useForm({
    name: '',
    roles: [],
});

const submit = () => {
    form.post(route('system.permissions.store'));
};
</script>

<template>

    <Head title="Nova Permissão" />

    <AuthenticatedLayout>
        <div>
            <Card class="w-full p-6 mx-auto" style="max-width: 65%; min-width: 350px;">
                <form @submit.prevent="submit">

                    <CardHeader>
                        <CardTitle>Nova Permissão</CardTitle>
                        <CardDescription>
                            As permissões dão acesso a funcionalidades específicas. Crie nomes claros e em inglês (ex:
                            'edit articles').
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid items-center w-full gap-4">
                            <div class="flex flex-col space-y-1.5">
                                <Label for="name">Nome da Permissão</Label>
                                <Input id="name" v-model="form.name" type="text" placeholder="Ex: view posts"
                                    autofocus />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="roles">Associar aos Papéis</Label>
                                <SearchableSelect v-model="form.roles" :api-url="route('system.roles.selection')"
                                    :initial-options="roles.data" :multiple="true"
                                    placeholder="Selecione os papéis..." />
                                <p v-if="form.errors.roles" class="text-sm text-red-500">
                                    {{ form.errors.roles }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button :disabled="form.processing">
                            Salvar Permissão
                        </Button>
                    </CardFooter>

                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
