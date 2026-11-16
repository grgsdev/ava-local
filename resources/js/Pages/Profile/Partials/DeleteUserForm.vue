<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';


const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};

const lastChance = 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.';
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium">{{ $t('Delete Account') }}</h2>
            <p class="mt-1 text-sm text-card-foreground">
                {{ $t('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
            </p>
        </header>

        <AlertDialog :open="confirmingUserDeletion" @update:open="confirmingUserDeletion = $event">
            <AlertDialogTrigger as-child>
                <Button variant="destructive">{{ $t('Delete Account') }}</Button>
            </AlertDialogTrigger>
            <AlertDialogContent class="bg-card text-card-foreground">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ $t('Are you sure you want to delete your account?') }}</AlertDialogTitle>
                    <AlertDialogDescription class="text-card-foreground">
                        {{ $t(lastChance) }}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="py-4 space-y-1.5">
                    <Label for="password" class="sr-only">{{ $t('Password') }}</Label>
                    <Input id="password" ref="passwordInput" v-model="form.password" type="password"
                        placeholder="Password" @keyup.enter="deleteUser" />
                    <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeModal">{{ $t('Cancel') }}</AlertDialogCancel>
                    <AlertDialogAction @click="deleteUser" :disabled="form.processing"
                        class="text-card-foreground bg-destructive hover:bg-destructive/90">
                        {{ $t('Delete Account') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </section>
</template>
