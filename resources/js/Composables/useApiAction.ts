// Em resources/js/Composables/useApiAction.ts

import { useToast } from "@/components/ui/toast/use-toast";

// Tipos para as mensagens de erro
interface ErrorMessages {
    title?: string;
    description?: string;
}

export function useApiAction() {
    const { toast } = useToast();

    /**
     * Executa uma ação (ex: chamada de API) dentro de um bloco try...catch,
     * exibindo uma notificação de erro amigável em caso de falha no lado do cliente.
     * @param action A função (geralmente assíncrona) a ser executada.
     * @param errorMessages Mensagens customizadas para o toast de erro.
     */
    const executeAction = (
        action: () => void,
        errorMessages: ErrorMessages = {}
    ) => {
        try {
            // Executa a ação passada. A ação em si é responsável por lidar
            // com os callbacks de sucesso (onSuccess) e erro de servidor (onError).
            action();
        } catch (e) {
            // Captura erros síncronos que acontecem ANTES da requisição, como o erro do Ziggy.
            console.error("Erro de cliente ao preparar a ação:", e);

            toast({
                variant: "destructive",
                title: errorMessages.title || "Ocorreu um Erro",
                description:
                    errorMessages.description ||
                    "Não foi possível realizar a operação. Por favor, recarregue a página.",
            });
        }
    };

    return { executeAction };
}
