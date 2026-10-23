export function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export async function getJson(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
}

export async function postJson(url, data) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok && response.status !== 404) throw new Error(`HTTP ${response.status}`);
    return { status: response.status, body };
}
