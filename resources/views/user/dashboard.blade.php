@extends('user.layout')

@section('title', 'SuriCore LCE Dashboard')
@php($active = 'dashboard')
@section('page_title', 'Bedrijfscompliance')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 hidden" id="actionRequiredPanel">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold mb-1">Action Required</p>
                <h3 class="text-base font-bold text-slate-800">Documenten bijna verlopen</h3>
                <p class="text-sm text-slate-500" id="actionRequiredMsg">Er zijn documenten die binnen 30 dagen verlopen.</p>
            </div>
            <button type="button" id="actionRequiredCta" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 transition">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i> Actie ondernemen
            </button>
        </div>
    </div>

    <section class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h3 class="text-base font-bold text-slate-800">Checklist naar 100%</h3>
            <p class="text-sm text-slate-500 mt-1" id="checklistIntro">Per document zie je status, betekenis en de volgende actie.</p>

            <div id="score-insights-section" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 scroll-mt-24">
                <h4 class="text-sm font-semibold text-slate-700">Totale Compliance Score</h4>
                <div class="mt-3 flex items-center gap-4">
                    <div class="relative w-28 h-32 flex-shrink-0">
                        <svg class="w-28 h-28 overflow-visible" viewBox="0 0 100 100">
                            <circle class="text-slate-100 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent"></circle>
                            <circle id="gaugeProgress" class="text-slate-300 stroke-current gauge-ring" stroke-width="8" stroke-linecap="round" cx="50" cy="50" r="40" fill="transparent" stroke-dasharray="251.2" stroke-dashoffset="251.2"></circle>
                        </svg>
                        <div class="absolute inset-x-0 top-0 h-28 flex items-center justify-center">
                            <span class="text-2xl font-black leading-none text-slate-800 tracking-tight" id="scoreDisplay">0%</span>
                        </div>
                        <span class="absolute left-1/2 bottom-0 -translate-x-1/2 text-[9px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded whitespace-nowrap" id="scoreLabel">UNVERIFIED</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-600" id="scoreOverviewIntro">Je score is 0%: 0 van 0 verplichte documenten zijn geldig.</p>
                        <p class="text-xs text-slate-500 mt-1" id="scoreMessage">Nog geen geldige documenten gevonden.</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-slate-200 text-slate-700" id="scoreAccountBadge">Account: Free</span>
                    <span class="text-xs text-slate-500" id="scoreDocReady">0 / 0 documenten gereed</span>
                    <span class="text-xs text-slate-500" id="scoreRemaining">0% te gaan</span>
                </div>
                <div class="mt-3">
                    <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div id="scoreProgressBar" class="h-full bg-emerald-500 transition-all duration-500" style="width:0%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1" id="scoreProgressLabel">0% van 100%</p>
                </div>
            </div>

            <div id="score-checklist-section" class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-3 scroll-mt-24">
                <div id="scoreChecklist" class="contents">
                <div class="rounded-xl border border-slate-200 p-4 text-sm text-slate-500">Nog geen documenten beschikbaar.</div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
