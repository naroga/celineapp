import React, { useMemo, useState } from 'react';
import { Link, Navigate, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { useAuth } from '../components/auth/AuthProvider.jsx';
import { ApiError } from '../lib/apiClient.js';

export function ResetPasswordPage() {
    const { selector } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { resetPassword } = useAuth();

    const token = useMemo(() => searchParams.get('token') ?? '', [searchParams]);

    const [form, setForm] = useState({
        password: '',
        passwordConfirmation: '',
    });
    const [errors, setErrors] = useState([]);
    const [submitting, setSubmitting] = useState(false);

    if (!token) {
        return <Navigate to="/forgot-password" replace />;
    }

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
            await resetPassword(selector, { ...form, token });
            navigate('/login?password_reset=1', { replace: true });
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
        <main className="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center gap-8 px-6 py-12">
            <section className="space-y-6">
                <header className="space-y-2 text-center">
                    <h1 className="text-3xl font-semibold text-[rgb(var(--text-primary))]">Choose a new password</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Create a strong password to keep your workspace secure.
                    </p>
                </header>

                {errors.length > 0 && (
                    <div className="space-y-2 rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-sm text-red-100">
                        {errors.map((message) => (
                            <p key={message}>{message}</p>
                        ))}
                    </div>
                )}

                <form
                    onSubmit={handleSubmit}
                    className="space-y-5 rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-6 shadow-panel"
                >
                    <div className="space-y-2">
                        <label
                            htmlFor="password"
                            className="block text-sm font-medium text-[rgb(var(--text-secondary))]"
                        >
                            New password
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

                    <button type="submit" className="btn-primary w-full justify-center" disabled={submitting}>
                        {submitting ? 'Updating password…' : 'Update password'}
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
