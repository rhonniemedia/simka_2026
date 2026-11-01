<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Sortir tabel yang dipakai bersama oleh semua menu.
 *
 * Definisi kolom ($columns), key = nilai ?sort= dari header:
 *   'effective_date' => 'effective_date'                       kolom di tabel utama
 *   'name'  => ['relation' => 'staff', 'column' => 'name']     kolom milik relasi BelongsTo (subquery, tanpa join)
 *   'x'     => fn (Builder $q, string $dir) => $q->...         bebas
 *   'tmt'   => null                                            untuk sortArray() / memakai key sebagai nama kolom
 */
final class TableSort
{
    private function __construct(
        private readonly ?string $key,
        private readonly string $direction,
        private readonly array $columns,
    ) {}

    /**
     * @param array<string, string|array|Closure|null> $columns
     * @param ?string $default  key bawaan; null = tanpa sortir sampai header diklik
     */
    public static function make(
        Request $request,
        array $columns,
        ?string $default = null,
        string $defaultDirection = 'desc'
    ): self {
        $requested = $request->input('sort');
        $key = is_string($requested) && array_key_exists($requested, $columns) ? $requested : $default;

        if ($key !== null && ! array_key_exists($key, $columns)) {
            $key = null;
        }

        $fallback = strtolower($defaultDirection) === 'asc' ? 'asc' : 'desc';
        $requestedDir = $request->input('direction');
        $direction = is_string($requestedDir) && in_array(strtolower($requestedDir), ['asc', 'desc'], true)
            ? strtolower($requestedDir)
            : $fallback;

        return new self($key, $direction, $columns);
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    /** Variabel untuk view: view(..., compact('rows') + $sorter->viewData()) */
    public function viewData(): array
    {
        return ['sort' => $this->key, 'direction' => $this->direction];
    }

    /**
     * Terapkan ke query Eloquent. Tie-breaker ($then) lalu primary key
     * ditambahkan otomatis agar pagination stabil.
     *
     * @param array<int, string|array{0:string,1?:string}> $then  'kolom' (asc) atau ['kolom', 'desc']
     */
    public function apply(Builder $query, array $then = []): Builder
    {
        if ($this->key === null) {
            return $query;
        }

        $model = $query->getModel();
        $definition = $this->columns[$this->key];

        if ($definition instanceof Closure) {
            $definition($query, $this->direction);
        } elseif (is_array($definition)) {
            self::orderByRelation($query, $definition['relation'], $definition['column'], $this->direction);
        } else {
            $query->orderBy($model->qualifyColumn($definition ?? $this->key), $this->direction);
        }

        foreach ($then as $item) {
            [$column, $dir] = is_array($item) ? [$item[0], $item[1] ?? 'asc'] : [$item, 'asc'];
            $query->orderBy($model->qualifyColumn($column), $dir);
        }

        return $query->orderBy($model->getQualifiedKeyName(), 'desc');
    }

    /** Urutkan berdasarkan kolom relasi BelongsTo lewat subquery (tanpa join). */
    public static function orderByRelation(Builder $query, string $relation, string $column, string $direction = 'asc'): Builder
    {
        $rel = $query->getModel()->{$relation}();

        if (! $rel instanceof BelongsTo) {
            throw new InvalidArgumentException("Relasi [{$relation}] harus BelongsTo untuk dipakai sortir.");
        }

        return $query->orderBy(
            $rel->getRelated()->newQuery()
                ->select($rel->getRelated()->qualifyColumn($column))
                ->whereColumn($rel->getQualifiedOwnerKeyName(), $rel->getQualifiedForeignKeyName())
                ->limit(1),
            $direction
        );
    }

    /**
     * Sortir array di PHP, untuk data yang dihitung/dienkripsi sehingga tidak
     * bisa di-orderBy di database. Nilai null selalu di akhir.
     *
     * @param array<string, Closure> $accessors key => fn($row): string|int|float|null
     * @param array<int, string|array{0:string,1?:string}> $then key accessor untuk tie-breaker
     */
    public function sortArray(array $rows, array $accessors, array $then = []): array
    {
        if ($this->key === null || ! isset($accessors[$this->key])) {
            return $rows;
        }

        $levels = [[$this->key, $this->direction === 'desc' ? -1 : 1]];
        foreach ($then as $item) {
            [$key, $dir] = is_array($item) ? [$item[0], $item[1] ?? 'asc'] : [$item, 'asc'];
            $levels[] = [$key, strtolower($dir) === 'desc' ? -1 : 1];
        }

        $value = static function (Closure $accessor, $row) {
            $v = $accessor($row);

            return is_string($v) ? mb_strtolower($v) : $v;
        };

        // usort di PHP 8 stabil: baris yang setara tetap pada urutan semula
        usort($rows, function ($rowA, $rowB) use ($levels, $accessors, $value) {
            foreach ($levels as [$key, $dir]) {
                if (! isset($accessors[$key])) {
                    continue;
                }

                $a = $value($accessors[$key], $rowA);
                $b = $value($accessors[$key], $rowB);

                if ($a === $b) {
                    continue;
                }
                if ($a === null) {
                    return 1;
                }
                if ($b === null) {
                    return -1;
                }

                $result = (is_string($a) && is_string($b) ? strcmp($a, $b) : $a <=> $b) * $dir;

                if ($result !== 0) {
                    return $result;
                }
            }

            return 0;
        });

        return $rows;
    }
}
