import { reactive } from "vue";

export interface Toast {
    id: number;
    message: string;
    type: "success" | "error" | "info";
}

// Estado global reativo (fora da função para ser compartilhado)
const state = reactive({
    toasts: [] as Toast[],
});

let nextId = 1;

export function useToast() {
    const remove = (id: number) => {
        const index = state.toasts.findIndex((t) => t.id === id);
        if (index > -1) {
            state.toasts.splice(index, 1);
        }
    };

    const add = (
        message: string,
        type: "success" | "error" | "info" = "success"
    ) => {
        const id = nextId++;
        state.toasts.push({ id, message, type });

        // Auto remove após 3 segundos
        setTimeout(() => remove(id), 3000);
    };

    return {
        toasts: state.toasts,
        add,
        remove,
    };
}
