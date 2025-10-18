import React from 'react';
import { Navigate } from 'react-router-dom';
import { FullPageSpinner } from '../feedback/FullPageSpinner.jsx';
import { useAuth } from './AuthProvider.jsx';

export function GuestOnly({ children }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <FullPageSpinner />;
    }

    if (user) {
        return <Navigate to="/" replace />;
    }

    return children;
}
