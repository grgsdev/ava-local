<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head :title="$t('Confirm Password')" />

        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle>{{ $t('Confirm Password') }}</CardTitle>
                <CardDescription>
                    {{ $t('This is a secure area of the application. Please confirm your password before continuing.')
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="password">{{ $t('Password') }}</Label>
                        <Input id="password" type="password" v-model="form.password" required
                            autocomplete="current-password" autofocus />
                        <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-end">
                        <Button :disabled="form.processing">
                            {{ $t('Confirm') }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
