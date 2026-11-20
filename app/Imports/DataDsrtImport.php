<?php

namespace App\Imports;

use App\Models\DataDsrt;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DataDsrtImport implements
    ToModel,
    WithHeadingRow,
    WithValidation
{
    protected array $occurrences = [];
    protected string $program;

    public function __construct(string $program = 'susenas')
    {
        $this->program = in_array($program, ['susenas', 'seruti'], true) ? $program : 'susenas';
    }


    public function model(array $row)
    {
        $row = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $row);

        // Hanya import baris DSRT yang dsrt_ssn-nya bernilai 1.
        // Baris dengan dsrt_ssn = 0 (atau nilai selain 1) dilewati.
        // Susenas: hanya dsrt_ssn = 1. Seruti: hanya F_SERUTI = 1.
        $dsrt    = (string) $this->value($row, 'dsrt_ssn', null, 0);
        $seruti  = (string) $this->value($row, 'f_seruti', 'F_SERUTI', 0);

        // Susenas: dsrt_ssn = 1.
        // Seruti : F_SERUTI = 1 DAN dsrt_ssn = 1.
        $accepted = $this->program === 'seruti'
            ? ($seruti === '1' && $dsrt === '1')
            : ($dsrt === '1');

        if (!$accepted) {
            return null;
        }

        // Simpan sebagai baris milik satu program saja, supaya Susenas dan Seruti berdiri sendiri.
        $row['dsrt_ssn'] = $this->program === 'seruti' ? 0 : 1;
        $row['f_seruti'] = $this->program === 'seruti' ? 1 : 0;

        $key = $this->logicalKey($row);
        $occurrence = $this->occurrences[$key] ?? 0;
        $this->occurrences[$key] = $occurrence + 1;

        $query = DataDsrt::query()
            ->where('kec', $this->value($row, 'kec'))
            ->where('desa', $this->value($row, 'desa'))
            ->where('kdbs', $this->value($row, 'kdbs'))
            ->where('klas', $this->value($row, 'klas'))
            ->where('idbs', $this->value($row, 'idbs'))
            ->where('nks_sak22', $this->codeValue($row, 'nks_sak22'))
            ->where('F_SERUTI', $this->value($row, 'f_seruti', 'F_SERUTI'))
            ->where('nmslsm', $this->value($row, 'nmslsm'))
            ->where('r503', $this->value($row, 'r503'))
            ->where('r503b', $this->value($row, 'r503b'))
            ->where('dsrt_ssn', $this->value($row, 'dsrt_ssn', null, 0))
            ->where('nus_ssn', $this->value($row, 'nus_ssn', null, 0))
            ->orderBy('id');

        $model = $query->get()->get($occurrence) ?? new DataDsrt();

        // Hanya field sumber/import yang di-update.
        // Progress ceklis + timestamp SENGAJA tidak disentuh agar import ulang
        // tidak menghapus progress yang sudah dikerjakan petugas.
        $model->fill([
            'kec' => $this->value($row, 'kec'),
            'desa' => $this->value($row, 'desa'),
            'kdbs' => $this->codeValue($row, 'kdbs'),
            'klas' => $this->value($row, 'klas'),
            'idbs' => $this->value($row, 'idbs'),
            'nmkec' => $this->value($row, 'nmkec'),
            'nmdesa' => $this->value($row, 'nmdesa'),
            'nks_sak22' => $this->codeValue($row, 'nks_sak22'),
            'F_SERUTI' => $this->value($row, 'f_seruti', 'F_SERUTI'),
            'nmslsm' => $this->value($row, 'nmslsm'),
            'r503' => $this->value($row, 'r503'),
            'r503b' => $this->value($row, 'r503b'),
            'dsrt_ssn' => $this->value($row, 'dsrt_ssn', null, 0),
            'nus_ssn' => $this->value($row, 'nus_ssn', null, 0),
        ]);

        return $model;
    }

    protected function logicalKey(array $row): string
    {
        return sha1(json_encode([
            $this->value($row, 'kec'),
            $this->value($row, 'desa'),
            $this->value($row, 'kdbs'),
            $this->value($row, 'klas'),
            $this->value($row, 'idbs'),
            $this->codeValue($row, 'nks_sak22'),
            $this->value($row, 'f_seruti', 'F_SERUTI'),
            $this->value($row, 'nmslsm'),
            $this->value($row, 'r503'),
            $this->value($row, 'r503b'),
            $this->value($row, 'dsrt_ssn', null, 0),
            $this->value($row, 'nus_ssn', null, 0),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Kode NBS/NKS disimpan PERSIS seperti yang dibaca dari Excel.
     * Tidak melakukan padding, trimming angka, atau perubahan format.
     * Jika Excel berisi 00223 sebagai teks, database menerima 00223.
     */
    protected function codeValue(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row) || $row[$key] === null) {
            return null;
        }

        // Hanya ubah tipe menjadi string; jangan mengubah isi kodenya.
        return (string) $row[$key];
    }

    protected function value(array $row, string $key, ?string $fallback = null, $default = null)
    {
        if (array_key_exists($key, $row) && $row[$key] !== null) {
            return $row[$key];
        }

        if ($fallback !== null && array_key_exists($fallback, $row) && $row[$fallback] !== null) {
            return $row[$fallback];
        }

        return $default;
    }

    protected function checkbox(array $row, string $key): bool
    {
        return in_array(
            strtolower(trim((string) ($this->value($row, $key) ?? ''))),
            ['v', '1', 'ya', 'yes', 'true', 'x'],
            true
        );
    }

    public function rules(): array
    {
        return [];
    }
}
