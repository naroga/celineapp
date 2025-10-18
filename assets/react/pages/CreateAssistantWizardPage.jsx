import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../lib/apiClient.js';
import {
    createAssistant,
    generateAssistantName,
    generateAssistantProfilePicture,
} from '../lib/assistantApi.js';

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

export function CreateAssistantWizardPage() {
    const { workspaceId } = useParams();
    const navigate = useNavigate();

    const [gender, setGender] = useState('female');
    const [name, setName] = useState('');
    const [nameLoading, setNameLoading] = useState(false);
    const [nameError, setNameError] = useState(null);

    const [profilePicture, setProfilePicture] = useState(null);
    const [pictureLoading, setPictureLoading] = useState(false);
    const [pictureError, setPictureError] = useState(null);

    const [instructions, setInstructions] = useState('');
    const [createError, setCreateError] = useState(null);
    const [creating, setCreating] = useState(false);

    const nameRef = useRef('');
    const nameRequestRef = useRef(0);
    const pictureRequestRef = useRef(0);

    const imagePreview = useMemo(() => buildImagePreview(profilePicture), [profilePicture]);

    const loadPicture = useCallback(
        async (selectedGender, assistantName) => {
            if (!workspaceId) {
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
        [workspaceId],
    );

    const loadName = useCallback(
        async (selectedGender, regenerate = false) => {
            if (!workspaceId) {
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

                if (regenerate) {
                    await loadPicture(selectedGender, generated);
                }
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
        [workspaceId, loadPicture],
    );

    useEffect(() => {
        void loadName(gender, true);
    }, [gender, workspaceId, loadName]);

    async function handleGenerateName() {
        await loadName(gender, false);
    }

    async function handleGeneratePicture() {
        await loadPicture(gender, nameRef.current);
    }

    async function handleSubmit(event) {
        event.preventDefault();

        setCreateError(null);

        if (!workspaceId) {
            setCreateError('Workspace not found.');
            return;
        }

        const trimmedName = name.trim();

        if (trimmedName === '') {
            setCreateError('Choose a name for the assistant before continuing.');
            return;
        }

        if (!profilePicture) {
            setCreateError('Generate a profile picture before creating the assistant.');
            return;
        }

        setCreating(true);

        try {
            await createAssistant(workspaceId, {
                name: trimmedName,
                gender,
                profilePicture,
                instructions: instructions.trim() === '' ? null : instructions.trim(),
            });

            navigate(`/workspaces/${workspaceId}?assistantCreated=1`, { replace: true });
        } catch (error) {
            if (error instanceof ApiError) {
                setCreateError(error.data?.message ?? 'Unable to create the assistant.');
            } else {
                setCreateError('Unable to create the assistant right now. Try again later.');
            }
        } finally {
            setCreating(false);
        }
    }

    return (
        <main className="mx-auto flex min-h-screen w-full max-w-4xl flex-col gap-8 px-6 py-12 text-[rgb(var(--text-primary))]">
            <div className="flex items-center justify-between gap-4">
                <div className="space-y-2">
                    <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Assistant wizard</p>
                    <h1 className="text-3xl font-semibold">Create a new assistant</h1>
                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                        Configure personality, name, and visuals. You can refine the playbook later in settings.
                    </p>
                </div>

                <Link
                    to={`/workspaces/${workspaceId}`}
                    className="pill-action"
                >
                    ← Back to workspace
                </Link>
            </div>

            <form onSubmit={handleSubmit} className="space-y-8">
                <section className="card space-y-5">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Persona</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Choose the persona your assistant should embody. We'll suggest names and visuals based on it.
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
                </section>

                <section className="card space-y-6">
                    <header className="space-y-1">
                        <h2 className="text-lg font-semibold">Name</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            We generate a name aligned with the persona. Feel free to tweak it or spin up another option.
                        </p>
                    </header>

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
                            We generate a portrait that matches the name and persona. Refresh until it fits.
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
                        <h2 className="text-lg font-semibold">Base instructions</h2>
                        <p className="text-sm text-[rgb(var(--text-secondary))]">
                            Optional guidelines that shape the assistant’s tone or intro. You can expand the playbook later.
                        </p>
                    </header>

                    <div className="space-y-2">
                        <label htmlFor="assistantInstructions" className="text-sm font-medium text-[rgb(var(--text-secondary))]">
                            Base instructions (optional)
                        </label>
                        <textarea
                            id="assistantInstructions"
                            value={instructions}
                            onChange={(event) => {
                                setInstructions(event.target.value);
                            }}
                            rows={5}
                            className="w-full rounded-lg border border-slate-700 bg-[rgb(var(--surface-panel))] px-3 py-2 text-sm text-[rgb(var(--text-primary))] focus:border-[rgb(var(--accent-primary))] focus:outline-none"
                            placeholder="e.g. Greet members by their first name, speak in concise bullet points, suggest next actions proactively."
                        />
                        <p className="text-xs text-[rgb(var(--text-tertiary))]">
                            The assistant will treat these as its default playbook unless you override it per conversation.
                        </p>
                    </div>
                </section>

                {createError && (
                    <p className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-100">
                        {createError}
                    </p>
                )}

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
                        disabled={creating || name.trim() === '' || !profilePicture}
                    >
                        {creating ? 'Creating…' : 'Create assistant'}
                    </button>
                </div>
            </form>
        </main>
    );
}
