import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { FullPageSpinner } from '../components/feedback/FullPageSpinner.jsx';
import { ApiError } from '../lib/apiClient.js';
import { fetchWorkspace, inviteToWorkspace } from '../lib/workspaceApi.js';
import { fetchAssistants } from '../lib/assistantApi.js';
import { AssistantCard } from '../components/assistants/AssistantCard.jsx';

const ROLE_OPTIONS = [
    { value: 'member', label: 'Member' },
    { value: 'admin', label: 'Admin' },
];

export function WorkspaceDetailPage() {
    const { workspaceId } = useParams();
    const navigate = useNavigate();
    const location = useLocation();

    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(null);
    const [workspace, setWorkspace] = useState(null);

    const [invites, setInvites] = useState([]);
    const [members, setMembers] = useState([]);

    const [assistants, setAssistants] = useState([]);
    const [assistantsLoading, setAssistantsLoading] = useState(true);
    const [assistantsError, setAssistantsError] = useState(null);

    const [inviteEmail, setInviteEmail] = useState('');
    const [inviteRole, setInviteRole] = useState('member');
    const [inviting, setInviting] = useState(false);
    const [inviteError, setInviteError] = useState(null);
    const [inviteSuccess, setInviteSuccess] = useState(null);

    const [showAssistantCreated, setShowAssistantCreated] = useState(false);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            setLoading(true);
            setLoadError(null);
            setAssistantsLoading(true);
            setAssistantsError(null);

            try {
                const data = await fetchWorkspace(workspaceId);

                if (!isMounted) {
                    return;
                }

                setWorkspace(data);
                setInvites(data.invites ?? []);
                setMembers(data.members ?? []);

                try {
                    const assistantList = await fetchAssistants(workspaceId);

                    if (isMounted) {
                        setAssistants(assistantList ?? []);
                    }
                } catch (assistantError) {
                    if (!isMounted) {
                        return;
                    }

                    if (assistantError instanceof ApiError) {
                        setAssistantsError(assistantError.data?.message ?? 'Unable to load assistants.');
                    } else {
                        setAssistantsError('Unable to load assistants.');
                    }
                }
            } catch (error) {
                if (!isMounted) {
                    return;
                }

                if (error instanceof ApiError && error.status === 404) {
                    setLoadError('Workspace not found or you no longer have access.');
                } else {
                    setLoadError('Unable to load workspace details.');
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                    setAssistantsLoading(false);
                }
            }
        }

        load().catch(() => {});

        return () => {
            isMounted = false;
        };
    }, [workspaceId]);

    const sortedMembers = useMemo(
        () => [...members].sort((a, b) => a.user.firstName.localeCompare(b.user.firstName)),
        [members],
    );

    const sortedInvites = useMemo(
        () => [...invites].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)),
        [invites],
    );

    const canManageAssistants = useMemo(() => {
        if (!workspace) {
            return false;
        }

        return workspace.role === 'owner' || workspace.role === 'admin';
    }, [workspace]);

    useEffect(() => {
        const params = new URLSearchParams(location.search);
        if (params.get('assistantCreated') === '1') {
            setShowAssistantCreated(true);
            params.delete('assistantCreated');
            navigate({ pathname: location.pathname, search: params.toString() ? `?${params.toString()}` : '' }, { replace: true });
        }
    }, [location.pathname, location.search, navigate]);

    async function handleSendInvite(event) {
        event.preventDefault();

        if (!workspace?.canManageInvites) {
            return;
        }

        setInviteError(null);
        setInviteSuccess(null);
        setInviting(true);

        try {
            const invite = await inviteToWorkspace(workspaceId, {
                email: inviteEmail.trim(),
                role: inviteRole,
            });

            setInviteEmail('');
            setInviteRole('member');
            setInvites((previous) => [invite, ...previous]);
            setInviteSuccess('Invite sent. We emailed the recipient with a join link.');
        } catch (error) {
            if (error instanceof ApiError) {
                const message = error.data?.message ?? error.data?.messages?.[0] ?? error.message;
                setInviteError(message ?? 'Unable to send invite.');
            } else {
                setInviteError('Unable to send invite. Try again later.');
            }
        } finally {
            setInviting(false);
        }
    }

    if (loading) {
        return <FullPageSpinner />;
    }

    if (loadError) {
        return (
            <main className="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 px-6 py-16 text-[rgb(var(--text-primary))]">
                <div className="space-y-4 text-center">
                    <h1 className="text-3xl font-semibold">Workspace unavailable</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">{loadError}</p>
                    <div className="flex justify-center gap-3">
                        <button type="button" className="btn-secondary" onClick={() => navigate('/')}>Back to home</button>
                        <Link to="/" className="btn-primary">
                            Dashboard
                        </Link>
                    </div>
                </div>
            </main>
        );
    }

    return (
        <main className="mx-auto flex min-h-screen max-w-4xl flex-col gap-8 px-6 py-12 text-[rgb(var(--text-primary))]">
            <header className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-2">
                    <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Workspace</p>
                    <h1 className="text-3xl font-semibold sm:text-4xl">{workspace.name}</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Manage members, invites, and shared context for this workspace.
                    </p>
                </div>
                <Link to="/" className="pill-action">
                    ← Back to dashboard
                </Link>
            </header>

            {showAssistantCreated && (
                <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p>Assistant created successfully. Configure advanced settings from the assistant list.</p>
                        <button
                            type="button"
                            className="btn-secondary"
                            onClick={() => {
                                setShowAssistantCreated(false);
                            }}
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            )}

            <section className="card space-y-6">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <div className="space-y-1">
                        <h2 className="text-lg font-semibold">Assistants</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Personas available to members in this workspace.
                        </p>
                    </div>

                    {canManageAssistants && (
                        <Link
                            to={`/workspaces/${workspaceId}/assistants/new`}
                            className="btn-primary"
                        >
                            Create assistant
                        </Link>
                    )}
                </header>

                {assistantsLoading && (
                    <p className="rounded-lg border border-slate-800/70 bg-[rgb(var(--surface-panel))] px-4 py-4 text-sm text-[rgb(var(--text-secondary))]">
                        Loading assistants…
                    </p>
                )}

                {!assistantsLoading && assistantsError && (
                    <p className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-4 text-sm text-rose-100">
                        {assistantsError}
                    </p>
                )}

                {!assistantsLoading && !assistantsError && assistants.length === 0 && (
                    <div className="rounded-lg border border-dashed border-slate-800/70 bg-[rgb(var(--surface-panel))] px-4 py-6 text-sm text-[rgb(var(--text-secondary))]">
                        {canManageAssistants ? 'No assistants yet. Spin up the first one to start routing conversations.' : 'No assistants have been created for this workspace yet.'}
                    </div>
                )}

                {!assistantsLoading && !assistantsError && assistants.length > 0 && (
                    <ul className="grid gap-4 sm:grid-cols-2">
                        {assistants.map((assistant) => (
                            <li key={assistant.id}>
                                <AssistantCard assistant={assistant} workspaceId={workspaceId} />
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section className="card space-y-6">
                <header className="space-y-1">
                    <h2 className="text-lg font-semibold">Members</h2>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Everyone with access to this workspace. Owners can manage billing and settings; admins can
                        invite additional members.
                    </p>
                </header>

                {sortedMembers.length > 0 ? (
                    <ul className="divide-y divide-slate-800/60">
                        {sortedMembers.map((member) => (
                            <li key={member.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <p className="text-sm font-semibold text-[rgb(var(--text-primary))]">
                                        {member.user.firstName} {member.user.lastName}
                                    </p>
                                    <p className="text-xs text-[rgb(var(--text-secondary))]">{member.user.email}</p>
                                </div>
                                <div className="text-right">
                                    <span className="rounded-full bg-[rgb(var(--surface-panel))] px-3 py-1 text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                        {member.role}
                                    </span>
                                    <p className="mt-1 text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                        Joined {new Date(member.joinedAt).toLocaleDateString()}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="rounded-lg border border-dashed border-slate-700/60 bg-[rgb(var(--surface-panel))] px-4 py-6 text-center text-sm text-[rgb(var(--text-secondary))]">
                        This workspace has no members yet.
                    </p>
                )}
            </section>

            <section className="card space-y-6">
                <header className="space-y-1">
                    <h2 className="text-lg font-semibold">Invite people</h2>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Send invites by email. We notify the recipient instantly and link the workspace when they sign
                        in.
                    </p>
                </header>

                {!workspace.canManageInvites && (
                    <div className="rounded-lg border border-slate-800/70 bg-[rgb(var(--surface-panel))] px-4 py-3 text-sm text-[rgb(var(--text-secondary))]">
                        You need invite permissions to add people. Contact an owner to promote your access.
                    </div>
                )}

                {workspace.canManageInvites && (
                    <form onSubmit={handleSendInvite} className="space-y-4">
                        {inviteError && (
                            <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-100">
                                {inviteError}
                            </div>
                        )}
                        {inviteSuccess && (
                            <div className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-100">
                                {inviteSuccess}
                            </div>
                        )}

                        <div className="flex flex-col gap-4 sm:flex-row">
                            <label className="flex-1 space-y-2 text-sm">
                                <span className="text-[rgb(var(--text-secondary))]">Email address</span>
                                <input
                                    type="email"
                                    value={inviteEmail}
                                    onChange={(event) => setInviteEmail(event.target.value)}
                                    required
                                    className="w-full rounded-lg border border-slate-800 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                />
                            </label>
                            <label className="w-full space-y-2 text-sm sm:w-48">
                                <span className="text-[rgb(var(--text-secondary))]">Role</span>
                                <select
                                    value={inviteRole}
                                    onChange={(event) => setInviteRole(event.target.value)}
                                    className="w-full rounded-lg border border-slate-800 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                >
                                    {ROLE_OPTIONS.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>

                        <div className="flex justify-end">
                            <button type="submit" className="btn-primary" disabled={inviting}>
                                {inviting ? 'Sending invite…' : 'Send invite'}
                            </button>
                        </div>
                    </form>
                )}

                <div className="space-y-4">
                    <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
                        Recent invites
                    </h3>

                    {sortedInvites.length > 0 ? (
                        <ul className="space-y-3">
                            {sortedInvites.map((invite) => (
                                <li key={invite.id} className="rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-semibold text-[rgb(var(--text-primary))]">
                                                {invite.email}
                                            </p>
                                            <p className="text-xs text-[rgb(var(--text-secondary))]">
                                                Role: {invite.role}
                                            </p>
                                            <p className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                Created {new Date(invite.createdAt).toLocaleString()}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <span className="rounded-full bg-[rgb(var(--surface-panel))] px-3 py-1 text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                {invite.status}
                                            </span>
                                            <p className="mt-1 text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                Expires {new Date(invite.expiresAt).toLocaleString()}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="rounded-lg border border-dashed border-slate-700/70 bg-[rgb(var(--surface-panel))] px-4 py-6 text-center text-sm text-[rgb(var(--text-secondary))]">
                            No invites sent yet.
                        </p>
                    )}
                </div>
            </section>
        </main>
    );
}
