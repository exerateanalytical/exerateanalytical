<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({ title: String });

const mobileOpen = ref(false);

const logout = () => router.post(route('logout'));

const navLinks = [
    { label: 'Feed',       routeName: 'civic.feed' },
    { label: 'Polls',      routeName: 'civic.polls.index' },
    { label: 'Petitions',  routeName: 'civic.petitions.index' },
    { label: 'Policies',   routeName: 'civic.policies.index' },
];
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-gray-50">
            <!-- Top nav -->
            <nav class="bg-white border-b border-gray-200 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">

                        <!-- Left: logo + nav links -->
                        <div class="flex items-center space-x-6">
                            <Link :href="route('dashboard')" class="shrink-0 flex items-center gap-2">
                                <ApplicationMark class="h-8 w-auto" />
                                <span class="font-bold text-gray-800 text-sm tracking-tight hidden sm:block">
                                    Exerate Analytical
                                </span>
                            </Link>

                            <div class="hidden sm:flex items-center space-x-1">
                                <Link
                                    v-for="link in navLinks"
                                    :key="link.routeName"
                                    :href="route(link.routeName)"
                                    :class="[
                                        'px-3 py-2 rounded-md text-sm font-medium transition',
                                        route().current(link.routeName)
                                            ? 'bg-indigo-50 text-indigo-700'
                                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                                    ]"
                                >
                                    {{ link.label }}
                                </Link>
                            </div>
                        </div>

                        <!-- Right: auth actions -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- Authenticated user -->
                            <template v-if="$page.props.auth?.user">
                                <Link
                                    :href="route('trust.profile', $page.props.auth.user.id)"
                                    class="text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium hover:bg-indigo-100 transition"
                                >
                                    {{ $page.props.auth.user.reputation_tier ?? 'Citizen' }}
                                </Link>

                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="flex items-center text-sm text-gray-600 hover:text-gray-900 transition gap-1">
                                            <img
                                                v-if="$page.props.jetstream?.managesProfilePhotos"
                                                class="h-8 w-8 rounded-full object-cover"
                                                :src="$page.props.auth.user.profile_photo_url"
                                                :alt="$page.props.auth.user.name"
                                            />
                                            <span v-else class="font-medium">{{ $page.props.auth.user.name }}</span>
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('dashboard')">Dashboard</DropdownLink>
                                        <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                        <DropdownLink :href="route('trust.profile', $page.props.auth.user.id)">My Trust Profile</DropdownLink>
                                        <div class="border-t border-gray-100" />
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">Log Out</DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </template>

                            <!-- Guest -->
                            <template v-else>
                                <Link :href="route('login')" class="text-sm text-gray-600 hover:text-gray-900 font-medium transition">
                                    Log in
                                </Link>
                                <Link
                                    v-if="route().has('register')"
                                    :href="route('register')"
                                    class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-medium"
                                >
                                    Register
                                </Link>
                            </template>
                        </div>

                        <!-- Mobile hamburger -->
                        <div class="flex items-center sm:hidden">
                            <button @click="mobileOpen = !mobileOpen" class="p-2 rounded-md text-gray-500 hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile nav -->
                <div v-show="mobileOpen" class="sm:hidden border-t border-gray-200 pb-3">
                    <div class="pt-2 space-y-1 px-3">
                        <Link
                            v-for="link in navLinks"
                            :key="link.routeName"
                            :href="route(link.routeName)"
                            class="block px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                    <div class="mt-3 px-3 border-t border-gray-200 pt-3 space-y-1">
                        <template v-if="$page.props.auth?.user">
                            <Link :href="route('dashboard')" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">Dashboard</Link>
                            <Link :href="route('profile.show')" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">Profile</Link>
                            <form @submit.prevent="logout">
                                <button class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">Log Out</button>
                            </form>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">Log in</Link>
                            <Link v-if="route().has('register')" :href="route('register')" class="block px-3 py-2 text-sm text-indigo-600 font-medium hover:bg-indigo-50 rounded-md">Register</Link>
                        </template>
                    </div>
                </div>
            </nav>

            <!-- Page heading slot -->
            <header v-if="$slots.header" class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
