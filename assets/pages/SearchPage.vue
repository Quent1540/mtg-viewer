<script setup>
import { ref, watch, onMounted } from 'vue';
import { searchCards, fetchSetCodes } from '../services/cardService';

const query = ref('');
const selectedSet = ref('');
const results = ref([]);
const loading = ref(false);
const sets = ref([]);
let timer = null;

async function loadSets() {
    try {
        sets.value = await fetchSetCodes();
    } catch (e) {
        // ignore
    }
}

async function runSearch() {
    if (query.value.length < 3 && selectedSet.value === '') {
        results.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await searchCards(query.value, selectedSet.value || null, 1, 20);
        results.value = res.data || [];
    } catch (e) {
        results.value = [];
    }
    loading.value = false;
}

watch(query, () => {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 350);
});

watch(selectedSet, () => {
    runSearch();
});

onMounted(() => {
    loadSets();
});
</script>

<template>
    <div>
        <h1>Rechercher une carte</h1>
        <div class="search-controls">
            <label for="search-input">Recherche</label>
            <input id="search-input" v-model="query" placeholder="Nom (min 3 caractères)" />

            <label for="search-set-select">Filtrer par set</label>
            <select id="search-set-select" v-model="selectedSet">
                <option value="">Tous les sets</option>
                <option v-for="s in sets" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div v-if="loading">Chargement...</div>
        <div v-else>
            <div v-if="results.length === 0">Aucun résultat</div>
            <ul>
                <li v-for="card in results" :key="card.id">
                    <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">{{ card.name }}</router-link>
                </li>
            </ul>
        </div>
    </div>
</template>
