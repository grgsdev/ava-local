import { ref, watchEffect } from 'vue';

export type FontTheme = 'sans' | 'serif' | 'mono' | 'jakarta' | 'newsreader' | 'nunito' | 'roboto';

export function useFont() {
    const fontTheme = ref<FontTheme>((localStorage.getItem('font-theme') as FontTheme) || 'sans');

    function setFontTheme(newFontTheme: FontTheme) {
        fontTheme.value = newFontTheme;
    }

    watchEffect(() => {
        localStorage.setItem('font-theme', fontTheme.value);
        document.documentElement.setAttribute('data-font-theme', fontTheme.value);
    });

    return { fontTheme, setFontTheme };
}
