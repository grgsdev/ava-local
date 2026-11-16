<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox'; // <-- shadcn Checkbox
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{ canResetPassword?: boolean; status?: string; }>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head :title="$t('Log in')" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>
        <Card class="w-full max-w-md mx-auto shadow-2xl bg-card text-card-foreground rounded-2xl">
            <CardHeader class="text-center">
                <CardTitle>{{ $t('Log in') }}</CardTitle>
                <CardDescription>
                    <p class="mt-2 text-card-foreground">
                        Bem-vindo de volta! Faça login na sua conta.
                    </p>
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="email">{{ $t('Email') }}</Label>
                        <Input id="email" type="email" v-model="form.email" required autofocus
                            autocomplete="username" />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password">{{ $t('Password') }}</Label>
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="text-sm underline hover:text-primary">
                            {{ $t('Forgot your password?') }}
                            </Link>
                        </div>
                        <Input id="password" type="password" v-model="form.password" required
                            autocomplete="current-password" />
                        <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox id="remember" v-model:checked="form.remember" />
                        <Label for="remember" class="font-normal">{{ $t('Remember me') }}</Label>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ $t('Log in') }}
                    </Button>
                </form>
            </CardContent>
            <CardFooter class="flex flex-col items-center pt-6 space-y-2">
                <!-- <p class="text-sm text-center text-card-foreground">
                    {{ $t("Don't have an account?") }}
                    <Link :href="route('register')" class="underline hover:text-primary">
                    {{ $t('Register') }}
                    </Link>
                </p> -->
            </CardFooter>
        </Card>
    </GuestLayout>
</template>
