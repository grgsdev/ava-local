<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>

    <Head title="Bem-vindo" />

    <GuestLayout>
        <div class="w-full max-w-md p-8 space-y-8 text-center shadow-2xl bg-card text-card-foreground rounded-2xl">

            <div>
                <h2 class="text-3xl font-extrabold tracking-tight">
                    Sua jornada começa aqui.
                </h2>
                <p class="mt-2 text-card-foreground">
                    Acesse sua conta para começar.
                </p>
            </div>

            <div v-if="$page.props.auth.user" class="flex flex-col space-y-4">
                <Button as-child size="lg">
                    <Link :href="route('dashboard')">
                    {{ $t('Dashboard') }}
                    </Link>
                </Button>
            </div>

            <div v-else class="flex flex-col space-y-4">
                <Button as-child size="lg">
                    <Link v-if="canLogin" :href="route('login')">
                    {{ $t('Log in') }}
                    </Link>
                </Button>

                <!-- <Button as-child variant="secondary" size="lg">
                    <Link v-if="canRegister" :href="route('register')">
                    {{ $t('Register') }}
                    </Link>
                </Button> -->
            </div>
        </div>
    </GuestLayout>
</template>
