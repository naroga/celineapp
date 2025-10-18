import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { FullPageSpinner } from '../feedback/FullPageSpinner.jsx';
import { useAuth } from './AuthProvider.jsx';

export function RequireAuth({ children }) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <FullPageSpinner />;
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return children;
}
