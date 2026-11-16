<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import type { DashboardStats } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Users, Building, Hourglass } from "lucide-vue-next";

const props = defineProps<{
    stats: DashboardStats;
}>();
</script>

<template>

    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
            <p class="text-muted-foreground">Um resumo da atividade na plataforma.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">


            <Card class="w-full max-w-md mx-auto shadow-2xl bg-card text-card-foreground rounded-2xl">
                <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                    <CardTitle class="text-sm font-medium">Total de Usuários</CardTitle>
                    <Users class="w-4 h-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.totalMembers }}</div>
                    <p class="text-xs">Usuários registados no aplicativo</p>
                </CardContent>
            </Card>
        </div>

    </AuthenticatedLayout>
</template>
