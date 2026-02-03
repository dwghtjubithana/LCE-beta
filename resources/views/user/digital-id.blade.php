@extends('user.layout')

@section('title', 'Digital ID')
@php($active = 'digital-id')
@section('page_title', 'Digital ID')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-lg">Digital ID</h3>
                <p class="text-sm text-slate-500">Maak je publieke profiel en deel het via WhatsApp.</p>
            </div>
            <div class="flex items-center gap-2">
                <a id="publicProfileLink" class="text-sm text-blue-600 hover:text-blue-700 font-semibold" href="#" target="_blank" rel="noopener">Bekijk publieke link</a>
            </div>
        </div>
        <form id="digitalIdForm" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="publicSlugInput">Publieke slug</label>
                <input type="text" id="publicSlugInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="sarah-consult">
                <p class="text-xs text-slate-400 mt-1">Voorbeeld: suricore.sr/p/sarah-consult</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="displayNameInput">Weergavenaam</label>
                <input type="text" id="displayNameInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Sarah Consult">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1" for="addressInput">Adres</label>
                <input type="text" id="addressInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="Paramaribo, Suriname">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1" for="latInput">Lat</label>
                    <input type="text" id="latInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="5.8520">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1" for="lngInput">Lng</label>
                    <input type="text" id="lngInput" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="-55.2038">
                </div>
            </div>
            <div class="md:col-span-2">
                <button type="button" id="geocodeBtn" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Locatie automatisch invullen
                </button>
                <span id="geocodeStatus" class="text-xs text-slate-400 ml-3"></span>
            </div>
            <div class="md:col-span-2 flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" id="saveDigitalIdBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="save" class="w-4 h-4"></i> Digital ID Opslaan
                </button>
                <button type="button" id="uploadPhotoBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md">
                    <i data-lucide="image" class="w-4 h-4"></i> Profielfoto uploaden
                </button>
                <input type="file" id="profilePhotoInput" class="hidden" accept="image/*">
                <div id="digitalIdStatus" class="text-sm text-slate-500 flex items-center"></div>
            </div>
        </form>
    </div>
</div>
@endsection
