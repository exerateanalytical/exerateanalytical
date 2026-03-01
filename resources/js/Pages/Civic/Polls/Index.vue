<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
// Link is used for both navigation and pagination to preserve Inertia's SPA behaviour
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import EmptyState from '@/Components/Civic/EmptyState.vue';

const props = defineProps({
    polls:   { type: Object, default: () => ({ data: [] }) },
    regions: { type: Array,  default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const regionId = ref(props.filters.region_id ?? '');
const status   = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(route('civic.polls.index'), {
        region_id: regionId.value || undefined,
        status:    status.value   || undefined,
    }, { preserveState: true, replace: true });
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout title="Polls">
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Polls</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Community polls across the federation.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="route('civic.polls.templates')"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium transition">
                        Browse templates
                    </a>
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('civic.polls.create')"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm"
                    >
                        + Create Poll
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Filters -->
            <div class="bg-white border border-gray-200 rounded-xl px-4 py-3 flex flex-wrap gap-3 items-center shadow-sm">
                <select v-model="regionId" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All regions</option>
                    <option v-for="r in regions" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <select v-model="status" @change="applyFilters" class="text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                    <option value="under_review">Under Review</option>
                </select>
                <span class="ms-auto text-xs text-gray-400">{{ polls.total ?? polls.data.length }} poll{{ (polls.total ?? polls.data.length) !== 1 ? 's' : '' }}</span>
            </div>

            <!-- Grid -->
            <div v-if="polls.data.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="poll in polls.data"
                    :key="poll.id"
                    :href="route('civic.polls.show', poll.id)"
                    class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-blue-200 transition group"
                >
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <StatusPill :status="poll.status" />
                        <span class="text-xs text-gray-400 shrink-0">{{ formatDate(poll.created_at) }}</span>
                    </div>

                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-700 transition line-clamp-2 mb-3">
                        {{ poll.title }}
                    </h3>

                    <div class="flex items-center justify-between text-xs text-gray-500 mt-auto">
                        <span>{{ poll.total_votes }} votes</span>
                        <TierBadge v-if="poll.creator?.reputation_tier" :tier="poll.creator.reputation_tier" />
                    </div>

                    <div v-if="poll.region" class="mt-2 text-xs text-gray-400">{{ poll.region.name }}</div>
                </Link>
            </div>

            <EmptyState
                v-else
                title="No polls found"
                description="No polls match your current filters."
                :action-label="$page.props.auth?.user ? 'Create a Poll' : null"
                :action-href="$page.props.auth?.user ? route('civic.polls.create') : null"
            />

            <!-- Pagination -->
            <div v-if="polls.last_page > 1" class="flex justify-center gap-1">
                <Link
                    v-for="link in polls.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    preserve-scroll
                    :class="[
                        'px-3 py-1.5 text-sm rounded-lg border transition',
                        link.active
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>
        </div>
    </CivicLayout>
</template>
