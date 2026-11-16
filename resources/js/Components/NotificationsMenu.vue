<script setup lang="ts">
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Badge } from '@/components/ui/badge'
import { Bell } from 'lucide-vue-next'

// Tipagem rápida para as props globais (melhoraremos isso no types/global.d.ts depois)
const page = usePage()
const unreadCount = computed(() => page.props.auth?.unread_notifications_count || 0)
const notifications = computed(() => page.props.auth?.notifications || []) // Vamos injetar as últimas 5 via middleware depois
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative w-9 h-9">
                <Bell class="w-5 h-5" />
                <Badge v-if="unreadCount > 0" variant="destructive"
                    class="absolute -top-1 -right-1 px-1.5 py-0.5 text-[10px] h-4 min-w-[16px] flex items-center justify-center">
                    {{ unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notificações</span>
                <span v-if="unreadCount > 0" class="text-xs cursor-pointer text-muted-foreground hover:underline">Marcar
                    lidas</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div v-if="notifications.length > 0" class="max-h-[300px] overflow-y-auto">
                <DropdownMenuItem v-for="note in notifications" :key="note.id"
                    class="flex flex-col items-start gap-1 p-3 cursor-pointer">
                    <div class="text-sm font-medium">{{ note.data.title }}</div>
                    <div class="text-xs text-muted-foreground line-clamp-2">{{ note.data.message }}</div>
                    <span class="text-[10px] text-slate-400 mt-1">{{ note.created_at_human }}</span>
                </DropdownMenuItem>
            </div>
            <div v-else class="p-4 text-sm text-center text-muted-foreground">
                Nenhuma notificação nova.
            </div>

            <DropdownMenuSeparator />
            <DropdownMenuItem class="justify-center cursor-pointer text-primary">
                Ver todas
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
