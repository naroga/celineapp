import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { ApiError } from '../lib/apiClient.js';

export function ForgotPasswordPage() {
    const { requestPasswordReset } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [error, setError] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError(null);

        try {
            await requestPasswordReset({ email });
            navigate('/login?reset_requested=1', { replace: true });
        } catch (submitError) {
            if (submitError instanceof ApiError) {
                const message =
                    submitError.data?.messages?.[0] ?? submitError.data?.message ?? 'Unable to process request.';
                setError(message);
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
                    <h1 className="text-3xl font-semibold text-[rgb(var(--text-primary))]">Reset your password</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Enter the email associated with your account and we will send a reset link.
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
                        <label htmlFor="email" className="block text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            required
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                        />
                    </div>

                    <button type="submit" className="btn-primary w-full justify-center" disabled={submitting}>
                        {submitting ? 'Sending reset link…' : 'Send reset link'}
                    </button>
                </form>
            </section>

            <p className="text-center text-sm text-[rgb(var(--text-secondary))]">
                Remembered your password?{' '}
                <Link to="/login" className="text-[rgb(var(--accent-primary))] hover:underline">
                    Sign in
                </Link>
            </p>
        </main>
    );
}
