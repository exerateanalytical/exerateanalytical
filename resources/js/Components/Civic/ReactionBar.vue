<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    reactableType: { type: String, required: true }, // 'poll' | 'petition' | 'policy'
    reactableId:   { type: String, required: true },
    counts:        { type: Object, default: () => ({}) }, // { support: 3, oppose: 1, ... }
    userReaction:  { type: String, default: null },       // current user's reaction type or null
});

const page = usePage();
const isAuth = computed(() => !!page.props.auth?.user);

const current  = ref(props.userReaction);
const totals   = ref({ ...props.counts });
const pending  = ref(false);
const error    = ref('');

const REACTIONS = [
    { type: 'support',    icon: '👍', label: 'Support',    active: 'bg-emerald-100 text-emerald-700 border-emerald-300' },
    { type: 'oppose',     icon: '👎', label: 'Oppose',     active: 'bg-red-100     text-red-700     border-red-300'     },
    { type: 'insightful', icon: '💡', label: 'Insightful', active: 'bg-blue-100    text-blue-700    border-blue-300'    },
    { type: 'concern',    icon: '⚠️', label: 'Concern',    active: 'bg-yellow-100  text-yellow-700  border-yellow-300'  },
    { type: 'report',     icon: '🚩', label: 'Report',     active: 'bg-gray-100    text-gray-500    border-gray-300'    },
];

const react = async (type) => {
    if (!isAuth.value || pending.value) return;
    error.value = '';
    pending.value = true;

    const previous = current.value;

    // Optimistic update
    if (previous) totals.value[previous] = Math.max(0, (totals.value[previous] ?? 0) - 1);
    if (previous === type) {
        // Clicking same button again — cancel reaction (treat as toggle off visually,
        // but API is idempotent so we just keep it — no "unreact" endpoint exists)
        current.value = null;
        pending.value = false;
        return;
    }
    totals.value[type] = (totals.value[type] ?? 0) + 1;
    current.value = type;

    try {
        await window.axios.post('/api/v1/reactions', {
            reactable_type: props.reactableType,
            reactable_id:   props.reactableId,
            type,
        });
    } catch (err) {
        // Rollback optimistic update
        if (previous) totals.value[previous] = (totals.value[previous] ?? 0) + 1;
        totals.value[type] = Math.max(0, (totals.value[type] ?? 0) - 1);
        current.value = previous;
        error.value = err.response?.data?.meta?.message ?? 'Could not save reaction.';
    } finally {
        pending.value = false;
    }
};

const totalCount = computed(() => Object.values(totals.value).reduce((a, b) => a + b, 0));
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="r in REACTIONS"
                :key="r.type"
                @click="react(r.type)"
                :disabled="!isAuth || pending"
                :title="isAuth ? r.label : 'Log in to react'"
                :class="[
                    'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition',
                    current === r.type
                        ? r.active
                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:text-gray-700',
                    (!isAuth || pending) ? 'cursor-default opacity-70' : 'cursor-pointer',
                ]"
            >
                <span>{{ r.icon }}</span>
                <span>{{ r.label }}</span>
                <span v-if="totals[r.type]" class="font-semibold">{{ totals[r.type] }}</span>
            </button>
        </div>

        <p v-if="error" class="text-red-500 text-xs">{{ error }}</p>

        <p v-if="!isAuth" class="text-xs text-gray-400">
            <a :href="route('login')" class="text-indigo-500 hover:underline">Log in</a>
            to react to this content.
        </p>

        <p v-if="totalCount > 0" class="text-xs text-gray-400">{{ totalCount }} reaction{{ totalCount !== 1 ? 's' : '' }}</p>
    </div>
</template>
