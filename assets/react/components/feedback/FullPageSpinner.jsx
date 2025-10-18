import React from 'react';

export function FullPageSpinner() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-[rgb(var(--surface-page))] text-[rgb(var(--text-secondary))]">
            <div className="flex items-center gap-3">
                <span className="h-3 w-3 animate-ping rounded-full bg-[rgb(var(--accent-primary))]" />
                <span className="text-sm uppercase tracking-[0.3em]">Loading</span>
            </div>
        </div>
    );
}
