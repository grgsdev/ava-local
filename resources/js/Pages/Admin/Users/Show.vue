<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
// Componentes de UI
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { PencilIcon, ArrowLeft } from "lucide-vue-next";
import { User } from "@/types";

const props = defineProps<{
    user: User;
}>();

// --- Funções Utilitárias para Formatação ---

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleString('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
};

const getStatusVariant = (status: string) => {
    if (status === 'active') return 'default';
    if (['cancellation', 'expiration'].includes(status)) return 'destructive';
    return 'secondary';
};

const formatCurrency = (value: number | undefined, currency: string | undefined) => {
    if (value === undefined || currency === undefined) return 'N/A';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: currency }).format(value);
};
</script>

<template>

    <Head :title="`Detalhes: ${user.name}`" />

    <AuthenticatedLayout>
        <div class="container py-8 mx-auto space-y-8">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <Avatar class="w-20 h-20 border">
                        <AvatarImage :src="user.avatar_url ?? ''" :alt="user.name" />
                        <AvatarFallback class="text-2xl">{{ user.name.substring(0, 2).toUpperCase() }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ user.name }}</h1>
                        <p class="text-muted-foreground">{{ user.email }}</p>
                    </div>
                </div>
                <Button as-child variant="outline">
                    <Link :href="route('admin.users.edit', user.id)">
                    <PencilIcon class="w-4 h-4 mr-2" />
                    Editar Usuário
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Informações Pessoais</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="font-medium text-muted-foreground">Telefone</p>
                            <p>{{ user.phone_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-muted-foreground">Data de Nascimento</p>
                            <p>{{ user.birth_date?.short || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-muted-foreground">Membro desde</p>
                            <p>{{ user.created_at?.short_with_time || 'N/A' }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Papéis (Roles)</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Badge v-for="role in user.roles" :key="role.id" variant="secondary">{{ role.name }}</Badge>
                        <p v-if="!user.roles || user.roles.length === 0" class="text-sm text-muted-foreground">Nenhum
                            papel atribuído.</p>
                    </CardContent>
                </Card>
            </div>


        </div>
    </AuthenticatedLayout>
</template>
