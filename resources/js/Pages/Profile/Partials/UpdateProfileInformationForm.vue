<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AvatarInput from '@/Components/AvatarInput.vue'; // <-- Nosso componente de Avatar!

defineProps<{ mustVerifyEmail?: Boolean; status?: String; }>();

const user = usePage<PageProps>().props.auth.user;

const form = useForm({
    _method: 'patch', // Necessário para upload de arquivo com PUT/PATCH
    name: user.name,
    email: user.email,
    avatar: null as File | null,
});

const submit = () => {
    form.post(route('profile.update'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium">Informações do Perfil</h2>
            <p class="mt-1 text-sm text-card-foreground">
                Atualize as informações do seu perfil e endereço de e-mail.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="flex flex-col items-center space-y-1.5">
                <Label>Avatar</Label>
                <AvatarInput v-model="form.avatar" :default-src="user.avatar_url ?? undefined" />
                <p v-if="form.errors.avatar" class="text-sm text-red-500">{{ form.errors.avatar }}</p>
            </div>

            <div class="space-y-1.5">
                <Label for="name">Nome</Label>
                <Input id="name" type="text" v-model="form.name" required autofocus />
                <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <div class="space-y-1.5">
                <Label for="email">Email</Label>
                <Input id="email" type="email" v-model="form.email" required />
                <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
            </div>

            <div class="flex items-center gap-4">
                <Button class="text-card-foreground" :disabled="form.processing">{{ $t('Save') }}</Button>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Salvo.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
