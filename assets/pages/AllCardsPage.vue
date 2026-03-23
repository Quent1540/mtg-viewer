<script setup>
import { onMounted, ref } from 'vue';
import { fetchAllCards, fetchSetCodes } from '../services/cardService';

const cards = ref([]);
const loadingCards = ref(true);
const page = ref(1);
const limit = 100;
const total = ref(0);
const sets = ref([]);
const selectedSet = ref('');

async function loadSets() {
    try {
        sets.value = await fetchSetCodes();
    } catch (e) {
        // ignore
    }
}

async function loadCards() {
    loadingCards.value = true;
    try {
        const res = await fetchAllCards(page.value, limit, selectedSet.value || null);
        cards.value = res.data;
        total.value = res.total;
    } catch (e) {
        cards.value = [];
    }
    loadingCards.value = false;
}

onMounted(() => {
    loadSets();
    loadCards();
});

function nextPage() {
    if (page.value * limit >= total.value) return;
    page.value += 1;
    loadCards();
}
function prevPage() {
    if (page.value <= 1) return;
    page.value -= 1;
    loadCards();
}

function onSetChange() {
    page.value = 1;
    loadCards();
}
</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
        <div class="filters">
            <label for="set-select">Filtrer par set</label>
            <select id="set-select" v-model="selectedSet" @change="onSetChange">
                <option value="">Tous les sets</option>
                <option v-for="s in sets" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>
    </div>
    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card-result" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>

            <div class="pagination">
                <button type="button" @click="prevPage" :disabled="page === 1">Précédent</button>
                <span>Page {{ page }} / {{ Math.ceil(total / limit) || 1 }}</span>
                <button type="button" @click="nextPage" :disabled="page * limit >= total">Suivant</button>
            </div>
        </div>
    </div>
</template>
