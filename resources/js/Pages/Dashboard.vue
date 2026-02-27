<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/Civic/StatCard.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import BadgeChip from '@/Components/Civic/BadgeChip.vue';

const props = defineProps({
    stats:     { type: Object, default: () => ({}) },
    recent:    { type: Array,  default: () => [] },
    userTrust: { type: Object, default: null },
});

const typeLabel = { poll: 'Poll', petition: 'Petition', policy: 'Policy' };
const typeColor  = {
    poll:     'bg-blue-100 text-blue-700',
    petition: 'bg-emerald-100 text-emerald-700',
    policy:   'bg-violet-100 text-violet-700',
};

const quickLinks = [
    { label: 'Civic Feed',       href: route('civic.feed'),            color: 'bg-indigo-600' },
    { label: 'Polls',            href: route('civic.polls.index'),     color: 'bg-blue-600'   },
    { label: 'Petitions',        href: route('civic.petitions.index'), color: 'bg-emerald-600'},
    { label: 'Policy Proposals', href: route('civic.policies.index'),  color: 'bg-violet-600' },
];

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
    : '';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-7">

                <!-- Quick navigation pills -->
                <div class="flex flex-wrap gap-2">
                    <a
                        v-for="ql in quickLinks"
                        :key="ql.label"
                        :href="ql.href"
                        :class="['px-4 py-2 rounded-lg text-white text-sm font-semibold hover:opacity-90 transition shadow-sm', ql.color]"
                    >{{ ql.label }}</a>
                </div>

                <!-- Platform stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard label="Active Polls"      :value="stats.polls     ?? 0" color="blue"    :href="route('civic.polls.index')" />
                    <StatCard label="Open Petitions"    :value="stats.petitions ?? 0" color="emerald"  :href="route('civic.petitions.index')" />
                    <StatCard label="Policy Proposals"  :value="stats.policies  ?? 0" color="violet"   :href="route('civic.policies.index')" />
                    <StatCard label="Platform Members"  :value="stats.users     ?? 0" color="indigo" />
                </div>

                <!-- Main grid -->
                <div class="grid lg:grid-cols-3 gap-6">

                    <!-- Recent activity (2/3) -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">Recent Civic Activity</h3>
                            <Link :href="route('civic.feed')" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                Full feed →
                            </Link>
                        </div>

                        <div v-if="recent.length" class="divide-y divide-gray-50">
                            <a
                                v-for="item in recent"
                                :key="item.id"
                                :href="item.url"
                                class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition group"
                            >
                                <span :class="['shrink-0 text-xs font-bold px-2 py-0.5 rounded', typeColor[item.type] ?? 'bg-gray-100 text-gray-600']">
                                    {{ typeLabel[item.type] ?? item.type }}
                                </span>
                                <p class="flex-1 text-sm font-medium text-gray-800 truncate group-hover:text-indigo-700 transition">
                                    {{ item.title }}
                                </p>
                                <span class="shrink-0 text-xs text-gray-400">{{ formatDate(item.created_at) }}</span>
                                <StatusPill :status="item.status" />
                            </a>
                        </div>
                        <div v-else class="py-12 text-center text-sm text-gray-400">No civic activity yet.</div>
                    </div>

                    <!-- Side column (1/3) -->
                    <div class="space-y-4">

                        <!-- Trust card -->
                        <div v-if="userTrust" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800">Your Trust Profile</h3>
                                <TierBadge :tier="userTrust.tier" />
                            </div>
                            <div v-if="userTrust.badges.length" class="flex flex-wrap gap-2">
                                <BadgeChip
                                    v-for="b in userTrust.badges"
                                    :key="b.code"
                                    :code="b.code"
                                    :awarded-at="b.awarded_at"
                                />
                            </div>
                            <p v-else class="text-xs text-gray-400">No badges yet — start participating!</p>
                            <a :href="userTrust.trust_url" class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                View full profile →
                            </a>
                        </div>

                        <!-- Create shortcuts -->
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                            <h3 class="font-semibold text-gray-800 mb-3">Create</h3>
                            <div class="space-y-2">
                                <Link :href="route('civic.polls.create')"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition text-gray-700">
                                    <span class="text-blue-600 font-bold">+</span> New Poll
                                </Link>
                                <Link :href="route('civic.petitions.create')"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm rounded-lg border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition text-gray-700">
                                    <span class="text-emerald-600 font-bold">+</span> New Petition
                                </Link>
                                <Link :href="route('civic.policies.create')"
                                    class="flex items-center gap-2 w-full px-3 py-2 text-sm rounded-lg border border-gray-200 hover:border-violet-300 hover:bg-violet-50 transition text-gray-700">
                                    <span class="text-violet-600 font-bold">+</span> Policy Proposal
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
