import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthProvider.jsx';
import { fetchWorkspaces } from '../../lib/workspaceApi.js';
import { ApiError } from '../../lib/apiClient.js';

export function Navbar() {
    const { user, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();

    const [workspaceMenuOpen, setWorkspaceMenuOpen] = useState(false);
    const [workspaceLoading, setWorkspaceLoading] = useState(true);
    const [workspaceError, setWorkspaceError] = useState(null);
    const [workspaces, setWorkspaces] = useState([]);

    const menuRef = useRef(null);
    const roles = Array.isArray(user?.roles) ? user.roles : [];
    const isAdmin = roles.includes('ROLE_ADMIN');

    const loadWorkspaces = useCallback(async () => {
        setWorkspaceLoading(true);
        setWorkspaceError(null);

        try {
            const data = await fetchWorkspaces();
            setWorkspaces(data);
        } catch (error) {
            if (error instanceof ApiError) {
                setWorkspaceError(error.data?.message ?? 'Unable to load workspaces.');
            } else {
                setWorkspaceError('Unable to load workspaces.');
            }
        } finally {
            setWorkspaceLoading(false);
        }
    }, []);

    useEffect(() => {
        void loadWorkspaces();
    }, [loadWorkspaces, location.pathname]);

    useEffect(() => {
        if (!workspaceMenuOpen) {
            return;
        }

        function handleClick(event) {
            if (menuRef.current && !menuRef.current.contains(event.target)) {
                setWorkspaceMenuOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClick);

        return () => {
            document.removeEventListener('mousedown', handleClick);
        };
    }, [workspaceMenuOpen]);

    function workspaceLinkClasses(isActive) {
        return [
            'flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition',
            isActive ? 'bg-[rgb(var(--surface-panel))] text-[rgb(var(--accent-primary))]' : 'hover:bg-slate-800/60',
        ].join(' ');
    }

    async function handleLogout() {
        try {
            await logout();
            navigate('/login', { replace: true });
        } catch {
            // Error feedback handled elsewhere.
        }
    }

    return (
        <header className="fixed inset-x-0 top-0 z-40 border-b border-slate-900/60 bg-[rgb(var(--surface-body))]/80 backdrop-blur-md">
            <nav className="mx-auto flex max-w-workspace items-center justify-between gap-6 px-6 py-4">
                <div className="flex items-center gap-8">
                    <Link to="/" className="text-lg font-semibold text-[rgb(var(--text-primary))]">
                        Naroga Assistant
                    </Link>

                    <NavLink
                        to="/"
                        className={({ isActive }) =>
                            [
                                'text-sm font-medium transition',
                                isActive
                                    ? 'text-[rgb(var(--accent-primary))]'
                                    : 'text-[rgb(var(--text-secondary))] hover:text-[rgb(var(--text-primary))]',
                            ].join(' ')
                        }
                        end
                    >
                        Home
                    </NavLink>

                    {isAdmin && (
                        <NavLink
                            to="/admin"
                            className={({ isActive }) =>
                                [
                                    'text-sm font-medium transition',
                                    isActive
                                        ? 'text-[rgb(var(--accent-primary))]'
                                        : 'text-[rgb(var(--text-secondary))] hover:text-[rgb(var(--text-primary))]',
                                ].join(' ')
                            }
                            end
                        >
                            Admin
                        </NavLink>
                    )}

                    <div className="relative" ref={menuRef}>
                        <button
                            type="button"
                            className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-[rgb(var(--text-secondary))] transition hover:text-[rgb(var(--text-primary))]"
                            onClick={() => {
                                setWorkspaceMenuOpen((previous) => !previous);
                            }}
                            aria-expanded={workspaceMenuOpen}
                            aria-haspopup="true"
                        >
                            Workspaces
                            <span aria-hidden>▾</span>
                        </button>

                        {workspaceMenuOpen && (
                            <div className="absolute left-0 top-full mt-2 w-72 rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-3 shadow-panel">
                                <div className="max-h-64 overflow-y-auto">
                                    {workspaceLoading && (
                                        <p className="px-2 py-2 text-sm text-[rgb(var(--text-secondary))]">Loading…</p>
                                    )}
                                    {!workspaceLoading && workspaceError && (
                                        <p className="px-2 py-2 text-sm text-red-200">{workspaceError}</p>
                                    )}
                                    {!workspaceLoading && !workspaceError && workspaces.length === 0 && (
                                        <p className="px-2 py-2 text-sm text-[rgb(var(--text-secondary))]">
                                            No workspaces yet.
                                        </p>
                                    )}
                                    {!workspaceLoading &&
                                        !workspaceError &&
                                        workspaces.map((workspace) => (
                                            <NavLink
                                                key={workspace.id}
                                                to={`/workspaces/${workspace.id}`}
                                                className={({ isActive }) => workspaceLinkClasses(isActive)}
                                                onClick={() => {
                                                    setWorkspaceMenuOpen(false);
                                                }}
                                            >
                                                <span>{workspace.name}</span>
                                                <span className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {workspace.role ?? 'member'}
                                                </span>
                                            </NavLink>
                                        ))}
                                </div>

                                <div className="mt-3 border-t border-slate-800/70 pt-3">
                                    <Link
                                        to="/workspaces/new"
                                        className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-[rgb(var(--accent-primary))] transition hover:bg-slate-800/60"
                                        onClick={() => {
                                            setWorkspaceMenuOpen(false);
                                        }}
                                    >
                                        <span className="text-lg">+</span>
                                        Create workspace
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="flex items-center gap-4">
                    <div className="text-right text-xs text-[rgb(var(--text-secondary))]">
                        <p className="font-semibold text-[rgb(var(--text-primary))]">
                            {user?.firstName} {user?.lastName}
                        </p>
                        <p>{user?.email}</p>
                    </div>
                    <button
                        type="button"
                        className="btn-secondary"
                        onClick={() => {
                            handleLogout().catch(() => {});
                        }}
                    >
                        Sign out
                    </button>
                </div>
            </nav>
        </header>
    );
}
