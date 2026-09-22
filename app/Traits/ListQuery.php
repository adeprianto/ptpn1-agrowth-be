<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
 * Pembantu untuk endpoint daftar (index): pencarian per kolom, filter
 * banyak-nilai, dan pengurutan yang dibatasi kolom yang diizinkan.
 *
 * Dipakai bersama tabel di frontend:
 * - kotak cari di bawah judul kolom  -> applyLike()
 * - daftar centang di modal filter   -> applyInFilter()
 * - klik judul kolom untuk mengurutkan -> applySort()
 */
trait ListQuery
{
    /**
     * Baca satu parameter query sebagai daftar nilai.
     * Menerima `key[]=a&key[]=b` maupun `key=a`, dan membuang nilai kosong.
     *
     * @return array<int, string>
     */
    protected function queryList(Request $request, string $key): array
    {
        $value = $request->query($key);

        if ($value === null) {
            return [];
        }

        $values = array_map(
            static fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            is_array($value) ? $value : [$value],
        );

        return array_values(array_filter($values, static fn ($item) => $item !== ''));
    }

    /**
     * Filter "salah satu dari" — hanya dipasang kalau parameternya diisi.
     * Inilah yang membuat daftar centang boleh memilih lebih dari satu nilai.
     */
    protected function applyInFilter($query, Request $request, string $param, string $column): void
    {
        $values = $this->queryList($request, $param);

        if ($values !== []) {
            $query->whereIn($column, $values);
        }
    }

    /** Pencarian "mengandung" untuk satu kolom. */
    protected function applyLike($query, Request $request, string $param, string $column): void
    {
        $keyword = trim((string) $request->query($param, ''));

        if ($keyword !== '') {
            $query->where($column, 'like', "%{$keyword}%");
        }
    }

    /**
     * Urutkan berdasarkan parameter `sort` dan `direction`.
     *
     * `$allowed` memetakan nama kolom yang dikirim frontend ke kolom database,
     * sekaligus jadi daftar putih — nama di luar itu diabaikan dan memakai
     * `$default`, jadi parameter dari luar tidak bisa menyentuh kolom lain.
     *
     * Nilainya boleh berupa nama kolom, atau `DB::raw()` untuk kolom yang
     * sumbernya bergantung pada isi baris (mis. regional seorang pegawai).
     *
     * @param  array<string, string|\Illuminate\Contracts\Database\Query\Expression>  $allowed
     */
    protected function applySort($query, Request $request, array $allowed, string $default): void
    {
        $sort = (string) $request->query('sort', '');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($allowed[$sort] ?? $default, $direction);
    }
}
