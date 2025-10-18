import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { ApiError } from '../lib/apiClient.js';
import { fetchInviteByToken } from '../lib/workspaceApi.js';

export function RegisterPage() {
    const { register } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const inviteToken = useMemo(() => {
        const params = new URLSearchParams(location.search);
        return params.get('inviteToken');
    }, [location.search]);

    const [form, setForm] = useState({
        firstName: '',
        lastName: '',
        email: '',
        password: '',
        passwordConfirmation: '',
        inviteToken: inviteToken ?? '',
    });
    const [errors, setErrors] = useState([]);
    const [submitting, setSubmitting] = useState(false);
    const [inviteInfo, setInviteInfo] = useState(null);
    const [inviteInfoError, setInviteInfoError] = useState(null);
    const [loadingInvite, setLoadingInvite] = useState(false);

    useEffect(() => {
        setForm((previous) => ({
            ...previous,
            inviteToken: inviteToken ?? '',
        }));

        if (!inviteToken) {
            setInviteInfo(null);
            setInviteInfoError(null);
            return;
        }

        let isMounted = true;
        setLoadingInvite(true);
        setInviteInfoError(null);

        fetchInviteByToken(inviteToken)
            .then((data) => {
                if (!isMounted) {
                    return;
                }

                setInviteInfo(data);
            })
            .catch((error) => {
                if (!isMounted) {
                    return;
                }

                if (error instanceof ApiError && error.status === 404) {
                    setInviteInfoError('That invite link is no longer valid.');
                } else {
                    setInviteInfoError('Unable to verify the invite right now.');
                }
            })
            .finally(() => {
                if (isMounted) {
                    setLoadingInvite(false);
                }
            });

        return () => {
            isMounted = false;
        };
    }, [inviteToken]);

    function updateField(field, value) {
        setForm((previous) => ({
            ...previous,
            [field]: value,
        }));
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setErrors([]);

        try {
            const payload = {
                ...form,
            };

            if (!payload.inviteToken) {
                delete payload.inviteToken;
            }

            await register(payload);
            navigate('/login?registered=1', { replace: true });
        } catch (submitError) {
            if (submitError instanceof ApiError) {
                const messages = submitError.data?.messages ?? [submitError.data?.message ?? submitError.message];
                setErrors(messages.filter(Boolean));
            } else {
                setErrors(['Something went wrong. Please try again.']);
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <main className="mx-auto flex min-h-screen w-full max-w-xl flex-col justify-center gap-8 px-6 py-12">
            <section className="space-y-6">
                <header className="space-y-2 text-center">
                    <h1 className="text-3xl font-semibold text-[rgb(var(--text-primary))]">Create an account</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Join Naroga Assistant to orchestrate personalised workflows.
                    </p>
                </header>

                {inviteToken && (
                    <div className="rounded-xl border border-[rgb(var(--accent-primary))]/40 bg-[rgb(var(--accent-primary))]/10 p-4 text-left text-sm text-[rgb(var(--accent-primary))]">
                        {loadingInvite && <p>Validating your invite…</p>}
                        {!loadingInvite && inviteInfo && (
                            <p>
                                You'll join <span className="font-semibold">{inviteInfo.workspace.name}</span> as a
                                { ' ' }
                                <span className="font-semibold">{inviteInfo.role}</span>. Use the email that received
                                the invite ({inviteInfo.email}).
                            </p>
                        )}
                        {!loadingInvite && inviteInfoError && <p>{inviteInfoError}</p>}
                    </div>
                )}

                {errors.length > 0 && (
                    <div className="space-y-2 rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
                        {errors.map((message) => (
                            <p key={message}>{message}</p>
                        ))}
                    </div>
                )}

                <form
                    onSubmit={handleSubmit}
                    className="grid gap-5 rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-6 shadow-panel"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label
                                htmlFor="firstName"
                                className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                            >
                                First name
                            </label>
                            <input
                                id="firstName"
                                type="text"
                                value={form.firstName}
                                onChange={(event) => updateField('firstName', event.target.value)}
                                required
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            />
                        </div>

                        <div className="space-y-2">
                            <label
                                htmlFor="lastName"
                                className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                            >
                                Last name
                            </label>
                            <input
                                id="lastName"
                                type="text"
                                value={form.lastName}
                                onChange={(event) => updateField('lastName', event.target.value)}
                                required
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <label htmlFor="email" className="block text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={form.email}
                            autoComplete="email"
                            onChange={(event) => updateField('email', event.target.value)}
                            required
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label
                                htmlFor="password"
                                className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={form.password}
                                autoComplete="new-password"
                                onChange={(event) => updateField('password', event.target.value)}
                                required
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            />
                        </div>

                        <div className="space-y-2">
                            <label
                                htmlFor="passwordConfirmation"
                                className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                            >
                                Confirm password
                            </label>
                            <input
                                id="passwordConfirmation"
                                type="password"
                                value={form.passwordConfirmation}
                                autoComplete="new-password"
                                onChange={(event) => updateField('passwordConfirmation', event.target.value)}
                                required
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            />
                        </div>
                    </div>

                    <button type="submit" className="btn-primary justify-center" disabled={submitting}>
                        {submitting ? 'Creating account…' : 'Create account'}
                    </button>
                </form>
            </section>

            <p className="text-center text-sm text-[rgb(var(--text-secondary))]">
                Already have an account?{' '}
                <Link to="/login" className="text-[rgb(var(--accent-primary))] hover:underline">
                    Sign in
                </Link>
            </p>
        </main>
    );
}
