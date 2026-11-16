<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
const wesendemail = 'No problem. Just let us know your email address and we will email you a password reset link.';
</script>

<template>
    <GuestLayout>

        <Head :title="$t('Forgot Password')" />

        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle>{{ $t('Forgot your password?') }}</CardTitle>
                <CardDescription>
                    <p class="mt-2 text-card-foreground">
                        {{
                            $t(wesendemail)
                        }}
                    </p>

                </CardDescription>


            </CardHeader>
            <CardContent>
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-1.5">
                        <Label for="email">{{ $t('Email') }}</Label>
                        <Input id="email" type="email" v-model="form.email" required autofocus
                            autocomplete="username" />
                        <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ $t('Email Password Reset Link') }}
                    </Button>
                </form>
            </CardContent>
            <CardFooter class="flex justify-center pt-6">


                <p class="text-sm text-center text-card-foreground">

                    <Link :href="route('login')" class="underline hover:text-primary">
                    {{ $t('Back to login') }}
                    </Link>
                </p>

            </CardFooter>
        </Card>
    </GuestLayout>
</template>
