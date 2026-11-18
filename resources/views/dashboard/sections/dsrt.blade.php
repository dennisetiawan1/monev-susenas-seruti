@php
    $p = $prefix . '-';
    $nksList = [
        '00223' => 'RT 004 DUSUN 02',
        '50148' => 'RT 002 DUSUN I',
        '50150' => 'RT 004 DUSUN 11',
        '00259' => 'RT 02 DUSUN I',
        '00293' => 'RT 04 DUSUN 2',
        '00642' => 'RT 06 DUSUN III',
        '00649' => 'RT 03 DUSUN II',
        '50328' => 'RT 002 DUSUN 01',
        '50343' => 'RT 05 DUSUN II',
        '00875' => 'RT 002 DUSUN 001',
        '50495' => 'RT 002 LINGKUNGAN I',
        '01004' => 'RT 06 DUSUN III',
        '50578' => 'RT 012 LINGKUNGAN III',
        '50549' => 'RT 005 LINGKUNGAN III',
        '01146' => 'RT 006 DUSUN 3',
    ];
@endphp

<div id="section-{{ $prefix }}-dsrt"
    class="dashboard-section {{ $activeTab !== $prefix . '-dsrt' ? 'hidden' : '' }} space-y-6">

    {{-- Header: judul + import + export --}}
    <div class="glass p-6 rounded-md relative z-30">
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
            <div>
                <h3 class="text-2xl font-bold text-bps-dark">Data DSRT {{ ucfirst($prefix) }}</h3>
                <p class="text-gray-500 text-sm">Monitoring progres pemutakhiran DSRT</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Import: Susenas dan Seruti, hanya Super Admin --}}
                @if ($isSuperAdmin)
                    <form action="{{ route('data_dsrt.import') }}" method="POST" enctype="multipart/form-data"
                        class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200 shadow-inner">
                        @csrf
                        <input type="hidden" name="program" value="{{ $prefix }}">
                        <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required
                            class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-bps-orange file:text-white cursor-pointer">
                        <button type="submit"
                            class="bg-bps-dark hover:bg-black text-white px-5 py-2 rounded-md text-xs font-bold whitespace-nowrap">
                            <i class="fa-solid fa-cloud-arrow-up mr-2"></i>Import
                        </button>
                    </form>
                @endif

                {{-- Export: hanya di Susenas --}}
                @if ($isSuperAdmin || $isAdminIpds || $isAdminSosial)
                    <div class="relative inline-block text-left export-dropdown">
                        <button type="button"
                            class="export-dropdown-btn inline-flex items-center gap-2 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-md focus:outline-none"
                            style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <i class="fa-solid fa-file-export"></i> Export Data
                            <i
                                class="fa-solid fa-chevron-down text-[10px] export-chevron transition-transform duration-200"></i>
                        </button>

                        <div
                            class="export-dropdown-menu hidden absolute right-0 mt-2 min-w-[260px] rounded-2xl bg-white border border-stone-100 overflow-hidden shadow-xl z-50">
                            <div class="px-4 py-2.5 bg-stone-50 border-b border-stone-100">
                                <span class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Pilih
                                    Format Export</span>
                            </div>
                            <div class="py-1.5 px-1.5 flex flex-col gap-0.5">
                                @if ($isSuperAdmin || $isAdminIpds)
                                    <a href="{{ route('data_dsrt.export_ipds', ['program' => $prefix]) }}"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-blue-700 hover:bg-blue-50 transition-all">
                                        <i class="fa-solid fa-file-excel w-4"></i> Export IPDS
                                    </a>
                                @endif
                                @if ($isSuperAdmin)
                                    <div class="border-t border-stone-100 my-1 mx-1"></div>
                                @endif
                                @if ($isSuperAdmin || $isAdminSosial)
                                    <a href="{{ route('data_dsrt.export_sosial', ['program' => $prefix]) }}"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-all">
                                        <i class="fa-solid fa-file-excel w-4"></i> Export Sosial Penerimaan oleh Kab
                                    </a>
                                    <a href="{{ route('data_dsrt.export_sosial_kab', ['program' => $prefix]) }}"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-all">
                                        <i class="fa-solid fa-file-excel w-4"></i> Export Sosial Pengiriman ke Kab
                                    </a>
                                    <a href="{{ route('data_dsrt.export_lapangan', ['program' => $prefix]) }}"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-all">
                                        <i class="fa-solid fa-map-location-dot w-4"></i> Export Lapangan
                                    </a>
                                    <a href="{{ route('data_dsrt.export_pemeriksaan', ['program' => $prefix]) }}"
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-teal-700 hover:bg-teal-50 transition-all">
                                        <i class="fa-solid fa-clipboard-check w-4"></i> Export Pemeriksaan
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="glass p-4 rounded-md">
        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] gap-3 items-end">
            <div class="min-w-0">
                <label for="{{ $p }}filter-dsrt-nks"
                    class="block text-[10px] font-black uppercase tracking-wider text-gray-500 mb-1.5">Filter
                    NBS/NKS</label>
                <select id="{{ $p }}filter-dsrt-nks"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs">
                    <option value="">Semua NBS/NKS</option>
                    @foreach ($nksList as $kode => $nama)
                        <option value="{{ $kode }}">{{ $kode }} ({{ $nama }})</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label for="{{ $p }}filter-dsrt-wilayah"
                    class="block text-[10px] font-black uppercase tracking-wider text-gray-500 mb-1.5">Filter
                    Wilayah</label>
                <select id="{{ $p }}filter-dsrt-wilayah"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs">
                    <option value="">Semua Wilayah</option>
                </select>
            </div>
            <div class="min-w-0">
                <label for="{{ $p }}filter-dsrt-search"
                    class="block text-[10px] font-black uppercase tracking-wider text-gray-500 mb-1.5">Pencarian</label>
                <input type="search" id="{{ $p }}filter-dsrt-search"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs"
                    placeholder="Cari KRT, NKS, wilayah, dll...">
            </div>
            <button type="button" id="{{ $p }}reset-filter-dsrt-code"
                class="whitespace-nowrap rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 text-xs font-bold">
                <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filter
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="glass p-6 rounded-md overflow-x-auto">
        <table id="dt-{{ $prefix }}-dsrt" class="display nowrap w-full" style="width:100%">
            <thead>
                <tr>
                    @if ($isSuperAdmin)
                        <div id="dt-actions-{{ $prefix }}-dsrt" class="hidden gap-1.5">
                            <button type="button" onclick="deleteSelected('dsrt', '{{ $prefix }}-dsrt')"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                                <i class="fa-solid fa-trash-can mr-1.5"></i>Hapus Terpilih
                            </button>
                            <button type="button"
                                onclick="deleteAll('dsrt', '{{ $prefix }}-dsrt', '{{ $prefix }}')"
                                class="bg-red-800 hover:bg-red-900 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                                <i class="fa-solid fa-dumpster mr-1.5"></i>Reset {{ ucfirst($prefix) }}
                            </button>
                        </div>
                    @endif
                    @if ($isSuperAdmin)
                        <th class="w-10 text-center">
                            <input type="checkbox" class="select-all w-4 h-4 rounded-md border-gray-300"
                                data-section="{{ $prefix }}-dsrt" data-type="dsrt">
                        </th>
                    @endif
                    <th>Action</th>
                    <th>Wilayah</th>
                    <th>NBS/NKS</th>
                    <th>KRT</th>

                    <th class="text-center">Lapangan</th>
                    @if ($isSuperAdmin || $isAdminSosial)
                        <th>R203 KOR</th>
                        <th>R203 KP</th>
                        <th>PPL</th>
                        <th>PML</th>
                    @endif

                    <th class="text-center">Sosial</th>
                    @if ($isSuperAdmin || $isAdminSosial)
                        <th>Catatan KOR</th>
                        <th>Catatan KP</th>
                    @endif

                    <th class="text-center">Pemeriksaan</th>
                    @if ($isSuperAdmin || $isAdminSosial)
                        <th>R301(Jml ART)</th>
                        <th>R407(Jml ART 2-4 Thun)</th>
                        <th>R503(Jml ART kode 2)</th>
                    @endif

                    @if ($isSuperAdmin || $isAdminIpds)
                        <th class="text-center">IPDS</th>
                        @if ($prefix !== 'seruti')
                            <th>Susenas</th>
                        @endif
                        @if ($prefix !== 'susenas')
                            <th>Seruti</th>
                        @endif
                    @endif
                </tr>
            </thead>
            <tbody id="tbody-{{ $prefix }}-dsrt"></tbody>
        </table>
    </div>
</div>
