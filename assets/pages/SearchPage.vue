<script setup>
import { ref, watch } from 'vue';
import { searchCards } from '../services/cardService';

const query = ref('');
const results = ref([]);
const loading = ref(false);
let timer = null;

async function runSearch() {
    if (query.value.length < 3) {
        results.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await searchCards(query.value, null, 1, 20);
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
</script>

<template>
    <div>
        <h1>Rechercher une carte</h1>
        <input v-model="query" placeholder="Nom (min 3 caractères)" />

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
