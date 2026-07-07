@php
    use kayedspace\Doctor\Output\AiMappedReport;$report = $report ?? null;
    $summary = array_merge([
        'critical' => 0,
        'error' => 0,
        'warning' => 0,
        'info' => 0,
        'skipped' => 0,
        'errors' => 0,
    ], is_array($report['summary'] ?? null) ? $report['summary'] : []);
    $findings = collect(is_array($report['findings'] ?? null) ? $report['findings'] : [])
        ->sortBy(fn ($finding) => ['critical' => 0, 'error' => 1, 'warning' => 2, 'info' => 3][$finding['severity'] ?? 'info'] ?? 9)
        ->values();
    $findingRules = $findings->pluck('ruleId')->unique()->sort()->values();
    $scanErrors = collect(is_array($report['errors'] ?? null) ? $report['errors'] : []);
    $severityClasses = [
        'critical' => 'border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300',
        'error' => 'border-orange-200 dark:border-orange-900 bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-300',
        'warning' => 'border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300',
        'info' => 'border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300',
    ];
@endphp

@extends('doctor::layout')

@section('content')
    <section
            class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm dark:shadow-lg w-full min-w-0">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="mb-2">
                    <a href="{{ route('doctor.dashboard') }}"
                       class="inline-flex items-center text-sm font-semibold text-red-600 hover:text-red-700 dark:hover:text-red-400 gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Scan Setup
                    </a>
                </div>
                <h2 class="text-xl font-bold text-slate-955 dark:text-white flex items-center gap-2">
                    Results
                    @if (! empty($summary['errors']))
                        <span class="inline-flex rounded-full border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-2.5 py-0.5 text-xs font-bold text-red-700 dark:text-red-400">
                            {{ $summary['errors'] }} Scan {{ Str::plural('Error', $summary['errors']) }}
                        </span>
                    @endif
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400" data-progress-text>
                    @if ($scanStatus)
                        {{ $scanStatus['progressLabel'] ?? ucfirst((string) $scanStatus['status']) }}
                    @elseif ($selectedReportId)
                        Saved report {{ $selectedReportId }}
                    @else
                        Scan completed.
                    @endif
                </p>
            </div>
            @if ($report && ($report['savedReport']['reportId'] ?? null))
                <span class="inline-flex rounded-full border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">saved {{ $report['savedReport']['reportId'] }}</span>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            @foreach (['critical' => 'rose', 'error' => 'orange', 'warning' => 'amber', 'info' => 'blue'] as $key => $tone)
                <button type="button"
                        class="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-4 text-left shadow-sm hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-950 dark:hover:text-white"
                        data-severity-toggle="{{ $key }}" aria-pressed="false">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $key }}</span>
                    <strong class="mt-2 block text-3xl font-bold text-{{ $tone }}-600 dark:text-{{ $tone }}-400">{{ $summary[$key] ?? 0 }}</strong>
                </button>
            @endforeach
        </div>
    </section>

    <!-- Filters & Export Card -->
    <section
            class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm dark:shadow-lg w-full min-w-0">
        <div class="mb-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_260px_auto] md:items-center">
            <input class="min-h-10 w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-sm text-slate-905 text-slate-900 dark:text-slate-105 dark:text-slate-100 shadow-sm focus:border-red-600 focus:outline-none"
                   type="search" placeholder="Search findings..." data-finding-search>

            <select class="min-h-10 w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-sm text-slate-700 dark:text-slate-300 focus:border-red-600 focus:outline-none"
                    data-rule-filter>
                <option value="all">All Rules</option>
                @foreach ($findingRules as $ruleId)
                    <option value="{{ $ruleId }}">{{ $ruleId }}</option>
                @endforeach
            </select>

            <div class="flex flex-wrap gap-2">
                @foreach (['all' => 'All', 'critical' => 'Critical', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Info'] as $key => $label)
                    <button type="button"
                            class="inline-flex min-h-9 items-center rounded-md border border-slate-300 dark:border-slate-750 bg-white dark:bg-slate-800 px-3 text-sm font-semibold text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-750 hover:text-slate-950 dark:hover:text-white aria-pressed:bg-red-600 aria-pressed:text-white dark:aria-pressed:bg-white dark:aria-pressed:text-slate-950"
                            data-severity-toggle="{{ $key }}"
                            aria-pressed="{{ $key === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <!-- Export Toolbar -->
        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm transition-all hover:shadow-md"
             data-ai-copy-toolbar>
            <div class="flex flex-col gap-4 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Export Findings</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Copy findings to clipboard in your
                            preferred format.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select class="h-9 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 text-sm font-medium text-slate-700 dark:text-slate-300 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 cursor-pointer"
                            data-export-format>
                        <option value="markdown">Markdown</option>
                        <option value="json">JSON</option>
                        <option value="compact-json">Compact JSON</option>
                    </select>

                    <button type="button"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-750 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            data-select-visible
                            data-copy-interactions="1"
                            @disabled($findings->isEmpty())>
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3 3L22 4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 12v7a2 2 0 01-2 2H5a2 2 0 012-2h11"/>
                        </svg>
                        <span class="hidden sm:inline">Select All</span>
                    </button>

                    <button type="button"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 px-4 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            data-copy-selected
                            data-copy-interactions="1"
                            disabled>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V5a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2h-2"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 9h8a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a2 2 0 012-2z"/>
                        </svg>
                        <span>Copy Selected</span>
                    </button>

                    <button type="button"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-slate-800 dark:bg-slate-100 px-4 text-sm font-semibold text-white dark:text-slate-900 shadow-sm hover:bg-slate-700 dark:hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            data-copy-all
                            data-copy-interactions="1"
                            @disabled($findings->isEmpty())>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <span>Copy All</span>
                    </button>
                </div>
            </div>
            <!-- Status message container -->
            <div class="px-4 pb-3 pt-0 flex justify-end">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 empty:hidden" data-copy-status
                      aria-live="polite"></span>
            </div>
        </div>
    </section>

    <!-- Results Card -->
    <section
            class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm dark:shadow-lg w-full min-w-0">
        @foreach ($scanErrors as $scanError)
            <div class="mb-3 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-400 break-words">
                <div class="break-words">{{ $scanError['message'] ?? (string) $scanError }}</div>
                @if (! empty($scanError['file']))
                    <code class="font-mono break-all block mt-1.5 text-xs bg-red-100/50 dark:bg-red-950/50 px-2 py-0.5 rounded border border-red-200/30 dark:border-red-900/30 w-fit">{{ $scanError['file'] }}@if (! empty($scanError['line']))
                            :{{ $scanError['line'] }}
                        @endif</code>
                @endif
            </div>
        @endforeach

        <div data-empty-filter @class(['hidden' => ! $findings->isEmpty(), 'rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-909 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm text-slate-600 dark:text-slate-400'])>
            No findings match the current filters.
        </div>

        @if ($findings->isEmpty())
            <div class="rounded-lg border border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-400">
                No findings.
            </div>
        @else
            <div class="divide-y divide-slate-200 dark:divide-slate-800" data-findings>
                @foreach ($findings as $finding)
                    @php
                        $aiCopyRow = $finding['aiCopyRow'] ?? AiMappedReport::rowFromArray((array) $finding);
                        $aiCopyRowJson = json_encode($aiCopyRow, JSON_UNESCAPED_SLASHES);
                    @endphp
                    <article class="finding-row py-5 w-full min-w-0 overflow-hidden"
                             data-severity="{{ $finding['severity'] ?? 'info' }}"
                             data-rule-id="{{ $finding['ruleId'] ?? '' }}"
                             data-search="{{ strtolower(($finding['ruleId'] ?? '').' '.($finding['title'] ?? '').' '.($finding['message'] ?? '').' '.($finding['file'] ?? '')) }}"
                             data-ai-copy-payload="{{ base64_encode($aiCopyRowJson === false ? '{}' : $aiCopyRowJson) }}">
                        <div class="flex flex-col gap-2 w-full min-w-0">
                            <!-- Row 1: Severity pill + Rule Name on the left, Rule Key on the right -->
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 w-full min-w-0 max-w-full">
                                <div class="flex items-start gap-3 max-w-full">
                                    <label class="mt-0.5 inline-flex shrink-0 items-center justify-center rounded cursor-pointer">
                                        <input type="checkbox"
                                               class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-white dark:focus:ring-offset-slate-900 cursor-pointer"
                                               aria-label="Select finding"
                                               data-finding-select>
                                    </label>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $severityClasses[$finding['severity'] ?? 'info'] ?? $severityClasses['info'] }}">{{ $finding['severity'] ?? 'info' }}</span>
                                        <strong class="text-base text-slate-900 dark:text-white break-words">{{ $finding['title'] ?? 'Finding' }}</strong>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <code class="rounded bg-slate-100 dark:bg-slate-800 px-2 py-1 font-mono text-xs text-slate-700 dark:text-slate-305 dark:text-slate-300 border border-slate-200 dark:border-slate-700 break-all w-fit">{{ $finding['ruleId'] ?? '' }}</code>
                                    <div class="relative inline-flex items-center">
                                        <button type="button"
                                                class="inline-flex min-h-7 items-center justify-center gap-1.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-750 transition-colors relative"
                                                data-copy-one
                                                data-copy-interactions="1"
                                                title="Copy Finding">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 7V5a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2h-2"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 9h8a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a2 2 0 012-2z"/>
                                            </svg>
                                            Copy
                                        </button>
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 mt-1.5 opacity-0 transition-opacity duration-200 pointer-events-none whitespace-nowrap rounded bg-slate-800 dark:bg-slate-700 px-2 py-1 text-[10px] font-bold text-white shadow-lg z-10"
                                             data-copy-tooltip>
                                            Copied!
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-0.5 border-4 border-transparent border-b-slate-800 dark:border-b-slate-700"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Clickable File/Line (Filename under the rule name) -->
                            <div class="mt-0.5">
                                <a :href="getEditorUrl('{{ $finding['file'] ?? '' }}', {{ $finding['line'] ?? 'null' }})"
                                   class="rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 px-2.5 py-1 font-mono text-xs text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 hover:underline break-all inline-block max-w-full"
                                   title="Open in Editor">
                                    {{ $finding['file'] ?? 'project' }}@if (! empty($finding['line']))
                                        :{{ $finding['line'] }}
                                    @endif
                                </a>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-slate-700 dark:text-slate-300 break-words">{{ $finding['message'] ?? '' }}</p>
                        @if (! empty($finding['evidence']))
                            <pre class="mt-3 overflow-x-auto rounded-md bg-slate-950 p-3 font-mono text-xs text-slate-100 break-all whitespace-pre-wrap"><code>{{ $finding['evidence'] }}</code></pre>
                        @endif
                        @if (! empty($finding['remediation']))
                            <details
                                    class="mt-3 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 px-3 py-2">
                                <summary
                                        class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    Remediation
                                </summary>
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 break-words">{{ $finding['remediation'] }}</p>
                            </details>
                        @endif
                        @if (! empty($finding['id']))
                            <p class="mt-3 font-mono text-xs text-slate-500 break-all">{{ $finding['id'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
