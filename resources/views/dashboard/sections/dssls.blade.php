<div id="section-{{ $prefix }}-dssls"
    class="dashboard-section {{ $activeTab !== $prefix . '-dssls' ? 'hidden' : '' }} space-y-6">
    <div class="glass p-6 rounded-md flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
        <div>
            <h3 class="text-2xl font-bold text-bps-dark">Data DSSLS</h3>
            <p class="text-gray-500 text-sm">Monitoring progres pemutakhiran DSSLS</p>
        </div>
        @if ($isAdminIpds)
            <span class="text-xs text-gray-400 italic"><i class="fa-solid fa-eye mr-1"></i>Mode Lihat Saja</span>
        @else
            <div class="flex flex-wrap items-center gap-3">
                <form action="{{ route('data_dssls.import') }}" method="POST" enctype="multipart/form-data"
                    class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
                    @csrf
                    <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required
                        class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-bps-orange file:text-white cursor-pointer">
                    <button type="submit"
                        class="bg-bps-dark text-white px-5 py-2 rounded-md text-xs font-bold">Import</button>
                </form>
                <a href="{{ route('data_dssls.export') }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md text-xs font-bold">
                    Export DSSLS Pemutakhiran
                </a>
            </div>
        @endif
    </div>

    <div class="glass p-6 rounded-md overflow-x-auto">
        <table id="dt-{{ $prefix }}-dssls" class="display nowrap w-full" style="width:100%">
            <thead>
                @if (!$isAdminIpds)
                    <div id="dt-actions-{{ $prefix }}-dssls" class="hidden gap-1.5">
                        <button type="button" onclick="deleteSelected('dssls', '{{ $prefix }}-dssls')"
                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                            <i class="fa-solid fa-trash-can mr-1.5"></i>Hapus Terpilih
                        </button>
                        <button type="button" onclick="deleteAll('dssls', '{{ $prefix }}-dssls', '')"
                            class="bg-red-800 hover:bg-red-900 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                            <i class="fa-solid fa-dumpster mr-1.5"></i>Reset
                        </button>
                    </div>
                @endif
                <tr>
                    @if (!$isAdminIpds)
                        <th class="w-10 text-center">
                            <input type="checkbox" class="select-all w-4 h-4 rounded-md border-gray-300"
                                data-section="{{ $prefix }}-dssls" data-type="dssls">
                        </th>
                        <th>Action</th>
                    @endif
                    <th>Wilayah</th>
                    <th>SLS</th>
                    <th class="text-center">Lapangan</th>
                    @if ($isSuperAdmin || $isAdminSosial)
                        <th>PPL</th>
                        <th>PML</th>
                    @endif
                    <th>Keluarga Awal</th>
                    <th>Keluarga Hasil Updating</th>
                    <th>Ruta Hasil Updating</th>
                    <th class="text-center">Sosial</th>
                    <th class="text-center">IPDS</th>
                    @if ($isSuperAdmin || $isAdminIpds)
                        <th>Entry</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tbody-{{ $prefix }}-dssls"></tbody>
        </table>
    </div>
</div>
