<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Exerate Analytical' },
});

const page = usePage();
const mobileOpen = ref(false);

const navItems = [
    { label: 'Dashboard',     href: '/dashboard',                 auth: true },
    { label: 'Countries',     href: '/countries' },
    { label: 'Risk',          href: '/risk' },
    { label: 'Executive',     href: '/executive' },
    { label: 'Control Tower', href: '/executive/control-tower' },
    { label: 'Federation',    href: '/federation' },
    { label: 'Civic',         href: '/civic/feed' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top nav -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14">

                    <!-- Brand -->
                    <a href="/" class="flex items-center gap-2 shrink-0">
                        <span class="text-sm font-bold text-gray-900 tracking-tight">Exerate <span class="text-indigo-600">Analytical</span></span>
                    </a>

                    <!-- Desktop nav -->
                    <div class="hidden md:flex items-center gap-1">
                        <template v-for="item in navItems" :key="item.label">
                            <a
                                v-if="!item.auth || page.props.auth?.user"
                                :href="item.href"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition"
                            >
                                {{ item.label }}
                            </a>
                        </template>
                    </div>

                    <!-- Right actions -->
                    <div class="flex items-center gap-2">
                        <template v-if="page.props.auth?.user">
                            <a
                                v-if="page.props.auth.user.hasRole?.includes('SuperAdmin')"
                                href="/admin"
                                class="hidden sm:inline-flex px-3 py-1.5 text-xs font-semibold bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition"
                            >Admin</a>
                            <a href="/dashboard" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                                {{ page.props.auth.user.name.charAt(0).toUpperCase() }}
                                <span class="hidden lg:inline">{{ page.props.auth.user.name.split(' ')[0] }}</span>
                            </a>
                        </template>
                        <template v-else>
                            <a :href="route('login')" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 transition">Log in</a>
                            <a :href="route('register')" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">Register</a>
                        </template>

                        <!-- Mobile hamburger -->
                        <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileOpen" class="md:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
                <template v-for="item in navItems" :key="`m-${item.label}`">
                    <a
                        v-if="!item.auth || page.props.auth?.user"
                        :href="item.href"
                        class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >{{ item.label }}</a>
                </template>
                <template v-if="page.props.auth?.user">
                    <a href="/admin" class="block px-3 py-2 rounded-lg text-sm font-medium text-red-700 hover:bg-red-50">Admin</a>
                </template>
                <template v-else>
                    <a :href="route('login')" class="block px-3 py-2 rounded-lg text-sm font-medium text-gray-700">Log in</a>
                    <a :href="route('register')" class="block px-3 py-2 rounded-lg text-sm font-medium text-indigo-700">Register</a>
                </template>
            </div>
        </nav>

        <!-- Page header slot -->
        <div v-if="$slots.header" class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <slot name="header" />
            </div>
        </div>

        <!-- Main content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-16 border-t border-gray-200 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-wrap items-center justify-between gap-4 text-xs text-gray-400">
                    <span>© {{ new Date().getFullYear() }} Exerate Analytical Federation Platform</span>
                    <div class="flex gap-4">
                        <a href="/civic/feed" class="hover:text-gray-600">Civic</a>
                        <a href="/countries" class="hover:text-gray-600">Countries</a>
                        <a href="/federation" class="hover:text-gray-600">Federation</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
