<script setup>
import CivicLayout from '@/Layouts/CivicLayout.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import BadgeChip from '@/Components/Civic/BadgeChip.vue';
import StatCard from '@/Components/Civic/StatCard.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';

defineProps({
    profile:       { type: Object, required: true },
    participation: { type: Object, required: true },
    activity:      { type: Array,  default: () => [] },
});

const activityRoute = (item) => {
    if (item.type === 'poll')     return route('civic.polls.show',     item.id);
    if (item.type === 'petition') return route('civic.petitions.show', item.id);
    if (item.type === 'policy')   return route('civic.policies.show',  item.id);
    return '#';
};

const activityTypeLabel = { poll: 'Poll', petition: 'Petition', policy: 'Policy' };

const activityTypeColor = {
    poll:     'bg-blue-100 text-blue-700',
    petition: 'bg-emerald-100 text-emerald-700',
    policy:   'bg-violet-100 text-violet-700',
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '';

const tierOrder = ['Citizen', 'Contributor', 'Trusted', 'Steward', 'Institutional'];

const tierDescription = {
    Citizen:       'A member of the civic platform. Every journey starts here.',
    Contributor:   'Actively contributing to polls, petitions and policy discussions.',
    Trusted:       'A recognised voice within the federation community.',
    Steward:       'A respected leader shaping civic discourse.',
    Institutional: 'An anchor of the federation with the highest level of trust.',
};
</script>

<template>
    <CivicLayout :title="`${profile.name} — Trust Profile`">
        <template #header>
            <div class="flex items-center gap-3">
                <a :href="route('civic.feed')" class="text-sm text-gray-500 hover:text-gray-700">← Civic</a>
                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-700 font-medium">Trust Profile</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Profile header -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col sm:flex-row items-start sm:items-center gap-6">
                <!-- Avatar initial -->
                <div class="shrink-0 w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-2xl font-bold select-none">
                    {{ profile.name.charAt(0).toUpperCase() }}
                </div>

                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900">{{ profile.name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <TierBadge :tier="profile.reputation_tier" />
                        <span class="text-xs text-gray-400">
                            {{ tierDescription[profile.reputation_tier] ?? '' }}
                        </span>
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-3xl font-bold text-gray-800">{{ participation.total }}</p>
                    <p class="text-xs text-gray-400">total actions</p>
                </div>
            </div>

            <!-- Participation breakdown -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <StatCard label="Votes Cast"         :value="participation.votes"      color="blue"    />
                <StatCard label="Petitions Signed"   :value="participation.signatures" color="emerald" />
                <StatCard label="Petitions Created"  :value="participation.petitions"  color="indigo"  />
                <StatCard label="Policy Proposals"   :value="participation.policies"   color="violet"  />
            </div>

            <!-- Badges -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Badges</h2>
                <div v-if="profile.badges.length" class="flex flex-wrap gap-3">
                    <BadgeChip
                        v-for="b in profile.badges"
                        :key="b.code"
                        :code="b.code"
                        :awarded-at="b.awarded_at"
                    />
                </div>
                <p v-else class="text-sm text-gray-400">No badges awarded yet.</p>
            </div>

            <!-- Recent activity -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Recent Activity</h2>
                <div v-if="activity.length" class="divide-y divide-gray-100">
                    <a
                        v-for="item in activity"
                        :key="`${item.type}-${item.id}`"
                        :href="activityRoute(item)"
                        class="flex items-center gap-3 py-3 hover:bg-gray-50 -mx-2 px-2 rounded-lg transition group"
                    >
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full shrink-0', activityTypeColor[item.type]]">
                            {{ activityTypeLabel[item.type] }}
                        </span>
                        <span class="flex-1 text-sm text-gray-700 group-hover:text-indigo-700 transition truncate">
                            {{ item.title }}
                        </span>
                        <span class="shrink-0 flex items-center gap-1.5">
                            <StatusPill :status="item.status" />
                            <span class="text-xs text-gray-400">{{ formatDate(item.created_at) }}</span>
                        </span>
                    </a>
                </div>
                <p v-else class="text-sm text-gray-400">No created content yet.</p>
            </div>

            <!-- Trust tier ladder -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-5">Trust Tiers</h2>
                <div class="space-y-3">
                    <div
                        v-for="tier in tierOrder"
                        :key="tier"
                        :class="[
                            'flex items-center gap-3 p-3 rounded-lg transition',
                            tier === profile.reputation_tier ? 'bg-indigo-50 border border-indigo-200' : 'bg-gray-50',
                        ]"
                    >
                        <TierBadge :tier="tier" />
                        <span class="text-sm text-gray-600 flex-1">{{ tierDescription[tier] }}</span>
                        <span v-if="tier === profile.reputation_tier" class="text-xs text-indigo-600 font-semibold">Current</span>
                    </div>
                </div>
            </div>

        </div>
    </CivicLayout>
</template>
