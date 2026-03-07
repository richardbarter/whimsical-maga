<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ChevronDown, LogOut, Menu, User, X } from 'lucide-vue-next';
import type { PageProps } from '@/types';

interface NavLink {
    label: string;
    routeName: string;
    pattern: string;
}

const FLASH_DURATION_MS = 4000;

const navLinks: NavLink[] = [
    { label: 'Dashboard', routeName: 'admin.dashboard', pattern: 'admin.dashboard' },
    { label: 'Quotes', routeName: 'admin.quotes.index', pattern: 'admin.quotes.*' },
    { label: 'Backgrounds', routeName: 'admin.backgrounds.index', pattern: 'admin.backgrounds.*' },
    { label: 'Tags', routeName: 'admin.tags.index', pattern: 'admin.tags.*' },
    { label: 'Categories', routeName: 'admin.categories.index', pattern: 'admin.categories.*' },
];

const showingNavigationDropdown = ref(false);
const page = usePage<PageProps>();
const flash = ref<{ success?: string; error?: string }>({});
let flashTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => page.props.flash,
    (newFlash) => {
        flash.value = { ...newFlash };
        if (flashTimeout) clearTimeout(flashTimeout);
        if (newFlash.success || newFlash.error) {
            flashTimeout = setTimeout(() => {
                flash.value = {};
            }, FLASH_DURATION_MS);
        }
    },
    { immediate: true },
);

router.on('navigate', () => { showingNavigationDropdown.value = false; });
</script>

<template>
    <div class="min-h-screen bg-background">

        <!-- Top nav -->
        <nav class="border-b border-border bg-card">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">

                    <!-- Left: brand + nav links -->
                    <div class="flex items-center gap-8">
                        <Link
                            :href="route('admin.dashboard')"
                            class="text-xl font-bold text-primary"
                        >
                            Whimsical MAGA
                        </Link>

                        <div class="hidden items-center gap-1 sm:flex">
                            <Link
                                v-for="link in navLinks"
                                :key="link.routeName"
                                :href="route(link.routeName)"
                                :class="[
                                    'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                    route().current(link.pattern)
                                        ? 'bg-primary/10 text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                ]"
                            >
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>

                    <!-- Right: user dropdown (desktop) -->
                    <div class="hidden sm:flex">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="sm">
                                    {{ $page.props.auth.user.name }}
                                    <ChevronDown class="ml-1 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem as-child>
                                    <Link :href="route('profile.edit')" class="flex w-full items-center">
                                        <User class="mr-2 h-4 w-4" />
                                        Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="flex w-full items-center text-destructive focus:text-destructive"
                                    >
                                        <LogOut class="mr-2 h-4 w-4" />
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <!-- Hamburger (mobile) -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="sm:hidden"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <X v-if="showingNavigationDropdown" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </Button>

                </div>
            </div>

            <!-- Mobile menu -->
            <div
                v-if="showingNavigationDropdown"
                class="border-t border-border bg-card px-4 pb-4 pt-2 sm:hidden"
            >
                <div class="space-y-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.routeName"
                        :href="route(link.routeName)"
                        :class="[
                            'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            route().current(link.pattern)
                                ? 'bg-primary/10 text-primary'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                </div>

                <div class="mt-3 border-t border-border pt-3">
                    <p class="px-3 text-sm font-medium">{{ $page.props.auth.user.name }}</p>
                    <p class="px-3 text-xs text-muted-foreground">{{ $page.props.auth.user.email }}</p>
                    <div class="mt-2 space-y-1">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <User class="mr-2 h-4 w-4" />
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex w-full items-center rounded-md px-3 py-2 text-sm text-destructive transition-colors hover:bg-destructive/10"
                        >
                            <LogOut class="mr-2 h-4 w-4" />
                            Log out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page heading slot -->
        <header v-if="$slots.header" class="border-b border-border bg-card">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Flash messages -->
        <div
            v-if="flash.success || flash.error"
            class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8"
        >
            <div
                v-if="flash.success"
                class="rounded-lg border border-secondary/50 bg-secondary/20 px-4 py-3 text-sm text-secondary-foreground"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            >
                {{ flash.error }}
            </div>
        </div>

        <!-- Page content -->
        <main>
            <slot />
        </main>

    </div>
</template>
