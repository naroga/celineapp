import { apiFetch } from './apiClient.js';

export async function fetchAssistants(workspaceId) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants`);
    return response.assistants;
}

export async function fetchAssistant(workspaceId, assistantId) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants/${assistantId}`);
    return response.assistant;
}

export async function generateAssistantName(workspaceId, payload) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants/generate-name`, {
        method: 'POST',
        body: payload,
    });

    return response.name;
}

export async function generateAssistantProfilePicture(workspaceId, payload) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants/generate-profile-picture`, {
        method: 'POST',
        body: payload,
    });

    return response.image;
}

export async function createAssistant(workspaceId, payload) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants`, {
        method: 'POST',
        body: payload,
    });

    return response.assistant;
}

export async function updateAssistant(workspaceId, assistantId, payload) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/assistants/${assistantId}`, {
        method: 'PUT',
        body: payload,
    });

    return response.assistant;
}
