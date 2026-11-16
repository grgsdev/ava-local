<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Eye, Plus } from 'lucide-vue-next';
defineProps<{ companies: Array<{ id: number; name: string; users_count: number }> }>();
</script>
<template>
    <Head title="Empresas" />

    <AuthenticatedLayout>
        <Card class="overflow-hidden">
            <CardHeader>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <CardTitle>Empresas</CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">Gerencie as empresas da plataforma e seus vínculos.</p>
                    </div>
                    <Button as-child>
                        <Link :href="route('admin.companies.create')">
                            <Plus class="w-4 h-4 mr-2" />
                            Nova Empresa
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Empresa</TableHead>
                            <TableHead>Vínculos</TableHead>
                            <TableHead class="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="company in companies" :key="company.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-md bg-muted">
                                        <Building2 class="w-4 h-4 text-muted-foreground" />
                                    </div>
                                    {{ company.name }}
                                </div>
                            </TableCell>
                            <TableCell>{{ company.users_count }}</TableCell>
                            <TableCell class="text-right">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="route('admin.companies.show', company.id)">
                                        <Eye class="w-4 h-4 mr-2" />
                                        Abrir
                                    </Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="companies.length === 0">
                            <TableCell colspan="3" class="py-12 text-center text-muted-foreground">
                                Nenhuma empresa cadastrada.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </AuthenticatedLayout>
</template>
