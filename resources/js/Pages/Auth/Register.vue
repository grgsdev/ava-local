<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head :title="$t('Register')" />

        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle>{{ $t('Create an account') }}</CardTitle>
                <CardDescription>
                    <p class="mt-2 text-card-foreground">
                        Insira seus dados abaixo para criar sua conta no {{ $page.props.appName }}.
                    </p>


                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="name">{{ $t('Name') }}</Label>
                        <Input id="name" type="text" v-model="form.name" required autofocus autocomplete="name" />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email">{{ $t('Email') }}</Label>
                        <Input id="email" type="email" v-model="form.email" required autocomplete="username" />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password">{{ $t('Password') }}</Label>
                        <Input id="password" type="password" v-model="form.password" required
                            autocomplete="new-password" />
                        <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="password_confirmation">{{ $t('Confirm Password') }}</Label>
                        <Input id="password_confirmation" type="password" v-model="form.password_confirmation" required
                            autocomplete="new-password" />
                        <p v-if="form.errors.password_confirmation" class="text-sm text-red-500">{{
                            form.errors.password_confirmation }}</p>
                    </div>

                    <div class="flex flex-col items-center justify-end pt-4 space-y-4">
                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ $t('Register') }}
                        </Button>

                        <!-- <Link :href="route('login')" class="text-sm underline">
                        {{ $t('Already registered?') }}
                        </Link> -->
                    </div>
                </form>
            </CardContent>
            <CardFooter class="flex flex-col items-center pt-6 space-y-2">
                <p class="text-sm text-center text-card-foreground">
                    {{ $t("Have an account?") }}
                    <Link :href="route('login')" class="underline hover:text-primary">
                    {{ $t('Log in') }}
                    </Link>
                </p>
            </CardFooter>
        </Card>
    </GuestLayout>
</template>
