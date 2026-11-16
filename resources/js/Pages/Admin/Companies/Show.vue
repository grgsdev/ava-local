<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Pencil, UserPlus } from 'lucide-vue-next';
defineProps<{ company: { id: number; name: string }; adms: Array<{ id:number; name:string; email:string }> }>();
</script>
<template>
    <Head :title="company.name" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg bg-muted">
                        <Building2 class="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ company.name }}</h1>
                        <p class="text-muted-foreground">Detalhes e administradores da empresa.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <Link :href="route('admin.companies.edit', company.id)">
                            <Pencil class="w-4 h-4 mr-2" />
                            Editar Empresa
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="route('admin.companies.adms.create', company.id)">
                            <UserPlus class="w-4 h-4 mr-2" />
                            Cadastrar ADM
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>ADM Cliente PJ</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>E-mail</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="adm in adms" :key="adm.id">
                                <TableCell class="font-medium">{{ adm.name }}</TableCell>
                                <TableCell>{{ adm.email }}</TableCell>
                            </TableRow>
                            <TableRow v-if="adms.length === 0">
                                <TableCell colspan="2" class="py-10 text-center text-muted-foreground">
                                    Nenhum ADM Cliente PJ vinculado.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
