<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CivicLayout from '@/Layouts/CivicLayout.vue';
import StatusPill from '@/Components/Civic/StatusPill.vue';
import TierBadge from '@/Components/Civic/TierBadge.vue';
import ReactionBar from '@/Components/Civic/ReactionBar.vue';

const props = defineProps({
    poll:           { type: Object, required: true },
    userVote:       { type: Object, default: null },
    reactionCounts: { type: Object, default: () => ({}) },
    userReaction:   { type: String, default: null },
});

const selected  = ref([]);
const error     = ref('');
const submitting = ref(false);
const voted     = ref(!!props.userVote);

const toggleOption = (opt) => {
    if (props.poll.allow_multiple_votes) {
        const idx = selected.value.indexOf(opt);
        idx === -1 ? selected.value.push(opt) : selected.value.splice(idx, 1);
    } else {
        selected.value = [opt];
    }
};

const submitVote = async () => {
    if (!selected.value.length) { error.value = 'Please select an option.'; return; }
    error.value = '';
    submitting.value = true;
    try {
        await window.axios.post(`/api/v1/polls/${props.poll.id}/vote`, {
            selected_options: selected.value,
        });
        voted.value = true;
        router.reload({ only: ['poll'] });
    } catch (err) {
        error.value = err.response?.data?.meta?.message ?? 'Could not record vote. Please try again.';
    } finally {
        submitting.value = false;
    }
};

const formatDate = (iso) => iso
    ? new Date(iso).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' })
    : '';
</script>

<template>
    <CivicLayout :title="poll.title">
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <a :href="route('civic.polls.index')" class="text-sm text-gray-500 hover:text-gray-700">← Polls</a>
                <StatusPill :status="poll.status" />
                <span v-if="poll.region" class="text-sm text-gray-500">{{ poll.region.name }}</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Poll header -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ poll.title }}</h1>
                <p v-if="poll.description" class="text-gray-600 mb-4">{{ poll.description }}</p>

                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                    <span>{{ poll.total_votes }} vote{{ poll.total_votes !== 1 ? 's' : '' }}</span>
                    <span v-if="poll.ends_at">Closes {{ formatDate(poll.ends_at) }}</span>
                    <span v-if="poll.allow_multiple_votes" class="text-blue-600 text-xs font-medium">Multi-choice</span>
                    <span v-if="poll.verified_only" class="text-yellow-600 text-xs font-medium">Verified only</span>
                    <div class="flex items-center gap-1.5" v-if="poll.creator">
                        <span class="text-xs">by {{ poll.creator.name }}</span>
                        <TierBadge :tier="poll.creator.reputation_tier" />
                    </div>
                </div>
            </div>

            <!-- Voted confirmation -->
            <div v-if="voted" class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                <p class="text-blue-800 font-semibold">✓ Your vote has been recorded</p>
                <p v-if="userVote" class="text-sm text-blue-600 mt-1">
                    You voted for: {{ userVote.selected_options?.join(', ') }}
                </p>
                <p class="text-sm text-blue-500 mt-1">Thank you for participating.</p>
            </div>

            <!-- Voting form -->
            <div v-else-if="poll.status === 'active' && $page.props.auth?.user" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="font-semibold text-gray-800">Cast your vote</h2>
                <p v-if="poll.allow_multiple_votes" class="text-xs text-gray-500">You may select multiple options.</p>

                <div class="space-y-2">
                    <button
                        v-for="opt in poll.options"
                        :key="opt"
                        type="button"
                        @click="toggleOption(opt)"
                        :class="[
                            'w-full text-left px-4 py-3 rounded-lg border text-sm font-medium transition',
                            selected.includes(opt)
                                ? 'border-blue-500 bg-blue-50 text-blue-800'
                                : 'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50',
                        ]"
                    >
                        <span class="flex items-center gap-2">
                            <span :class="['w-4 h-4 rounded-full border-2 shrink-0 transition', selected.includes(opt) ? 'border-blue-500 bg-blue-500' : 'border-gray-300']" />
                            {{ opt }}
                        </span>
                    </button>
                </div>

                <p v-if="error" class="text-red-500 text-sm">{{ error }}</p>

                <button
                    @click="submitVote"
                    :disabled="submitting || !selected.length"
                    class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-60"
                >
                    {{ submitting ? 'Submitting…' : 'Submit Vote' }}
                </button>
            </div>

            <!-- Guest nudge -->
            <div v-else-if="poll.status === 'active' && !$page.props.auth?.user"
                class="bg-gray-50 border border-gray-200 rounded-xl p-5 text-center">
                <p class="text-gray-600 text-sm">
                    <a :href="route('login')" class="text-blue-600 hover:underline font-medium">Log in</a>
                    to vote on this poll.
                </p>
            </div>

            <!-- Options summary (always shown) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Options</h2>
                <ul class="space-y-2">
                    <li v-for="opt in poll.options" :key="opt"
                        class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0" />
                        {{ opt }}
                    </li>
                </ul>
            </div>

            <!-- Reactions -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-4">Community Reactions</h2>
                <ReactionBar
                    reactable-type="poll"
                    :reactable-id="poll.id"
                    :counts="reactionCounts"
                    :user-reaction="userReaction"
                />
            </div>

        </div>
    </CivicLayout>
</template>
