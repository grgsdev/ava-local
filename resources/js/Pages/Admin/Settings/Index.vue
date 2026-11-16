<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import type { PlatformSetting } from "@/types";
import { ref } from "vue";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import AvatarInput from "@/Components/AvatarInput.vue";
import { Trash2 } from "lucide-vue-next";

const props = defineProps<{
    settings: PlatformSetting;
}>();


const parseJsonProp = (propValue: any): object => {
    if (typeof propValue === "string") {
        try {
            return JSON.parse(propValue);
        } catch (e) {
            return {};
        }
    }
    return propValue || {};
};
const contactInfoArray = ref(
    Object.entries(parseJsonProp(props.settings.contact_info)).map(
        ([type, value]) => ({ type, value })
    )
);
const socialLinksArray = ref(
    Object.entries(parseJsonProp(props.settings.social_links)).map(
        ([type, value]) => ({ type, value })
    )
);

const form = useForm({
    _method: "PUT",
    platform_name: props.settings.platform_name,
    physical_address: props.settings.physical_address || "",
    logo_web: null as File | null,
    logo_mobile: null as File | null,
    contact_info: {},
    social_links: {},
});

const submit = () => {
    // Antes de submeter, transformamos os arrays de volta para objetos JSON
    form.contact_info = contactInfoArray.value.reduce((acc, item) => {
        if (item.type && item.value) acc[item.type] = item.value;
        return acc;
    }, {} as Record<string, string>);

    form.social_links = socialLinksArray.value.reduce((acc, item) => {
        if (item.type && item.value) acc[item.type] = item.value;
        return acc;
    }, {} as Record<string, string>);

    form.post(route("admin.settings.update"));
};

// Funções para gerir os campos dinâmicos
const addContact = () => contactInfoArray.value.push({ type: "", value: "" });
const removeContact = (index: number) =>
    contactInfoArray.value.splice(index, 1);

const addSocialLink = () =>
    socialLinksArray.value.push({ type: "", value: "" });
const removeSocialLink = (index: number) =>
    socialLinksArray.value.splice(index, 1);
</script>

<template>

    <Head title="Configurações da Plataforma" />
    <AuthenticatedLayout>
        <Card class="w-full p-6 mx-auto" style="max-width: 65%; min-width: 350px;">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="md:max-w-[70%]">
                        <h1 class="mb-1 text-2xl font-bold">Configurações da Plataforma</h1>
                        <p class="text-muted-foreground">
                            Altere as regras e informações globais da aplicação.
                        </p>
                    </div>
                    <Button :disabled="form.processing" class="self-start md:self-auto">Salvar Alterações</Button>
                </div>


                <div>
                    <h2 class="mb-4 text-lg font-semibold">Informações Gerais</h2>
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="platform_name">Nome da Plataforma</Label><Input id="platform_name"
                                v-model="form.platform_name" />
                        </div>

                        <div class="space-y-2">
                            <Label>Informações de Contato</Label>
                            <div v-for="(contact, index) in contactInfoArray" :key="index" class="flex items-end gap-2">
                                <div class="flex-1">
                                    <Input v-model="contact.type" placeholder="Tipo (ex: email)" />
                                </div>
                                <div class="flex-1">
                                    <Input v-model="contact.value" placeholder="Valor (ex: contato@lupa.app)" />
                                </div>
                                <Button type="button" @click="removeContact(index)" variant="destructive" size="sm">
                                    <Trash2 class="w-2 h-2" />
                                </Button>
                            </div>
                            <Button type="button" @click="addContact" variant="outline" size="sm">Adicionar
                                Contato</Button>
                        </div>

                        <div class="space-y-2">
                            <Label>Mídias Sociais e Links</Label>
                            <div v-for="(link, index) in socialLinksArray" :key="index" class="flex items-end gap-2">
                                <div class="flex-1">
                                    <Input v-model="link.type" placeholder="Tipo (ex: instagram)" />
                                </div>
                                <div class="flex-1">
                                    <Input v-model="link.value" placeholder="URL Completa" />
                                </div>
                                <Button type="button" @click="removeSocialLink(index)" variant="destructive"
                                    size="icon">
                                    <Trash2 class="w-2 h-2" />
                                </Button>
                            </div>
                            <Button type="button" @click="addSocialLink" variant="outline" size="sm">Adicionar
                                Link</Button>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Endereço Físico</Label><Textarea v-model="form.physical_address" />
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="mb-4 text-lg font-semibold">Logos da Plataforma</h2>
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>Logo para Web</Label>
                            <AvatarInput v-model="form.logo_web" :default-src="settings.logo_web_url ?? ''" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Logo para App Mobile</Label>
                            <AvatarInput v-model="form.logo_mobile" :default-src="settings.logo_mobile_url ?? ''" />
                        </div>
                    </div>
                </div>

                <CardFooter class="flex justify-end pt-4">
                    <Button :disabled="form.processing" type="submit">Salvar Alterações</Button>
                </CardFooter>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
