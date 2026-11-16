import { usePage } from "@inertiajs/vue3";
import type { App } from "vue";

export default {
    install: (app: App) => {
        app.config.globalProperties.$can = (permission: string): boolean => {
            const page = usePage();
            const user = page.props.auth?.user;

            // Garante que o usuário e as permissões existam e sejam um array
            if (user && Array.isArray(user.can)) {
                return user.can.includes(permission);
            }

            return false;
        };
    },
};
