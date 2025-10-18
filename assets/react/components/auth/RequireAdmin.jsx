import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from './AuthProvider.jsx';
import { FullPageSpinner } from '../feedback/FullPageSpinner.jsx';

export function RequireAdmin({ children }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <FullPageSpinner />;
    }

    const roles = Array.isArray(user?.roles) ? user.roles : [];

    if (!roles.includes('ROLE_ADMIN')) {
        return <Navigate to="/" replace />;
    }

    return children;
}

