<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin:       { type: Boolean },
    canRegister:    { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion:     { type: String, required: true },
});

const modules = [
    {
        title:       'Countries',
        description: 'Browse all federation member states. View risk tiers, governance scores, regional data, and institutional profiles.',
        href:        '/countries',
        icon:        '🌍',
        color:       'indigo',
    },
    {
        title:       'Risk Intelligence',
        description: 'Real-time national risk rankings, domain drivers (governance, fiscal, accountability), and alert watchlist.',
        href:        '/risk',
        icon:        '⚡',
        color:       'red',
    },
    {
        title:       'Executive Dashboard',
        description: 'Strategic intelligence briefings, network rankings, governance metrics, and alert acknowledgement tools.',
        href:        '/executive',
        icon:        '📊',
        color:       'violet',
    },
    {
        title:       'Federation Overview',
        description: 'Global and regional federated snapshots, systemic risk indicators, and cross-border exposure analysis.',
        href:        '/federation',
        icon:        '🔗',
        color:       'blue',
    },
    {
        title:       'Civic Participation',
        description: 'Polls, petitions, and policy proposals. Build trust through active civic engagement within the federation.',
        href:        '/civic/feed',
        icon:        '🗳️',
        color:       'emerald',
    },
    {
        title:       'Transparency',
        description: 'Methodology versions, indicator weights, reliability assessments, publications, and data quality reports.',
        href:        '/countries',
        icon:        '📋',
        color:       'amber',
    },
];

const colorMap = {
    indigo:  { bg: 'bg-indigo-50',  border: 'border-indigo-200',  icon: 'bg-indigo-100',  text: 'text-indigo-700',  btn: 'bg-indigo-600 hover:bg-indigo-700' },
    red:     { bg: 'bg-red-50',     border: 'border-red-200',     icon: 'bg-red-100',     text: 'text-red-700',     btn: 'bg-red-600 hover:bg-red-700' },
    violet:  { bg: 'bg-violet-50',  border: 'border-violet-200',  icon: 'bg-violet-100',  text: 'text-violet-700',  btn: 'bg-violet-600 hover:bg-violet-700' },
    blue:    { bg: 'bg-blue-50',    border: 'border-blue-200',    icon: 'bg-blue-100',    text: 'text-blue-700',    btn: 'bg-blue-600 hover:bg-blue-700' },
    emerald: { bg: 'bg-emerald-50', border: 'border-emerald-200', icon: 'bg-emerald-100', text: 'text-emerald-700', btn: 'bg-emerald-600 hover:bg-emerald-700' },
    amber:   { bg: 'bg-amber-50',   border: 'border-amber-200',   icon: 'bg-amber-100',   text: 'text-amber-700',   btn: 'bg-amber-600 hover:bg-amber-700' },
};
</script>

<template>
    <Head title="Exerate Analytical — Federation Intelligence Platform" />

    <div class="min-h-screen bg-gray-50">

        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                <a href="/" class="font-bold text-gray-900 text-sm tracking-tight">
                    Exerate <span class="text-indigo-600">Analytical</span>
                </a>
                <div class="flex items-center gap-2">
                    <template v-if="$page.props.auth?.user">
                        <a :href="route('dashboard')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Dashboard
                        </a>
                    </template>
                    <template v-else>
                        <a v-if="canLogin" :href="route('login')" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 transition">Log in</a>
                        <a v-if="canRegister" :href="route('register')" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                            Register
                        </a>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <section class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
                <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-indigo-200 mb-6">
                    Federation Intelligence Platform
                </div>
                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
                    Exerate Analytical
                </h1>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto mb-8 leading-relaxed">
                    A comprehensive federation-wide platform for governance risk intelligence, civic participation,
                    executive analytics, and transparency reporting.
                </p>
                <div class="flex flex-wrap gap-3 justify-center">
                    <a href="/countries" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                        Explore Countries
                    </a>
                    <a href="/risk" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-sm transition">
                        Risk Dashboard
                    </a>
                    <a href="/civic/feed" class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-sm transition">
                        Civic Feed
                    </a>
                </div>
            </div>
        </section>

        <!-- Platform Modules Grid -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-2">Platform Modules</h2>
            <p class="text-gray-500 text-center mb-10">Six integrated analytical pillars powering federation intelligence</p>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a
                    v-for="mod in modules"
                    :key="mod.title"
                    :href="mod.href"
                    :class="[
                        'block rounded-2xl border p-6 hover:shadow-md transition group',
                        colorMap[mod.color].bg,
                        colorMap[mod.color].border,
                    ]"
                >
                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4', colorMap[mod.color].icon]">
                        {{ mod.icon }}
                    </div>
                    <h3 :class="['text-lg font-bold mb-2 group-hover:underline', colorMap[mod.color].text]">{{ mod.title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ mod.description }}</p>
                </a>
            </div>
        </section>

        <!-- Pillars Section -->
        <section class="bg-white border-t border-gray-200 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Five Analytical Pillars</h2>
                <p class="text-gray-500 mb-10">Every country is assessed across five core dimensions</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div v-for="pillar in ['Governance','Fiscal','Development','Civic','Accountability']" :key="pillar"
                        class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <p class="text-sm font-semibold text-gray-800">{{ pillar }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center" v-if="!$page.props.auth?.user">
            <div class="bg-indigo-600 rounded-2xl p-10 text-white">
                <h2 class="text-2xl font-bold mb-3">Join the Federation</h2>
                <p class="text-indigo-200 mb-6">Create an account to participate in civic activities, vote on polls, sign petitions, and submit policy proposals.</p>
                <div class="flex gap-3 justify-center">
                    <a v-if="canRegister" :href="route('register')" class="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-xl hover:bg-indigo-50 transition">
                        Create Account
                    </a>
                    <a v-if="canLogin" :href="route('login')" class="px-6 py-3 border border-indigo-400 text-white font-semibold rounded-xl hover:bg-indigo-700 transition">
                        Log In
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-400">
                <span>© {{ new Date().getFullYear() }} Exerate Analytical Federation Platform</span>
                <div class="flex gap-4">
                    <a href="/countries" class="hover:text-gray-600">Countries</a>
                    <a href="/risk" class="hover:text-gray-600">Risk</a>
                    <a href="/executive" class="hover:text-gray-600">Executive</a>
                    <a href="/federation" class="hover:text-gray-600">Federation</a>
                    <a href="/civic/feed" class="hover:text-gray-600">Civic</a>
                </div>
            </div>
        </footer>

    </div>
</template>
