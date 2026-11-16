<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import type { DateValue } from "@internationalized/date"
import { DateFormatter, getLocalTimeZone, } from "@internationalized/date"

import { computed, ref } from "vue";

import Datepicker from "@/Components/Datepicker.vue";
import { format } from 'date-fns';
defineProps<{
    roles: Array<{ name: string }>;
}>();

const form = useForm({
    title: "",
    body: "",
    target_roles: [] as string[],
    delivery_channels: [] as string[],
    start_date: null,
    period_in_days: 1,
    is_active: true,
});
const df = new DateFormatter("pt-BR", {
    dateStyle: "long",
})
const selectedDate = ref<DateValue>();


const submit = () => {
    const dataToSend = {
        ...form.data(),
        start_date: form.start_date ? format(form.start_date, 'yyyy-MM-dd') : null,


    };


    form.transform(() => dataToSend).post(route('admin.scheduled-notifications.store'), {
        forceFormData: true,
    });

};
</script>

<template>

    <Head title="Agendar Nova Notificação" />
    <AuthenticatedLayout>
        <Card class="w-full p-6 mx-auto" style="max-width: 65%; min-width: 350px;">
            <form @submit.prevent="submit" class="mx-auto space-y-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="md:max-w-[70%]">
                        <h1 class="mb-1 text-2xl font-bold">Agendar Nova Notificação</h1>
                        <p class="text-muted-foreground">
                            Agende as notificações. O usuário receberá via e-mail e/ou no app a notificação.
                        </p>
                    </div>
                    <Button :disabled="form.processing" class="self-start md:self-auto">Salvar Agendamento</Button>
                </div>
                <div>
                    <h2 class="mb-4 text-lg font-semibold">Conteúdo e Agendamento</h2>

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="title">Título</Label>
                            <Input id="title" v-model="form.title" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="body">Corpo da Mensagem</Label>
                            <Textarea id="body" v-model="form.body" />
                            <p v-if="form.errors.body" class="mt-1 text-sm text-red-600">{{ form.errors.body }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="start_date">Data de Início</Label>
                            <Datepicker v-model="form.start_date" locale="pt-BR"
                                placeholder="Selecione a data final da isenção" />
                            <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date
                                }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="period_in_days">Período de Envio (em dias)</Label>
                            <Input id="period_in_days" type="number" v-model="form.period_in_days" min="1" />
                            <p v-if="form.errors.period_in_days" class="mt-1 text-sm text-red-600">
                                {{ form.errors.period_in_days }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Público Alvo</Label>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="role in roles" :key="role.name" class="flex items-center gap-2">
                                    <input type="checkbox" :id="role.name" :value="role.name"
                                        v-model="form.target_roles"
                                        class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                                    <Label :for="role.name">{{ $t(role.name) }}</Label>
                                </div>
                            </div>
                            <p v-if="form.errors.target_roles" class="mt-1 text-sm text-red-600">{{
                                form.errors.target_roles }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Canais de Envio</Label>
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="database" value="database"
                                        v-model="form.delivery_channels"
                                        class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                                    <Label for="database">App (Database)</Label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="mail" value="mail" v-model="form.delivery_channels"
                                        class="w-4 h-4 border-gray-300 rounded text-primary focus:ring-primary" />
                                    <Label for="mail">E-mail</Label>
                                </div>
                            </div>
                            <p v-if="form.errors.delivery_channels" class="mt-1 text-sm text-red-600">{{
                                form.errors.delivery_channels }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Switch id="is_active" v-model="form.is_active" />
                            <Label for="is_active">Campanha Ativa</Label>
                        </div>
                    </div>
                </div>
                <CardFooter class="flex justify-end pt-4">
                    <Button :disabled="form.processing" type="submit">Salvar Agendamento</Button>
                </CardFooter>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
