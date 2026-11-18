@php $p = $prefix . '-'; @endphp
<div id="section-{{ $prefix }}-dashboard"
    class="dashboard-section {{ $activeTab !== $prefix . '-dashboard' ? 'hidden' : '' }} space-y-6">

    <div class="glass p-6 rounded-2xl flex flex-wrap gap-4 items-center justify-between shadow-sm">
        <div>
            <h4 class="font-bold text-bps-dark">Filter Wilayah</h4>
            <p class="text-xs text-gray-500">Batasi statistik berdasarkan kecamatan dan desa</p>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <select id="{{ $p }}filter-kecamatan" class="w-48 rounded-lg border border-gray-200 p-2.5 text-xs">
                <option value="">Semua Kecamatan</option>
            </select>
            <select id="{{ $p }}filter-desa" class="w-48 rounded-lg border border-gray-200 p-2.5 text-xs"
                disabled>
                <option value="">Semua Desa/Kelurahan</option>
            </select>
            <button id="{{ $p }}btn-reset-filter"
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-bold">
                <i class="fa-solid fa-arrows-rotate"></i> Reset
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach ([['summary-ppl', 'Total PPL'], ['summary-pml', 'Total PML'], ['summary-entry', 'Total Entry'], ['summary-total-petugas', 'Total Petugas']] as [$id, $label])
            <div class="glass p-6 rounded-2xl shadow-sm">
                <p class="text-sm text-gray-500 font-bold uppercase tracking-wider mb-1">{{ $label }}</p>
                <h4 class="text-3xl font-black text-bps-dark" id="{{ $p . $id }}">...</h4>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if ($showDssls)
            <div class="glass p-6 rounded-2xl shadow-sm">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-bold uppercase tracking-wider text-gray-500">Progres Keseluruhan DSSLS</h4>
                    <span class="text-xs font-black text-white px-2 py-0.5 rounded-full bg-bps-orange"
                        id="{{ $p }}dssls-progress-badge">0%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3 mb-2 overflow-hidden">
                    <div id="{{ $p }}dssls-progress-bar"
                        class="bg-gradient-to-r from-bps-orange to-bps-yellow h-3 rounded-full transition-all duration-500"
                        style="width: 0%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Selesai: <strong id="{{ $p }}dssls-completed-count">0</strong> SLS</span>
                    <span>Target: <strong id="{{ $p }}dssls-total-count">0</strong> SLS</span>
                </div>
            </div>
        @endif
        <div class="glass p-6 rounded-2xl shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-sm font-bold uppercase tracking-wider text-gray-500">Progres Keseluruhan DSRT</h4>
                <span class="text-xs font-black text-white px-2 py-0.5 rounded-full bg-bps-orange"
                    id="{{ $p }}dsrt-progress-badge">0%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3 mb-2 overflow-hidden">
                <div id="{{ $p }}dsrt-progress-bar"
                    class="bg-gradient-to-r from-bps-orange to-bps-yellow h-3 rounded-full transition-all duration-500"
                    style="width: 0%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
                <span>Selesai: <strong id="{{ $p }}dsrt-completed-count">0</strong> Keluarga/Ruta</span>
                <span>Target: <strong id="{{ $p }}dsrt-total-count">0</strong> Keluarga/Ruta</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if ($showDssls)
            <div class="glass p-6 rounded-2xl shadow-sm flex flex-col">
                <h3 class="text-lg font-bold text-bps-dark">Jumlah Ceklis DSSLS</h3>
                <p class="text-gray-500 text-xs mb-6">Total Target: <span id="{{ $p }}chart-dssls-total"
                        class="font-bold text-bps-dark">...</span> SLS</p>
                <div class="relative flex-1 w-full flex items-center justify-center min-h-[300px]">
                    <canvas id="{{ $p }}chart-dssls"></canvas>
                </div>
            </div>
        @endif
        <div class="glass p-6 rounded-2xl shadow-sm flex flex-col {{ $showDssls ? '' : 'lg:col-span-2' }}">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-bps-dark">Jumlah Ceklis DSRT</h3>
                    <p class="text-gray-500 text-xs">Total Target: <span id="{{ $p }}chart-dsrt-total"
                            class="font-bold text-bps-dark">...</span> Keluarga/Ruta</p>
                </div>
                <select id="{{ $p }}filter-chart-dsrt"
                    class="w-40 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700">
                    <option value="lap">Lapangan</option>
                    <option value="pemeriksaan">Pemeriksaan</option>
                    <option value="sosial">Sosial</option>
                    <option value="ipds">IPDS</option>
                </select>
            </div>
            <div class="relative flex-1 w-full min-h-[340px] overflow-x-auto overflow-y-hidden">
                <div class="relative h-full min-w-[900px] w-full">
                    <canvas id="{{ $p }}chart-dsrt"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="glass p-6 rounded-2xl shadow-sm space-y-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-bps-dark">Rekap Penugasan Petugas Entry</h3>
                <p class="text-gray-500 text-xs">Jumlah beban tugas entry data per petugas</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="exportRekapPetugas('{{ $prefix }}')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg text-xs font-bold">
                    <i class="fa-solid fa-file-excel"></i> Export Rekap
                </button>
                <input type="text" id="{{ $p }}search-rekap" placeholder="Cari nama atau kode petugas..."
                    class="w-64 rounded-lg border border-gray-200 p-2.5 text-xs">
            </div>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50/70 border-b border-gray-200/80 text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4 text-center w-12">No</th>
                        <th class="py-3 px-4">Nama Petugas</th>
                        <th class="py-3 px-4 text-center">Kode Petugas</th>
                        <th class="py-3 px-4 text-center">Entry Pemutakhiran</th>
                        <th class="py-3 px-4 text-center">Entry Susenas</th>
                        <th class="py-3 px-4 text-center">Entry Seruti</th>
                        <th class="py-3 px-4 text-center text-bps-orange">Total Tugas</th>
                    </tr>
                </thead>
                <tbody id="{{ $p }}tbody-rekap" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="7" class="py-8 text-center text-xs text-gray-400">Memuat rekap petugas...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
