<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head :title="$t('Reset Password')" />

        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle>{{ $t('Reset Password') }}</CardTitle>
                <CardDescription>
                    <p class="mt-2 text-card-foreground">
                        {{ $t('Choose a new, strong password to secure your account.') }}
                    </p>


                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="email">{{ $t('Email') }}</Label>
                        <Input id="email" type="email" v-model="form.email" required autofocus
                            autocomplete="username" />
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

                    <div class="flex items-center justify-end pt-2">
                        <Button :disabled="form.processing">
                            {{ $t('Reset Password') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
