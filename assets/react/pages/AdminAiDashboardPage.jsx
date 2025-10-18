import React, { useEffect, useMemo, useState } from 'react';
import { fetchAiDashboard, fetchAiInteractionDetail } from '../lib/adminApi.js';
import { ApiError } from '../lib/apiClient.js';
import { FullPageSpinner } from '../components/feedback/FullPageSpinner.jsx';

const numberFormatter = new Intl.NumberFormat('en-US');
const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function formatNumber(value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return '—';
    }

    return numberFormatter.format(value);
}

function formatCost(cents) {
    if (typeof cents !== 'number' || Number.isNaN(cents)) {
        return '—';
    }

    return currencyFormatter.format(cents / 100);
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString();
}

function formatDurationMs(value) {
    if (typeof value !== 'number' || Number.isNaN(value)) {
        return '—';
    }

    if (value >= 1000) {
        return `${(value / 1000).toFixed(2)} s`;
    }

    return `${value.toFixed(3)} ms`;
}

function StatusBadge({ status }) {
    const normalized = status === 'failure' ? 'failure' : 'success';
    const styles =
        normalized === 'success'
            ? 'bg-emerald-500/15 text-emerald-200 border border-emerald-500/40'
            : 'bg-rose-500/15 text-rose-200 border border-rose-500/40';

    return (
        <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium uppercase tracking-[0.25em] ${styles}`}>
            {normalized}
        </span>
    );
}

function PromptBadge({ promptType }) {
    const label = promptType.replace(/_/g, ' ');

    return (
        <span className="inline-flex items-center rounded-full border border-slate-700 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
            {label}
        </span>
    );
}

export function AdminAiDashboardPage() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedInteractionId, setSelectedInteractionId] = useState(null);
    const [interactionDetail, setInteractionDetail] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState(null);

    useEffect(() => {
        let isMounted = true;

        async function load() {
            setLoading(true);
            setError(null);

            try {
                const response = await fetchAiDashboard();

                if (!isMounted) {
                    return;
                }

                setData(response);
            } catch (requestError) {
                if (!isMounted) {
                    return;
                }

                if (requestError instanceof ApiError) {
                    setError(requestError.data?.message ?? 'Unable to load AI dashboard.');
                } else {
                    setError('Unable to load AI dashboard.');
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
    }, []);

    useEffect(() => {
        if (!selectedInteractionId) {
            setInteractionDetail(null);
            setDetailError(null);
            setDetailLoading(false);

            return;
        }

        let isMounted = true;

        async function loadDetail() {
            setDetailLoading(true);
            setDetailError(null);

            try {
                const response = await fetchAiInteractionDetail(selectedInteractionId);

                if (!isMounted) {
                    return;
                }

                setInteractionDetail(response);
            } catch (requestError) {
                if (!isMounted) {
                    return;
                }

                setInteractionDetail(null);

                if (requestError instanceof ApiError) {
                    setDetailError(requestError.data?.message ?? 'Unable to load interaction detail.');
                } else {
                    setDetailError('Unable to load interaction detail.');
                }
            } finally {
                if (isMounted) {
                    setDetailLoading(false);
                }
            }
        }

        loadDetail().catch(() => {});

        return () => {
            isMounted = false;
        };
    }, [selectedInteractionId]);

    useEffect(() => {
        if (!selectedInteractionId) {
            return;
        }

        function handleKeyDown(event) {
            if (event.key === 'Escape') {
                setSelectedInteractionId(null);
                setInteractionDetail(null);
                setDetailError(null);
                setDetailLoading(false);
            }
        }

        window.addEventListener('keydown', handleKeyDown);

        return () => {
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [selectedInteractionId]);

    const summaryCards = useMemo(() => {
        if (!data) {
            return [];
        }

        return [
            {
                key: 'totalInteractions',
                label: 'Total interactions',
                value: formatNumber(data.summary.totalInteractions),
                helper: `${formatNumber(data.summary.promptTokens)} prompt • ${formatNumber(data.summary.completionTokens)} completion tokens`,
            },
            {
                key: 'failureCount',
                label: 'Failed interactions',
                value: formatNumber(data.summary.totalFailures),
                helper: `${formatNumber(data.summary.failureRate)}% failure rate`,
                tone: 'warning',
            },
            {
                key: 'totalTokens',
                label: 'Total tokens',
                value: formatNumber(data.summary.totalTokens),
                helper: `${formatCost(data.summary.costCents)} estimated cost`,
            },
        ];
    }, [data]);

    function handleInteractionClick(interactionId) {
        if (!interactionId) {
            return;
        }

        setSelectedInteractionId(interactionId);
    }

    function closeDetail() {
        setSelectedInteractionId(null);
        setInteractionDetail(null);
        setDetailError(null);
        setDetailLoading(false);
    }

    function renderPromptMessages(messages) {
        if (!Array.isArray(messages) || messages.length === 0) {
            return <p className="text-sm text-[rgb(var(--text-secondary))]">No prompt messages recorded.</p>;
        }

        return (
            <div className="space-y-3">
                {messages.map((message, index) => (
                    <article
                        key={`${message.role ?? 'message'}-${index}`}
                        className="rounded-lg border border-slate-800/70 bg-slate-950/60 p-4"
                    >
                        <div className="flex items-center justify-between text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                            <span>{message.role ?? 'unknown'}</span>
                            <span>#{index + 1}</span>
                        </div>
                        <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-[rgb(var(--text-primary))]">
                            {message.content ?? ''}
                        </p>
                    </article>
                ))}
            </div>
        );
    }

    function renderConversationTurns(turns) {
        if (!Array.isArray(turns) || turns.length === 0) {
            return <p className="text-sm text-[rgb(var(--text-secondary))]">No conversation history captured.</p>;
        }

        return (
            <div className="space-y-4">
                {turns.map((turn) => {
                    const content = turn.content;
                    let body = null;

                    if (content && typeof content === 'object' && !Array.isArray(content)) {
                        if (typeof content.text === 'string') {
                            body = (
                                <p className="whitespace-pre-wrap text-sm leading-6 text-[rgb(var(--text-primary))]">
                                    {content.text}
                                </p>
                            );
                        } else {
                            body = (
                                <pre className="overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                    {JSON.stringify(content, null, 2)}
                                </pre>
                            );
                        }
                    } else if (typeof content === 'string') {
                        body = (
                            <p className="whitespace-pre-wrap text-sm leading-6 text-[rgb(var(--text-primary))]">
                                {content}
                            </p>
                        );
                    }

                    return (
                        <article key={turn.id} className="rounded-xl border border-slate-800/70 bg-slate-950/60 p-4">
                            <header className="flex flex-wrap items-baseline justify-between gap-3">
                                <div>
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
                                        {turn.role}
                                    </h3>
                                    <p className="text-xs text-[rgb(var(--text-tertiary))]">{turn.promptType}</p>
                                </div>
                                <div className="text-right text-xs text-[rgb(var(--text-tertiary))]">
                                    <p>{formatDate(turn.createdAt)}</p>
                                    {(turn.tokens?.prompt ?? null) !== null && (
                                        <p>
                                            {formatNumber(turn.tokens.prompt)} prompt • {formatNumber(turn.tokens.completion)} completion
                                        </p>
                                    )}
                                </div>
                            </header>

                            {body && <div className="mt-3">{body}</div>}

                            {turn.metadata && Object.keys(turn.metadata).length > 0 && (
                                <details className="mt-3 text-xs text-[rgb(var(--text-tertiary))]">
                                    <summary className="cursor-pointer text-[rgb(var(--text-secondary))]">Metadata</summary>
                                    <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                        {JSON.stringify(turn.metadata, null, 2)}
                                    </pre>
                                </details>
                            )}
                        </article>
                    );
                })}
            </div>
        );
    }

    const detailInteraction = interactionDetail?.interaction ?? null;
    const detailMetadata = interactionDetail?.metadata ?? null;
    const detailPromptDetails = detailMetadata?.prompt?.details ?? null;
    const detailResult = detailMetadata?.result ?? null;
    const detailException = detailMetadata?.exception ?? null;
    const detailConversation = interactionDetail?.conversation ?? null;
    const detailOverrides = detailMetadata?.overrides ?? null;
    const detailTools = interactionDetail?.tools ?? [];
    const promptMetaEntries = detailPromptDetails
        ? Object.entries(detailPromptDetails).filter(([key, value]) => {
              if (key === 'messages') {
                  return false;
              }

              if (value === null || value === '' || value === undefined) {
                  return false;
              }

              if (Array.isArray(value) && value.length === 0) {
                  return false;
              }

              if (typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length === 0) {
                  return false;
              }

              return true;
          })
        : [];

    if (loading) {
        return <FullPageSpinner />;
    }

    return (
        <main className="mx-auto flex min-h-screen max-w-workspace flex-col gap-6 px-6 pb-12 pt-6 text-[rgb(var(--text-primary))]">
            <header className="space-y-3">
                <span className="badge uppercase tracking-[0.32em]">Admin Dashboard</span>
                <div>
                    <h1 className="text-3xl font-semibold sm:text-4xl">AI Operations Overview</h1>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-[rgb(var(--text-secondary))]">
                        Monitor every AI interaction across workspaces. Track failures, model usage, token spend, and conversation performance.
                    </p>
                </div>
            </header>

            {error && (
                <div className="rounded-xl border border-rose-500/50 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">{error}</div>
            )}

            {data && (
                <>
                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {summaryCards.map((card) => (
                            <article
                                key={card.key}
                                className={[
                                    'card space-y-3 shadow-panel',
                                    card.tone === 'warning'
                                        ? 'border-rose-500/40 bg-rose-500/10'
                                        : 'border-slate-800/70 bg-[rgb(var(--surface-body))]',
                                ].join(' ')}
                            >
                                <header className="flex items-baseline justify-between">
                                    <h2 className="text-sm font-medium uppercase tracking-[0.3em] text-[rgb(var(--text-secondary))]">
                                        {card.label}
                                    </h2>
                                </header>
                                <p className="text-3xl font-semibold text-[rgb(var(--text-primary))]">{card.value}</p>
                                {card.helper && (
                                    <p className="text-xs text-[rgb(var(--text-tertiary))]">{card.helper}</p>
                                )}
                            </article>
                        ))}
                    </section>

                    <section className="grid gap-6 lg:grid-cols-[2fr,1fr]">
                        <article className="card space-y-5">
                            <header className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-[rgb(var(--text-primary))]">Recent interactions</h2>
                                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                                        Latest provider calls across all assistants and workspaces.
                                    </p>
                                </div>
                                <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                    {data.recentInteractions.length} listed
                                </span>
                            </header>

                            <div className="overflow-hidden overflow-x-auto rounded-xl border border-slate-800/70">
                                <table className="min-w-full divide-y divide-slate-800 text-left text-sm">
                                    <thead className="bg-slate-950/80 text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                        <tr>
                                            <th className="px-4 py-3">Time</th>
                                            <th className="px-4 py-3">Provider</th>
                                            <th className="px-4 py-3">Prompt</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3 text-right">Tokens</th>
                                            <th className="px-4 py-3 text-right">Cost</th>
                                            <th className="px-4 py-3">Context</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-900">
                                        {data.recentInteractions.length === 0 && (
                                            <tr>
                                                <td
                                                    className="px-4 py-5 text-center text-sm text-[rgb(var(--text-secondary))]"
                                                    colSpan={7}
                                                >
                                                    No interactions logged yet.
                                                </td>
                                            </tr>
                                        )}
                                        {data.recentInteractions.map((interaction) => (
                                            <tr
                                                key={interaction.id}
                                                className="cursor-pointer hover:bg-slate-950/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-[rgb(var(--accent-primary))]"
                                                onClick={() => {
                                                    handleInteractionClick(interaction.id);
                                                }}
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter' || event.key === ' ') {
                                                        event.preventDefault();
                                                        handleInteractionClick(interaction.id);
                                                    }
                                                }}
                                                role="button"
                                                tabIndex={0}
                                            >
                                                <td className="px-4 py-4 align-top text-xs text-[rgb(var(--text-tertiary))]">
                                                    {formatDate(interaction.createdAt)}
                                                </td>
                                                <td className="px-4 py-4 align-top space-y-1">
                                                    <p className="text-sm font-medium text-[rgb(var(--text-primary))]">
                                                        {interaction.provider}
                                                    </p>
                                                    <p className="text-xs text-[rgb(var(--text-secondary))]">
                                                        {interaction.model ?? 'default model'}
                                                    </p>
                                                    {interaction.workspace && (
                                                        <p className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                            {interaction.workspace.name}
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-4 align-top">
                                                    <PromptBadge promptType={interaction.promptType} />
                                                </td>
                                                <td className="px-4 py-4 align-top">
                                                    <StatusBadge status={interaction.status} />
                                                </td>
                                                <td className="px-4 py-4 align-top text-right text-sm text-[rgb(var(--text-secondary))]">
                                                    {formatNumber(interaction.tokens.total) || '—'}
                                                </td>
                                                <td className="px-4 py-4 align-top text-right text-sm text-[rgb(var(--text-secondary))]">
                                                    {formatCost(interaction.costCents)}
                                                </td>
                                                <td className="px-4 py-4 align-top text-sm text-[rgb(var(--text-secondary))]">
                                                    {interaction.context ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <article className="card space-y-5">
                            <header className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-[rgb(var(--text-primary))]">Recent failures</h2>
                                    <p className="text-sm text-[rgb(var(--text-secondary))]">
                                        Track errors to spot provider issues quickly.
                                    </p>
                                </div>
                                <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                    {data.recentFailures.length}
                                </span>
                            </header>

                            {data.recentFailures.length === 0 ? (
                                <div className="rounded-xl border border-slate-800/70 bg-slate-950/70 px-4 py-6 text-sm text-[rgb(var(--text-secondary))]">
                                    No recent failures. All providers are responding correctly.
                                </div>
                            ) : (
                                <ul className="space-y-4">
                                    {data.recentFailures.map((failure) => (
                                        <li
                                            key={failure.id}
                                            className="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
                                        >
                                            <div className="flex items-baseline justify-between gap-3">
                                                <div>
                                                    <p className="font-semibold text-rose-100">
                                                        {failure.error?.code ?? 'Unknown failure'}
                                                    </p>
                                                    <p className="text-xs text-rose-200/80">
                                                        {failure.error?.message ?? 'No error message provided.'}
                                                    </p>
                                                </div>
                                                <p className="text-[10px] uppercase tracking-[0.3em] text-rose-200/80">
                                                    {formatDate(failure.createdAt)}
                                                </p>
                                            </div>
                                            <p className="mt-2 text-xs text-rose-200/80">
                                                {failure.provider} • {failure.model ?? 'default'} •{' '}
                                                {failure.promptType.replace(/_/g, ' ')}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </article>
                    </section>

                    <section className="card space-y-6">
                        <header className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-semibold text-[rgb(var(--text-primary))]">Conversations</h2>
                                <p className="text-sm text-[rgb(var(--text-secondary))]">
                                    Consolidated metrics for the most recent conversations.
                                </p>
                            </div>
                            <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                {data.conversations.length} tracked
                            </span>
                        </header>

                        {data.conversations.length === 0 ? (
                            <div className="rounded-xl border border-slate-800/70 bg-slate-950/70 px-5 py-6 text-sm text-[rgb(var(--text-secondary))]">
                                No conversations have been started yet.
                            </div>
                        ) : (
                            <div className="grid gap-4 lg:grid-cols-2">
                                {data.conversations.map((conversation) => (
                                    <article
                                        key={conversation.id}
                                        className="rounded-xl border border-slate-800/70 bg-[rgb(var(--surface-body))] p-5 shadow-panel"
                                    >
                                        <header className="flex items-start justify-between gap-4">
                                            <div className="space-y-1">
                                                <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">
                                                    {conversation.title ?? 'Untitled conversation'}
                                                </h3>
                                                <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {conversation.assistant.name} • {conversation.workspace.name}
                                                </p>
                                            </div>
                                            <span className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                {conversation.provider ?? 'default'}
                                            </span>
                                        </header>

                                        <dl className="mt-4 grid grid-cols-2 gap-3 text-sm text-[rgb(var(--text-secondary))]">
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Model
                                                </dt>
                                                <dd className="mt-1 text-[rgb(var(--text-primary))]">
                                                    {conversation.model ?? 'default'}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Last activity
                                                </dt>
                                                <dd className="mt-1">{formatDate(conversation.updatedAt)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Interactions
                                                </dt>
                                                <dd className="mt-1 text-[rgb(var(--text-primary))]">
                                                    {formatNumber(conversation.metrics.interactionCount)} total (
                                                    {formatNumber(conversation.metrics.failureCount)} failures)
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Tokens
                                                </dt>
                                                <dd className="mt-1 text-[rgb(var(--text-primary))]">
                                                    {formatNumber(conversation.metrics.totalTokens)} (
                                                    {formatNumber(conversation.metrics.promptTokens)} prompt ·{' '}
                                                    {formatNumber(conversation.metrics.completionTokens)} completion)
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Cost
                                                </dt>
                                                <dd className="mt-1 text-[rgb(var(--text-primary))]">
                                                    {formatCost(conversation.metrics.costCents)}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    Created
                                                </dt>
                                                <dd className="mt-1">{formatDate(conversation.createdAt)}</dd>
                                            </div>
                                        </dl>
                                    </article>
                                ))}
                            </div>
                        )}
                    </section>
                </>
            )}

            {selectedInteractionId && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-8"
                    onClick={() => {
                        closeDetail();
                    }}
                    role="presentation"
                >
                    <div
                        className="modal-shell w-full max-w-5xl bg-[rgb(var(--surface-body))] shadow-panel"
                        onClick={(event) => {
                            event.stopPropagation();
                        }}
                        role="dialog"
                        aria-modal="true"
                    >
                        <header className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-xl font-semibold text-[rgb(var(--text-primary))]">Interaction detail</h2>
                                <p className="text-sm text-[rgb(var(--text-secondary))]">
                                    {detailInteraction?.provider ?? '—'} · {detailInteraction?.model ?? 'default model'}
                                </p>
                            </div>
                            <button
                                type="button"
                                className="btn-secondary"
                                onClick={() => {
                                    closeDetail();
                                }}
                            >
                                Close
                            </button>
                        </header>

                        <div className="mt-5 space-y-6">
                            {detailLoading && (
                                <div className="py-12 text-center text-sm text-[rgb(var(--text-secondary))]">
                                    Loading interaction…
                                </div>
                            )}

                            {!detailLoading && detailError && (
                                <div className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                                    {detailError}
                                </div>
                            )}

                            {!detailLoading && !detailError && detailInteraction && (
                                <div className="space-y-5">
                                    <section className="card space-y-4 border-slate-800/70 bg-[rgb(var(--surface-body))]">
                                        <header className="flex flex-wrap items-baseline justify-between gap-3">
                                            <div>
                                                <h3 className="text-lg font-semibold text-[rgb(var(--text-primary))]">
                                                    {detailInteraction.workspace?.name ?? 'Unknown workspace'}
                                                </h3>
                                                <p className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {detailInteraction.assistant?.name ?? 'Unknown assistant'}
                                                </p>
                                            </div>
                                            <div className="text-right text-xs text-[rgb(var(--text-tertiary))]">
                                                <p>{formatDate(detailInteraction.createdAt)}</p>
                                                <p>
                                                    {formatNumber(detailInteraction.tokens?.prompt ?? null)} prompt · {formatNumber(detailInteraction.tokens?.completion ?? null)} completion ·{' '}
                                                    {formatNumber(detailInteraction.tokens?.total ?? null)} total
                                                </p>
                                                <p>{formatCost(detailInteraction.costCents)}</p>
                                            </div>
                                        </header>
                                    </section>

                                    {detailPromptDetails && (
                                        <section className="card space-y-4">
                                            <header className="flex items-center justify-between">
                                                <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">Prompt</h3>
                                                <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {detailMetadata?.prompt?.type ?? 'unknown'}
                                                </span>
                                            </header>

                                            {Array.isArray(detailPromptDetails.messages) && detailPromptDetails.messages.length > 0 && (
                                                <div className="space-y-3">
                                                    <h4 className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">Messages</h4>
                                                    {renderPromptMessages(detailPromptDetails.messages)}
                                                </div>
                                            )}

                                            {promptMetaEntries.length > 0 && (
                                                <dl className="grid gap-3 sm:grid-cols-2">
                                                    {promptMetaEntries.map(([key, value]) => (
                                                        <div key={key}>
                                                            <dt className="text-[10px] uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                                {key}
                                                            </dt>
                                                            <dd className="mt-1 text-sm text-[rgb(var(--text-secondary))]">
                                                                {typeof value === 'object'
                                                                    ? JSON.stringify(value, null, 2)
                                                                    : String(value)}
                                                            </dd>
                                                        </div>
                                                    ))}
                                                </dl>
                                            )}
                                        </section>
                                    )}

                                    {detailResult && (
                                        <section className="card space-y-3">
                                            <header className="flex items-center justify-between">
                                                <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">Result</h3>
                                                <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {detailResult.type ?? 'unknown'}
                                                </span>
                                            </header>

                                            {typeof detailResult.content === 'string' && detailResult.content !== '' && (
                                                <p className="whitespace-pre-wrap text-sm leading-6 text-[rgb(var(--text-primary))]">
                                                    {detailResult.content}
                                                </p>
                                            )}

                                            {detailResult.content && typeof detailResult.content === 'object' && !Array.isArray(detailResult.content) && (
                                                <pre className="overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                    {JSON.stringify(detailResult.content, null, 2)}
                                                </pre>
                                            )}

                                            {detailResult.metadata && Object.keys(detailResult.metadata).length > 0 && (
                                                <details className="text-xs text-[rgb(var(--text-tertiary))]">
                                                    <summary className="cursor-pointer text-[rgb(var(--text-secondary))]">Provider metadata</summary>
                                                    <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                        {JSON.stringify(detailResult.metadata, null, 2)}
                                                    </pre>
                                                </details>
                                            )}
                                        </section>
                                    )}

                                    {detailException && (
                                        <section className="card space-y-3 border-rose-500/40 bg-rose-500/10">
                                            <header className="flex items-center justify-between">
                                                <h3 className="text-base font-semibold text-rose-100">Exception</h3>
                                                <span className="text-xs uppercase tracking-[0.3em] text-rose-200/80">
                                                    {detailException.class ?? 'Exception'}
                                                </span>
                                            </header>
                                            <p className="text-sm text-rose-100">{detailException.message ?? 'No message provided.'}</p>
                                            {detailException.trace && (
                                                <pre className="overflow-x-auto rounded-lg bg-rose-500/10 p-3 text-xs leading-5 text-rose-200/80">
                                                    {detailException.trace}
                                                </pre>
                                            )}
                                        </section>
                                    )}

                                    {detailTools.length > 0 && (
                                        <section className="card space-y-4">
                                            <header className="flex items-center justify-between">
                                                <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">Tool executions</h3>
                                                <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                    {detailTools.length} logged
                                                </span>
                                            </header>
                                            <div className="grid gap-4">
                                                {detailTools.map((execution, index) => {
                                                    const status = execution.status === 'failure' ? 'failure' : 'success';
                                                    const resultContent =
                                                        execution.result && typeof execution.result.content === 'string'
                                                            ? execution.result.content
                                                            : null;
                                                    const resultMetadata =
                                                        execution.result && execution.result.metadata && Object.keys(execution.result.metadata).length > 0
                                                            ? execution.result.metadata
                                                            : null;
                                                    const hasArguments = execution.arguments && Object.keys(execution.arguments).length > 0;

                                                    return (
                                                        <article
                                                            key={`${execution.callId ?? index}-${execution.timestamp ?? index}`}
                                                            className={[
                                                                'rounded-xl border p-4',
                                                                status === 'failure'
                                                                    ? 'border-rose-500/40 bg-rose-500/10'
                                                                    : 'border-slate-800/70 bg-[rgb(var(--surface-body))]',
                                                            ].join(' ')}
                                                        >
                                                            <header className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                                <div className="space-y-1">
                                                                    <h4 className="text-base font-semibold text-[rgb(var(--text-primary))]">
                                                                        {execution.tool ?? 'Unnamed tool'}
                                                                    </h4>
                                                                    <p className="text-xs text-[rgb(var(--text-tertiary))]">
                                                                        Call ID: {execution.callId ?? '—'} • Iteration {execution.iteration ?? '—'}
                                                                    </p>
                                                                    <p className="text-xs text-[rgb(var(--text-tertiary))]">
                                                                        Started {formatDate(execution.timestamp)} • Duration {formatDurationMs(execution.durationMs)}
                                                                    </p>
                                                                </div>
                                                                <StatusBadge status={status} />
                                                            </header>

                                                            {hasArguments && (
                                                                <details className="mt-3 text-xs text-[rgb(var(--text-tertiary))]">
                                                                    <summary className="cursor-pointer text-[rgb(var(--text-secondary))]">
                                                                        Arguments
                                                                    </summary>
                                                                    <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                                        {JSON.stringify(execution.arguments, null, 2)}
                                                                    </pre>
                                                                </details>
                                                            )}

                                                            {resultContent && (
                                                                <div className="mt-3">
                                                                    <h5 className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                                        Output
                                                                    </h5>
                                                                    <p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-[rgb(var(--text-primary))]">
                                                                        {resultContent}
                                                                    </p>
                                                                </div>
                                                            )}

                                                            {resultMetadata && (
                                                                <details className="mt-3 text-xs text-[rgb(var(--text-tertiary))]">
                                                                    <summary className="cursor-pointer text-[rgb(var(--text-secondary))]">
                                                                        Output metadata
                                                                    </summary>
                                                                    <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                                        {JSON.stringify(resultMetadata, null, 2)}
                                                                    </pre>
                                                                </details>
                                                            )}

                                                            {execution.error && (
                                                                <section className="mt-3 rounded-lg border border-rose-500/40 bg-rose-500/10 p-3 text-xs text-rose-100">
                                                                    <header className="flex items-center justify-between">
                                                                        <span className="uppercase tracking-[0.3em]">Tool error</span>
                                                                        <span>{execution.error.class ?? 'Exception'}</span>
                                                                    </header>
                                                                    <p className="mt-2 text-rose-100">{execution.error.message ?? 'No message provided.'}</p>
                                                                    {execution.error.trace && (
                                                                        <pre className="mt-2 overflow-x-auto rounded bg-rose-500/10 p-3 text-xs leading-5 text-rose-200/80">
                                                                            {execution.error.trace}
                                                                        </pre>
                                                                    )}
                                                                </section>
                                                            )}

                                                            {execution.stackTrace && (
                                                                <details className="mt-3 text-xs text-[rgb(var(--text-tertiary))]">
                                                                    <summary className="cursor-pointer text-[rgb(var(--text-secondary))]">
                                                                        Stack trace
                                                                    </summary>
                                                                    <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                                        {execution.stackTrace}
                                                                    </pre>
                                                                </details>
                                                            )}
                                                        </article>
                                                    );
                                                })}
                                            </div>
                                        </section>
                                    )}

                                    <section className="card space-y-4">
                                        <header className="flex items-center justify-between">
                                            <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">Conversation</h3>
                                            <span className="text-xs uppercase tracking-[0.3em] text-[rgb(var(--text-tertiary))]">
                                                {detailConversation?.turns?.length ?? 0} turns
                                            </span>
                                        </header>

                                        {renderConversationTurns(detailConversation?.turns ?? [])}
                                    </section>

                                    {(detailOverrides || (detailMetadata && Object.keys(detailMetadata).length > 0)) && (
                                        <section className="card space-y-3">
                                            <header className="flex items-center justify-between">
                                                <h3 className="text-base font-semibold text-[rgb(var(--text-primary))]">Raw metadata</h3>
                                            </header>
                                            <pre className="overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-xs leading-5 text-[rgb(var(--text-secondary))]">
                                                {JSON.stringify(detailMetadata, null, 2)}
                                            </pre>
                                        </section>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </main>
    );
}
