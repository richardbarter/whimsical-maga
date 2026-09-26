<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { ChevronDown, LogOut, Menu, User, X } from 'lucide-vue-next';

/**
 * Layout for signed-in users who are not admins (admins get AdminLayout).
 */
const showingNavigationDropdown = ref(false);

// Layouts remount on every visit here, so the listener must be removed each time.
const removeNavigateListener = router.on('navigate', () => { showingNavigationDropdown.value = false; });

onUnmounted(removeNavigateListener);
</script>

<template>
    <div class="min-h-screen bg-background">
        <nav class="border-b border-border bg-card">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <Link :href="route('home')" class="text-xl font-bold text-primary">
                            Whimsical MAGA
                        </Link>

                        <Link
                            :href="route('home')"
                            class="hidden rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground sm:block"
                        >
                            Home
                        </Link>
                    </div>

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

                    <Button
                        variant="ghost"
                        size="icon"
                        class="sm:hidden"
                        :aria-label="showingNavigationDropdown ? 'Close menu' : 'Open menu'"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <X v-if="showingNavigationDropdown" class="h-5 w-5" />
                        <Menu v-else class="h-5 w-5" />
                    </Button>
                </div>
            </div>

            <div v-if="showingNavigationDropdown" class="border-t border-border bg-card px-4 pb-4 pt-2 sm:hidden">
                <Link
                    :href="route('home')"
                    class="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    Home
                </Link>

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

        <header v-if="$slots.header" class="border-b border-border bg-card">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
