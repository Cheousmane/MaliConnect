<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LogOut, Palette, UserRound } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="flex w-full cursor-pointer items-center gap-2" :href="route('profile.edit')" as="button">
                <UserRound class="size-4 text-muted-foreground" aria-hidden="true" />
                Mon profil
            </Link>
        </DropdownMenuItem>

        <DropdownMenuItem :as-child="true">
            <Link class="flex w-full cursor-pointer items-center gap-2" :href="route('appearance')" as="button">
                <Palette class="size-4 text-muted-foreground" aria-hidden="true" />
                Apparence
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <DropdownMenuItem :as-child="true" class="text-destructive focus:bg-destructive/10 focus:text-destructive">
        <Link class="flex w-full cursor-pointer items-center gap-2" method="post" :href="route('logout')" as="button">
            <LogOut class="size-4" aria-hidden="true" />
            Se déconnecter
        </Link>
    </DropdownMenuItem>
</template>
