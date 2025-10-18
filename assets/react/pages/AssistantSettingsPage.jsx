import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../lib/apiClient.js';
import {
    fetchAssistant,
    generateAssistantName,
    generateAssistantProfilePicture,
    updateAssistant,
} from '../lib/assistantApi.js';
import { FullPageSpinner } from '../components/feedback/FullPageSpinner.jsx';

const GENDER_OPTIONS = [
    { value: 'female', label: 'Female' },
    { value: 'male', label: 'Male' },
];

function buildImagePreview(image) {
    if (!image) {
        return null;
    }

    if (image.type === 'base64') {
        const mime = image.mimeType || 'image/png';
        return `data:${mime};base64,${image.value}`;
    }

    return image.value;
}

function unpackProfilePicture(source) {
    if (typeof source !== 'string') {
        return null;
    }

    const trimmed = source.trim();

    if (trimmed === '') {
        return null;
    }

    const base64Index = trimmed.indexOf(';base64,');

    if (trimmed.startsWith('data:') && base64Index > -1) {
        const mimeType = trimmed.substring(5, base64Index).trim();
        const value = trimmed.substring(base64Index + ';base64,'.length).trim();

        return {
            type: 'base64',
            value,
            mimeType,
        };
    }

    return {
        type: 'url',
        value: trimmed,
        mimeType: null,
    };
}

function normalizeOptional(value) {
    if (value === undefined || value === null) {
        return null;
    }

    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
}

export function AssistantSettingsPage() {
    const { workspaceId, assistantId } = useParams();
    const navigate = useNavigate();

    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(null);
    const [assistant, setAssistant] = useState(null);

    const [gender, setGender] = useState('female');
    const [name, setName] = useState('');
    const [profilePicture, setProfilePicture] = useState(null);

    const [instructions, setInstructions] = useState('');
    const [email, setEmail] = useState('');
    const [phoneNumber, setPhoneNumber] = useState('');
    const [defaultProvider, setDefaultProvider] = useState('');
    const [defaultModel, setDefaultModel] = useState('');

    const [nameLoading, setNameLoading] = useState(false);
    const [nameError, setNameError] = useState(null);

    const [pictureLoading, setPictureLoading] = useState(false);
    const [pictureError, setPictureError] = useState(null);

    const [saveError, setSaveError] = useState(null);
    const [saveSuccess, setSaveSuccess] = useState(null);
    const [saving, setSaving] = useState(false);

    const nameRef = useRef('');
    const nameRequestRef = useRef(0);
    const pictureRequestRef = useRef(0);

    const imagePreview = useMemo(() => buildImagePreview(profilePicture), [profilePicture]);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            if (!workspaceId || !assistantId) {
                setLoadError('Assistant not found.');
                setLoading(false);
                return;
            }

            setLoading(true);
            setLoadError(null);

            try {
                const data = await fetchAssistant(workspaceId, assistantId);

                if (!isMounted) {
                    return;
                }

                setAssistant(data);
                setGender(data.gender ?? 'female');
                setName(data.name ?? '');
                nameRef.current = data.name ?? '';
                setProfilePicture(unpackProfilePicture(data.profilePicture));
                setInstructions(data.instructions ?? '');
                setEmail(data.email ?? '');
                setPhoneNumber(data.phoneNumber ?? '');
                setDefaultProvider(data.defaultProvider ?? '');
                setDefaultModel(data.defaultModel ?? '');
            } catch (error) {
                if (!isMounted) {
                    return;
                }

                if (error instanceof ApiError && error.status === 404) {
                    setLoadError('Assistant not found or you no longer have access.');
                } else if (error instanceof ApiError) {
                    setLoadError(error.data?.message ?? 'Unable to load assistant details.');
                } else {
                    setLoadError('Unable to load assistant details.');
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        }

        load().catch(() => {});

        return () => {
            isMounted = false;
        };
    }, [assistantId, workspaceId]);

    const loadName = useCallback(
        async (selectedGender) => {
            if (!workspaceId || !assistantId) {
                return;
            }

            const requestId = nameRequestRef.current + 1;
            nameRequestRef.current = requestId;
            setNameLoading(true);
            setNameError(null);

            try {
                const generated = await generateAssistantName(workspaceId, {
                    gender: selectedGender,
                });

                if (nameRequestRef.current !== requestId) {
                    return;
                }

                nameRef.current = generated;
                setName(generated);
            } catch (error) {
                if (error instanceof ApiError) {
                    setNameError(error.data?.message ?? 'Unable to generate a name.');
                } else {
                    setNameError('Unable to generate a name. Try again in a moment.');
                }
            } finally {
                setNameLoading(false);
            }
        },
        [assistantId, workspaceId],
    );

    const loadPicture = useCallback(
        async (selectedGender, assistantName) => {
            if (!workspaceId || !assistantId) {
                return;
            }

            const alignedName = assistantName ?? nameRef.current;

            if (alignedName.trim() === '') {
                return;
            }

            const requestId = pictureRequestRef.current + 1;
            pictureRequestRef.current = requestId;
            setPictureLoading(true);
            setPictureError(null);

            try {
                const image = await generateAssistantProfilePicture(workspaceId, {
                    gender: selectedGender,
                    name: alignedName,
                });

                if (pictureRequestRef.current !== requestId) {
                    return;
                }

                setProfilePicture(image);
            } catch (error) {
                if (error instanceof ApiError) {
                    setPictureError(error.data?.message ?? 'Unable to generate the profile picture.');
                } else {
                    setPictureError('Unable to generate the profile picture. Try again later.');
                }
            } finally {
                setPictureLoading(false);
            }
        },
        [assistantId, workspaceId],
    );

    async function handleGenerateName() {
        await loadName(gender);
    }

    async function handleGeneratePicture() {
        await loadPicture(gender, nameRef.current);
    }

    async function handleSubmit(event) {
        event.preventDefault();

        setSaveError(null);
        setSaveSuccess(null);

        if (!workspaceId || !assistantId) {
            setSaveError('Assistant not found.');
            return;
        }

        const trimmedName = name.trim();

        if (trimmedName === '') {
            setSaveError('Assistant name is required.');
            return;
        }

        if (!profilePicture) {
            setSaveError('Generate a profile picture before saving changes.');
            return;
        }

        setSaving(true);

        try {
            const updated = await updateAssistant(workspaceId, assistantId, {
                name: trimmedName,
                gender,
                profilePicture,
                instructions: normalizeOptional(instructions),
                email: normalizeOptional(email),
                phoneNumber: normalizeOptional(phoneNumber),
                defaultProvider: normalizeOptional(defaultProvider),
                defaultModel: normalizeOptional(defaultModel),
            });

            setAssistant(updated);
            setGender(updated.gender ?? gender);
            setName(updated.name ?? trimmedName);
            nameRef.current = updated.name ?? trimmedName;
            setProfilePicture(unpackProfilePicture(updated.profilePicture));
            setInstructions(updated.instructions ?? '');
            setEmail(updated.email ?? '');
            setPhoneNumber(updated.phoneNumber ?? '');
            setDefaultProvider(updated.defaultProvider ?? '');
            setDefaultModel(updated.defaultModel ?? '');
            setSaveSuccess('Assistant updated successfully.');
        } catch (error) {
            if (error instanceof ApiError) {
                const message = error.data?.message ?? error.data?.messages?.[0] ?? error.message;
                setSaveError(message ?? 'Unable to update the assistant.');
            } else {
                setSaveError('Unable to update the assistant right now. Try again later.');
            }
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return <FullPageSpinner />;
    }

    if (loadError) {
        return (
            <main className="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 px-6 py-16 text-[rgb(var(--text-primary))]">
                <div className="space-y-4 text-center">
                    <h1 className="text-3xl font-semibold">Assistant unavailable</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">{loadError}</p>
                    <div className="flex justify-center gap-3">
                        <button type="button" className="btn-secondary" onClick={() => navigate(`/workspaces/${workspaceId}`)}>
                            Back to workspace
                        </button>
                        <Link to="/" className="btn-primary">
                            Dashboard
                        </Link>
                    </div>
                </div>
            </main>
        );
    }

    return (
        <main className="mx-auto flex min-h-screen w-full max-w-4xl flex-col gap-8 px-6 py-12 text-[rgb(var(--text-primary))]">
            <div className="flex items-center justify-between gap-4">
                <div className="space-y-2">
                    <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Assistant settings</p>
                    <h1 className="text-3xl font-semibold">{assistant?.name ?? 'Assistant'}</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Adjust persona details, contact preferences, and defaults. Changes apply to every member of the workspace.
                    </p>
                </div>

                <Link
                    to={`/workspaces/${workspaceId}`}
                    className="pill-action"
                >
                    ← Back to workspace
                </Link>
            </div>

            {saveSuccess && (
                <div className="flex items-center justify-between gap-3 rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                    <p>{saveSuccess}</p>
                    <button
                        type="button"
                        className="btn-secondary"
                        onClick={() => {
                            setSaveSuccess(null);
                        }}
                    >
                        Dismiss
                    </button>
                </div>
            )}

            {saveError && (
                <div className="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {saveError}
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-8">
                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Identity</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Update the name and persona presented to workspace members.
                        </p>
                    </header>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {GENDER_OPTIONS.map((option) => {
                            const isActive = gender === option.value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    className={[
                                        'rounded-xl border px-4 py-4 text-left transition',
                                        isActive
                                            ? 'border-[rgb(var(--accent-primary))] bg-[rgb(var(--accent-primary))]/10 text-[rgb(var(--accent-primary))]'
                                            : 'border-slate-800 bg-[rgb(var(--surface-panel))] text-[rgb(var(--text-secondary))] hover:border-slate-600 hover:text-[rgb(var(--text-primary))]',
                                    ].join(' ')}
                                    onClick={() => {
                                        setGender(option.value);
                                    }}
                                >
                                    <span className="text-sm font-medium">{option.label}</span>
                                    <p className="mt-1 text-xs text-[rgb(var(--text-tertiary))]">
                                        {option.value === 'female'
                                            ? 'A thoughtful, empathetic professional tone.'
                                            : 'A confident, reassuring professional tone.'}
                                    </p>
                                </button>
                            );
                        })}
                    </div>

                    {nameError && (
                        <p className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-100">
                            {nameError}
                        </p>
                    )}

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label htmlFor="assistantName" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Assistant name
                        </label>
                        <div className="flex w-full items-center gap-3 sm:w-auto">
                            <input
                                id="assistantName"
                                type="text"
                                value={name}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    nameRef.current = value;
                                    setName(value);
                                }}
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none sm:min-w-[220px]"
                                placeholder="Assistant name"
                            />
                            <button
                                type="button"
                                className="btn-secondary flex items-center gap-2"
                                onClick={() => {
                                    handleGenerateName().catch(() => {});
                                }}
                                disabled={nameLoading}
                                aria-label="Regenerate name"
                            >
                                <span className="text-lg">⟲</span>
                                {nameLoading ? 'Generating…' : 'New name'}
                            </button>
                        </div>
                    </div>
                </section>

                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Profile picture</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Refresh the portrait to keep visuals aligned with the persona.
                        </p>
                    </header>

                    {pictureError && (
                        <p className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-100">
                            {pictureError}
                        </p>
                    )}

                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div className="h-48 w-48 overflow-hidden rounded-2xl border border-slate-800 bg-[rgb(var(--surface-panel))]">
                            {imagePreview ? (
                                <img
                                    src={imagePreview}
                                    alt={name === '' ? 'Assistant preview' : `${name} preview`}
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center text-sm text-[rgb(var(--text-tertiary))]">
                                    {pictureLoading ? 'Generating…' : 'No image yet'}
                                </div>
                            )}
                        </div>

                        <div className="flex gap-3">
                            <button
                                type="button"
                                className="btn-secondary flex items-center gap-2"
                                onClick={() => {
                                    handleGeneratePicture().catch(() => {});
                                }}
                                disabled={pictureLoading || name.trim() === ''}
                            >
                                <span className="text-lg">⟲</span>
                                {pictureLoading ? 'Generating…' : 'New portrait'}
                            </button>
                        </div>
                    </div>
                </section>

                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Contact details</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Optional metadata displayed when routing conversations or escalations.
                        </p>
                    </header>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label htmlFor="assistantEmail" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                                Contact email (optional)
                            </label>
                            <input
                                id="assistantEmail"
                                type="email"
                                value={email}
                                onChange={(event) => setEmail(event.target.value)}
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                placeholder="assistant@example.com"
                            />
                        </div>

                        <div className="space-y-2">
                            <label htmlFor="assistantPhoneNumber" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                                Contact phone (optional)
                            </label>
                            <input
                                id="assistantPhoneNumber"
                                type="text"
                                value={phoneNumber}
                                onChange={(event) => setPhoneNumber(event.target.value)}
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                placeholder="+1 555-123-4567"
                            />
                        </div>
                    </div>
                </section>

                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Default AI configuration</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Optionally override the default provider and model for this assistant.
                        </p>
                    </header>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <label htmlFor="assistantProvider" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                                Default provider (optional)
                            </label>
                            <input
                                id="assistantProvider"
                                type="text"
                                value={defaultProvider}
                                onChange={(event) => setDefaultProvider(event.target.value)}
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                placeholder="openai"
                            />
                        </div>

                        <div className="space-y-2">
                            <label htmlFor="assistantModel" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                                Default model (optional)
                            </label>
                            <input
                                id="assistantModel"
                                type="text"
                                value={defaultModel}
                                onChange={(event) => setDefaultModel(event.target.value)}
                                className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                                placeholder="gpt-5.1-pro"
                            />
                        </div>
                    </div>
                </section>

                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Base instructions</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Tune the assistant’s playbook. These guidelines are shared across conversations unless overridden.
                        </p>
                    </header>

                    <div className="space-y-2">
                        <label htmlFor="assistantInstructions" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Base instructions (optional)
                        </label>
                        <textarea
                            id="assistantInstructions"
                            value={instructions}
                            onChange={(event) => setInstructions(event.target.value)}
                            rows={6}
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            placeholder="e.g. Greet members by their first name, speak in concise bullet points, suggest next actions proactively."
                        />
                        <p className="text-xs text-[rgb(var(--text-tertiary))]">
                            Leave blank to inherit the workspace defaults.
                        </p>
                    </div>
                </section>

                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        to={`/workspaces/${workspaceId}`}
                        className="btn-secondary text-center"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        className="btn-primary"
                        disabled={saving || name.trim() === '' || !profilePicture}
                    >
                        {saving ? 'Saving…' : 'Save changes'}
                    </button>
                </div>
            </form>
        </main>
    );
}
