<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $page['props']['locale'] ?? app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://player.pandavideo.com.br" crossorigin>
    <link rel="preconnect" href="https://b-vz.pandavideo.com.br" crossorigin>
    <link rel="preconnect" href="https://player.vimeo.com" crossorigin>

    <link rel="dns-prefetch" href="https://player.pandavideo.com.br">
    <link rel="dns-prefetch" href="https://b-vz.pandavideo.com.br">
    <!-- Scripts -->

    <script>
        (function() {
            // Função para aplicar os temas
            function applyTheme() {
                try {
                    const pageEl = document.getElementById('app');
                    if (!pageEl || !pageEl.dataset.page) return;

                    const pageProps = JSON.parse(pageEl.dataset.page).props;

                    // Lógica do Tema de Cores
                    const layoutTheme = pageProps.layoutTheme; // Procura pelo tema vindo do backend
                    if (layoutTheme) {
                        document.documentElement.setAttribute('data-theme', layoutTheme);
                    } else {
                        const userTheme = localStorage.getItem('theme'); // Fallback para a escolha do usuário
                        if (userTheme) {
                            document.documentElement.setAttribute('data-theme', userTheme);
                        }
                    }

                    // Lógica do Modo Escuro (continua a mesma)
                    if (localStorage.getItem('theme-mode') === 'dark' || (!('theme-mode' in localStorage) && window
                            .matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }

                    // Lógica da Fonte (continua a mesma)
                    const fontTheme = localStorage.getItem('font-theme');
                    if (fontTheme) {
                        document.documentElement.setAttribute('data-font-theme', fontTheme);
                    }
                } catch (e) {
                    console.error('Falha ao aplicar o tema inicial:', e);
                }
            }
            applyTheme();
        })();
    </script>
    {{-- <script>
        // Este script roda antes de tudo para evitar o "flash" de tema.
        // Ele lê o tema salvo no localStorage e o aplica na tag <html>.
        const theme = localStorage.getItem('theme');
        if (theme) {
            document.documentElement.setAttribute('data-theme', theme);
        }
        // A lógica do modo escuro/claro do Breeze também pode ser adicionada aqui
        if (localStorage.getItem('theme-mode') === 'dark' || (!('theme-mode' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Script de inicialização da Fonte
        const fontTheme = localStorage.getItem('font-theme');
        if (fontTheme) {
            document.documentElement.setAttribute('data-font-theme', fontTheme);
        }
    </script> --}}
    @routes
    @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
