<script setup lang="ts">
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { ChevronDown } from "lucide-vue-next";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { loadLanguageAsync } from "laravel-vue-i18n";

const languages = [
    { value: "pt_BR", label: "Português (BR)", flag: "🇧🇷" },
    { value: "en", label: "English", flag: "🇺🇸" },
    { value: "es", label: "Español", flag: "🇪🇸" },
    { value: "it", label: "Italiano", flag: "🇮🇹" },
    { value: "de", label: "Deutsch", flag: "🇩🇪" },
    { value: "fr", label: "Français", flag: "🇫🇷" },
];
const page = usePage();
let currentLocale = ref(page.props.locale as string);
if (localStorage.getItem("preferred_language")) {
    currentLocale = ref(localStorage.getItem("preferred_language") as string);
}

loadLanguageAsync(currentLocale.value);

const currentLanguage = ref(
    languages.find((lang) => lang.value === currentLocale.value) || languages[0]
);
watch(currentLocale, async (newLocale) => {
    await loadLanguageAsync(newLocale);
    localStorage.setItem("preferred_language", newLocale);
    setCookie("preferred_language", newLocale);
});

const changeLanguage = async (lang: string) => {
    if (currentLocale.value === lang) return;

    currentLocale.value = lang;
    currentLanguage.value =
        languages.find((l) => l.value === lang) || languages[0];
    localStorage.setItem("preferred_language", lang);
    setCookie("preferred_language", lang);
    await router.post(
        route("language.switch"),
        { locale: lang },
        {
            preserveState: true,
            preserveScroll: true,
            headers: { "X-Locale": lang },
        }
    );

    // Recarrega os dados da página com Inertia
    await router.reload({
        headers: { "X-Locale": lang },
    });
};
const setCookie = (name: string, value: string, days = 365) => {
    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="gap-2 px-2">
                <span class="text-lg">{{ currentLanguage.flag }}</span>
                <span class="hidden text-xs sm:inline-block">{{
                    currentLanguage.label
                    }}</span>
                <ChevronDown class="w-4 h-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-[180px]">
            <DropdownMenuItem v-for="lang in languages" :key="lang.value" @click="changeLanguage(lang.value)"
                class="cursor-pointer" :class="{ 'bg-accent': currentLocale === lang.value }">
                <span class="mr-2 text-xs">{{ lang.flag }}</span>
                <span>{{ lang.label }}</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
