<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';

const props = defineProps({
    policy:    { type: Object,  required: true },
    stages:    { type: Array,   default: () => [] },
    nextStage: { type: String,  default: null },
    isCreator: { type: Boolean, default: false },
});

const advancing = ref(false);
const error     = ref('');

const stageLabel = {
    draft:               'Draft',
    public_consultation: 'Consultation',
    revision:            'Revision',
    finalized:           'Finalized',
    archived:            'Archived',
};

const stageColor = {
    draft:               'bg-gray-200  text-gray-500',
    public_consultation: 'bg-blue-500  text-white',
    revision:            'bg-yellow-400 text-yellow-900',
    finalized:           'bg-green-500 text-white',
    archived:            'bg-gray-300  text-gray-500',
};

const advanceStage = async () => {
    if (!props.nextStage) return;
    error.value     = '';
    advancing.value = true;
    try {
        await window.axios.patch(`/api/v1/policies/${props.policy.id}/stage`, {
            stage: props.nextStage,
        });
        router.reload();
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? 'Could not advance stage.';
    } finally {
        advancing.value = false;
    }
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout :title="policy.title">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a :href="route('civic.policies.index')" class="text-sm text-gray-500 hover:text-gray-700">← Policies</a>
                <StatusPill :status="policy.status" />
                <span v-if="policy.region" class="text-sm text-gray-500">{{ policy.region.name }}</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h1 class="text-2xl font-bold text-gray-900">{{ policy.title }}</h1>

                <!-- Stage pipeline -->
                <div class="flex items-center gap-1 flex-wrap">
                    <template v-for="(s, i) in stages" :key="s">
                        <span
                            :class="[
                                'px-3 py-1 rounded-full text-xs font-semibold transition',
                                policy.stage === s ? stageColor[s] ?? 'bg-gray-200 text-gray-600' : 'bg-gray-100 text-gray-400',
                            ]"
                        >
                            {{ stageLabel[s] ?? s }}
                        </span>
                        <svg v-if="i < stages.length - 1" class="w-3 h-3 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </template>
                </div>

                <p class="text-gray-600 leading-relaxed">{{ policy.abstract }}</p>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                    <span>Submitted {{ formatDate(policy.created_at) }}</span>
                    <div v-if="policy.creator" class="flex items-center gap-1.5">
                        <span>by {{ policy.creator.name }}</span>
                        <TierBadge :tier="policy.creator.reputation_tier" />
                    </div>
                </div>
            </div>

            <!-- Stage advance (creator only) -->
            <div v-if="isCreator && nextStage && policy.status === 'active'"
                class="bg-violet-50 border border-violet-200 rounded-xl p-5 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <p class="font-semibold text-violet-800 text-sm">Ready to advance?</p>
                    <p class="text-xs text-violet-600 mt-0.5">
                        Move from <strong>{{ stageLabel[policy.stage] }}</strong>
                        → <strong>{{ stageLabel[nextStage] ?? nextStage }}</strong>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>
                    <button @click="advanceStage" :disabled="advancing"
                        class="px-5 py-2 bg-violet-600 text-white text-sm font-semibold rounded-lg hover:bg-violet-700 transition disabled:opacity-60">
                        {{ advancing ? 'Advancing…' : `Advance to ${stageLabel[nextStage] ?? nextStage}` }}
                    </button>
                </div>
            </div>

            <!-- Full proposal text -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Proposal Text</h2>
                <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ policy.full_text }}</div>
            </div>

        </div>
    </CivicLayout>
</template>
