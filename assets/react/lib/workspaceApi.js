import { apiFetch } from './apiClient.js';

export async function fetchWorkspaces() {
    const response = await apiFetch('/api/workspaces');
    return response.workspaces;
}

export async function createWorkspace(payload) {
    const response = await apiFetch('/api/workspaces', {
        method: 'POST',
        body: payload,
    });

    return response.workspace;
}

export async function fetchWorkspace(workspaceId) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}`);
    return response.workspace;
}

export async function inviteToWorkspace(workspaceId, payload) {
    const response = await apiFetch(`/api/workspaces/${workspaceId}/invites`, {
        method: 'POST',
        body: payload,
    });

    return response.invite;
}

export async function fetchPendingInvites() {
    const response = await apiFetch('/api/workspace-invites/pending');
    return response.invites;
}

export async function acceptInvite(inviteId) {
    const response = await apiFetch(`/api/workspace-invites/${inviteId}/accept`, {
        method: 'POST',
    });

    return response;
}

export async function declineInvite(inviteId) {
    const response = await apiFetch(`/api/workspace-invites/${inviteId}/decline`, {
        method: 'POST',
    });

    return response;
}

export async function fetchInviteByToken(token) {
    const response = await apiFetch(`/api/workspace-invites/token/${encodeURIComponent(token)}`);
    return response.invite;
}

export async function acceptInviteByToken(token) {
    const response = await apiFetch(`/api/workspace-invites/token/${encodeURIComponent(token)}/accept`, {
        method: 'POST',
    });

    return response;
}
