import React, { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { FullPageSpinner } from '../components/feedback/FullPageSpinner.jsx';
import { ApiError } from '../lib/apiClient.js';
import { acceptInviteByToken, fetchInviteByToken } from '../lib/workspaceApi.js';

export function InviteLandingPage() {
    const { token } = useParams();
    const navigate = useNavigate();
    const { user, refresh } = useAuth();

    const [loading, setLoading] = useState(true);
    const [invite, setInvite] = useState(null);
    const [loadError, setLoadError] = useState(null);

    const [accepting, setAccepting] = useState(false);
    const [acceptError, setAcceptError] = useState(null);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            setLoading(true);
            setLoadError(null);

            try {
                const data = await fetchInviteByToken(token);

                if (!isMounted) {
                    return;
                }

                setInvite(data);
            } catch (error) {
                if (!isMounted) {
                    return;
                }

                if (error instanceof ApiError && error.status === 404) {
                    setLoadError('This invite link is no longer valid or has expired.');
                } else {
                    setLoadError('Unable to load the invite.');
                }
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
    }, [token]);

    const nextParam = encodeURIComponent(`/invites/${token}`);
    const loginLink = `/login?inviteToken=${encodeURIComponent(token)}&next=${nextParam}`;
    const registerLink = `/register?inviteToken=${encodeURIComponent(token)}`;

    async function handleAcceptInvite() {
        setAccepting(true);
        setAcceptError(null);

        try {
            const response = await acceptInviteByToken(token);
            await refresh();

            const workspaceId = response?.membership?.workspace?.id;

            if (workspaceId) {
                navigate(`/workspaces/${workspaceId}`, { replace: true });
            } else {
                navigate('/', { replace: true });
            }
        } catch (error) {
            if (error instanceof ApiError) {
                setAcceptError(error.data?.message ?? 'Unable to accept invite.');
            } else {
                setAcceptError('Unable to accept invite right now.');
            }
        } finally {
            setAccepting(false);
        }
    }

    if (loading) {
        return <FullPageSpinner />;
    }

    if (loadError) {
        return (
            <main className="mx-auto flex min-h-screen max-w-xl flex-col justify-center gap-6 px-6 py-12 text-center text-[rgb(var(--text-primary))]">
                <h1 className="text-3xl font-semibold">Invite unavailable</h1>
                <p className="text-sm text-[rgb(var(--text-secondary))]">{loadError}</p>
                <div className="flex justify-center gap-3">
                    <Link to="/" className="btn-primary">
                        Go to dashboard
                    </Link>
                    <Link to="/login" className="btn-secondary">
                        Sign in
                    </Link>
                </div>
            </main>
        );
    }

    return (
        <main className="mx-auto flex min-h-screen max-w-2xl flex-col justify-center gap-8 px-6 py-12 text-[rgb(var(--text-primary))]">
            <section className="space-y-6">
                <header className="space-y-2 text-center">
                    <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Workspace Invite</p>
                    <h1 className="text-3xl font-semibold">Join {invite.workspace.name}</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        {invite.invitedBy.firstName} {invite.invitedBy.lastName} invited you to collaborate. Accept to
                        access shared assistants, automations, and context.
                    </p>
                </header>

                <div className="rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-6 shadow-panel">
                    <dl className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Email</dt>
                            <dd className="text-sm text-[rgb(var(--text-primary))]">{invite.email}</dd>
                        </div>
                        <div>
                            <dt className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Role</dt>
                            <dd className="text-sm text-[rgb(var(--text-primary))]">{invite.role}</dd>
                        </div>
                    </dl>
                    <p className="mt-4 text-xs text-[rgb(var(--text-secondary))]">
                        This invite expires on {new Date(invite.expiresAt).toLocaleString()}.
                    </p>
                </div>

                {acceptError && (
                    <div className="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-2 text-sm text-red-100">
                        {acceptError}
                    </div>
                )}

                {user ? (
                    <div className="flex flex-wrap justify-center gap-3">
                        <button type="button" className="btn-primary" onClick={handleAcceptInvite} disabled={accepting}>
                            {accepting ? 'Accepting…' : 'Accept invite'}
                        </button>
                        <button type="button" className="btn-secondary" onClick={() => navigate('/') }>
                            Back to dashboard
                        </button>
                    </div>
                ) : (
                    <div className="flex flex-wrap justify-center gap-3">
                        <Link to={loginLink} className="btn-primary">
                            Sign in to accept
                        </Link>
                        <Link to={registerLink} className="btn-secondary">
                            Create account
                        </Link>
                    </div>
                )}
            </section>
        </main>
    );
}
