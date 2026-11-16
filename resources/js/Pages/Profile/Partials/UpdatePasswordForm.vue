<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">{{ $t('Update Password') }}</h2>
            <p class="mt-1 text-sm text-card-foreground">
                {{ $t('Ensure your account is using a long, random password to stay secure.') }}
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div class="space-y-1.5">
                <Label for="current_password">{{ $t('Current Password') }}</Label>
                <Input id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password"
                    autocomplete="current-password" />
                <p v-if="form.errors.current_password" class="text-sm text-red-500">{{ form.errors.current_password }}
                </p>
            </div>

            <div class="space-y-1.5">
                <Label for="password">{{ $t('New Password') }}</Label>
                <Input id="password" ref="passwordInput" v-model="form.password" type="password"
                    autocomplete="new-password" />
                <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
            </div>

            <div class="space-y-1.5">
                <Label for="password_confirmation">{{ $t('Confirm Password') }}</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password"
                    autocomplete="new-password" />
                <p v-if="form.errors.password_confirmation" class="text-sm text-red-500">{{
                    form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-4">
                <Button class="text-card-foreground" :disabled="form.processing">{{ $t('Save')
                    }}</Button>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">{{ $t('Saved.') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
