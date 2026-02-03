@extends('user.layout')

@section('title', 'SuriCore Dashboard')
@php($active = 'dashboard')
@section('page_title', 'Bedrijfscompliance')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 bg-white p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-green-500"></div>
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">Totale Compliance Score</h3>
            <div class="relative w-56 h-56">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle class="text-slate-100 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent"></circle>
                    <circle id="gaugeProgress" class="text-slate-300 stroke-current gauge-ring" stroke-width="8" stroke-linecap="round" cx="50" cy="50" r="40" fill="transparent" stroke-dasharray="251.2" stroke-dashoffset="251.2"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-5xl font-black text-slate-800 tracking-tight" id="scoreDisplay">--</span>
                    <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded mt-2" id="scoreLabel">...</span>
                </div>
            </div>
            <p class="text-center text-slate-500 mt-6 text-sm" id="scoreMessage">Wachten op server data...</p>
        </div>

        <div class="lg:col-span-8 bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hidden" id="actionRequiredPanel">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Action Required</p>
                    <h3 class="text-lg font-bold text-slate-800">Documenten bijna verlopen</h3>
                    <p class="text-sm text-slate-500" id="actionRequiredMsg">Er zijn documenten die binnen 30 dagen verlopen.</p>
                </div>
                <button type="button" id="actionRequiredCta" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> Actie ondernemen
                </button>
            </div>
        </div>

        <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-50 text-green-600 rounded-xl"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
                    <span class="text-slate-400 text-xs">Gevalideerd</span>
                </div>
                <h4 class="text-3xl font-bold text-slate-800" id="statValid">-</h4>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-orange-50 text-orange-600 rounded-xl"><i data-lucide="alert-triangle" class="w-6 h-6"></i></div>
                    <span class="text-slate-400 text-xs">Review Nodig</span>
                </div>
                <h4 class="text-3xl font-bold text-slate-800" id="statReview">-</h4>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="loader-2" class="w-6 h-6"></i></div>
                    <span class="text-slate-400 text-xs">In Verwerking</span>
                </div>
                <h4 class="text-3xl font-bold text-slate-800" id="statProcessing">-</h4>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-red-50 text-red-600 rounded-xl"><i data-lucide="x-circle" class="w-6 h-6"></i></div>
                    <span class="text-slate-400 text-xs">Ongeldig / Verlopen</span>
                </div>
                <h4 class="text-3xl font-bold text-slate-800" id="statInvalid">-</h4>
            </div>
        </div>
    </div>
</div>
@endsection
