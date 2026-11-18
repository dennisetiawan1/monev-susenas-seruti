@push('scripts')
    <script>
        // ─── Role flags (rendered server-side, cannot be tampered from client) ──────
        var isSuperAdmin = {{ Auth::user()->isSuperAdmin() ? 'true' : 'false' }};
        var isAdminIpds = {{ Auth::user()->isAdminIpds() ? 'true' : 'false' }};
        var isAdminSosial = {{ Auth::user()->isAdminSosial() ? 'true' : 'false' }};
        // ─────────────────────────────────────────────────────────────────────────────

        var petugasOptions = null;
        var petugasOptionsRequest = null;
        var dataTables = {};
        var sectionConfig = {
            lapangan: {
                type: 'lapangan'
            },
            entry: {
                type: 'entry'
            },
            'susenas-dashboard': {
                type: 'dashboard',
                prefix: 'susenas',
                program: 'susenas'
            },
            'susenas-dssls': {
                type: 'dssls',
                program: 'susenas'
            },
            'susenas-dsrt': {
                type: 'dsrt',
                prefix: 'susenas',
                program: 'susenas'
            },
            'seruti-dashboard': {
                type: 'dashboard',
                prefix: 'seruti',
                program: 'seruti'
            },
            'seruti-dsrt': {
                type: 'dsrt',
                prefix: 'seruti',
                program: 'seruti'
            }
        };

        function sel(p, n) {
            return '#' + p + '-' + n;
        }

        function eid(p, n) {
            return p + '-' + n;
        }

        // ── Helpers ─────────────────────────────────────────────────────────────────

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [c];
            });
        }

        function buildOptionsHtml(list, placeholder) {
            var html = '<option value="">' + escapeHtml(placeholder) + '</option>';
            list.forEach(function(p) {
                html += '<option value="' + escapeHtml(p.kode) + '">' + escapeHtml(p.nama) + '</option>';
            });
            return html;
        }

        function withPetugasOptions(callback) {
            if (petugasOptions) {
                if (typeof callback === 'function') callback(petugasOptions);
                return;
            }
            if (!petugasOptionsRequest) {
                petugasOptionsRequest = $.getJSON('{{ route('dashboard.petugas_options') }}')
                    .done(function(data) {
                        petugasOptions = data;
                    })
                    .fail(function() {
                        petugasOptionsRequest = null;
                        Swal.fire('Error', 'Gagal memuat daftar petugas', 'error');
                    });
            }
            if (typeof callback === 'function') petugasOptionsRequest.done(callback);
        }

        function petugasConfig(field) {
            var configs = {
                petugas_ppl: {
                    key: 'ppl',
                    label: '- PPL -',
                    modalLabel: '-- Pilih PPL --'
                },
                petugas_pml: {
                    key: 'pml',
                    label: '- PML -',
                    modalLabel: '-- Pilih PML --'
                },
                petugas_entry: {
                    key: 'entry',
                    label: '- Entry -',
                    modalLabel: '-- Pilih Entry --'
                },
                petugas_susenas: {
                    key: 'entry',
                    label: '- Susenas -',
                    modalLabel: '-- Pilih Susenas --'
                },
                petugas_seruti: {
                    key: 'entry',
                    label: '- Seruti -',
                    modalLabel: '-- Pilih Seruti --'
                }
            };
            return configs[field] || {
                key: 'entry',
                label: '-',
                modalLabel: '-- Pilih Petugas --'
            };
        }

        // ── Cell Renderers ───────────────────────────────────────────────────────────

        /** Interactive petugas dropdown button */
        function renderPetugasCell(row, field, type) {
            var cfg = petugasConfig(field);
            var selected = row[field] || '';
            var displayName = row[field + '_nama'] || cfg.label;
            return '<button type="button" class="petugas-inline-trigger text-[10px] font-bold bg-white/50 border border-white/60 rounded-xl px-2 py-1 w-full text-left focus:ring-bps-orange flex items-center justify-between gap-2"' +
                ' data-id="' + escapeHtml(row.id) + '"' +
                ' data-type="' + escapeHtml(type) + '"' +
                ' data-field="' + escapeHtml(field) + '"' +
                ' data-selected="' + escapeHtml(selected) + '">' +
                '<span class="truncate">' + escapeHtml(displayName) + '</span>' +
                '<i class="fa-solid fa-chevron-down text-[9px] text-gray-400"></i>' +
                '</button>';
        }

        /** Plain-text read-only cell for petugas */
        function renderPetugasReadonly(row, field) {
            var cfg = petugasConfig(field);
            var displayName = row[field + '_nama'] || cfg.label;
            return '<span class="text-xs font-medium text-gray-500">' + escapeHtml(displayName) + '</span>';
        }

        /** Disabled ceklis icon (view-only) with timestamp */
        function renderCeklisIcon(row, field) {
            var isChecked = row[field];
            var timeField = 'waktu_' + field;
            var icon = isChecked ?
                '<i class="fa-solid fa-check-circle text-green-500 text-base"></i>' :
                '<i class="fa-regular fa-circle text-gray-300 text-base"></i>';
            return '<div class="flex flex-col items-center gap-0.5">' +
                icon +
                '<span class="text-[8px] font-bold text-gray-400 uppercase">' + escapeHtml(row[timeField] || '') +
                '</span>' +
                '</div>';
        }

        /** Interactive ceklis checkbox */
        function renderStatusCheckbox(row, type, field) {
            var timeField = 'waktu_' + field;
            var labelKey = field.replace('ceklis_', '');
            var checked = row[field] ? 'checked' : '';
            return '<div class="flex flex-col items-center">' +
                '<input type="checkbox" class="ceklis-toggle w-5 h-5 text-bps-orange rounded-lg border-gray-300 transition-all cursor-pointer"' +
                ' data-id="' + escapeHtml(row.id) + '" data-type="' + escapeHtml(type) + '" data-field="' + escapeHtml(
                    field) + '" ' + checked + '>' +
                '<span class="text-[8px] font-bold text-gray-400 mt-1 uppercase" id="lbl-' + labelKey + '-' + type + '-' +
                escapeHtml(row.id) + '">' +
                escapeHtml(row[timeField] || '') + '</span></div>';
        }

        var r203StatusOptions = [{
                kode: '1',
                nama: 'Terisi Lengkap'
            },
            {
                kode: '2',
                nama: 'Terisi tdk lengkap'
            },
            {
                kode: '3',
                nama: 'Tidak ada ART/responden yang memberikan informasi sampai akhir masa pencacahan'
            },
            {
                kode: '4',
                nama: 'menolak'
            },
            {
                kode: '5',
                nama: 'Ruta pindah'
            }
        ];

        /**
         * Enum cell (r203 status).
         * @param readOnly  - jika true, render plain text
         */
        function renderEnumCell(row, field, readOnly) {
            var selected = row[field] || '';
            var displayLabel = '- Pilih Status -';
            if (selected) {
                var found = r203StatusOptions.find(function(o) {
                    return o.kode == selected;
                });
                if (found) displayLabel = found.nama;
            }
            if (readOnly) {
                return '<span class="text-xs font-medium">' + escapeHtml(displayLabel) + '</span>';
            }
            return '<button type="button" class="enum-inline-trigger text-[10px] font-bold bg-white/50 border border-white/60 rounded-xl px-2 py-1 w-full text-left focus:ring-bps-orange flex items-center justify-between gap-2 max-w-[150px]"' +
                ' data-id="' + escapeHtml(row.id) + '" data-field="' + escapeHtml(field) + '" data-selected="' + escapeHtml(
                    selected) + '">' +
                '<span class="truncate">' + escapeHtml(displayLabel) + '</span>' +
                '<i class="fa-solid fa-chevron-down text-[9px] text-gray-400"></i>' +
                '</button>';
        }

        /**
         * Bool cell (Ya/Tidak).
         * @param readOnly  - jika true, render plain text
         */
        function renderBoolCell(row, field, readOnly) {
            var selected = row[field];
            var displayLabel = '- Pilih -';
            if (selected === true || selected === 1 || selected === '1' || selected === 'true') displayLabel = 'YA';
            if (selected === false || selected === 0 || selected === '0' || selected === 'false') displayLabel = 'TIDAK';
            if (readOnly) {
                return '<span class="text-xs font-medium">' + escapeHtml(displayLabel) + '</span>';
            }
            return '<button type="button" class="bool-inline-trigger text-[10px] font-bold bg-white/50 border border-white/60 rounded-xl px-2 py-1 w-full text-left focus:ring-bps-orange flex items-center justify-between gap-2 max-w-[100px]"' +
                ' data-id="' + escapeHtml(row.id) + '" data-field="' + escapeHtml(field) + '"' +
                ' data-selected="' + (selected === null || selected === undefined ? '' : (selected ? '1' : '0')) + '">' +
                '<span class="truncate">' + escapeHtml(displayLabel) + '</span>' +
                '<i class="fa-solid fa-chevron-down text-[9px] text-gray-400"></i>' +
                '</button>';
        }

        /**
         * Int cell (inline number input).
         * @param readOnly  - jika true, render plain text
         */
        function renderIntCell(row, field, type, readOnly) {
            var value = row[field];
            var displayValue = (value === null || value === undefined || value === '') ? '-' : escapeHtml(value);
            if (readOnly) {
                return '<span class="text-xs font-semibold text-bps-dark">' + displayValue + '</span>';
            }
            return '<div class="flex items-center justify-between gap-2 px-2 py-1 border border-transparent rounded-xl hover:bg-gray-50/50 cursor-pointer group int-inline-trigger"' +
                ' data-id="' + escapeHtml(row.id) + '" data-field="' + escapeHtml(field) + '" data-type="' + escapeHtml(
                    type || 'dsrt') + '" data-value="' + escapeHtml(value || '') + '">' +
                '<span class="text-xs font-semibold text-bps-dark">' + displayValue + '</span>' +
                '<i class="fa-solid fa-pen text-[10px] text-gray-400 hover:text-bps-orange opacity-0 group-hover:opacity-100 transition-opacity"></i>' +
                '</div>';
        }

        function renderPlainText(data) {
            return '<span class="text-xs font-medium">' + escapeHtml(data) + '</span>';
        }

        function statusLabel(v) {
            var s = String(v ?? '').trim().toLowerCase();
            if (s === '1' || s === 'mitra') return 'Mitra';
            if (s === '2' || s === 'staf kabupaten') return 'Staf Kabupaten';
            return String(v ?? '');
        }

        function renderCheckbox(row, type) {
            var val = (type === 'lapangan' || type === 'entry') ? row.kode_petugas : row.id;
            return '<input type="checkbox" class="row-' + type + '-checkbox w-4 h-4 rounded-md border-gray-300" value="' +
                escapeHtml(val) + '">';
        }

        function renderActionButton(type) {
            var fnMap = {
                dssls: 'editDssls',
                entry: 'editEntry',
                lapangan: 'editLapangan'
            };
            var fn = fnMap[type] || 'editDsrt';
            return '<button type="button" onclick="' + fn +
                '(this)" class="w-8 h-8 rounded-lg bg-bps-orange/10 text-bps-orange hover:bg-bps-orange hover:text-white transition-all"><i class="fa-solid fa-pen-to-square"></i></button>';
        }
        // ── Modal helpers ────────────────────────────────────────────────────────────

        function closeModal(id) {
            $('#' + id).fadeOut(200);
        }

        window.openAddLapanganModal = function() {
            var $modal = $('#modal-add-lapangan');
            $modal.find('form')[0].reset();
            $modal.find('.error-msg').text('').addClass('hidden');
            $modal.find('input, select').removeClass('border-red-500');
            $modal.removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
        }

        window.openAddEntryModal = function() {
            var $modal = $('#modal-add-entry');
            $modal.find('form')[0].reset();
            $modal.find('.error-msg').text('').addClass('hidden');
            $modal.find('input, select').removeClass('border-red-500');
            $modal.removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
        }
        window.editEntry = function(button) {
            var row = dataTableRow($(button), 'entry');
            var item = row ? row.data() : null;
            if (!item) return;

            var $modal = $('#modal-edit-entry');
            $modal.find('.error-msg').text('').addClass('hidden');
            $modal.find('input').removeClass('border-red-500');

            $('#edit-entry-kode').val(item.kode_petugas);
            $('#edit-entry-kode-view').val(item.kode_petugas);
            $('#edit-entry-nama').val(item.nama_petugas);
            $('#edit-entry-email').val(item.email);
            $('#edit-entry-hp').val(item.no_hp);

            $modal.removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
        };
        window.editLapangan = function(button) {
            var row = dataTableRow($(button), 'lapangan');
            var item = row ? row.data() : null;
            if (!item) return;

            var $modal = $('#modal-edit-lapangan');
            $modal.find('.error-msg').text('').addClass('hidden');
            $modal.find('input, select').removeClass('border-red-500');

            $('#edit-lapangan-kode').val(item.kode_petugas);
            $('#edit-lapangan-kode-view').val(item.kode_petugas);
            $('#edit-lapangan-nama').val(item.nama_petugas);
            $('#edit-lapangan-hp').val(item.no_hp);
            $('#edit-lapangan-jabatan').val(item.jabatan);
            $('#edit-lapangan-status').val(statusLabel(item.status));

            $modal.removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
        };

        function populateModalSelects(modalId, fields, values) {
            withPetugasOptions(function(options) {
                fields.forEach(function(f) {
                    var cfg = petugasConfig(f.name);
                    $('#' + f.id).html(buildOptionsHtml(options[cfg.key] || [], cfg.modalLabel)).val(values[
                        f.name] || '');
                });
                $('#' + modalId).removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
            });
        }

        function hostRow($el) {
            var $tr = $el.closest('tr');
            if ($tr.hasClass('child')) $tr = $tr.prev();
            return $tr;
        }

        function dataTableRow($el) {
            var id = $el.closest('table.dataTable').attr('id') || '';
            var dt = dataTables[id.replace(/^dt-/, '')];
            if (!dt) return null;
            var row = dt.row(hostRow($el));
            return row && row.any() ? row : null;
        }

        function editDssls(button) {
            if (isAdminIpds) return; // adminipds: tidak ada action button di dssls
            var $btn = $(button);
            var item = $btn.attr('data-item') ? JSON.parse($btn.attr('data-item')) : null;
            if (!item) {
                var row = dataTableRow($btn, 'dssls');
                item = row ? row.data() : null;
            }
            if (!item) return;
            $('#dssls-id').val(item.id);
            $('#dssls-jml-kel').val(item.perkiraan_jumlah_keluarga);
            $('#dssls-sampel').val(item.sampel_seruti);
            // Build modal field list based on role
            var fields = [];
            if (isSuperAdmin || isAdminSosial) {
                fields.push({
                    id: 'dssls-ppl',
                    name: 'petugas_ppl'
                });
                fields.push({
                    id: 'dssls-pml',
                    name: 'petugas_pml'
                });
            }
            if (isSuperAdmin) {
                fields.push({
                    id: 'dssls-entry',
                    name: 'petugas_entry'
                });
            }
            populateModalSelects('modal-dssls', fields, item);
        }

        function editDsrt(button) {
            var $btn = $(button);
            var item = $btn.attr('data-item') ? JSON.parse($btn.attr('data-item')) : null;
            if (!item) {
                var row = dataTableRow($btn, 'dsrt');
                item = row ? row.data() : null;
            }
            if (!item) return;
            $('#dsrt-id').val(item.id);

            if (isAdminIpds) {
                // adminipds: hanya tampilkan modal notice saja (form sudah disembunyikan di Blade)
                $('#modal-dsrt').removeClass('hidden').css('display', 'flex').hide().fadeIn(200);
                return;
            }

            $('#dsrt-r503').val(item.r503);
            $('#dsrt-r503b').val(item.r503b);
            var fields = [{
                    id: 'dsrt-ppl',
                    name: 'petugas_ppl'
                },
                {
                    id: 'dsrt-pml',
                    name: 'petugas_pml'
                }
            ];
            if (isSuperAdmin) {
                fields.push({
                    id: 'dsrt-susenas',
                    name: 'petugas_susenas'
                });
                fields.push({
                    id: 'dsrt-seruti',
                    name: 'petugas_seruti'
                });
            }
            populateModalSelects('modal-dsrt', fields, item);
        }

        var deleteUrlMap = {
            dssls: '/data-dssls',
            dsrt: '/data-dsrt',
            lapangan: '/petugas-lapangan',
            entry: '/petugas-entry'
        };
        var selectAllMap = {
            dssls: '#selectAllDssls',
            dsrt: '#selectAllDsrt',
            lapangan: '#selectAllLapangan',
            entry: '#selectAllEntry'
        };

        function reloadTable(section) {
            if (dataTables[section]) {
                dataTables[section].ajax.reload(null, false);
                $('#dt-' + section + ' thead input[type="checkbox"]').prop('checked', false);
            }
        }

        function deleteSelected(type, section) {
            section = section || type;
            var ids = [];
            $('#dt-' + section + ' .row-' + type + '-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            if (!ids.length) {
                Swal.fire('Peringatan', 'Pilih minimal satu data', 'warning');
                return;
            }
            Swal.fire({
                title: 'Hapus data terpilih?',
                text: 'Anda akan menghapus ' + ids.length + ' data!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus!'
            }).then(function(r) {
                if (r.isConfirmed) $.post(deleteUrlMap[type] + '/delete-bulk', {
                    ids: ids
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Terhapus!', res.message, 'success');
                        reloadTable(section);
                    }
                });
            });
        }

        function deleteAll(type, section, program) {
            section = section || type;
            var label = program ? ' ' + program.charAt(0).toUpperCase() + program.slice(1) : '';
            Swal.fire({
                title: 'Reset semua data' + label + '?',
                text: 'Seluruh data' + label + ' akan dihapus permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Reset Semua!'
            }).then(function(r) {
                if (r.isConfirmed) $.post(deleteUrlMap[type] + '/delete-all', {
                    program: program || ''
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Terhapus!', res.message, 'success');
                        reloadTable(section);
                    }
                });
            });
        }

        // ── Column Definitions ───────────────────────────────────────────────────────

        function lapanganColumns() {
            var cols = [];
            if (isSuperAdmin || isAdminSosial) cols.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(d, t, row) {
                    return renderCheckbox(row, 'lapangan');
                }
            });
            cols.push({
                data: 'kode_petugas',
                render: function(d) {
                    return '<span class="font-bold text-bps-dark text-xs">' + escapeHtml(d) + '</span>';
                }
            }, {
                data: 'provinsi',
                render: renderPlainText
            }, {
                data: 'kabupaten',
                render: renderPlainText
            }, {
                data: 'nama_petugas',
                render: function(d) {
                    return '<span class="font-medium text-xs">' + escapeHtml(d) + '</span>';
                }
            }, {
                data: 'no_hp',
                render: renderPlainText
            }, {
                data: 'jabatan',
                render: renderPlainText
            }, {
                data: 'status',
                render: function(d) {
                    return '<span class="px-2 py-1 rounded-lg bg-green-50 text-green-700 text-[10px] font-bold uppercase">' +
                        escapeHtml(statusLabel(d)) + '</span>';
                }
            });
            if (isSuperAdmin || isAdminSosial) cols.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function() {
                    return renderActionButton('lapangan');
                }
            });
            return cols;
        }

        function entryColumns() {
            var cols = [];
            if (isSuperAdmin || isAdminIpds) cols.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(d, t, row) {
                    return renderCheckbox(row, 'entry');
                }
            });
            cols.push({
                data: 'kode_petugas',
                render: function(d) {
                    return '<span class="font-bold text-bps-dark text-xs">' + escapeHtml(d) + '</span>';
                }
            }, {
                data: 'provinsi',
                render: renderPlainText
            }, {
                data: 'kabupaten',
                render: renderPlainText
            }, {
                data: 'nama_petugas',
                render: function(d) {
                    return '<span class="font-medium text-xs">' + escapeHtml(d) + '</span>';
                }
            }, {
                data: 'email',
                render: renderPlainText
            }, {
                data: 'no_hp',
                render: renderPlainText
            }, {
                data: 'status',
                render: function(d) {
                    return '<span class="px-2 py-1 rounded-lg bg-green-50 text-green-700 text-[10px] font-bold uppercase">' +
                        escapeHtml(statusLabel(d)) + '</span>';
                }
            });
            if (isSuperAdmin || isAdminIpds) cols.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function() {
                    return renderActionButton('entry');
                }
            });
            return cols;
        }

        function dsslsColumns() {
            var cols = [];

            // ── Checkbox & Action: tidak ada untuk adminipds ──
            if (!isAdminIpds) {
                cols.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center border-b border-stone-200 dark:border-stone-800',
                    render: function(d, t, row) {
                        return renderCheckbox(row, 'dssls');
                    }
                });
                cols.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'border-b border-stone-200 dark:border-stone-800',
                    render: function() {
                        return renderActionButton('dssls');
                    }
                });
            }

            // ── Data utama ──
            cols.push({
                data: 'nama_kecamatan',
                className: 'border-b border-stone-200 dark:border-stone-800',
                render: function(d, t, row) {
                    return '<p class="font-bold text-bps-dark text-xs">' + escapeHtml(row.nama_kecamatan) +
                        '</p>' +
                        '<p class="text-[10px] text-gray-500 uppercase">' + escapeHtml(row
                            .nama_desa_kelurahan) + '</p>';
                }
            }, {
                data: 'kode_sls',
                className: 'border-b border-stone-200 dark:border-stone-800',
                render: function(d, t, row) {
                    return '<p class="font-bold text-bps-dark text-xs">' + escapeHtml(row.kode_sls) + '</p>' +
                        '<p class="text-[10px] text-gray-500 uppercase truncate max-w-[150px]">' + escapeHtml(
                            row.nama_sls) + '</p>';
                }
            });

            // ── Group 1: LAPANGAN s/d RUTA HASIL UPDATING (soft orange) ──
            // Ceklis Lapangan
            cols.push({
                data: 'ceklis_lap',
                className: 'text-center bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800 border-l border-l-orange-200 dark:border-l-orange-800',
                render: function(d, t, row) {
                    return isAdminIpds ? renderCeklisIcon(row, 'ceklis_lap') : renderStatusCheckbox(row,
                        'dssls', 'ceklis_lap');
                }
            });

            // Petugas PPL & PML: superadmin & adminsosial
            if (isSuperAdmin || isAdminSosial) {
                cols.push({
                    data: 'petugas_ppl_nama',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_ppl', 'dssls');
                    }
                });
                cols.push({
                    data: 'petugas_pml_nama',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_pml', 'dssls');
                    }
                });
            }

            // Keluarga Awal, Keluarga Hasil, Ruta Hasil
            cols.push({
                data: 'jumlah_keluarga_awal',
                className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                render: function(d, t, row) {
                    return renderIntCell(row, 'jumlah_keluarga_awal', 'dssls', isAdminIpds);
                }
            }, {
                data: 'jumlah_keluarga_hasil_updating',
                className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                render: function(d, t, row) {
                    return renderIntCell(row, 'jumlah_keluarga_hasil_updating', 'dssls', isAdminIpds);
                }
            }, {
                data: 'jumlah_rumah_tangga_hasil_updating',
                className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                render: function(d, t, row) {
                    return renderIntCell(row, 'jumlah_rumah_tangga_hasil_updating', 'dssls', isAdminIpds);
                }
            });

            // ── Group 2: SOSIAL (soft blue) ──
            cols.push({
                data: 'ceklis_sosial',
                className: 'text-center bg-blue-50/50 dark:bg-blue-950/12 border-b border-blue-200 dark:border-blue-800 border-l border-l-blue-200 dark:border-l-blue-800',
                render: function(d, t, row) {
                    return isAdminIpds ? renderCeklisIcon(row, 'ceklis_sosial') : renderStatusCheckbox(row,
                        'dssls', 'ceklis_sosial');
                }
            });

            // ── Group 3: IPDS s/d ENTRY (soft teal) ──
            cols.push({
                data: 'ceklis_ipds',
                className: 'text-center bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800 border-l border-l-teal-200 dark:border-l-teal-800',
                render: function(d, t, row) {
                    if (isAdminSosial) return renderCeklisIcon(row, 'ceklis_ipds');
                    return renderStatusCheckbox(row, 'dssls', 'ceklis_ipds');
                }
            });

            if (isSuperAdmin || isAdminIpds) {
                cols.push({
                    data: 'petugas_entry_nama',
                    className: 'bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_entry', 'dssls');
                    }
                });
            }

            return cols;
        }

        function dsrtColumns(cfg) {
            var cols = [];
            var ipdsReadOnly = isAdminIpds;

            if (isSuperAdmin) {
                cols.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center border-b border-stone-200 dark:border-stone-800',
                    render: function(d, t, row) {
                        return renderCheckbox(row, 'dsrt');
                    }
                });
            }
            // ── Action button (semua role) ──
            cols.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'border-b border-stone-200 dark:border-stone-800',
                render: function() {
                    return renderActionButton('dsrt');
                }
            });

            // ── Data dasar (semua role) ──
            cols.push({
                data: 'kec',
                className: 'border-b border-stone-200 dark:border-stone-800',
                render: function(d, t, row) {
                    return '<p class="font-bold text-bps-dark text-xs">' + escapeHtml(row.nmkec || row.kec) +
                        '</p>' +
                        '<p class="text-[10px] text-gray-500 uppercase">' + escapeHtml(row.nmdesa || row.desa) +
                        '</p>';
                }
            }, {
                data: 'nmslsm',
                className: 'border-b border-stone-200 dark:border-stone-800',
                render: function(d, t, row) {
                    return '<p class="font-bold text-bps-dark text-xs truncate max-w-[100px]">' + escapeHtml(row
                            .nmslsm) + '</p>' +
                        '<p class="text-[10px] text-gray-500 uppercase">' + escapeHtml(row.nks_sak22) + '</p>';
                }
            }, {
                data: 'r503',
                className: 'font-medium text-xs border-b border-stone-200 dark:border-stone-800',
                render: function(d) {
                    return escapeHtml(d);
                }
            });

            // ── Group 1: LAPANGAN s/d PML (soft orange) ──
            // Ceklis Lapangan
            cols.push({
                data: 'ceklis_lap',
                className: 'text-center bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800 border-l border-l-orange-200 dark:border-l-orange-800',
                render: function(d, t, row) {
                    return isAdminIpds ? renderCeklisIcon(row, 'ceklis_lap') : renderStatusCheckbox(row, 'dsrt',
                        'ceklis_lap');
                }
            });

            // R203 KOR & R203 KP: superadmin & adminsosial
            if (isSuperAdmin || isAdminSosial) {
                cols.push({
                    data: 'r203_kor',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderEnumCell(row, 'r203_kor', ipdsReadOnly);
                    }
                }, {
                    data: 'r203_kp',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderEnumCell(row, 'r203_kp', ipdsReadOnly);
                    }
                });
            }

            // Petugas PPL & PML: superadmin & adminsosial
            if (isSuperAdmin || isAdminSosial) {
                cols.push({
                    data: 'petugas_ppl_nama',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_ppl', 'dsrt');
                    }
                });
                cols.push({
                    data: 'petugas_pml_nama',
                    className: 'bg-orange-50/50 dark:bg-orange-950/12 border-b border-orange-200 dark:border-orange-800',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_pml', 'dsrt');
                    }
                });
            }

            // ── Group 2: SOSIAL s/d CATATAN KP (soft blue) ──
            // Ceklis Sosial
            cols.push({
                data: 'ceklis_sosial',
                className: 'text-center bg-blue-50/50 dark:bg-blue-950/12 border-b border-blue-200 dark:border-blue-800 border-l border-l-blue-200 dark:border-l-blue-800',
                render: function(d, t, row) {
                    return isAdminIpds ? renderCeklisIcon(row, 'ceklis_sosial') : renderStatusCheckbox(row,
                        'dsrt', 'ceklis_sosial');
                }
            });

            // Catatan KOR & Catatan KP: superadmin & adminsosial
            if (isSuperAdmin || isAdminSosial) {
                cols.push({
                    data: 'blok_catatan_kor',
                    className: 'bg-blue-50/50 dark:bg-blue-950/12 border-b border-blue-200 dark:border-blue-800',
                    render: function(d, t, row) {
                        return renderBoolCell(row, 'blok_catatan_kor', ipdsReadOnly);
                    }
                }, {
                    data: 'blok_catatan_kp',
                    className: 'bg-blue-50/50 dark:bg-blue-950/12 border-b border-blue-200 dark:border-blue-800',
                    render: function(d, t, row) {
                        return renderBoolCell(row, 'blok_catatan_kp', ipdsReadOnly);
                    }
                });
            }

            // ── Group 3: PEMERIKSAAN s/d R305 (soft teal) ──
            // Ceklis Pemeriksaan
            cols.push({
                data: 'ceklis_pemeriksaan',
                className: 'text-center bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800 border-l border-l-teal-200 dark:border-l-teal-800',
                render: function(d, t, row) {
                    return isAdminIpds ? renderCeklisIcon(row, 'ceklis_pemeriksaan') : renderStatusCheckbox(row,
                        'dsrt', 'ceklis_pemeriksaan');
                }
            });

            // R301 Jml ART, R304, R305: superadmin & adminsosial
            if (isSuperAdmin || isAdminSosial) {
                cols.push({
                    data: 'r301_jumlah_art',
                    className: 'bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800',
                    render: function(d, t, row) {
                        return renderIntCell(row, 'r301_jumlah_art', 'dsrt', ipdsReadOnly);
                    }
                }, {
                    data: 'r304_vsen26kp',
                    className: 'bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800',
                    render: function(d, t, row) {
                        return renderIntCell(row, 'r304_vsen26kp', 'dsrt', ipdsReadOnly);
                    }
                }, {
                    data: 'r305_vsen26kp',
                    className: 'bg-teal-50/50 dark:bg-teal-950/12 border-b border-teal-200 dark:border-teal-800',
                    render: function(d, t, row) {
                        return renderIntCell(row, 'r305_vsen26kp', 'dsrt', ipdsReadOnly);
                    }
                });
            }

            // ── Group 4: IPDS s/d SERUTI (soft purple) ──
            // Ceklis IPDS
            if (isSuperAdmin || isAdminIpds) {
                cols.push({
                    data: 'ceklis_ipds',
                    className: 'text-center bg-purple-50/50 dark:bg-purple-950/12 border-b border-purple-200 dark:border-purple-800 border-l border-l-purple-200 dark:border-l-purple-800',
                    render: function(d, t, row) {
                        return renderStatusCheckbox(row, 'dsrt', 'ceklis_ipds');
                    }
                });
            }

            // Petugas Susenas & Seruti: superadmin & adminipds
            if ((isSuperAdmin || isAdminIpds) && cfg.program !== 'seruti') {
                cols.push({
                    data: 'petugas_susenas_nama',
                    className: 'bg-purple-50/50 border-b border-purple-200',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_susenas', 'dsrt');
                    }
                });
            }
            if ((isSuperAdmin || isAdminIpds) && cfg.program !== 'susenas') {
                cols.push({
                    data: 'petugas_seruti_nama',
                    className: 'bg-purple-50/50 border-b border-purple-200',
                    render: function(d, t, row) {
                        return renderPetugasCell(row, 'petugas_seruti', 'dsrt');
                    }
                });
            }

            return cols;
        }

        function columnsFor(section) {
            var cfg = sectionConfig[section];
            return ({
                lapangan: lapanganColumns,
                entry: entryColumns,
                dssls: dsslsColumns,
                dsrt: dsrtColumns
            })[cfg.type](cfg);
        }

        // ── Dashboard Chart & Summary Logic ──────────────────────────────────────────

        var dashboardCharts = {};
        var dashboardData = {};
        var filterPopulated = {};

        var dsrtChartTargets = [{
                code: '00223',
                name: 'RT 004 DUSUN 02'
            }, {
                code: '50148',
                name: 'RT 002 DUSUN I'
            },
            {
                code: '50150',
                name: 'RT 004 DUSUN 11'
            }, {
                code: '00259',
                name: 'RT 02 DUSUN I'
            },
            {
                code: '00293',
                name: 'RT 04 DUSUN 2'
            }, {
                code: '00642',
                name: 'RT 06 DUSUN III'
            },
            {
                code: '00649',
                name: 'RT 03 DUSUN II'
            }, {
                code: '50328',
                name: 'RT 002 DUSUN 01'
            },
            {
                code: '50343',
                name: 'RT 05 DUSUN II'
            }, {
                code: '00875',
                name: 'RT 002 DUSUN 001'
            },
            {
                code: '50495',
                name: 'RT 002 LINGKUNGAN I'
            }, {
                code: '01004',
                name: 'RT 06 DUSUN III'
            },
            {
                code: '50578',
                name: 'RT 012 LINGKUNGAN III'
            }, {
                code: '50549',
                name: 'RT 005 LINGKUNGAN III'
            },
            {
                code: '01146',
                name: 'RT 006 DUSUN 3'
            }
        ];

        var dsrtChartFilterLabels = {
            semua: 'Semua',
            lap: 'Lapangan',
            pemeriksaan: 'Pemeriksaan',
            sosial: 'Sosial',
            ipds: 'IPDS'
        };

        function initChart(canvasId, type, labels, datasets, options = {}) {
            var canvas = document.getElementById(canvasId);
            if (!canvas) return;

            var container = canvas.parentElement;
            if (container) {
                var existingText = container.querySelector('.chart-no-data-text');
                if (existingText) existingText.remove();
            }

            if (dashboardCharts[canvasId]) {
                dashboardCharts[canvasId].destroy();
            }

            var hasData = datasets && datasets.some(function(ds) {
                return ds.data && ds.data.length > 0 && ds.data.some(function(v) {
                    return Number(v) > 0;
                });
            });

            if (!hasData) {
                canvas.style.display = 'none';
                if (container) {
                    var txt = document.createElement('div');
                    txt.className =
                        'chart-no-data-text text-sm font-semibold text-gray-400 absolute inset-0 flex items-center justify-center';
                    txt.innerText = 'Tidak ada data penugasan atau progres';
                    container.appendChild(txt);
                }
                return;
            }

            canvas.style.display = 'block';

            var defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 10
                            },
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                }
            };

            var mergedOptions = $.extend(true, {}, defaultOptions, options);

            dashboardCharts[canvasId] = new Chart(canvas.getContext('2d'), {
                type: type,
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: mergedOptions
            });
        }

        function renderDsrtChecklistChart(prefix, res) {
            var selectedType = $(sel(prefix, 'filter-chart-dsrt')).val() || 'lap';
            var selectedLabel = dsrtChartFilterLabels[selectedType] || 'Lapangan';
            var rows = (res && res.dsrt && Array.isArray(res.dsrt.chart)) ? res.dsrt.chart : [];
            var rowByCode = {};
            rows.forEach(function(r) {
                rowByCode[String(r.kode).trim()] = r;
            });

            var labels = dsrtChartTargets.map(function(t) {
                return [t.code, t.name];
            });
            var data = dsrtChartTargets.map(function(t) {
                var r = rowByCode[t.code] || {};
                return Number(r[selectedType] || 0);
            });

            initChart(eid(prefix, 'chart-dsrt'), 'bar', labels, [{
                label: selectedLabel,
                data: data,
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                maxBarThickness: 42
            }], {
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(items) {
                                return items.length ? 'NBS/NKS: ' + dsrtChartTargets[items[0].dataIndex].code :
                                    '';
                            },
                            label: function(c) {
                                return 'Jenis ceklis: ' + selectedLabel + ' | Jumlah: ' + c.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Ceklis'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            minRotation: 45,
                            maxRotation: 45,
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            });
        }

        function populateFilters(prefix, program, kecDesa) {
            if (filterPopulated[prefix]) return;
            filterPopulated[prefix] = true;
            var $kec = $(sel(prefix, 'filter-kecamatan')),
                $desa = $(sel(prefix, 'filter-desa'));
            Object.keys(kecDesa).sort().forEach(function(k) {
                $kec.append($('<option>', {
                    value: k.trim(),
                    text: k.trim()
                }));
            });
            $kec.on('change', function() {
                var v = $(this).val();
                $desa.find('option:not(:first)').remove();
                if (!v) {
                    $desa.prop('disabled', true);
                    loadDashboardSummary(prefix, program, '', '');
                    return;
                }
                var key = Object.keys(kecDesa).find(function(k) {
                    return k.trim() === v;
                });
                var list = (key && kecDesa[key]) || [];
                list.forEach(function(d) {
                    $desa.append($('<option>', {
                        value: d.trim(),
                        text: d.trim()
                    }));
                });
                $desa.prop('disabled', !list.length);
                loadDashboardSummary(prefix, program, v, '');
            });
            $desa.on('change', function() {
                loadDashboardSummary(prefix, program, $kec.val(), $(this).val());
            });
            $(sel(prefix, 'btn-reset-filter')).on('click', function() {
                $kec.val('');
                $desa.find('option:not(:first)').remove();
                $desa.prop('disabled', true);
                $(sel(prefix, 'search-rekap')).val('');
                loadDashboardSummary(prefix, program, '', '');
            });
        }

        function loadDashboardSummary(prefix, program, kec, desa) {
            $.getJSON('{{ route('dashboard.summary') }}', {
                    program: program || '',
                    kecamatan: kec || '',
                    desa: desa || ''
                })
                .done(function(res) {
                    dashboardData[prefix] = res;
                    populateFilters(prefix, program, res.kec_desa);
                    var S = function(n) {
                        return $(sel(prefix, n));
                    };

                    S('summary-ppl').text(res.petugas.ppl);
                    S('summary-pml').text(res.petugas.pml);
                    S('summary-entry').text(res.petugas.entry);
                    S('summary-total-petugas').text(res.petugas.total);

                    S('chart-dsrt-total').text(res.dsrt.total);
                    S('dsrt-progress-badge').text(res.dsrt.progress + '%');
                    S('dsrt-progress-bar').css('width', res.dsrt.progress + '%');
                    S('dsrt-completed-count').text(res.dsrt.completed);
                    S('dsrt-total-count').text(res.dsrt.total);

                    if (S('chart-dssls').length) {
                        S('chart-dssls-total').text(res.dssls.total);
                        S('dssls-progress-badge').text(res.dssls.progress + '%');
                        S('dssls-progress-bar').css('width', res.dssls.progress + '%');
                        S('dssls-completed-count').text(res.dssls.completed);
                        S('dssls-total-count').text(res.dssls.total);
                        initChart(eid(prefix, 'chart-dssls'), 'bar', ['Lapangan', 'Sosial', 'IPDS'],
                            [{
                                label: 'Selesai Ceklis',
                                data: [res.dssls.lap, res.dssls.sosial, res.dssls.ipds],
                                backgroundColor: '#FF8C00',
                                borderRadius: 4
                            }], {
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            });
                    }

                    renderDsrtChecklistChart(prefix, res);

                    var html = '';
                    if (res.rekap_entry && res.rekap_entry.length) {
                        res.rekap_entry.forEach(function(it, i) {
                            html += '<tr class="rekap-row" data-nama="' + escapeHtml(it.nama.toLowerCase()) +
                                '" data-kode="' + escapeHtml(it.kode.toLowerCase()) + '">' +
                                '<td class="py-3.5 px-4 text-xs text-center">' + (i + 1) + '</td>' +
                                '<td class="py-3.5 px-4 text-xs font-bold text-gray-800">' + escapeHtml(it
                                    .nama) + '</td>' +
                                '<td class="py-3.5 px-4 text-xs text-center">' + escapeHtml(it.kode) + '</td>' +
                                '<td class="py-3.5 px-4 text-xs font-bold text-center">' + it.pemutakhiran +
                                '</td>' +
                                '<td class="py-3.5 px-4 text-xs font-bold text-center">' + it.susenas +
                                '</td>' +
                                '<td class="py-3.5 px-4 text-xs font-bold text-center">' + it.seruti + '</td>' +
                                '<td class="py-3.5 px-4 text-xs font-black text-bps-orange text-center">' + it
                                .total + '</td></tr>';
                        });
                    } else {
                        html =
                            '<tr><td colspan="7" class="py-8 text-center text-xs text-gray-400">Tidak ada data petugas entry</td></tr>';
                    }
                    S('tbody-rekap').html(html);
                    if (S('search-rekap').val()) S('search-rekap').trigger('input');
                })
                .fail(function() {
                    console.error('Gagal memuat data dashboard.');
                });
        }

        // Filter custom DSRT (NBS, NKS, Wilayah).
        // NBS/NKS dibandingkan sebagai STRING supaya 00223 tetap berbeda dari 223.
        $.fn.dataTable.ext.search.push(function(settings, data, idx, rowData) {
            var m = /^dt-(\w+)-dsrt$/.exec(settings.nTable.id || '');
            if (!m) return true;
            var p = m[1];
            var nks = String($(sel(p, 'filter-dsrt-nks')).val() || '').trim();
            var wil = String($(sel(p, 'filter-dsrt-wilayah')).val() || '').trim().toLowerCase();
            if (!nks && !wil) return true;
            rowData = rowData || {};
            var rowNks = String(rowData.nks_sak22 ?? '').trim();
            var rowWil = (String(rowData.nmkec || rowData.kec || '').trim() + ' - ' +
                    String(rowData.nmdesa || rowData.desa || '').trim()).replace(/^\s*-\s*|\s*-\s*$/g, '')
                .toLowerCase();
            if (nks && rowNks !== nks) return false;
            if (wil && rowWil !== wil) return false;
            return true;
        });
        // ── DataTables Init ──────────────────────────────────────────────────────────

        $(document).ready(function() {

            $('#filter-chart-dsrt').on('change', function() {
                if (dashboardData) {
                    renderDsrtChecklistChart(dashboardData);
                }
            });

            var processingHtml = '<div class="custom-loader flex items-center justify-center space-x-1.5">' +
                '<div class="w-2.5 h-2.5 bg-[#FF8C00] rounded-full animate-bounce" style="animation-delay:-0.3s"></div>' +
                '<div class="w-2.5 h-2.5 bg-[#FF8C00] rounded-full animate-bounce" style="animation-delay:-0.15s"></div>' +
                '<div class="w-2.5 h-2.5 bg-[#FF8C00] rounded-full animate-bounce"></div>' +
                '</div>';

            var dtConfigs = {
                responsive: false,
                scrollX: false,
                autoWidth: false,
                searchDelay: 350,
                language: {
                    search: '',
                    searchPlaceholder: 'Cari data...',
                    lengthMenu: '_MENU_ baris',
                    processing: processingHtml,
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                },
                drawCallback: function() {
                    $('.dataTables_paginate .paginate_button').addClass('rounded-xl border-0');
                }
            };

            var serverEndpoints = {
                lapangan: '{{ route('dashboard.datatable_lapangan') }}',
                entry: '{{ route('dashboard.datatable_entry') }}',
                dssls: '{{ route('dashboard.datatable_dssls') }}',
                dsrt: '{{ route('dashboard.datatable_dsrt') }}'
            };

            function initServerTable(section) {
                var cfg = sectionConfig[section];
                if (!cfg) return;
                if (cfg.type === 'dashboard') {
                    loadDashboardSummary(cfg.prefix, cfg.program);
                    return;
                }
                if (dataTables[section]) {
                    if (cfg.program) dataTables[section].ajax.reload(null, false);
                    else dataTables[section].columns.adjust();
                    return;
                }
                $('#tbody-' + section).empty();
                var ajaxCfg = cfg.program ? {
                        url: serverEndpoints[cfg.type],
                        data: function(d) {
                            d.program = cfg.program;
                        }
                    } :
                    serverEndpoints[cfg.type];

                dataTables[section] = $('#dt-' + section).DataTable($.extend(true, {}, dtConfigs, {
                    processing: true,
                    serverSide: false,
                    deferRender: true,
                    order: [],
                    ajax: ajaxCfg,
                    columns: columnsFor(section),
                    initComplete: function() {
                        var api = this.api(),
                            p = cfg.prefix;
                        if (cfg.type === 'dsrt') {
                            $('#dt-' + section + '_filter').addClass('hidden');

                            var wilayah = {};
                            api.rows().every(function() {
                                var r = this.data() || {};
                                var kec = String(r.nmkec || r.kec || '').trim(),
                                    desa = String(r.nmdesa || r.desa || '').trim();
                                if (kec || desa) wilayah[kec && desa ? kec + ' - ' + desa :
                                    (kec || desa)] = true;
                            });
                            var $w = $(sel(p, 'filter-dsrt-wilayah'));
                            $w.find('option:not(:first)').remove();
                            Object.keys(wilayah).sort(function(a, b) {
                                    return a.localeCompare(b, 'id');
                                })
                                .forEach(function(l) {
                                    $w.append($('<option>', {
                                        value: l,
                                        text: l
                                    }));
                                });

                            var $search = $(sel(p, 'filter-dsrt-search'));
                            $search.off('.s').on('input.s', function() {
                                api.search(this.value).draw();
                            });
                            $(sel(p, 'filter-dsrt-nks') + ',' + sel(p, 'filter-dsrt-wilayah'))
                                .off('change.f').on('change.f', function() {
                                    api.draw();
                                });
                            $(sel(p, 'reset-filter-dsrt-code')).off('click.f').on('click.f',
                                function() {
                                    $(sel(p, 'filter-dsrt-nks') + ',' + sel(p,
                                        'filter-dsrt-wilayah')).val('');
                                    $search.val('');
                                    api.search('').draw();
                                });
                        }
                        var $a = $('#dt-actions-' + section);
                        if ($a.length) {
                            $('#dt-' + section + '_length').addClass('flex items-center flex-wrap')
                                .append($a.removeClass('hidden').css({
                                    display: 'inline-flex',
                                    'margin-left': '1.25rem'
                                }));
                        }
                    }
                }));
            }
            withPetugasOptions(); // pre-fetch

            var hash = window.location.hash.substring(1);
            var activeSection = '{{ session('active_tab', 'susenas-dashboard') }}';
            var available = $('.sidebar-link').map(function() {
                return $(this).data('section');
            }).get();
            if (hash && available.includes(hash)) activeSection = hash;

            $('.sidebar-link').removeClass('active');
            var $activeLink = $('.sidebar-link[data-section="' + activeSection + '"]');
            $activeLink.addClass('active');
            $('#current-section-title').text($activeLink.find('span').text());
            $('.dashboard-section').addClass('hidden');
            $('#section-' + activeSection).removeClass('hidden');
            if (!window.location.hash) window.location.hash = activeSection;

            initServerTable(activeSection);
            $(document).on('tabChanged', function(e, section) {
                initServerTable(section);
            });

            // ── Inline: petugas dropdown ─────────────────────────────────────────────
            $(document).on('click', '.petugas-inline-trigger', function() {
                var $btn = $(this),
                    type = $btn.data('type'),
                    field = $btn.data('field');
                var row = dataTableRow($btn, type);
                if (!row) return;
                withPetugasOptions(function(options) {
                    var rowData = row.data(),
                        cfg = petugasConfig(field);
                    var selected = rowData[field] || '';
                    var selectClass = type === 'dssls' ? 'petugas-dssls-update' :
                        'petugas-dsrt-update';
                    var html = '<select class="' + selectClass +
                        ' inline-petugas-select text-[10px] font-bold bg-white/50 border-white/60 rounded-xl px-2 py-1 w-full focus:ring-bps-orange"' +
                        ' data-id="' + escapeHtml(rowData.id) + '" data-type="' + escapeHtml(type) +
                        '" data-field="' + escapeHtml(field) + '" data-selected="' + escapeHtml(
                            selected) + '">' +
                        buildOptionsHtml(options[cfg.key] || [], cfg.label) + '</select>';
                    var $cell = $btn.closest('td');
                    $cell.html(html);
                    var sel = $cell.find('select')[0];
                    sel.value = selected;
                    sel.focus();
                    try {
                        sel.showPicker();
                    } catch (e) {
                        sel.dispatchEvent(new MouseEvent('mousedown', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        }));
                    }
                });
            });

            $(document).on('blur', '.inline-petugas-select', function() {
                var $s = $(this),
                    type = $s.data('type'),
                    field = $s.data('field');
                var row = dataTableRow($s, type);
                if (row) $s.closest('td').html(renderPetugasCell(row.data(), field, type));
            });

            // ── Inline: enum dropdown ────────────────────────────────────────────────
            $(document).on('click', '.enum-inline-trigger', function() {
                if (isAdminIpds) return;
                var $btn = $(this),
                    field = $btn.data('field');
                var row = dataTableRow($btn, 'dsrt');
                if (!row) return;
                var rowData = row.data(),
                    selected = rowData[field] || '';
                var opts = '<option value="">-- Pilih Status --</option>';
                r203StatusOptions.forEach(function(o) {
                    opts += '<option value="' + o.kode + '">' + o.nama + '</option>';
                });
                var html =
                    '<select class="inline-enum-select text-[10px] font-bold bg-white/50 border border-white/60 rounded-xl px-2 py-1 w-full focus:ring-bps-orange max-w-[150px]"' +
                    ' data-id="' + escapeHtml(rowData.id) + '" data-field="' + escapeHtml(field) + '">' +
                    opts + '</select>';
                var $cell = $btn.closest('td');
                $cell.html(html);
                var sel = $cell.find('select')[0];
                sel.value = selected;
                sel.focus();
                try {
                    sel.showPicker();
                } catch (e) {
                    sel.dispatchEvent(new MouseEvent('mousedown', {
                        bubbles: true,
                        cancelable: true,
                        view: window
                    }));
                }
            });

            $(document).on('change', '.inline-enum-select', function() {
                var $s = $(this),
                    val = $s.val(),
                    id = $s.data('id'),
                    field = $s.data('field');
                var row = dataTableRow($s, 'dsrt');
                $s.css('opacity', '0.5');
                $.post('/data-dsrt/update-inline', {
                    id: id,
                    field: field,
                    value: val
                }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'Gagal menyimpan data', 'error');
                        return;
                    }
                    if (row) {
                        var rd = row.data();
                        rd[field] = val;
                        row.data(rd);
                        $s.closest('td').html(renderEnumCell(rd, field, false));
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Koneksi error', 'error');
                    if (row) $s.closest('td').html(renderEnumCell(row.data(), field, false));
                });
            });

            $(document).on('blur', '.inline-enum-select', function() {
                var $s = $(this),
                    field = $s.data('field'),
                    row = dataTableRow($s, 'dsrt');
                if (row) $s.closest('td').html(renderEnumCell(row.data(), field, false));
            });

            // ── Inline: bool dropdown ────────────────────────────────────────────────
            $(document).on('click', '.bool-inline-trigger', function() {
                if (isAdminIpds) return;
                var $btn = $(this),
                    field = $btn.data('field');
                var row = dataTableRow($btn, 'dsrt');
                if (!row) return;
                var rowData = row.data(),
                    selected = rowData[field];
                var val = (selected === null || selected === undefined) ? '' : (selected ? '1' : '0');
                var html =
                    '<select class="inline-bool-select text-[10px] font-bold bg-white/50 border border-white/60 rounded-xl px-2 py-1 w-full focus:ring-bps-orange max-w-[100px]"' +
                    ' data-id="' + escapeHtml(rowData.id) + '" data-field="' + escapeHtml(field) + '">' +
                    '<option value="">-- Pilih --</option><option value="1">YA</option><option value="0">TIDAK</option></select>';
                var $cell = $btn.closest('td');
                $cell.html(html);
                var sel = $cell.find('select')[0];
                sel.value = val;
                sel.focus();
                try {
                    sel.showPicker();
                } catch (e) {
                    sel.dispatchEvent(new MouseEvent('mousedown', {
                        bubbles: true,
                        cancelable: true,
                        view: window
                    }));
                }
            });

            $(document).on('change', '.inline-bool-select', function() {
                var $s = $(this),
                    val = $s.val(),
                    id = $s.data('id'),
                    field = $s.data('field');
                var row = dataTableRow($s, 'dsrt');
                $s.css('opacity', '0.5');
                $.post('/data-dsrt/update-inline', {
                    id: id,
                    field: field,
                    value: val
                }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'Gagal menyimpan data', 'error');
                        return;
                    }
                    if (row) {
                        var rd = row.data();
                        rd[field] = val === '' ? null : (val === '1');
                        row.data(rd);
                        $s.closest('td').html(renderBoolCell(rd, field, false));
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Koneksi error', 'error');
                    if (row) $s.closest('td').html(renderBoolCell(row.data(), field, false));
                });
            });

            $(document).on('blur', '.inline-bool-select', function() {
                var $s = $(this),
                    field = $s.data('field'),
                    row = dataTableRow($s, 'dsrt');
                if (row) $s.closest('td').html(renderBoolCell(row.data(), field, false));
            });

            // ── Inline: int input ────────────────────────────────────────────────────
            $(document).on('click', '.int-inline-trigger', function() {
                var $div = $(this),
                    type = $div.data('type') || 'dsrt';
                // adminipds tidak boleh edit int di dsrt
                if (isAdminIpds && type === 'dsrt') return;
                var field = $div.data('field'),
                    id = $div.data('id'),
                    value = $div.data('value');
                var html =
                    '<input type="number" class="inline-int-input text-xs font-semibold text-bps-dark bg-white border border-gray-300 rounded-lg px-2 py-1 w-full max-w-[80px] focus:ring-1 focus:ring-bps-orange focus:border-bps-orange"' +
                    ' data-id="' + escapeHtml(id) + '" data-field="' + escapeHtml(field) + '" data-type="' +
                    escapeHtml(type) + '" data-original-value="' + escapeHtml(value) + '" value="' +
                    escapeHtml(value) + '">';
                var $cell = $div.closest('td');
                $cell.html(html);
                var inp = $cell.find('input')[0];
                inp.focus();
                inp.select();
            });

            function saveIntField($input) {
                if ($input.data('saving')) return;
                $input.data('saving', true);
                var value = $input.val(),
                    id = $input.data('id'),
                    field = $input.data('field'),
                    type = $input.data('type') || 'dsrt';
                var row = dataTableRow($input, type);
                if (value === String($input.data('original-value'))) {
                    if (row) $input.closest('td').html(renderIntCell(row.data(), field, type, false));
                    return;
                }
                $input.css('opacity', '0.5');
                $.post('/data-' + type + '/update-inline', {
                    id: id,
                    field: field,
                    value: value
                }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'Gagal menyimpan data', 'error');
                        if (row) $input.closest('td').html(renderIntCell(row.data(), field, type, false));
                        return;
                    }
                    if (row) {
                        var rd = row.data();
                        rd[field] = value === '' ? null : value;
                        row.data(rd);
                        $input.closest('td').html(renderIntCell(rd, field, type, false));
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Koneksi error', 'error');
                    if (row) $input.closest('td').html(renderIntCell(row.data(), field, type, false));
                });
            }

            $(document).on('keypress', '.inline-int-input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    saveIntField($(this));
                }
            });
            $(document).on('blur', '.inline-int-input', function() {
                saveIntField($(this));
            });

            // ── Select all checkboxes ────────────────────────────────────────────────
            $(document).on('change', '.select-all', function() {
                var s = $(this).data('section'),
                    t = $(this).data('type');
                $('#dt-' + s + ' .row-' + t + '-checkbox').prop('checked', $(this).is(':checked'));
            });
            $(document).on('change', '#selectAllLapangan', function() {
                $('.row-lapangan-checkbox').prop('checked', $(this).is(':checked'));
            });
            $(document).on('change', '#selectAllEntry', function() {
                $('.row-entry-checkbox').prop('checked', $(this).is(':checked'));
            });

            // ── Inline petugas update ────────────────────────────────────────────────
            $(document).on('change', '.petugas-dssls-update, .petugas-dsrt-update', function() {
                var $s = $(this),
                    val = $s.val(),
                    text = val ? $s.find('option:selected').text() : null;
                var id = $s.data('id'),
                    field = $s.data('field');
                var type = $s.data('type') || ($s.hasClass('petugas-dssls-update') ? 'dssls' : 'dsrt');
                var row = dataTableRow($s, type);
                $s.css('opacity', '0.5');
                $.post('/data-' + type + '/update-inline', {
                    id: id,
                    field: field,
                    value: val
                }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message || 'Gagal menyimpan petugas', 'error');
                        return;
                    }
                    if (row) {
                        var rd = row.data();
                        rd[field] = val;
                        rd[field + '_nama'] = text;
                        row.data(rd);
                        $s.closest('td').html(renderPetugasCell(rd, field, type));
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                }).always(function() {
                    $s.css('opacity', '1');
                });
            });

            // ── Ceklis toggle ────────────────────────────────────────────────────────
            $(document).on('change', '.ceklis-toggle', function() {
                var $cb = $(this),
                    isChecked = $cb.is(':checked') ? 1 : 0;
                var id = $cb.data('id'),
                    type = $cb.data('type'),
                    field = $cb.data('field');
                var row = dataTableRow($cb, type);
                $cb.parent().css('opacity', '0.5');
                $.post('/data-' + type + '/toggle-ceklis', {
                    id: id,
                    field: field,
                    state: isChecked
                }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', 'Gagal update status ceklis', 'error');
                        return;
                    }
                    var timeLabelId = '#lbl-' + field.replace('ceklis_', '') + '-' + type + '-' +
                        id;
                    var timestamp = res.timestamp || '';
                    $cb.siblings('span').text(timestamp);
                    if (row) {
                        var rd = row.data();
                        rd[field] = Boolean(isChecked);
                        rd['waktu_' + field] = timestamp;
                        row.data(rd);
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                }).always(function() {
                    $cb.parent().css('opacity', '1');
                });
            });

            // ── Search Rekap Petugas Entry ──
            $(document).on('input', '[id$="-search-rekap"]', function() {
                var prefix = this.id.replace('-search-rekap', '');
                var val = $(this).val().toLowerCase().trim(),
                    visible = 0,
                    total = 0;
                var $tb = $(sel(prefix, 'tbody-rekap'));
                $tb.find('.no-data-row').remove();
                $tb.find('tr.rekap-row').each(function() {
                    total++;
                    var ok = ($(this).data('nama') || '').indexOf(val) > -1 || ($(this).data(
                        'kode') || '').indexOf(val) > -1;
                    $(this).toggle(ok);
                    if (ok) visible++;
                });
                if (!visible && total) $tb.append(
                    '<tr class="no-data-row"><td colspan="7" class="py-8 text-center text-xs text-gray-400">Tidak ada petugas yang cocok</td></tr>'
                );
            });

            $(document).on('change', '[id$="-filter-chart-dsrt"]', function() {
                var prefix = this.id.replace('-filter-chart-dsrt', '');
                if (dashboardData[prefix]) renderDsrtChecklistChart(prefix, dashboardData[prefix]);
            });

            // ── AJAX Submit for Add Petugas Lapangan ──
            $('#form-add-lapangan').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $modal = $('#modal-add-lapangan');
                var $btnSubmit = $form.find('button[type="submit"]');

                // Reset errors
                $form.find('.error-msg').text('').addClass('hidden');
                $form.find('input, select').removeClass('border-red-500');
                $btnSubmit.prop('disabled', true).css('opacity', '0.7');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(res) {
                        $btnSubmit.prop('disabled', false).css('opacity', '1');
                        if (res.success) {
                            $modal.fadeOut(200);
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message ||
                                    'Petugas Lapangan berhasil ditambahkan!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            petugasOptions = null;
                            petugasOptionsRequest = null;
                            reloadTable('lapangan');
                        } else {
                            Swal.fire('Error', res.message || 'Gagal menambahkan petugas',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        $btnSubmit.prop('disabled', false).css('opacity', '1');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                var errorMsg = errors[key][0];
                                var $input = $form.find('[name="' + key + '"]');
                                $input.addClass('border-red-500');
                                var $errSpan = $form.find('#error-' + key);
                                if ($errSpan.length) {
                                    $errSpan.text(errorMsg).removeClass('hidden');
                                }
                            });
                        } else {
                            Swal.fire('Error', xhr.responseJSON.message ||
                                'Terjadi kesalahan sistem', 'error');
                        }
                    }
                });
            });

            // ── AJAX Submit for Add Petugas Entry ──
            $('#form-add-entry').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $modal = $('#modal-add-entry');
                var $btnSubmit = $form.find('button[type="submit"]');

                // Reset errors
                $form.find('.error-msg').text('').addClass('hidden');
                $form.find('input, select').removeClass('border-red-500');
                $btnSubmit.prop('disabled', true).css('opacity', '0.7');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(res) {
                        $btnSubmit.prop('disabled', false).css('opacity', '1');
                        if (res.success) {
                            $modal.fadeOut(200);
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message ||
                                    'Petugas Entry berhasil ditambahkan!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            petugasOptions = null;
                            petugasOptionsRequest = null;
                            reloadTable('entry');
                        } else {
                            Swal.fire('Error', res.message || 'Gagal menambahkan petugas',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        $btnSubmit.prop('disabled', false).css('opacity', '1');
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                var errorMsg = errors[key][0];
                                var $input = $form.find('[name="' + key + '"]');
                                $input.addClass('border-red-500');
                                var $errSpan = $form.find('#error-' + key);
                                if ($errSpan.length) {
                                    $errSpan.text(errorMsg).removeClass('hidden');
                                }
                            });
                        } else {
                            Swal.fire('Error', xhr.responseJSON.message ||
                                'Terjadi kesalahan sistem', 'error');
                        }
                    }
                });
            });
        });
        // ── AJAX Submit for Edit Petugas Entry ──
        $('#form-edit-entry').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btnSubmit = $form.find('button[type="submit"]');
            var kode = $('#edit-entry-kode').val();

            $form.find('.error-msg').text('').addClass('hidden');
            $form.find('input').removeClass('border-red-500');
            $btnSubmit.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: '/petugas-entry/' + encodeURIComponent(kode),
                type: 'PUT',
                data: $form.serialize(),
                dataType: 'json',
                success: function(res) {
                    $btnSubmit.prop('disabled', false).css('opacity', '1');
                    if (res.success) {
                        $('#modal-edit-entry').fadeOut(200);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        petugasOptions = null;
                        petugasOptionsRequest = null;
                        reloadTable('entry');
                    } else {
                        Swal.fire('Error', res.message || 'Gagal menyimpan perubahan', 'error');
                    }
                },
                error: function(xhr) {
                    $btnSubmit.prop('disabled', false).css('opacity', '1');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            $form.find('[name="' + key + '"]').addClass('border-red-500');
                            $form.find('#error-' + key).text(errors[key][0]).removeClass(
                                'hidden');
                        });
                    } else {
                        Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) ||
                            'Terjadi kesalahan sistem', 'error');
                    }
                }
            });
        });
        $(document).on('submit', '#form-edit-lapangan', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btnSubmit = $form.find('button[type="submit"]');
            var kode = $('#edit-lapangan-kode').val();

            $form.find('.error-msg').text('').addClass('hidden');
            $form.find('input, select').removeClass('border-red-500');
            $btnSubmit.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: '/petugas-lapangan/' + encodeURIComponent(kode),
                type: 'PUT',
                data: $form.serialize(),
                dataType: 'json',
                success: function(res) {
                    $btnSubmit.prop('disabled', false).css('opacity', '1');
                    if (res.success) {
                        $('#modal-edit-lapangan').fadeOut(200);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        petugasOptions = null;
                        petugasOptionsRequest = null;
                        reloadTable('lapangan');
                    } else {
                        Swal.fire('Error', res.message || 'Gagal menyimpan perubahan', 'error');
                    }
                },
                error: function(xhr) {
                    $btnSubmit.prop('disabled', false).css('opacity', '1');
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            $form.find('[name="' + key + '"]').addClass('border-red-500');
                            $form.find('#error-' + key).text(errors[key][0]).removeClass(
                                'hidden');
                        });
                    } else {
                        Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) ||
                            'Terjadi kesalahan sistem', 'error');
                    }
                }
            });
        });

        // ── DSRT Export Dropdown ──

        window.exportRekapPetugas = function(prefix) {
            var kec = $(sel(prefix, 'filter-kecamatan')).val() || '';
            var desa = $(sel(prefix, 'filter-desa')).val() || '';
            window.location.href = '{{ route('dashboard.export_rekap') }}?kecamatan=' + encodeURIComponent(kec) +
                '&desa=' + encodeURIComponent(desa);
        };
        $(document).on('click', '.export-dropdown-btn', function(e) {
            e.stopPropagation();
            var $dd = $(this).closest('.export-dropdown');
            var $menu = $dd.find('.export-dropdown-menu');
            var willOpen = $menu.hasClass('hidden');

            $('.export-dropdown-menu').addClass('hidden');
            $('.export-chevron').removeClass('rotate-180');

            if (willOpen) {
                $menu.removeClass('hidden');
                $dd.find('.export-chevron').addClass('rotate-180');
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.export-dropdown').length) {
                $('.export-dropdown-menu').addClass('hidden');
                $('.export-chevron').removeClass('rotate-180');
            }
        });
    </script>
@endpush
