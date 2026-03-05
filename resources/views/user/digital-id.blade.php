@extends('user.layout')

@section('title', 'Digitale ID')
@php($active = 'digital-id')
@section('page_title', 'Digitale ID')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Digitale ID</h3>
                <p class="text-sm text-slate-500">Deze kaart wordt automatisch opgebouwd vanuit je Bedrijfsprofiel.</p>
            </div>
            <div class="flex items-center gap-2">
                <a id="publicProfileLink" class="text-sm text-blue-600 hover:text-blue-700 font-semibold" href="#" target="_blank" rel="noopener">Bekijk publieke link</a>
            </div>
        </div>
        <form id="digitalIdForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="publicSlugInput">Publieke slug</label>
                <input type="text" id="publicSlugInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="sarah-consult">
                <p class="text-xs text-slate-400 mt-1">Voorbeeld: jouwdomein/p/sarah-consult</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Bron</label>
                <div class="w-full px-4 py-3 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-600">
                    Bedrijfsprofiel (gesynchroniseerd)
                </div>
            </div>
            <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-2">Preview gegevens</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-700">
                    <div><span class="text-slate-500">Naam:</span> <span id="digitalPreviewName">--</span></div>
                    <div><span class="text-slate-500">Adres:</span> <span id="digitalPreviewAddress">--</span></div>
                    <div><span class="text-slate-500">Website:</span> <span id="digitalPreviewWebsite">--</span></div>
                    <div><span class="text-slate-500">WhatsApp:</span> <span id="digitalPreviewWhatsapp">--</span></div>
                    <div><span class="text-slate-500">Facebook:</span> <span id="digitalPreviewFacebook">--</span></div>
                    <div><span class="text-slate-500">LinkedIn:</span> <span id="digitalPreviewLinkedin">--</span></div>
                </div>
            </div>
            <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" id="saveDigitalIdBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Digitale ID opslaan
                </button>
                <a href="/profile" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition">
                    <i data-lucide="building" class="w-4 h-4"></i> Bewerk brondata in Bedrijfsprofiel
                </a>
                <div id="digitalIdStatus" class="text-sm text-slate-500 flex items-center"></div>
            </div>
        </form>
    </div>
</div>
@endsection
