@php
    $savedReports = collect($savedReports ?? []);
    $selectedReportId = $selectedReportId ?? null;
    $scanStatus = is_array($scanStatus ?? null) ? $scanStatus : null;
    $terminal = in_array($scanStatus['status'] ?? null, ['completed', 'failed', 'expired'], true);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Doctor - {{config('app.name')}}</title>
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23dc2626'%3E%3Cpath d='M7.5,4A5.5,5.5 0 0,0 2,9.5C2,10 2.09,10.5 2.22,11H6.3L7.57,7.63C7.87,6.83 9.05,6.75 9.43,7.63L11.5,13L12.09,11.58C12.22,11.25 12.57,11 13,11H21.78C21.91,10.5 22,10 22,9.5A5.5,5.5 0 0,0 16.5,4C14.64,4 13,4.93 12,6.34C11,4.93 9.36,4 7.5,4V4M3,12.5A1,1 0 0,0 2,13.5A1,1 0 0,0 3,14.5H5.44L11,20C12,20.9 12,20.9 13,20L18.56,14.5H21A1,1 0 0,0 22,13.5A1,1 0 0,0 21,12.5H13.4L12.47,14.8C12.07,15.81 10.92,15.67 10.55,14.83L8.5,9.5L7.54,11.83C7.39,12.21 7.05,12.5 6.6,12.5H3Z' /%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-955 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-150">
<div class="min-h-screen py-6" x-data="doctorDashboard()" data-doctor-dashboard
     data-scan-status-url="{{ $scanStatus ? str_replace('__SCAN_ID__', $scanStatus['scanId'], $routes['statusTemplate']) : '' }}"
     data-scan-terminal="{{ $terminal ? '1' : '0' }}">
    <main class="mx-auto max-w-screen-2xl px-6">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
            <a href="{{ route('doctor.dashboard') }}">
                <div class="flex items-center gap-5">
                    <svg class="h-16 w-16" viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="256" height="256" rx="56" fill="#F8FAFC"/>
                        <rect x="20" y="20" width="216" height="216" rx="44" fill="#DC2626"/>
                        <rect x="32" y="32" width="192" height="192" rx="36" fill="url(#bg)"/>
                        <g transform="translate(44 44) scale(7)" fill="#FFFFFF">
                            <path d="M7.5,4A5.5,5.5 0 0,0 2,9.5C2,10 2.09,10.5 2.22,11H6.3L7.57,7.63C7.87,6.83 9.05,6.75 9.43,7.63L11.5,13L12.09,11.58C12.22,11.25 12.57,11 13,11H21.78C21.91,10.5 22,10 22,9.5A5.5,5.5 0 0,0 16.5,4C14.64,4 13,4.93 12,6.34C11,4.93 9.36,4 7.5,4V4M3,12.5A1,1 0 0,0 2,13.5A1,1 0 0,0 3,14.5H5.44L11,20C12,20.9 12,20.9 13,20L18.56,14.5H21A1,1 0 0,0 22,13.5A1,1 0 0,0 21,12.5H13.4L12.47,14.8C12.07,15.81 10.92,15.67 10.55,14.83L8.5,9.5L7.54,11.83C7.39,12.21 7.05,12.5 6.6,12.5H3Z"/>
                        </g>
                        <defs>
                            <linearGradient id="bg" x1="32" y1="32" x2="224" y2="224" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#EF4444"/>
                                <stop offset="1" stop-color="#B91C1C"/>
                            </linearGradient>
                        </defs>
                    </svg>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-500">Laravel
                            Doctor - {{config('app.name')}}</p>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-955 dark:text-white">Diagnostics
                            Dashboard</h1>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Scan, filter, select, save, and clear
                            reports from the same package engine.</p>
                    </div>
                </div>
            </a>
            <div class="flex flex-wrap items-center gap-3">
                @if ($scanStatus)
                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold {{ ($scanStatus['status'] ?? '') === 'failed' ? 'border-red-200 dark:border-red-950 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400' : 'border-emerald-200 dark:border-emerald-950 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400' }}" data-status-pill>
                        {{ $scanStatus['status'] }}
                    </span>
                @endif

                <!-- Editor Link Configuration Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="inline-flex min-h-10 items-center justify-center rounded-md border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 text-sm font-semibold text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800" title="Editor Link Settings">
                        <svg class="h-4 w-4 mr-2 text-red-650 dark:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Editor: <span class="text-red-600 dark:text-red-400 capitalize font-bold" x-text="editorScheme === 'custom' ? 'Custom' : (editorScheme === 'vscode' ? 'VS Code' : (editorScheme === 'cursor' ? 'Cursor' : (editorScheme === 'antigravity' ? 'Antigravity' : (editorScheme === 'phpstorm' ? 'PhpStorm' : (editorScheme === 'sublime' ? 'Sublime' : (editorScheme === 'textmate' ? 'TextMate' : 'MacVim'))))))"></span></span>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 z-20 mt-2 w-64 origin-top-right rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 shadow-xl" style="display: none;">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Editor Link Scheme</label>
                        <select :value="editorScheme" @change="setEditorScheme($event.target.value)" class="mt-2 w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-2 text-xs text-slate-800 dark:text-slate-200 focus:outline-none">
                            <option value="vscode">VS Code (vscode://)</option>
                            <option value="cursor">Cursor (cursor://)</option>
                            <option value="antigravity">Antigravity (antigravity://)</option>
                            <option value="phpstorm">PhpStorm (phpstorm://)</option>
                            <option value="sublime">Sublime Text (subl://)</option>
                            <option value="textmate">TextMate (txmt://)</option>
                            <option value="macvim">MacVim (mvim://)</option>
                            <option value="custom">Custom Template</option>
                        </select>
                        <div x-show="editorScheme === 'custom'" class="mt-3">
                            <label class="block text-[10px] uppercase text-slate-500">Custom Template</label>
                            <input type="text" :value="customTemplate" @input="setCustomTemplate($event.target.value)" class="mt-1 w-full rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-1.5 text-xs text-slate-800 dark:text-slate-200 focus:outline-none" placeholder="editor://{file}:{line}">
                        </div>
                    </div>
                </div>

                <!-- Dark Mode Toggle Switch (icon-only, at the end) -->
                <button @click="toggleDarkMode()" type="button" class="inline-flex min-h-10 w-10 items-center justify-center rounded-md border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800" title="Toggle Dark/Light Mode">
                    <svg x-show="darkMode" class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <svg x-show="!darkMode" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Notices -->
        @if ($error)
            <div class="mb-6 rounded-md bg-red-50 dark:bg-red-950/40 p-4 border border-red-200 dark:border-red-900">
                <div class="text-sm font-medium text-red-850 text-red-800 dark:text-red-300">{{ $error }}</div>
            </div>
        @endif

        @if ($notice)
            <div class="mb-6 rounded-md bg-emerald-50 dark:bg-emerald-950/40 p-4 border border-emerald-200 dark:border-emerald-900">
                <div class="text-sm font-medium text-emerald-800 dark:text-emerald-300">{{ $notice }}</div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[320px_minmax(0,1fr)] w-full min-w-0">
            <!-- Main Content Area -->
            <section class="grid gap-6 w-full min-w-0 order-first lg:order-last">
                @yield('content')
            </section>

            <!-- Reports History -->
            <section
                    class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-lg w-full min-w-0 order-last lg:order-first lg:self-start">
                <div class="border-b border-slate-200 dark:border-slate-800 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">Reports</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $savedReports->count() }} saved</p>
                        </div>
                        @if (($httpDeletesEnabled ?? false) && $savedReports->isNotEmpty())
                            <form method="POST" action="{{ route('doctor.dashboard.reports.clear') }}" data-clear-reports>
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-bold text-red-600 dark:text-red-500 hover:underline" type="submit">Clear all</button>
                            </form>
                        @endif
                    </div>
                    <input class="mt-3 min-h-9 w-full rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-805 bg-white dark:bg-slate-800 px-3 text-xs text-slate-905 text-slate-900 dark:text-slate-100 shadow-sm focus:border-red-600 focus:outline-none" type="search" placeholder="Search reports..." data-report-search>
                </div>
                <div class="max-h-[540px] overflow-y-auto" data-report-list>
                    @forelse ($savedReports as $saved)
                        <div class="report-row border-b border-slate-100 dark:border-slate-800 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 @if (($saved['reportId'] ?? null) === $selectedReportId) bg-red-600/5 dark:bg-red-500/5 border-l-4 border-red-605 border-red-600 @endif"
                             data-report-row
                             data-valid="{{ ($saved['valid'] ?? true) ? '1' : '0' }}"
                             data-search="{{ strtolower(($saved['reportId'] ?? '').' '.($saved['status'] ?? '').' '.($saved['scopeLabel'] ?? '')) }}">
                            <a href="{{ route('doctor.dashboard.reports.show', ['reportId' => $saved['reportId']]) }}"
                               class="break-all text-sm font-bold text-slate-950 dark:text-white hover:text-red-600 dark:hover:text-red-400">
                                {{ $saved['reportId'] }}
                            </a>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ ($saved['valid'] ?? true) ? 'border-emerald-200 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400' : 'border-red-200 dark:border-red-950 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400' }}">{{ ($saved['valid'] ?? true) ? ($saved['status'] ?? 'completed') : 'invalid' }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $saved['createdAt'] ?: 'unavailable' }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $saved['scopeLabel'] ?? '' }}</p>
                            @if (! ($saved['valid'] ?? true))
                                <p class="mt-2 text-sm text-red-700 dark:text-red-400">{{ $saved['error'] ?? 'Malformed report.' }}</p>
                            @endif
                            @if ($httpDeletesEnabled ?? false)
                                <form class="mt-3" method="POST"
                                      action="{{ route('doctor.dashboard.reports.delete', ['reportId' => $saved['reportId']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex min-h-8 items-center rounded-md border border-red-250 border-red-200 dark:border-red-900 bg-slate-50 dark:bg-slate-800 px-3 text-xs font-semibold text-red-700 dark:text-red-400 shadow-sm hover:bg-red-50 dark:hover:bg-red-950/40 hover:text-red-800 dark:hover:text-red-300"
                                            type="submit">Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="p-4 text-sm text-slate-500">No saved reports.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</div>

<script>
    window.doctorDashboard = () => ({
        editorScheme: localStorage.getItem('doctor_editor_scheme') || 'vscode',
        customTemplate: localStorage.getItem('doctor_custom_template') || 'vscode://file/{file}:{line}',
        darkMode: localStorage.getItem('doctor_dark_mode') !== 'false',

        getEditorUrl(file, line) {
            const absolutePath = '{{ $projectRoot }}/' + file;
            const lineStr = line ? String(line) : '1';

            if (this.editorScheme === 'vscode') {
                return `vscode://file/${absolutePath}:${lineStr}`;
            }
            if (this.editorScheme === 'cursor') {
                return `cursor://file/${absolutePath}:${lineStr}`;
            }
            if (this.editorScheme === 'antigravity') {
                return `antigravity://open?file=${encodeURIComponent(absolutePath)}&line=${lineStr}`;
            }
            if (this.editorScheme === 'phpstorm') {
                return `phpstorm://open?file=${encodeURIComponent(absolutePath)}&line=${lineStr}`;
            }
            if (this.editorScheme === 'sublime') {
                return `subl://open?url=file://${encodeURIComponent(absolutePath)}&line=${lineStr}`;
            }
            if (this.editorScheme === 'textmate') {
                return `txmt://open?url=file://${encodeURIComponent(absolutePath)}&line=${lineStr}`;
            }
            if (this.editorScheme === 'macvim') {
                return `mvim://open?url=file://${encodeURIComponent(absolutePath)}&line=${lineStr}`;
            }
            return this.customTemplate.replace('{file}', absolutePath).replace('{line}', lineStr);
        },

        setEditorScheme(val) {
            this.editorScheme = val;
            localStorage.setItem('doctor_editor_scheme', val);
        },

        setCustomTemplate(val) {
            this.customTemplate = val;
            localStorage.setItem('doctor_custom_template', val);
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('doctor_dark_mode', this.darkMode);
            this.applyDarkMode();
        },

        applyDarkMode() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        init() {
            this.applyDarkMode();

            const root = this.$el;
            const manualPanel = root.querySelector('[data-manual-scope]');

            root.querySelectorAll('input[name="scopePreset"]').forEach((input) => {
                input.addEventListener('change', () => {
                    if (manualPanel) manualPanel.classList.toggle('hidden', input.value !== 'manual' || !input.checked);
                });
            });

            const findingSearch = root.querySelector('[data-finding-search]');
            const ruleFilter = root.querySelector('[data-rule-filter]');
            const findingRows = [...root.querySelectorAll('[data-findings] .finding-row')];
            const emptyFilter = root.querySelector('[data-empty-filter]');
            const selectVisible = root.querySelector('[data-select-visible]');
            const copySelected = root.querySelector('[data-copy-selected]');
            const copyAll = root.querySelector('[data-copy-all]');
            const copyStatus = root.querySelector('[data-copy-status]');
            const exportFormat = root.querySelector('[data-export-format]');
            let severity = 'all';
            let selectedRule = 'all';

            const filterFindings = () => {
                const q = (findingSearch?.value || '').trim().toLowerCase();
                let visible = 0;
                findingRows.forEach((row) => {
                    const show = (severity === 'all' || row.dataset.severity === severity)
                        && (selectedRule === 'all' || row.dataset.ruleId === selectedRule)
                        && (q === '' || (row.dataset.search || '').includes(q));
                    row.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                if (emptyFilter) emptyFilter.classList.toggle('hidden', visible > 0 || findingRows.length === 0);
                updateCopyButtons();
            };

            const visibleFindingRows = () => findingRows.filter((row) => !row.classList.contains('hidden'));

            const selectedFindingRows = () => visibleFindingRows().filter((row) => row.querySelector('[data-finding-select]')?.checked);

            const keyFor = (value, prefix, map, keys) => {
                if (!keys[value]) {
                    keys[value] = `${prefix}${Object.keys(keys).length + 1}`;
                    map[keys[value]] = value;
                }

                return keys[value];
            };

            const rowPayload = (row) => {
                try {
                    const raw = row.dataset.aiCopyPayload;
                    if (!raw) return null;
                    // Decode base64 to get the original JSON string
                    const json = atob(raw);
                    const payload = JSON.parse(json);

                    return payload && typeof payload === 'object' ? payload : null;
                } catch (error) {
                    console.error('Payload parse error:', error);
                    return null;
                }
            };

            const setCopyStatus = (message) => {
                if (copyStatus) copyStatus.textContent = message;
                setTimeout(() => {
                    if (copyStatus && copyStatus.textContent === message) {
                        copyStatus.textContent = '';
                    }
                }, 3000);
            };

            const writeClipboard = async (text) => {
                if (text === '') {
                    setCopyStatus('Nothing selected');
                    return false;
                }

                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', 'readonly');
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    textarea.remove();
                }

                setCopyStatus('Copied to clipboard');
                return true;
            };

            const updateCopyButtons = () => {
                if (copySelected) copySelected.disabled = selectedFindingRows().length === 0;
                if (copyAll) copyAll.disabled = visibleFindingRows().length === 0;
                if (selectVisible) selectVisible.disabled = visibleFindingRows().length === 0;
            };

            root.querySelectorAll('[data-finding-select]').forEach((input) => {
                input.addEventListener('change', updateCopyButtons);
            });
            
            const compactPayloadForRows = (rows) => {
                const rules = {};
                const ruleKeys = {};
                const remediations = {};
                const remediationKeys = {};
                const findings = [];

                rows.forEach((row) => {
                    const payload = rowPayload(row);
                    if (!payload || !payload.rule) return;

                    const finding = {
                        r: keyFor(String(payload.rule), 'r', rules, ruleKeys),
                        s: String(payload.severity || 'info'),
                        l: String(payload.location || 'project'),
                        m: String(payload.message || ''),
                    };

                    const remediation = String(payload.remediation || `doctor_explain_rule ${payload.rule}`);
                    finding.x = keyFor(remediation, 'x', remediations, remediationKeys);
                    findings.push(finding);
                });

                return JSON.stringify({rules, remediations, findings});
            };
            
            const formatForRows = (rows) => {
                const format = exportFormat ? exportFormat.value : 'markdown';
                
                if (format === 'compact-json') {
                    return compactPayloadForRows(rows);
                }
                
                const payloads = rows.map(rowPayload).filter(Boolean);
                
                if (payloads.length === 0) return '';
                
                if (format === 'json') {
                    return JSON.stringify(payloads, null, 2);
                }
                
                return payloads.map(p => {
                    return `### Finding: ${p.rule}\nRule: ${p.rule}\nSeverity: ${p.severity}\nLocation: ${p.location}\nMessage: ${p.message}\nRemediation: ${p.remediation}`;
                }).join('\n\n');
            };

            root.querySelectorAll('[data-copy-one]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const row = button.closest('[data-ai-copy-payload]');
                    const success = await writeClipboard(row ? formatForRows([row]) : '');
                    
                    if (success) {
                        const tooltip = button.parentElement.querySelector('[data-copy-tooltip]');
                        if (tooltip) {
                            tooltip.classList.remove('opacity-0');
                            setTimeout(() => {
                                tooltip.classList.add('opacity-0');
                            }, 2000);
                        }
                    }
                });
            });
            
            copySelected?.addEventListener('click', async () => {
                await writeClipboard(formatForRows(selectedFindingRows()));
            });
            
            copyAll?.addEventListener('click', async () => {
                await writeClipboard(formatForRows(visibleFindingRows()));
            });

            selectVisible?.addEventListener('click', () => {
                const visibleRows = visibleFindingRows();
                // Check if all visible rows are already selected
                const allSelected = visibleRows.every(row => {
                    const input = row.querySelector('[data-finding-select]');
                    return input && input.checked;
                });
                
                // Toggle state
                const targetState = !allSelected;
                
                findingRows.forEach((row) => {
                    const input = row.querySelector('[data-finding-select]');
                    if (input && visibleRows.includes(row)) {
                        input.checked = targetState;
                    }
                });
                updateCopyButtons();
            });
            updateCopyButtons();

            root.querySelectorAll('[data-severity-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    severity = button.dataset.severityToggle || 'all';
                    root.querySelectorAll('[data-severity-toggle]').forEach((item) => {
                        item.setAttribute('aria-pressed', item.dataset.severityToggle === severity ? 'true' : 'false');
                    });
                    filterFindings();
                });
            });
            findingSearch?.addEventListener('input', filterFindings);
            ruleFilter?.addEventListener('change', (e) => {
                selectedRule = e.target.value;
                filterFindings();
            });

            const reportSearch = root.querySelector('[data-report-search]');
            const reportRows = [...root.querySelectorAll('[data-report-row]')];
            reportSearch?.addEventListener('input', () => {
                const q = reportSearch.value.trim().toLowerCase();
                reportRows.forEach((row) => {
                    row.classList.toggle('hidden', q !== '' && !(row.dataset.search || '').includes(q));
                });
            });

            root.querySelectorAll('form[data-clear-reports], .report-row form').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!window.confirm('Delete saved report data?')) event.preventDefault();
                });
            });

            const statusUrl = root.dataset.scanStatusUrl;
            const terminal = root.dataset.scanTerminal === '1';
            if (statusUrl && !terminal) {
                const progress = root.querySelector('[data-progress-text]');
                const pill = root.querySelector('[data-status-pill]');
                const timer = window.setInterval(async () => {
                    try {
                        const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
                        if (!response.ok) return;
                        const data = await response.json();
                        if (progress) progress.textContent = data.progressLabel || data.status;
                        if (pill) pill.textContent = data.status;
                        if (['completed', 'failed', 'expired'].includes(data.status)) {
                            window.clearInterval(timer);
                            if (data.reportId) {
                                window.location.href = "{{ route('doctor.dashboard.reports.show', ['reportId' => '__REPORT_ID__']) }}".replace('__REPORT_ID__', data.reportId);
                            } else {
                                window.location.href = "{{ route('doctor.dashboard') }}";
                            }
                        }
                    } catch (error) {
                         window.clearInterval(timer);
                    }
                }, 1500);
            }
        },
    });
</script>
</body>
</html>
