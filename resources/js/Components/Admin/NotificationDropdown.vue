<script setup>
import { usePage, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
// CORREÇÃO: Removido 'DropdownMenuFooter' da importação
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Bell, Check, Trash2 } from 'lucide-vue-next';

const page = usePage();
const notifications = computed(() => page.props.auth.user?.notifications || []);
const notificationCount = computed(() => page.props.auth.user?.notification_count || 0);

const markAsRead = (notificationId) => {
    router.put(route('admin.notifications.read', notificationId), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.auth.user) {
                page.props.auth.user.notifications = notifications.value.filter(n => n.id !== notificationId);
                page.props.auth.user.notification_count--;
            }
        }
    });
};

const deleteNotification = (notificationId) => {
    router.delete(route('admin.notifications.destroy', notificationId), {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.auth.user) {
                page.props.auth.user.notifications = notifications.value.filter(n => n.id !== notificationId);
                page.props.auth.user.notification_count--;
            }
        }
    });
};

const markAllAsRead = () => {
    router.post(route('admin.notifications.markAllRead'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (page.props.auth.user) {
                page.props.auth.user.notifications = [];
                page.props.auth.user.notification_count = 0;
            }
        }
    });
};
</script>

<template>
    <DropdownMenu v-if="page.props.auth.user">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="w-5 h-5" />
                <span v-if="notificationCount > 0"
                    class="absolute flex items-center justify-center w-4 h-4 text-xs text-white bg-red-500 rounded-full top-1 right-1">
                    {{ notificationCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 md:w-96">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notificações</span>
                <span v-if="notificationCount > 0" class="text-xs font-normal text-muted-foreground">{{
                    notificationCount }} nova(s)</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div v-if="notifications.length > 0" class="overflow-y-auto max-h-96">
                <DropdownMenuItem v-for="notification in notifications" :key="notification.id" as-child
                    class="group !p-0">
                    <div class="flex items-center w-full">
                        <Link :href="route('admin.notifications.get.read', { notification: notification.id })"
                            class="flex flex-col items-start flex-grow p-3">
                        <p class="text-sm font-medium whitespace-normal">{{ notification.data.title }}</p>
                        <p class="text-xs whitespace-normal">{{ notification.data.body }}</p>
                        <span class="mt-1 text-xs text-muted-foreground">{{ new
                            Date(notification.created_at).toLocaleString('pt-BR') }}</span>
                        </Link>
                        <div class="pr-2 transition-opacity opacity-0 group-hover:opacity-100">
                            <Button @click.stop.prevent="markAsRead(notification.id)" variant="ghost" size="icon"
                                class="w-7 h-7 text-secondary-foreground" title="Marcar como lida">
                                <Check class="w-4 h-4" />
                            </Button>
                            <Button @click.stop.prevent="deleteNotification(notification.id)" variant="ghost"
                                size="icon" class="w-7 h-7 text-destructive" title="Apagar">
                                <Trash2 class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </DropdownMenuItem>
            </div>
            <div v-else class="px-3 py-4 text-sm text-center text-muted-foreground">
                Nenhuma notificação nova.
            </div>
            <div v-if="notificationCount > 0">
                <DropdownMenuSeparator />
                <div class="p-2">
                    <Button @click="markAllAsRead" variant="outline" class="w-full">Marcar todas como lidas</Button>
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
