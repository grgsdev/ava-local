<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import type { Paginated, Permission, Role } from '@/types';
// Recebemos a permissão a ser editada como prop do controller
const props = defineProps<{
    permission: Permission;
    allRoles: Paginated<Role>; // <-- Recebemos todos os papéis
}>();
const roleIds = props.permission.roles.map(r => r.id);
// O useForm é inicializado com os dados da permissão
const form = useForm({
    name: props.permission.name,
    roles: roleIds, // <-- Inicializamos o form com os IDs dos papéis já associados
});

const submit = () => {
    // Usamos o método PUT para a rota de update
    form.put(route('system.permissions.update', props.permission.id));
};
</script>

<template>

    <Head title="Editar Permissão" />

    <AuthenticatedLayout>
        <div>
            <Card class="w-full p-6 mx-auto" style="max-width: 65%; min-width: 350px;">
                <form @submit.prevent="submit">

                    <CardHeader>
                        <CardTitle>Editar Permissão</CardTitle>
                        <CardDescription>
                            Ajuste o nome da permissão. Lembre-se que mudanças aqui podem impactar o acesso dos
                            usuários.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid items-center w-full gap-4">
                            <div class="flex flex-col space-y-1.5">
                                <Label for="name">Nome da Permissão</Label>
                                <Input id="name" v-model="form.name" type="text" autofocus />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="roles">Associar aos Papéis</Label>
                                <SearchableSelect v-model="form.roles" :api-url="route('system.roles.selection')"
                                    :initial-options="allRoles.data" :multiple="true"
                                    placeholder="Selecione os papéis..." />
                                <p v-if="form.errors.roles" class="text-sm text-red-500">
                                    {{ form.errors.roles }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button :disabled="form.processing">
                            Salvar Alterações
                        </Button>
                    </CardFooter>

                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
