import React from 'react';
import { Link } from 'react-router-dom';

export function AssistantCard({ assistant, workspaceId }) {
    const previewText = assistant.instructions?.trim() ?? '';
    const gender = typeof assistant.gender === 'string' ? assistant.gender.toLowerCase() : null;
    const genderLabel = gender === 'female' ? 'Female persona' : gender === 'male' ? 'Male persona' : null;

    return (
        <Link
            to={`/workspaces/${workspaceId}/assistants/${assistant.id}`}
            className="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--accent-primary))] focus-visible:ring-offset-2 focus-visible:ring-offset-[rgb(var(--surface-body))]"
            aria-label={`Edit ${assistant.name}`}
        >
            <article className="flex gap-4 rounded-xl border border-slate-800 bg-[rgb(var(--surface-panel))] p-4 transition group-hover:border-[rgb(var(--accent-primary))] group-hover:bg-[rgb(var(--surface-panel))]/90">
                <div className="h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                    {assistant.profilePicture ? (
                        <img
                            src={assistant.profilePicture}
                            alt={`${assistant.name}'s portrait`}
                            className="h-full w-full object-cover transition group-hover:scale-105"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center text-xs text-[rgb(var(--text-tertiary))]">
                            No photo
                        </div>
                    )}
                </div>

                <div className="flex flex-1 flex-col gap-2">
                    <div>
                        <p className="text-sm font-semibold text-[rgb(var(--text-primary))]">{assistant.name}</p>
                        <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                            Created {new Date(assistant.createdAt).toLocaleDateString()}
                        </p>
                    </div>

                    {genderLabel && (
                        <span className="w-fit rounded-full border border-slate-700 px-2 py-1 text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
                            {genderLabel}
                        </span>
                    )}

                    {previewText !== '' && (
                        <p className="text-xs text-[rgb(var(--text-secondary))]">
                            {previewText}
                        </p>
                    )}

                    {(assistant.defaultProvider || assistant.defaultModel) && (
                        <div className="flex flex-wrap gap-3 text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                            {assistant.defaultProvider && <span>Provider {assistant.defaultProvider}</span>}
                            {assistant.defaultModel && <span>Model {assistant.defaultModel}</span>}
                        </div>
                    )}
                </div>
            </article>
        </Link>
    );
}
