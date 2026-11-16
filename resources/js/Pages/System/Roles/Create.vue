<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import type { Permission, Paginated } from '@/types'; // MODIFICADO: Importa Paginated
import { computed } from 'vue';

// MODIFICADO: A prop agora espera corretamente um objeto paginado
const props = defineProps<{
    permissions: Paginated<Permission>;
}>();

const form = useForm({
    name: '',
    permissions: [] as number[], // O v-model dos checkboxes irá popular este array com os IDs
});

// Agrupa as permissões pelo "assunto" (a segunda palavra do nome da permissão)
const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};

    // MODIFICADO: Itera sobre `props.permissions.data` em vez de `props.permissions`
    const permissionsList = props.permissions?.data || [];

    permissionsList.forEach(p => {
        // Ex: "create users" -> grupo "users"
        const groupName = p.name.split(' ')[1] || p.name;
        if (!groups[groupName]) {
            groups[groupName] = [];
        }
        groups[groupName].push(p);
    });

    // Ordena os grupos alfabeticamente para uma exibição consistente
    const sortedKeys = Object.keys(groups).sort();
    const sortedGroups: Record<string, Permission[]> = {};
    sortedKeys.forEach(key => {
        sortedGroups[key] = groups[key];
    });

    return sortedGroups;
});

// Verifica se todos os checkboxes de um grupo estão marcados
const isGroupSelected = (groupPermissions: Permission[]): boolean => {
    const groupIds = groupPermissions.map(p => p.id);
    return groupIds.every(id => form.permissions.includes(id));
};

// Marca ou desmarca todos os checkboxes de um grupo
const toggleGroupSelection = (groupPermissions: Permission[]) => {
    const groupIds = groupPermissions.map(p => p.id);
    const allSelected = isGroupSelected(groupPermissions);

    if (allSelected) {
        // Se todos estiverem selecionados, remove todos
        form.permissions = form.permissions.filter(id => !groupIds.includes(id));
    } else {
        // Se algum não estiver selecionado, adiciona os que faltam
        const permissionsToAdd = groupIds.filter(id => !form.permissions.includes(id));
        form.permissions.push(...permissionsToAdd);
    }
};

const submit = () => {
    form.post(route('system.roles.store'));
};
</script>

<template>

    <Head title="Novo Papel" />

    <AuthenticatedLayout>
        <div>
            <Card class="w-full p-6 mx-auto" style="max-width: 65%; min-width: 350px;">
                <form @submit.prevent="submit">

                    <CardHeader>
                        <CardTitle>Novo Papel</CardTitle>
                        <CardDescription>
                            Papéis agrupam um conjunto de permissões que podem ser atribuídas a um usuário.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="space-y-1.5">
                            <Label for="name">Nome do Papel</Label>
                            <Input id="name" v-model="form.name" type="text" placeholder="Ex: Editor de Conteúdo"
                                autofocus />
                            <p v-if="form.errors.name" class="text-sm text-red-500">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <Label>Permissões</Label>
                            <div v-for="(group, groupName) in groupedPermissions" :key="groupName"
                                class="p-4 border rounded-md">
                                <div class="flex items-center justify-between pb-4 mb-4 border-b">
                                    <h3 class="font-semibold capitalize">{{ groupName }}</h3>
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" :id="`select-all-${groupName}`"
                                            :checked="isGroupSelected(group)" @change="toggleGroupSelection(group)"
                                            class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                                        <Label :for="`select-all-${groupName}`" class="text-sm font-medium">
                                            Marcar Todos
                                        </Label>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                                    <div v-for="permission in group" :key="permission.id"
                                        class="flex items-center space-x-2">
                                        <input type="checkbox" :id="`perm-${permission.id}`" :value="permission.id"
                                            v-model="form.permissions"
                                            class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                                        <Label :for="`perm-${permission.id}`" class="text-sm font-normal">
                                            {{ permission.name }}
                                        </Label>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.permissions" class="text-sm text-red-500">
                                {{ form.errors.permissions }}
                            </p>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-end">
                        <Button :disabled="form.processing">
                            Salvar Papel
                        </Button>
                    </CardFooter>

                </form>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
