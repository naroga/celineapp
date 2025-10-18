import React from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './auth/AuthProvider.jsx';
import { GuestOnly } from './auth/GuestOnly.jsx';
import { RequireAuth } from './auth/RequireAuth.jsx';
import { RequireAdmin } from './auth/RequireAdmin.jsx';
import { ForgotPasswordPage } from '../pages/ForgotPasswordPage.jsx';
import { HomePage } from '../pages/HomePage.jsx';
import { LoginPage } from '../pages/LoginPage.jsx';
import { RegisterPage } from '../pages/RegisterPage.jsx';
import { ResetPasswordPage } from '../pages/ResetPasswordPage.jsx';
import { WorkspaceDetailPage } from '../pages/WorkspaceDetailPage.jsx';
import { InviteLandingPage } from '../pages/InviteLandingPage.jsx';
import { AuthenticatedLayout } from './layout/AuthenticatedLayout.jsx';
import { CreateWorkspacePage } from '../pages/CreateWorkspacePage.jsx';
import { CreateAssistantWizardPage } from '../pages/CreateAssistantWizardPage.jsx';
import { AssistantSettingsPage } from '../pages/AssistantSettingsPage.jsx';
import { AdminAiDashboardPage } from '../pages/AdminAiDashboardPage.jsx';

export default function App() {
    return (
        <AuthProvider>
            <BrowserRouter>
                <Routes>
                    <Route
                        element={
                            <RequireAuth>
                                <AuthenticatedLayout />
                            </RequireAuth>
                        }
                    >
                        <Route index element={<HomePage />} />
                        <Route path="workspaces" element={<HomePage />} />
                        <Route path="workspaces/new" element={<CreateWorkspacePage />} />
                        <Route path="workspaces/:workspaceId" element={<WorkspaceDetailPage />} />
                        <Route path="workspaces/:workspaceId/assistants/new" element={<CreateAssistantWizardPage />} />
                        <Route path="workspaces/:workspaceId/assistants/:assistantId" element={<AssistantSettingsPage />} />
                        <Route
                            path="admin"
                            element={
                                <RequireAdmin>
                                    <AdminAiDashboardPage />
                                </RequireAdmin>
                            }
                        />
                        <Route path="*" element={<Navigate to="/" replace />} />
                    </Route>
                    <Route
                        path="/login"
                        element={
                            <GuestOnly>
                                <LoginPage />
                            </GuestOnly>
                        }
                    />
                    <Route
                        path="/register"
                        element={
                            <GuestOnly>
                                <RegisterPage />
                            </GuestOnly>
                        }
                    />
                    <Route
                        path="/forgot-password"
                        element={
                            <GuestOnly>
                                <ForgotPasswordPage />
                            </GuestOnly>
                        }
                    />
                    <Route
                        path="/reset-password/:selector"
                        element={
                            <GuestOnly>
                                <ResetPasswordPage />
                            </GuestOnly>
                        }
                    />
                    <Route path="/invites/:token" element={<InviteLandingPage />} />
                </Routes>
            </BrowserRouter>
        </AuthProvider>
    );
}
