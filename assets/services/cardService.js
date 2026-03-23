export async function fetchAllCards() {
    const response = await fetch('/api/card/all');
    if (!response.ok) throw new Error('Failed to fetch cards');
    return response.json();
}

export async function fetchCard(uuid) {
    const response = await fetch(`/api/card/${uuid}`);
    if (response.status === 404) return null;
    if (!response.ok) throw new Error('Failed to fetch card');
    const card = await response.json();
    if (card.text) card.text = card.text.replaceAll('\\n', '\n');
    return card;
}

export async function searchCards(name, setCode = null, page = 1, limit = 20) {
    const params = new URLSearchParams();
    if (name) params.set('name', name);
    if (setCode) params.set('setCode', setCode);
    params.set('page', String(page));
    params.set('limit', String(limit));
    const response = await fetch(`/api/card/search?${params.toString()}`);
    if (!response.ok) throw new Error('Failed to search cards');
    return response.json();
}

export async function fetchSetCodes() {
    const response = await fetch('/api/card/sets');
    if (!response.ok) throw new Error('Failed to fetch set codes');
    return response.json();
}
