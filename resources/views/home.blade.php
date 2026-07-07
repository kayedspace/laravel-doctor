@php
    $scopePreset = (string) ($form['scopePreset'] ?? 'full');
@endphp

@extends('doctor::layout')

@section('content')
    <form id="scan-form" class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm dark:shadow-lg"
          method="POST" action="{{ route('doctor.dashboard.scan') }}" data-scan-form>
        @csrf
        <div class="grid gap-5">
            <div class="grid gap-5">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Scope</label>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4" data-scope-tabs>
                        @foreach (['full' => 'Full', 'changed' => 'Changed', 'laravel' => 'Laravel', 'manual' => 'Manual'] as $value => $label)
                            <label class="relative">
                                <input class="peer sr-only" type="radio" name="scopePreset"
                                       value="{{ $value }}" @checked($scopePreset === $value)>
                                <span class="flex min-h-10 cursor-pointer items-center justify-center rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm font-semibold text-slate-700 dark:text-slate-300 shadow-sm peer-checked:border-red-600 peer-checked:bg-red-500/10 peer-checked:text-red-600 dark:peer-checked:text-red-400">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div data-manual-scope @class(['hidden' => $scopePreset !== 'manual'])>
                    <label for="paths" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Manual paths</label>
                    <textarea id="paths" name="paths"
                              class="mt-2 min-h-28 w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 font-mono text-sm text-slate-900 dark:text-slate-100 shadow-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                              placeholder="app/Http/Controllers/UserController.php">{{ $form['paths'] ?? '' }}</textarea>
                </div>

                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 p-4">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                        Advanced scan options
                    </h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">

                        <!-- Rules Multiselect -->
                        <div x-data="{ 
                            search: '', 
                            open: false, 
                            selectedRules: @json(is_array($form['rules'] ?? []) ? $form['rules'] : ($form['rules'] ? explode(',', $form['rules']) : [])),
                            toggleRule(id) {
                                if (this.selectedRules.includes(id)) {
                                    this.selectedRules = this.selectedRules.filter(x => x !== id);
                                } else {
                                    this.selectedRules.push(id);
                                }
                            }
                        }" class="relative">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Rules</label>
                            <div @click="open = !open"
                                 class="mt-2 flex min-h-10 w-full items-center justify-between rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-805 bg-white dark:bg-slate-800 px-3 py-1.5 text-sm cursor-pointer shadow-sm">
                                <span class="text-slate-600 dark:text-slate-300 truncate"
                                      x-text="selectedRules.length ? selectedRules.length + ' rules selected' : 'Select rules...'"></span>
                                <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <div x-show="open" @click.away="open = false"
                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-909 bg-white dark:bg-slate-900 p-2 shadow-lg"
                                 style="display: none;">
                                <input x-model="search" type="text"
                                       class="mb-2 min-h-8 w-full rounded border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-red-600"
                                       placeholder="Search rules...">
                                <div class="grid gap-1">
                                    @foreach ($allRules as $rule)
                                        <label class="flex items-start gap-2 rounded px-2 py-1 text-xs text-slate-705 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                               x-show="search === '' || @json($rule['id']).toLowerCase().includes(search.toLowerCase()) || @json($rule['name']).toLowerCase().includes(search.toLowerCase())">
                                            <input type="checkbox" name="rules[]" value="{{ $rule['id'] }}"
                                                   :checked="selectedRules.includes('{{ $rule['id'] }}')"
                                                   @change="toggleRule('{{ $rule['id'] }}')"
                                                   class="mt-0.5 h-3.5 w-3.5 rounded border-slate-300 dark:border-slate-606 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-red-600 focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-slate-900">
                                            <div class="truncate">
                                                <span class="font-semibold block text-slate-900 dark:text-slate-200 truncate">{{ $rule['id'] }}</span>
                                                <span class="text-slate-505 text-slate-500 dark:text-slate-400 text-[10px] block truncate">{{ $rule['name'] }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Packs Multiselect -->
                        <div x-data="{ 
                            search: '', 
                            open: false, 
                            selectedPacks: @json(is_array($form['packs'] ?? []) ? $form['packs'] : ($form['packs'] ? explode(',', $form['packs']) : [])),
                            togglePack(id) {
                                if (this.selectedPacks.includes(id)) {
                                    this.selectedPacks = this.selectedPacks.filter(x => x !== id);
                                } else {
                                    this.selectedPacks.push(id);
                                }
                            }
                        }" class="relative">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Packs</label>
                            <div @click="open = !open"
                                 class="mt-2 flex min-h-10 w-full items-center justify-between rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-sm cursor-pointer shadow-sm">
                                <span class="text-slate-606 text-slate-600 dark:text-slate-300 truncate"
                                      x-text="selectedPacks.length ? selectedPacks.length + ' packs selected' : 'Select packs...'"></span>
                                <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <div x-show="open" @click.away="open = false"
                                 class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-2 shadow-lg"
                                 style="display: none;">
                                <input x-model="search" type="text"
                                       class="mb-2 min-h-8 w-full rounded border border-slate-300 dark:border-slate-707 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-2 py-1 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:border-red-600"
                                       placeholder="Search packs...">
                                <div class="grid gap-1">
                                    @foreach ($allPacks as $pack)
                                        <label class="flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                               x-show="search === '' || @json($pack).toLowerCase().includes(search.toLowerCase())">
                                            <input type="checkbox" name="packs[]" value="{{ $pack }}"
                                                   :checked="selectedPacks.includes('{{ $pack }}')"
                                                   @change="togglePack('{{ $pack }}')"
                                                   class="h-3.5 w-3.5 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-red-600 focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-slate-900">
                                            <span class="font-semibold text-slate-900 dark:text-slate-200">{{ $pack }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" for="exclusions">Exclusions</label>
                            <input class="mt-2 min-h-10 w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-sm text-slate-900 dark:text-slate-100 shadow-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                                   id="exclusions" name="exclusions" value="{{ $form['exclusions'] ?? '' }}"
                                   placeholder="{{ implode(', ', Config::get('doctor.exclusions', ['vendor/', 'node_modules/'])) }}">
                        </div>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                                   type="checkbox" name="booted"
                                   value="1" @checked((bool) ($form['booted'] ?? Config::get('doctor.runtime.enabled', true)))>
                            Booted rules (Config: {{ Config::get('doctor.runtime.enabled', true) ? 'On' : 'Off' }})
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input class="h-4 w-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-red-600 focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-slate-900"
                                   type="checkbox" name="auditDependencies"
                                   value="1" @checked((bool) ($form['auditDependencies'] ?? Config::get('doctor.dependency_audit.enabled', true)))>
                            Dependency audit
                            (Config: {{ Config::get('doctor.dependency_audit.enabled', true) ? 'On' : 'Off' }})
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <button class="w-full inline-flex min-h-12 items-center justify-center rounded-md bg-red-600 px-6 text-base font-semibold text-white shadow-sm hover:bg-red-700"
                        type="submit">Run scan
                </button>
            </div>
        </div>
    </form>

    @if ($scanStatus)
        <section
                class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm dark:shadow-lg"
                data-ai-copy-entrypoints>
            @if (! in_array($scanStatus['status'] ?? null, ['completed', 'failed', 'expired'], true))
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-950 dark:text-white flex items-center gap-2">
                        Results
                    </h2>
                    <p class="mt-1 text-sm text-slate-650 text-slate-600 dark:text-slate-400" data-progress-text>
                        {{ $scanStatus['progressLabel'] ?? ucfirst((string) $scanStatus['status']) }}
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-center p-8">
                <svg class="animate-spin h-8 w-8 text-red-655 text-red-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            @elseif (($scanStatus['status'] ?? '') === 'failed')
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-950 dark:text-white flex items-center gap-2">
                        Scan Failed
                    </h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">The scan could not be completed.</p>
                </div>
            </div>
            <div class="grid gap-3">
                @foreach ($scanStatus['errors'] ?? [] as $errorMsg)
                    <div class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-400 font-mono">
                        {{ $errorMsg }}
                    </div>
                @endforeach
            </div>
            @endif
        </section>
    @endif
@endsection
