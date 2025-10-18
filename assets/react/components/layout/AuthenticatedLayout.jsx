import React from 'react';
import { Outlet } from 'react-router-dom';
import { Navbar } from './Navbar.jsx';

export function AuthenticatedLayout() {
    return (
        <div className="min-h-screen bg-[rgb(var(--surface-page))] text-[rgb(var(--text-primary))]">
            <Navbar />
            <div className="pt-20 sm:pt-24">
                <Outlet />
            </div>
        </div>
    );
}
