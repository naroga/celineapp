import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { apiFetch } from '../../lib/apiClient.js';

const AuthContext = createContext(undefined);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchCurrentUser = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const data = await apiFetch('/api/auth/me');
            setUser(data);
        } catch (fetchError) {
            setUser(null);
            setError(fetchError);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void fetchCurrentUser();
    }, [fetchCurrentUser]);

    const value = useMemo(
        () => ({
            user,
            loading,
            error,
            setError,
            async login(credentials) {
                setError(null);

                const response = await apiFetch('/api/auth/login', {
                    method: 'POST',
                    body: credentials,
                });

                setUser(response);
                return response;
            },
            async register(payload) {
                setError(null);

                const response = await apiFetch('/api/auth/register', {
                    method: 'POST',
                    body: payload,
                });

                return response;
            },
            async requestPasswordReset(payload) {
                setError(null);

                const response = await apiFetch('/api/auth/forgot-password', {
                    method: 'POST',
                    body: payload,
                });

                return response;
            },
            async resetPassword(selector, payload) {
                setError(null);

                const response = await apiFetch(`/api/auth/reset-password/${selector}`, {
                    method: 'POST',
                    body: payload,
                });

                return response;
            },
            async logout() {
                await apiFetch('/api/auth/logout', { method: 'POST' });
                setUser(null);
            },
            refresh: fetchCurrentUser,
        }),
        [error, fetchCurrentUser, user],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }

    return context;
}
