<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
const page = usePage();
</script>

<template>
    <div class="flex min-h-screen antialiased">
        <div class="relative items-center justify-center flex-1 hidden w-0 lg:flex">
            <div class="w-full max-w-md p-12 text-primary-foreground">
                <div class="flex justify-center">
                    <Link href="/">
                    <img v-if="$page.props.platformSettings?.logo_web_url"
                        :src="$page.props.platformSettings.logo_web_url" alt="Logo"
                        class="block object-contain w-auto h-40" />
                    <ApplicationLogo v-else class="w-auto h-40 fill-current text-primary-foreground" />
                    </Link>
                </div>
                <div class="mt-8">
                    <h1 class="text-4xl font-extrabold tracking-tight text-indigo-500">
                        Centralize o fluxo.
                    </h1>
                    <p class="mt-4 text-lg text-indigo-500">
                        Bem-vindo ao <span class="text-2xl text-primary">{{ $page.props.appName }}</span>.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center flex-1 w-full lg:w-1/2 bg-background">
            <div class="w-full max-w-md p-6">
                <slot />
            </div>
        </div>
    </div>
</template>
