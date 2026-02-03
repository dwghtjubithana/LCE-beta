@extends('user.layout')

@section('title', 'Upgrade')
@php($active = 'upgrade')
@section('page_title', 'Upgrade')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Upgrade & Betaling</h3>
                <p class="text-sm text-slate-500">Upgrade om AI‑analyse en extra functies te ontgrendelen.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-700" id="planBadge">PLAN: FREE</span>
                <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-200 text-slate-700" id="planStatusBadge">STATUS: ACTIVE</span>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl border border-slate-200 bg-white">
                <h4 class="font-bold text-slate-800">FREE</h4>
                <p class="text-sm text-slate-500 mt-1">Uploaden en basisstatus</p>
                <p class="text-xs text-slate-400 mt-2">AI‑analyse: niet beschikbaar</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-white">
                <h4 class="font-bold text-slate-800">PRO</h4>
                <p class="text-sm text-slate-500 mt-1">AI‑analyse + advies</p>
                <p class="text-xs text-slate-400 mt-2">Voor compliance checks</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 bg-white">
                <h4 class="font-bold text-slate-800">BUSINESS</h4>
                <p class="text-sm text-slate-500 mt-1">Alles in PRO + Tender details</p>
                <p class="text-xs text-slate-400 mt-2">Voor grotere bedrijven</p>
            </div>
        </div>
        <div class="p-6 border-t border-slate-100">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <p class="text-sm font-semibold text-slate-700">Bankgegevens</p>
                    <p class="text-sm text-slate-500 mt-1">Bank: TCB</p>
                    <p class="text-sm text-slate-500">Rekening: 12.34.56.789</p>
                    <p class="text-sm text-slate-500">Naam: Wapcomtek NV</p>
                    <p class="text-xs text-slate-400 mt-2">Stuur een screenshot van je betaling hieronder.</p>
                </div>
                <div class="p-4 rounded-xl border border-slate-200 bg-white">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Betaalbewijs uploaden</p>
                            <p class="text-xs text-slate-400">JPG/PNG/PDF</p>
                        </div>
                        <button type="button" id="paymentUploadBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                            <i data-lucide="upload" class="w-4 h-4"></i> Upload bewijs
                        </button>
                        <input type="file" id="paymentInput" class="hidden" accept="image/*,application/pdf">
                    </div>
                    <div class="mt-3 text-sm text-slate-500" id="paymentStatus">Nog geen betaalbewijs geüpload.</div>
                    <div class="mt-4">
                        <div class="text-xs text-slate-400 mb-2">Laatste betaalbewijs</div>
                        <img id="paymentProofPreview" class="w-48 h-32 object-cover rounded-lg border border-slate-200 hidden" alt="Betaalbewijs">
                        <a id="paymentProofLink" class="text-xs text-blue-600 hover:text-blue-700 font-semibold hidden" target="_blank" rel="noopener">Open betaalbewijs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
