<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { History, Newspaper, Sun } from "lucide-vue-next";
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";

// CORREÇÃO: Tipamos o model como booleano
const open = defineModel<boolean>("open");

const commandGroups = [
    {
        heading: "Navegação",
        commands: [
            {
                label: "Dashboard",
                icon: Sun,
                action: () => router.get(route("dashboard")),
            },
            {
                label: "System Permissions",
                icon: Newspaper,
                action: () => router.get(route("system.permissions.index")),
            },
            {
                label: "System Roles",
                icon: History,
                action: () => router.get(route("system.roles.index")),
            },

            {
                label: "System Users",
                icon: History,
                action: () => router.get(route("system.users.index")),
            },

        ],
    },
    {
        heading: "Ações",
        commands: [
            {
                label: "Criar Novo Model",
                icon: Newspaper,
                action: () => router.get(route("system.generator.create")),
            },
        ],
    },
];

// CORREÇÃO: Tipamos o parâmetro como uma função que não retorna nada
function runCommand(commandAction: () => void) {
    commandAction();
    open.value = false;
}
</script>

<template>
    <CommandDialog v-model:open="open">
        <CommandInput placeholder="Digite um comando ou pesquise..." />
        <CommandList>
            <CommandEmpty>Nenhum resultado encontrado.</CommandEmpty>

            <CommandGroup v-for="group in commandGroups" :key="group.heading" :heading="group.heading">
                <CommandItem v-for="item in group.commands" :key="item.label" :value="item.label"
                    @select="runCommand(item.action)">
                    <component :is="item.icon" class="w-4 h-4 mr-2" />
                    <span>{{ $t(item.label) }}</span>
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
