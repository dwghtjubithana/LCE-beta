@extends('user.layout')

@section('title', 'Documenten')
@php($active = 'documents')
@section('page_title', 'Documenten')

@section('content')
<div class="max-w-7xl mx-auto space-y-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" id="documentsSection">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">Documentenkluis</h3>
                    <p class="text-sm text-slate-500">Upload documenten en volg de status in real-time.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                    <select id="categorySelect" class="w-full sm:w-56 px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                        <option value="">Kies documenttype</option>
                    </select>
                    <select id="idSubtypeSelect" class="hidden w-full sm:w-56 px-4 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                        <option value="">Kies ID subtype</option>
                        <option value="paspoort">Paspoort</option>
                        <option value="id_kaart">ID-kaart</option>
                        <option value="rijbewijs">Rijbewijs</option>
                    </select>
                    <button type="button" id="uploadBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md justify-center">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                        <span>Voorzijde kiezen</span>
                        <span id="uploadSpinner" class="hidden items-center gap-2 text-xs font-semibold">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Analyseren...
                        </span>
                    </button>
                    <button type="button" id="uploadBackBtn" class="hidden bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-medium text-sm items-center gap-2 transition shadow-md justify-center">
                        <i data-lucide="flip-horizontal" class="w-4 h-4"></i>
                        <span>Achterzijde kiezen</span>
                    </button>
                    <button type="button" id="cameraBtn" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-medium text-sm flex items-center gap-2 transition shadow-md justify-center">
                        <i data-lucide="camera" class="w-4 h-4"></i> Camera
                    </button>

                    <input type="file" id="fileInput" class="hidden" accept="image/*,application/pdf" onchange="handleFileInputChange(event)">
                    <input type="file" id="backFileInput" class="hidden" accept="image/*,application/pdf" onchange="handleBackFileInputChange(event)">
                    <input type="file" id="cameraInput" class="hidden" accept="image/*" capture="environment" onchange="handleFileInputChange(event)">
                </div>
            </div>

            <div id="uploadDropzone" class="mt-4 rounded-2xl border-2 border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500 transition">
                <div class="flex items-center justify-center gap-2 text-slate-600 font-medium">
                    <i data-lucide="mouse-pointer-click" class="w-4 h-4"></i>
                    <span>Sleep je document hierheen of klik op “Document Uploaden”</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Ondersteund: PDF of foto (JPG/PNG)</p>
                <p class="text-xs text-slate-400 mt-1" id="uploadFilename"></p>
                <p class="text-xs text-slate-400 mt-1 hidden" id="uploadBackFilename"></p>
            </div>
            <div id="uploadProgress" class="hidden mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                <div class="flex items-center justify-between">
                    <span id="uploadProgressLabel">Verwerking starten...</span>
                    <span id="uploadProgressPct">0%</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div id="uploadProgressBar" class="h-2 bg-blue-600 transition-all" style="width:0%"></div>
                </div>
            </div>
            <div id="uploadError" class="hidden mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            <div id="geminiHealth" class="mt-3 text-xs text-slate-500"></div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Document Naam</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Datum</th>
                        <th class="px-6 py-4 text-right">Actie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm" id="documentsTableBody">
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 italic">Verbinding maken met API...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js" defer></script>
@endsection
