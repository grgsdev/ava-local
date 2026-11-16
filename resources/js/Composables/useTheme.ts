import { ref, watchEffect } from 'vue';

// Nossos 4 temas disponíveis
export type Theme = 'slate' | 'violet' | 'green' | 'orange' | 'rose' | 'cyan' | 'zinc' | 'indigo' | 'sky' | 'mint' | 'lavender' | 'peach' | 'stone' | 'amber' | 'lime' | 'teal';

export function useTheme() {
    // Tenta carregar o tema do localStorage, ou usa 'slate' como padrão
    const theme = ref<Theme>(
        (localStorage.getItem('theme') as Theme) || 'slate',
    );

    // Função para definir um novo tema
    function setTheme(newTheme: Theme) {
        theme.value = newTheme;
    }

    // Efeito que roda sempre que 'theme.value' muda
    watchEffect(() => {
        const currentTheme = theme.value;

        // Salva a escolha no localStorage para persistência
        localStorage.setItem('theme', currentTheme);

        // Aplica o atributo data-theme na tag <html>, o que ativa o CSS do tema
        document.documentElement.setAttribute('data-theme', currentTheme);
    });

    // Garante que o tema seja aplicado no carregamento inicial da página
    if (localStorage.getItem('theme')) {
        document.documentElement.setAttribute(
            'data-theme',
            localStorage.getItem('theme') as string,
        );
    } else {
        document.documentElement.setAttribute('data-theme', 'slate');
    }

    return { theme, setTheme };
}
