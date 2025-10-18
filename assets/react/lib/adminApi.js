import { apiFetch } from './apiClient.js';

export async function fetchAiDashboard() {
    return apiFetch('/api/admin/ai/dashboard');
}

export async function fetchAiInteractionDetail(interactionId) {
    return apiFetch(`/api/admin/ai/interactions/${interactionId}`);
}
