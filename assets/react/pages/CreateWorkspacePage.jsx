import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { createWorkspace } from '../lib/workspaceApi.js';
import { ApiError } from '../lib/apiClient.js';

export function CreateWorkspacePage() {
    const [name, setName] = useState('');
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const navigate = useNavigate();

    async function handleSubmit(event) {
        event.preventDefault();

        if (name.trim().length < 3) {
            setError('Workspace names must be at least 3 characters.');
            return;
        }

        setSubmitting(true);
        setError(null);

        try {
            const workspace = await createWorkspace({ name: name.trim() });
            setName('');
            navigate(`/workspaces/${workspace.id}`, { replace: true });
        } catch (submitError) {
            if (submitError instanceof ApiError) {
                const message = submitError.data?.messages?.[0] ?? submitError.data?.message ?? submitError.message;
                setError(message ?? 'Unable to create workspace right now.');
            } else {
                setError('Unable to create workspace right now. Try again in a moment.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <main className="mx-auto flex min-h-[calc(100vh-8rem)] w-full max-w-2xl flex-col justify-center gap-8 px-6 py-12">
            <section className="space-y-6">
                <header className="space-y-2">
                    <Link to="/" className="text-sm text-[rgb(var(--accent-primary))] hover:underline">
                        ← Back to dashboard
                    </Link>
                    <h1 className="text-3xl font-semibold text-[rgb(var(--text-primary))]">Create a workspace</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Workspaces keep assistants, automations, and shared memory scoped to the right group. Invite your
                        team after creating the workspace.
                    </p>
                </header>

                {error && (
                    <div className="rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
                        {error}
                    </div>
                )}

                <form
                    onSubmit={handleSubmit}
                    className="space-y-5 rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-6 shadow-panel"
                >
                    <div className="space-y-2">
                        <label htmlFor="workspaceName" className="block text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Workspace name
                        </label>
                        <input
                            id="workspaceName"
                            type="text"
                            value={name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder="e.g. Parker Family or Naroga Inc."
                            required
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                        />
                        <p className="text-xs text-[rgb(var(--text-tertiary))]">
                            Use something everyone recognises. You can rename it later.
                        </p>
                    </div>

                    <button type="submit" className="btn-primary justify-center" disabled={submitting}>
                        {submitting ? 'Creating…' : 'Create workspace'}
                    </button>
                </form>
            </section>
        </main>
    );
}
