import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export function usePermissions() {
    const page = usePage();

    // Usamos um Set para uma verificação de permissão mais rápida (O(1))
    const permissions = computed(() => {
        const user = page.props.auth?.user;
        if (user && Array.isArray(user.can)) {
            return new Set(user.can);
        }
        return new Set();
    });

    /**
     * Verifica se o usuário logado possui uma permissão específica.
     * @param name O nome da permissão a ser verificada.
     */
    const can = (name: string): boolean => {
        return permissions.value.has(name);
    };

    return { can };
}
