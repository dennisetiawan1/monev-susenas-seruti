@extends('layouts.app')

@php
    $activeTab = session('active_tab', 'susenas-dashboard');
    $isSuperAdmin = Auth::user()->isSuperAdmin();
    $isAdminIpds = Auth::user()->isAdminIpds();
    $isAdminSosial = Auth::user()->isAdminSosial();
@endphp

@section('content')
    <div class="space-y-8">

        <!-- =========================================================
                                     Section: Dashboard
                                ========================================================== -->
        @include('dashboard.sections.dashboard', ['prefix' => 'susenas', 'showDssls' => true])
        @include('dashboard.sections.dssls', ['prefix' => 'susenas'])
        @include('dashboard.sections.dsrt', ['prefix' => 'susenas'])

        @include('dashboard.sections.dashboard', ['prefix' => 'seruti', 'showDssls' => false])
        @include('dashboard.sections.dsrt', ['prefix' => 'seruti'])

        <!-- =========================================================
                                     Section: Petugas Lapangan
                                ========================================================== -->
        <div id="section-lapangan" class="dashboard-section {{ $activeTab !== 'lapangan' ? 'hidden' : '' }} space-y-6">
            <div class="glass p-6 rounded-md">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-bps-dark">Petugas Lapangan</h3>
                        <p class="text-gray-500 text-sm">Kelola data petugas yang bertugas di lapangan</p>
                    </div>
                    @if ($isSuperAdmin || $isAdminSosial)
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" onclick="openAddLapanganModal()"
                                class="bg-bps-orange hover:bg-seorange-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-user-plus"></i> Tambah Petugas
                            </button>
                            <form action="{{ route('petugas_lapangan.import') }}" method="POST"
                                enctype="multipart/form-data"
                                class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200 shadow-inner">
                                @csrf
                                <input type="file" name="file_excel"
                                    class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-bps-orange file:text-white hover:file:bg-seorange-600 cursor-pointer"
                                    accept=".xlsx,.xls,.csv" required>
                                <button type="submit"
                                    class="bg-bps-dark hover:bg-black text-white px-5 py-2 rounded-md text-xs font-bold transition-all shadow-sm">
                                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>Import
                                </button>
                            </form>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic"><i class="fa-solid fa-eye mr-1"></i>Mode Lihat
                            Saja</span>
                    @endif
                </div>
            </div>

            {{-- Tombol hapus/reset di-inject ke toolbar DataTables --}}
            @if ($isSuperAdmin || $isAdminSosial)
                <div id="dt-actions-lapangan" class="hidden gap-1.5">
                    <button type="button" onclick="deleteSelected('lapangan')"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-trash-can mr-1.5"></i>Hapus Terpilih
                    </button>
                    <button type="button" onclick="deleteAll('lapangan')"
                        class="bg-red-800 hover:bg-red-900 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-dumpster mr-1.5"></i>Reset
                    </button>
                </div>
            @endif

            <div class="glass p-6 rounded-md overflow-x-auto">
                <table id="dt-lapangan" class="display nowrap w-full" style="width:100%">
                    <thead>
                        <tr>
                            @if ($isSuperAdmin || $isAdminSosial)
                                <th class="w-10 text-center"><input type="checkbox" id="selectAllLapangan"
                                        class="w-4 h-4 rounded-md border-gray-300"></th>
                            @endif
                            @if ($isSuperAdmin || $isAdminSosial)
                                <th class="text-center">Aksi</th>
                            @endif
                            <th>Kode</th>
                            <th>Provinsi</th>
                            <th>Kabupaten</th>
                            <th>Nama Petugas</th>
                            <th>No HP</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-lapangan">
                        <tr>
                            <td colspan="{{ $isSuperAdmin || $isAdminSosial ? 9 : 7 }}" class="text-center py-8">
                                <div class="flex items-center justify-center gap-3">
                                    <div
                                        class="w-5 h-5 border-2 border-bps-orange border-t-transparent rounded-full animate-spin">
                                    </div>
                                    <div class="text-xs text-gray-400">Memuat data petugas lapangan...</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- =========================================================
                                     Section: Petugas Entry
                                ========================================================== -->
        <div id="section-entry" class="dashboard-section {{ $activeTab !== 'entry' ? 'hidden' : '' }} space-y-6">
            <div class="glass p-6 rounded-md">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-bps-dark">Petugas Entry Data</h3>
                        <p class="text-gray-500 text-sm">Kelola data petugas pengolahan dan entri data</p>
                    </div>
                    @if ($isSuperAdmin || $isAdminIpds)
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" onclick="openAddEntryModal()"
                                class="bg-bps-orange hover:bg-seorange-600 text-white px-5 py-2.5 rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                                <i class="fa-solid fa-user-plus"></i> Tambah Petugas
                            </button>
                            <form action="{{ route('petugas_entry.import') }}" method="POST" enctype="multipart/form-data"
                                class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200 shadow-inner">
                                @csrf
                                <input type="file" name="file_excel"
                                    class="text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-bps-orange file:text-white hover:file:bg-seorange-600 cursor-pointer"
                                    accept=".xlsx,.xls,.csv" required>
                                <button type="submit"
                                    class="bg-bps-dark hover:bg-black text-white px-5 py-2 rounded-md text-xs font-bold transition-all shadow-sm">
                                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>Import
                                </button>
                            </form>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic"><i class="fa-solid fa-eye mr-1"></i>Mode Lihat
                            Saja</span>
                    @endif
                </div>
            </div>

            {{-- Tombol hapus/reset di-inject ke toolbar DataTables --}}
            @if ($isSuperAdmin || $isAdminIpds)
                <div id="dt-actions-entry" class="hidden gap-1.5">
                    <button type="button" onclick="deleteSelected('entry')"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-trash-can mr-1.5"></i>Hapus Terpilih
                    </button>
                    <button type="button" onclick="deleteAll('entry')"
                        class="bg-red-800 hover:bg-red-900 text-white px-3 py-1.5 rounded-md text-xs font-bold transition-all shadow-sm whitespace-nowrap">
                        <i class="fa-solid fa-dumpster mr-1.5"></i>Reset
                    </button>
                </div>
            @endif

            <div class="glass p-6 rounded-md overflow-x-auto">
                <table id="dt-entry" class="display nowrap w-full" style="width:100%">
                    <thead>
                        <tr>
                            @if ($isSuperAdmin || $isAdminIpds)
                                <th class="w-10 text-center"><input type="checkbox" id="selectAllEntry"
                                        class="w-4 h-4 rounded-md border-gray-300"></th>
                            @endif
                            @if ($isSuperAdmin || $isAdminIpds)
                                <th class="text-center">Aksi</th>
                            @endif
                            <th>Kode</th>
                            <th>Provinsi</th>
                            <th>Kabupaten</th>
                            <th>Nama Petugas</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-entry">
                        <tr>
                            <td colspan="{{ $isSuperAdmin || $isAdminIpds ? 9 : 7 }}" class="text-center py-8">
                                <div class="flex items-center justify-center gap-3">
                                    <div
                                        class="w-5 h-5 border-2 border-bps-orange border-t-transparent rounded-full animate-spin">
                                    </div>
                                    <div class="text-xs text-gray-400">Memuat data petugas entry...</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- =========================================================
                Modals
            ========================================================== -->

    {{-- Modal Tambah Petugas Lapangan --}}
    @if ($isSuperAdmin || $isAdminSosial)
        <div id="modal-add-lapangan" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('modal-add-lapangan')"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl scale-in border border-gray-100">
                <div
                    class="px-8 py-5 bg-gradient-to-r from-bps-orange/5 to-bps-orange/10 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-extrabold text-bps-dark">Tambah Petugas Lapangan</h3>
                        <p class="text-gray-400 text-[10px] mt-0.5 uppercase tracking-widest font-bold">Input Informasi
                            Petugas Baru</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-add-lapangan')"
                        class="w-8 h-8 rounded-full bg-white flex items-center justify-center hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all border border-gray-100 shadow-sm">
                        <i class="fa-solid fa-times text-sm"></i>
                    </button>
                </div>
                <form id="form-add-lapangan" action="{{ route('petugas_lapangan.store') }}" method="POST"
                    class="p-8 space-y-5">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Kode
                                Petugas</label>
                            <div class="relative">
                                <input type="text" name="kode_petugas" required placeholder="Contoh: 16109999"
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-id-card absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-kode_petugas"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Nama
                                Petugas</label>
                            <div class="relative">
                                <input type="text" name="nama_petugas" required placeholder="Nama lengkap petugas..."
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-user absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-nama_petugas"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">No
                                HP</label>
                            <div class="relative">
                                <input type="text" name="no_hp" required placeholder="Contoh: 0853..."
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-phone absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-no_hp"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Jabatan</label>
                            <div class="relative">
                                <select name="jabatan" required
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-10 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm appearance-none cursor-pointer">
                                    <option value="">-- Pilih Jabatan --</option>
                                    <option value="Pencacah (PPL)">Pencacah (PPL)</option>
                                    <option value="Pengawas (PML)">Pengawas (PML)</option>
                                </select>
                                <i class="fa-solid fa-briefcase absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                                <i
                                    class="fa-solid fa-chevron-down absolute right-3.5 top-3 text-gray-400 text-[10px] pointer-events-none"></i>
                            </div>
                            <span id="error-jabatan"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Status</label>
                        <div class="relative">
                            <select name="status" required
                                class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-10 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm appearance-none cursor-pointer">
                                <option value="">-- Pilih Status --</option>
                                <option value="Mitra">Mitra</option>
                                <option value="Staf Kabupaten">Staf Kabupaten</option>
                            </select>
                            <i class="fa-solid fa-user-shield absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3.5 top-3 text-gray-400 text-[10px] pointer-events-none"></i>
                        </div>
                        <span id="error-status"
                            class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50/50 px-8 py-4 -mx-8 -mb-8">
                        <button type="button" onclick="closeModal('modal-add-lapangan')"
                            class="px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-100 hover:text-gray-800 active:scale-95 transition-all shadow-sm">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-bps-orange to-orange-500 text-white font-extrabold text-xs shadow-md shadow-bps-orange/20 hover:shadow-lg hover:shadow-bps-orange/30 hover:brightness-105 active:scale-95 transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-sm"></i> Simpan Petugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div id="modal-edit-lapangan" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="bg-white rounded-md p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-bps-dark mb-4">Edit Petugas Lapangan</h3>
                <form id="form-edit-lapangan" class="space-y-4">
                    <input type="hidden" id="edit-lapangan-kode">

                    <div>
                        <label class="text-xs font-bold text-gray-600">Kode Petugas</label>
                        <input type="text" id="edit-lapangan-kode-view" disabled
                            class="w-full border rounded-md px-3 py-2 text-sm bg-gray-100">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">Nama Petugas</label>
                        <input type="text" name="nama_petugas" id="edit-lapangan-nama"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                        <p id="error-nama_petugas" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">No HP</label>
                        <input type="text" name="no_hp" id="edit-lapangan-hp"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                        <p id="error-no_hp" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">Jabatan</label>
                        <select name="jabatan" id="edit-lapangan-jabatan"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                            <option value="Pencacah (PPL)">Pencacah (PPL)</option>
                            <option value="Pengawas (PML)">Pengawas (PML)</option>
                        </select>
                        <p id="error-jabatan" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">Status</label>
                        <select name="status" id="edit-lapangan-status"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                            <option value="Mitra">Mitra</option>
                            <option value="Staf Kabupaten">Staf Kabupaten</option>
                        </select>
                        <p id="error-status" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('modal-edit-lapangan')"
                            class="px-4 py-2 rounded-md text-xs font-bold bg-gray-200 hover:bg-gray-300">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md text-xs font-bold bg-bps-orange hover:bg-seorange-600 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Tambah Petugas Entry --}}
    @if ($isSuperAdmin || $isAdminIpds)
        <div id="modal-add-entry" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('modal-add-entry')"></div>
            <div
                class="relative bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl scale-in border border-gray-100">
                <div
                    class="px-8 py-5 bg-gradient-to-r from-bps-orange/5 to-bps-orange/10 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-extrabold text-bps-dark">Tambah Petugas Entry Data</h3>
                        <p class="text-gray-400 text-[10px] mt-0.5 uppercase tracking-widest font-bold">Input Informasi
                            Petugas Baru</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-add-entry')"
                        class="w-8 h-8 rounded-full bg-white flex items-center justify-center hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all border border-gray-100 shadow-sm">
                        <i class="fa-solid fa-times text-sm"></i>
                    </button>
                </div>
                <form id="form-add-entry" action="{{ route('petugas_entry.store') }}" method="POST"
                    class="p-8 space-y-5">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Kode
                                Petugas</label>
                            <div class="relative">
                                <input type="text" name="kode_petugas" required placeholder="Contoh: 16109999"
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-id-card absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-kode_petugas"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Nama
                                Petugas</label>
                            <div class="relative">
                                <input type="text" name="nama_petugas" required placeholder="Nama lengkap petugas..."
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-user absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-nama_petugas"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">Email</label>
                            <div class="relative">
                                <input type="email" name="email" required placeholder="Contoh: nama@bps.go.id"
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-email"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-extrabold text-gray-500 uppercase mb-1.5 tracking-wider">No
                                HP</label>
                            <div class="relative">
                                <input type="text" name="no_hp" required placeholder="Contoh: 0853..."
                                    class="block w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-10 pr-4 py-2.5 text-xs font-semibold text-bps-dark focus:bg-white focus:ring-4 focus:ring-bps-orange/10 focus:border-bps-orange focus:outline-none transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-phone absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                            </div>
                            <span id="error-no_hp"
                                class="text-red-500 text-[10px] font-semibold mt-1 block error-msg hidden"></span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50/50 px-8 py-4 -mx-8 -mb-8">
                        <button type="button" onclick="closeModal('modal-add-entry')"
                            class="px-5 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-100 hover:text-gray-800 active:scale-95 transition-all shadow-sm">Batal</button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-bps-orange to-orange-500 text-white font-extrabold text-xs shadow-md shadow-bps-orange/20 hover:shadow-lg hover:shadow-bps-orange/30 hover:brightness-105 active:scale-95 transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-sm"></i> Simpan Petugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div id="modal-edit-entry" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
            <div class="bg-white rounded-md p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-bps-dark mb-4">Edit Petugas Entry</h3>
                <form id="form-edit-entry" class="space-y-4">
                    <input type="hidden" id="edit-entry-kode">

                    <div>
                        <label class="text-xs font-bold text-gray-600">Kode Petugas</label>
                        <input type="text" id="edit-entry-kode-view" disabled
                            class="w-full border rounded-md px-3 py-2 text-sm bg-gray-100">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">Nama Petugas</label>
                        <input type="text" name="nama_petugas" id="edit-entry-nama"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                        <p id="error-nama_petugas" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">Email</label>
                        <input type="email" name="email" id="edit-entry-email"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                        <p id="error-email" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-600">No HP</label>
                        <input type="text" name="no_hp" id="edit-entry-hp"
                            class="w-full border rounded-md px-3 py-2 text-sm">
                        <p id="error-no_hp" class="error-msg text-xs text-red-500 hidden"></p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('modal-edit-entry')"
                            class="px-4 py-2 rounded-md text-xs font-bold bg-gray-200 hover:bg-gray-300">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md text-xs font-bold bg-bps-orange hover:bg-seorange-600 text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit DSSLS Modal: disembunyikan untuk adminipds --}}
    @if (!$isAdminIpds)
        <div id="modal-dssls" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('modal-dssls')"></div>
            <div class="glass relative rounded-md w-full max-w-xl overflow-hidden shadow-2xl scale-in">
                <div class="px-8 py-6 bg-white border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold">Edit Data DSSLS</h3>
                        <p class="text-gray-500 text-xs mt-1 uppercase tracking-widest font-semibold">Update Detail
                            Informasi</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-dssls')"
                        class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 text-gray-500 transition-all">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form action="{{ route('data_dssls.update') }}" method="POST" class="p-8 space-y-6">
                    @csrf
                    <input type="hidden" name="id" id="dssls-id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wide">Jml
                                Keluarga</label>
                            <input type="number" name="perkiraan_jumlah_keluarga" id="dssls-jml-kel"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange focus:border-bps-orange">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wide">Sampel
                                Seruti</label>
                            <input type="number" name="sampel_seruti" id="dssls-sampel"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange focus:border-bps-orange">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Penugasan
                            Petugas</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if ($isSuperAdmin || $isAdminSosial)
                                <select name="petugas_ppl" id="dssls-ppl"
                                    class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                    <option value="">-- Pilih PPL --</option>
                                </select>
                                <select name="petugas_pml" id="dssls-pml"
                                    class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                    <option value="">-- Pilih PML --</option>
                                </select>
                            @endif
                        </div>
                        @if ($isSuperAdmin)
                            <select name="petugas_entry" id="dssls-entry"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                <option value="">-- Pilih Petugas Entry --</option>
                            </select>
                        @endif
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeModal('modal-dssls')"
                            class="px-6 py-3 rounded-lg bg-gray-100 text-gray-500 font-bold text-sm hover:bg-gray-200 transition-all">Batal</button>
                        <button type="submit"
                            class="px-8 py-3 rounded-lg bg-bps-orange text-white font-bold text-sm shadow-sm shadow-bps-orange/30 hover:bg-seorange-600 transition-all">Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Edit DSRT Modal --}}
    <div id="modal-dsrt" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('modal-dsrt')"></div>
        <div class="glass relative rounded-md w-full max-w-xl overflow-hidden shadow-2xl scale-in">
            <div class="px-8 py-6 bg-white border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold">Edit Data DSRT</h3>
                    <p class="text-gray-500 text-xs mt-1 uppercase tracking-widest font-semibold">Update Detail Informasi
                    </p>
                </div>
                <button type="button" onclick="closeModal('modal-dsrt')"
                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 text-gray-500 transition-all">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            @if ($isAdminIpds)
                <div class="p-8 text-center text-gray-400 text-sm italic">
                    <i class="fa-solid fa-lock mr-2 text-gray-300"></i>Anda hanya dapat melihat data ini.
                </div>
            @else
                <form action="{{ route('data_dsrt.update') }}" method="POST" class="p-8 space-y-6">
                    @csrf
                    <input type="hidden" name="id" id="dsrt-id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wide">R503</label>
                            <input type="text" name="r503" id="dsrt-r503"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2 tracking-wide">R503B</label>
                            <input type="text" name="r503b" id="dsrt-r503b"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Penugasan
                            Petugas</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <select name="petugas_ppl" id="dsrt-ppl"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                <option value="">-- Pilih PPL --</option>
                            </select>
                            <select name="petugas_pml" id="dsrt-pml"
                                class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                <option value="">-- Pilih PML --</option>
                            </select>
                        </div>
                        @if ($isSuperAdmin)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select name="petugas_susenas" id="dsrt-susenas"
                                    class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                    <option value="">-- Pilih Susenas --</option>
                                </select>
                                <select name="petugas_seruti" id="dsrt-seruti"
                                    class="block w-full rounded-md bg-gray-50 border border-gray-200 p-4 text-sm focus:ring-bps-orange">
                                    <option value="">-- Pilih Seruti --</option>
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeModal('modal-dsrt')"
                            class="px-6 py-3 rounded-lg bg-gray-100 text-gray-500 font-bold text-sm hover:bg-gray-200 transition-all">Batal</button>
                        <button type="submit"
                            class="px-8 py-3 rounded-lg bg-bps-orange text-white font-bold text-sm shadow-sm shadow-bps-orange/30 hover:bg-seorange-600 transition-all">Simpan
                            Perubahan</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

@endsection

@include('dashboard.scripts')
