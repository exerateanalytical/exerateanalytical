<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import EmptyState from '@/Components/Civic/EmptyState.vue';

const props = defineProps({
    proposals: { type: Object, default: () => ({ data: [] }) },
    regions:   { type: Array,  default: () => [] },
    filters:   { type: Object, default: () => ({}) },
    stages:    { type: Array,  default: () => [] },
});

const regionId   = ref(props.filters.region_id ?? '');
const stageFilter = ref(props.filters.stage ?? '');

const applyFilters = () => {
    router.get(route('civic.policies.index'), {
        region_id: regionId.value   || undefined,
        stage:     stageFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const stageLabel = {
    draft:               'Draft',
    public_consultation: 'Consultation',
    revision:            'Revision',
    finalized:           'Finalized',
    archived:            'Archived',
};

const stageColor = {
    draft:               'bg-gray-100  text-gray-500',
    public_consultation: 'bg-blue-100  text-blue-700',
    revision:            'bg-yellow-100 text-yellow-700',
    finalized:           'bg-green-100 text-green-700',
    archived:            'bg-gray-100  text-gray-400',
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout title="Policy Proposals">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Policy Proposals</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Draft and community-reviewed policy proposals.</p>
                </div>
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('civic.policies.create')"
                    class="px-4 py-2 bg-violet-600 text-white text-sm font-semibold rounded-lg hover:bg-violet-700 transition shadow-sm"
                >
                    + Submit Proposal
                </Link>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap gap-3 items-center shadow-sm">
                <select v-model="stageFilter" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-violet-500 focus:border-violet-500">
                    <option value="">All stages</option>
                    <option v-for="s in stages" :key="s" :value="s">{{ stageLabel[s] ?? s }}</option>
                </select>
                <select v-model="regionId" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-violet-500 focus:border-violet-500">
                    <option value="">All regions</option>
                    <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <span class="ms-auto text-xs text-gray-400">
                    {{ proposals.total ?? proposals.data.length }} proposal{{ (proposals.total ?? proposals.data.length) !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Grid -->
            <div v-if="proposals.data.length" class="grid sm:grid-cols-2 gap-4">
                <Link
                    v-for="p in proposals.data"
                    :key="p.id"
                    :href="route('civic.policies.show', p.id)"
                    class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-violet-200 transition group"
                >
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', stageColor[p.stage] ?? 'bg-gray-100 text-gray-500']">
                            {{ stageLabel[p.stage] ?? p.stage }}
                        </span>
                        <StatusPill :status="p.status" />
                    </div>

                    <h3 class="font-semibold text-gray-900 group-hover:text-violet-700 transition line-clamp-2 mb-2">
                        {{ p.title }}
                    </h3>
                    <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ p.abstract }}</p>

                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <div v-if="p.creator" class="flex items-center gap-1.5">
                            <span>{{ p.creator.name }}</span>
                            <TierBadge :tier="p.creator.reputation_tier" />
                        </div>
                        <span>{{ formatDate(p.created_at) }}</span>
                    </div>
                </Link>
            </div>

            <EmptyState
                v-else
                title="No policy proposals found"
                description="Submit a proposal to start the community consultation process."
                :action-label="$page.props.auth?.user ? 'Submit Proposal' : null"
                :action-href="$page.props.auth?.user ? route('civic.policies.create') : null"
            />

            <!-- Pagination -->
            <div v-if="proposals.last_page > 1" class="flex justify-center gap-1">
                <a
                    v-for="link in proposals.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border transition',
                        link.active ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-gray-600 border-gray-200 hover:border-violet-300',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>
        </div>
    </CivicLayout>
</template>
