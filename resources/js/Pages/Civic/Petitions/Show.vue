<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import ProgressBar from '@/Components/Civic/ProgressBar.vue';
import ReactionBar from '@/Components/Civic/ReactionBar.vue';

const props = defineProps({
    petition:       { type: Object,  required: true },
    hasSigned:      { type: Boolean, default: false },
    reactionCounts: { type: Object,  default: () => ({}) },
    userReaction:   { type: String,  default: null },
});

const signing  = ref(false);
const error    = ref('');
const signed   = ref(props.hasSigned);

const sign = async () => {
    error.value   = '';
    signing.value = true;
    try {
        await window.axios.post(`/api/v1/petitions/${props.petition.id}/sign`);
        signed.value = true;
        router.reload({ only: ['petition'] });
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? 'Could not sign. Please try again.';
    } finally {
        signing.value = false;
    }
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout :title="petition.title">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a :href="route('civic.petitions.index')" class="text-sm text-gray-500 hover:text-gray-700">← Petitions</a>
                <StatusPill :status="petition.status" />
                <span v-if="petition.region" class="text-sm text-gray-500">{{ petition.region.name }}</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Header card -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h1 class="text-2xl font-bold text-gray-900">{{ petition.title }}</h1>
                <p class="text-gray-600 text-sm leading-relaxed">{{ petition.summary }}</p>

                <ProgressBar :current="petition.signature_count" :goal="petition.signature_goal" color="emerald" />

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                    <span>{{ petition.signature_count }} of {{ petition.signature_goal }} signatures</span>
                    <span v-if="petition.deadline">Deadline {{ formatDate(petition.deadline) }}</span>
                    <div class="flex items-center gap-1.5" v-if="petition.creator">
                        <span class="text-xs">by {{ petition.creator.name }}</span>
                        <TierBadge :tier="petition.creator.reputation_tier" />
                    </div>
                </div>
            </div>

            <!-- Sign action -->
            <div v-if="signed" class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                <p class="text-emerald-800 font-semibold">✓ You have signed this petition</p>
                <p class="text-sm text-emerald-600 mt-1">Thank you for your support.</p>
            </div>

            <div v-else-if="['active', 'milestone_reached'].includes(petition.status) && $page.props.auth?.user"
                class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3">
                <h2 class="font-semibold text-gray-800">Add your signature</h2>
                <p class="text-sm text-gray-500">Your signature will be recorded and your name will be added to the public count.</p>
                <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>
                <button @click="sign" :disabled="signing"
                    class="w-full py-3 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition disabled:opacity-60">
                    {{ signing ? 'Signing…' : 'Sign this Petition' }}
                </button>
            </div>

            <div v-else-if="!$page.props.auth?.user" class="bg-gray-50 border border-gray-200 rounded-xl p-5 text-center">
                <p class="text-gray-600 text-sm">
                    <a :href="route('login')" class="text-emerald-600 hover:underline font-medium">Log in</a>
                    to sign this petition.
                </p>
            </div>

            <!-- Full text -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Full Text</h2>
                <div v-if="petition.body" class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ petition.body }}</div>
                <p v-else class="text-sm text-gray-400 italic">No full text provided for this petition.</p>
            </div>

            <!-- Reactions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Community Reactions</h2>
                <ReactionBar
                    reactable-type="petition"
                    :reactable-id="petition.id"
                    :counts="reactionCounts"
                    :user-reaction="userReaction"
                />
            </div>

        </div>
    </CivicLayout>
</template>
