import React, { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { FullPageSpinner } from '../components/feedback/FullPageSpinner.jsx';
import {
    acceptInvite,
    declineInvite,
    fetchPendingInvites,
    fetchWorkspaces,
} from '../lib/workspaceApi.js';
import { ApiError } from '../lib/apiClient.js';

export function HomePage() {
    const { user } = useAuth();
    const navigate = useNavigate();

    const [loading, setLoading] = useState(true);
    const [workspaces, setWorkspaces] = useState([]);
    const [invites, setInvites] = useState([]);
    const [loadError, setLoadError] = useState(null);

    const [inviteError, setInviteError] = useState(null);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            setLoading(true);
            setLoadError(null);

            try {
                const [workspaceData, inviteData] = await Promise.all([
                    fetchWorkspaces(),
                    fetchPendingInvites(),
                ]);

                if (!isMounted) {
                    return;
                }

                setWorkspaces(workspaceData);
                setInvites(inviteData);
            } catch (error) {
                if (!isMounted) {
                    return;
                }

                setLoadError('Unable to load your workspaces right now.');
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        }

        load().catch(() => {});

        return () => {
            isMounted = false;
        };
    }, []);

    const hasWorkspaces = workspaces.length > 0;
    const hasInvites = invites.length > 0;

    const sortedWorkspaces = useMemo(
        () => [...workspaces].sort((a, b) => a.name.localeCompare(b.name)),
        [workspaces],
    );

    async function handleAcceptInvite(inviteId) {
        setInviteError(null);

        try {
            const response = await acceptInvite(inviteId);
            setInvites((previous) => previous.filter((invite) => invite.id !== inviteId));

            const workspace = response?.membership?.workspace;

            if (workspace) {
                setWorkspaces((previous) => {
                    const exists = previous.some((item) => item.id === workspace.id);

                    if (exists) {
                        return previous;
                    }

                    return [...previous, { ...workspace, role: response.membership.role }];
                });

                navigate(`/workspaces/${workspace.id}`);
            }
        } catch (error) {
            if (error instanceof ApiError) {
                setInviteError(error.data?.message ?? 'Unable to accept invite.');
            } else {
                setInviteError('Unable to accept invite. Try again later.');
            }
        }
    }

    async function handleDeclineInvite(inviteId) {
        setInviteError(null);

        try {
            await declineInvite(inviteId);
            setInvites((previous) => previous.filter((invite) => invite.id !== inviteId));
        } catch (error) {
            if (error instanceof ApiError) {
                setInviteError(error.data?.message ?? 'Unable to decline invite.');
            } else {
                setInviteError('Unable to decline invite. Try again later.');
            }
        }
    }

    if (loading) {
        return <FullPageSpinner />;
    }

    return (
        <main className="mx-auto flex min-h-screen max-w-workspace flex-col gap-10 px-6 pb-12 pt-6 text-[rgb(var(--text-primary))]">
            <header className="space-y-6">
                <div className="space-y-3">
                    <span className="badge uppercase tracking-[0.32em]">Workspace Hub</span>
                    <h1 className="text-3xl font-semibold sm:text-4xl">Welcome back, {user?.firstName ?? 'there'}</h1>
                    <p className="max-w-2xl text-sm leading-6 text-[rgb(var(--text-secondary))]">
                        Access all of your workspaces here. Use the Workspaces menu in the navigation bar to switch,
                        create new spaces, or jump into existing teams.
                    </p>
                </div>
            </header>

            {loadError && (
                <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                    {loadError}
                </div>
            )}

            <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <div className="card space-y-6">
                    <header className="flex items-center justify-between">
                        <div className="space-y-1">
                            <h2 className="text-lg font-semibold">Your workspaces</h2>
                            <p className="text-sm text-[rgb(var(--text-secondary))]">
                                Open a workspace to manage members, automations, and shared context.
                            </p>
                        </div>
                        {hasWorkspaces && (
                            <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                {sortedWorkspaces.length} workspace{sortedWorkspaces.length === 1 ? '' : 's'}
                            </span>
                        )}
                    </header>

                    {hasWorkspaces ? (
                        <ul className="space-y-4">
                            {sortedWorkspaces.map((workspace) => (
                                <li key={workspace.id} className="rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-4 shadow-panel">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">
                                                {workspace.name}
                                            </h3>
                                            <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                {workspace.role ?? 'member'}
                                            </p>
                                        </div>
                                        <Link to={`/workspaces/${workspace.id}`} className="pill-action">
                                            Open workspace
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="rounded-xl border border-dashed border-slate-700/70 bg-[rgb(var(--surface-panel))] px-5 py-8 text-center text-sm text-[rgb(var(--text-secondary))]">
                            <p className="font-medium text-[rgb(var(--text-primary))]">No workspaces yet</p>
                            <p className="mt-1">
                                Use the Workspaces menu above to create one, or accept an invite to collaborate with your
                                team.
                            </p>
                        </div>
                    )}
                </div>

                <aside className="space-y-4">
                    <div className="card space-y-4">
                        <header className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
                                Pending invites
                            </h2>
                            {hasInvites && (
                                <span className="text-xs text-[rgb(var(--text-tertiary))]">
                                    {invites.length} open
                                </span>
                            )}
                        </header>

                        {inviteError && (
                            <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-xs text-red-100">
                                {inviteError}
                            </div>
                        )}

                        {hasInvites ? (
                            <ul className="space-y-3">
                                {invites.map((invite) => (
                                    <li key={invite.id} className="rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-4">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div className="space-y-1">
                                                <p className="text-sm font-semibold text-[rgb(var(--text-primary))]">
                                                    {invite.workspace.name}
                                                </p>
                                                <p className="text-xs text-[rgb(var(--text-secondary))]">
                                                    Invited by {invite.invitedBy.firstName} {invite.invitedBy.lastName}
                                                </p>
                                                <p className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Expires {new Date(invite.expiresAt).toLocaleString()}
                                                </p>
                                            </div>
                                            <div className="flex flex-col gap-2 text-xs sm:flex-row">
                                                <button
                                                    type="button"
                                                    className="btn-primary"
                                                    onClick={() => handleAcceptInvite(invite.id)}
                                                >
                                                    Accept
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn-secondary"
                                                    onClick={() => handleDeclineInvite(invite.id)}
                                                >
                                                    Decline
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="rounded-xl border border-dashed border-slate-700/70 bg-[rgb(var(--surface-panel))] px-4 py-6 text-center text-xs text-[rgb(var(--text-secondary))]">
                                You have no pending invites.
                            </p>
                        )}
                    </div>
                </aside>
            </section>
        </main>
    );
}
