<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, LayoutGrid, LogOut, Shield, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navClasses = (component: string) => [
    'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
    page.component === component ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900',
];
</script>

<template>
    <TooltipProvider>
        <div class="min-h-screen bg-[#fafafa]">
            <Toaster position="top-right" richColors />

            <!-- Top bar -->
            <header class="sticky top-0 z-50 w-full border-b border-gray-100 bg-white">
                <div class="mx-auto flex h-14 max-w-[1200px] items-center justify-between px-6">
                    <!-- Left: logo + nav -->
                    <div class="flex items-center gap-8">
                        <Link href="/" class="flex items-center transition-opacity hover:opacity-70">
                            <img src="/images/log-ltr-dark.png" class="h-[22px] w-auto" alt="WeblexAI" />
                        </Link>

                        <nav class="hidden items-center gap-1 md:flex">
                            <Link :href="route('projects.index')" prefetch :class="navClasses('Projects')">
                                <LayoutGrid :size="15" class="opacity-60" />
                                Projects
                            </Link>
                            <a href="/admin" target="_blank" rel="noopener" :class="navClasses('Admin')">
                                <Shield :size="15" class="opacity-60" />
                                Admin
                            </a>
                        </nav>
                    </div>

                    <!-- Right: user -->
                    <DropdownMenu>
                        <DropdownMenuTrigger class="focus:outline-none">
                            <div class="flex cursor-pointer items-center gap-2 rounded-full p-1 pr-2.5 transition-colors hover:bg-gray-50">
                                <Avatar class="h-7 w-7">
                                    <AvatarFallback class="bg-gray-100 text-[10px] font-semibold text-gray-600">
                                        {{ user.name.substring(0, 2).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="hidden text-[13px] font-medium text-gray-700 sm:block">{{ user.name }}</span>
                                <ChevronDown :size="12" class="text-gray-400" />
                            </div>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-52 p-1.5">
                            <DropdownMenuLabel class="px-2 py-1.5 font-normal">
                                <div class="flex flex-col">
                                    <p class="text-[13px] font-medium text-gray-900">{{ user.name }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-400">{{ user.email }}</p>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <Link :href="route('profile')">
                                    <DropdownMenuItem class="cursor-pointer rounded-md text-[13px]">
                                        <UserIcon class="mr-2 h-3.5 w-3.5 opacity-60" />
                                        Profile
                                    </DropdownMenuItem>
                                </Link>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <Link :href="route('logout', undefined, false)" method="post" as="button" class="w-full">
                                <DropdownMenuItem class="cursor-pointer rounded-md text-[13px] text-red-600 hover:!bg-red-50 hover:!text-red-700">
                                    <LogOut class="mr-2 h-3.5 w-3.5 opacity-60" />
                                    Log out
                                </DropdownMenuItem>
                            </Link>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Content -->
            <main class="mx-auto max-w-[1200px] px-6 py-8">
                <slot />
            </main>
        </div>
    </TooltipProvider>
</template>
