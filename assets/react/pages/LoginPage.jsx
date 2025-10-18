import React, { useMemo, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { ApiError } from '../lib/apiClient.js';

export function LoginPage() {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    const googleError = useMemo(() => {
        const params = new URLSearchParams(location.search);
        return params.get('error') === 'google';
    }, [location.search]);

    const inviteToken = useMemo(() => {
        const params = new URLSearchParams(location.search);
        return params.get('inviteToken');
    }, [location.search]);

    const redirectTo = useMemo(() => {
        const params = new URLSearchParams(location.search);
        return params.get('next');
    }, [location.search]);

    const successMessage = useMemo(() => {
        const params = new URLSearchParams(location.search);

        if (params.get('registered')) {
            return 'Account created. Sign in with your new credentials.';
        }

        if (params.get('reset_requested')) {
            return 'If that email is on file, we sent a reset link.';
        }

        if (params.get('password_reset')) {
            return 'Password updated. Sign in with your new password.';
        }

        return null;
    }, [location.search]);

    async function handleSubmit(event) {
        event.preventDefault();

        setSubmitting(true);
        setError(null);

        try {
            await login({ email, password, remember });
            navigate(redirectTo ?? '/', { replace: true });
        } catch (submitError) {
            if (submitError instanceof ApiError) {
                setError(submitError.data?.message ?? 'Invalid credentials. Please try again.');
            } else {
                setError('Something went wrong. Please try again.');
            }
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <main className="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center gap-8 px-6 py-12">
            <section className="space-y-6">
                <header className="space-y-2 text-center">
                    <h1 className="text-3xl font-semibold text-[rgb(var(--text-primary))]">Welcome back</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">Sign in to continue to Naroga Assistant.</p>
                </header>

                {googleError && (
                    <div className="rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
                        Unable to sign in with Google. Please try again.
                    </div>
                )}

                {inviteToken && (
                    <div className="rounded-xl border border-[rgb(var(--accent-primary))]/40 bg-[rgb(var(--accent-primary))]/10 p-4 text-sm text-[rgb(var(--accent-primary))]">
                        Sign in to accept your workspace invite.
                    </div>
                )}

                {successMessage && (
                    <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-sm text-emerald-100">
                        {successMessage}
                    </div>
                )}

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
                        <label htmlFor="email" className="block text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Email
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value={email}
                            autoComplete="email"
                            onChange={(event) => setEmail(event.target.value)}
                            required
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                        />
                    </div>

                    <div className="space-y-2">
                        <label
                            htmlFor="password"
                            className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            value={password}
                            autoComplete="current-password"
                            onChange={(event) => setPassword(event.target.value)}
                            required
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                        />
                    </div>

                    <div className="flex items-center justify-between text-sm">
                        <label className="flex items-center gap-2 text-[rgb(var(--text-secondary))]">
                            <input
                                type="checkbox"
                                name="remember"
                                checked={remember}
                                onChange={(event) => setRemember(event.target.checked)}
                                className="rounded border border-slate-700 bg-[rgb(var(--surface-panel))]"
                            />
                            Remember me
                        </label>
                        <Link to="/forgot-password" className="text-[rgb(var(--accent-primary))] hover:underline">
                            Forgot password?
                        </Link>
                    </div>

                    <button type="submit" className="btn-primary w-full justify-center" disabled={submitting}>
                        {submitting ? 'Signing in…' : 'Sign in'}
                    </button>
                </form>

                <div className="space-y-4">
                    <div className="relative flex items-center justify-center">
                        <span className="absolute w-full border-t border-slate-800" />
                        <span className="relative bg-[rgb(var(--surface-page))] px-3 text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                            Or continue with
                        </span>
                    </div>

                    <a href="/connect/google" className="btn-secondary flex w-full items-center justify-center gap-2">
                        <span>Google</span>
                    </a>
                </div>
            </section>

            <p className="text-center text-sm text-[rgb(var(--text-secondary))]">
                Don't have an account?{' '}
                <Link to="/register" className="text-[rgb(var(--accent-primary))] hover:underline">
                    Create one
                </Link>
            </p>
        </main>
    );
}
