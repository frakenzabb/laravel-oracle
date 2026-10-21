<?php

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BarangController extends Controller
{
    private const TABLE = 't_data_barang';

    public function index(): View
    {
        $data = DB::connection('oracle')
            ->table(self::TABLE)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $stok = $data
            ->groupBy(fn (object $row): string => mb_strtolower(trim($row->nama_barang)))
            ->map(function (Collection $mutasi): array {
                $masuk = (int) $mutasi
                    ->where('jenis_transaksi', 'masuk')
                    ->sum('jumlah');
                $keluar = (int) $mutasi
                    ->where('jenis_transaksi', 'keluar')
                    ->sum('jumlah');

                return [
                    'nama_barang' => $mutasi->first()->nama_barang,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'stok' => $masuk - $keluar,
                ];
            })
            ->sortBy('nama_barang', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ringkasan = [
            'jenis_barang' => $stok->count(),
            'total_stok' => $stok->sum('stok'),
            'total_masuk' => $data->where('jenis_transaksi', 'masuk')->sum('jumlah'),
            'total_keluar' => $data->where('jenis_transaksi', 'keluar')->sum('jumlah'),
        ];

        return view('welcome', compact('data', 'stok', 'ringkasan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMovement($request);
        $connection = DB::connection('oracle');

        $connection->transaction(function () use ($connection, $validated): void {
            if ($validated['jenis_transaksi'] === 'keluar') {
                $mutasi = $this->lockMovements($connection, $validated['nama_barang']);
                $tersedia = $this->calculateStock($mutasi);

                if ($validated['jumlah'] > $tersedia) {
                    throw ValidationException::withMessages([
                        'jumlah' => "Stok {$validated['nama_barang']} hanya {$tersedia}.",
                    ]);
                }
            }

            $connection->table(self::TABLE)->insert([
                'id' => $connection->selectOne(
                    'SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
                )->id,
                'nama_barang' => $validated['nama_barang'],
                'jenis_transaksi' => $validated['jenis_transaksi'],
                'jumlah' => $validated['jumlah'],
                'tanggal' => $validated['tanggal'].' 00:00:00',
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        });

        return redirect()->route('barang.index')->with(
            'success',
            'Transaksi barang berhasil dicatat.'
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $connection = DB::connection('oracle');

        $connection->transaction(function () use ($connection, $id): void {
            $movement = $connection->table(self::TABLE)->where('id', $id)->first();

            if ($movement === null) {
                abort(404);
            }

            $mutasi = $this->lockMovements($connection, $movement->nama_barang);
            $movement = $mutasi->first(
                fn (object $row): bool => (int) $row->id === $id
            );

            if ($movement === null) {
                abort(404);
            }

            $sisaStok = $this->calculateStock($mutasi)
                - ($movement->jenis_transaksi === 'masuk'
                    ? (int) $movement->jumlah
                    : -(int) $movement->jumlah);

            if ($sisaStok < 0) {
                throw ValidationException::withMessages([
                    'transaksi' => 'Transaksi masuk ini tidak dapat dihapus karena stok akan menjadi negatif.',
                ]);
            }

            $connection->table(self::TABLE)->where('id', $id)->delete();
        });

        return redirect()->route('barang.index')->with(
            'success',
            'Transaksi barang berhasil dihapus.'
        );
    }

    /**
     * @return array{nama_barang: string, jenis_transaksi: string, jumlah: int, tanggal: string, keterangan?: string|null}
     */
    private function validateMovement(Request $request): array
    {
        $request->merge([
            'nama_barang' => \is_string($request->input('nama_barang'))
                ? trim($request->input('nama_barang'))
                : $request->input('nama_barang'),
        ]);

        $validated = $request->validate([
            'nama_barang' => ['required', 'string', 'max:150'],
            'jenis_transaksi' => ['required', 'in:masuk,keluar'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['jumlah'] = (int) $validated['jumlah'];

        return $validated;
    }

    private function lockMovements(ConnectionInterface $connection, string $namaBarang): Collection
    {
        return $connection->table(self::TABLE)
            ->whereRaw('LOWER(nama_barang) = ?', [mb_strtolower(trim($namaBarang))])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function calculateStock(Collection $mutasi): int
    {
        return (int) $mutasi
            ->sum(fn (object $row): int => $row->jenis_transaksi === 'masuk'
                ? (int) $row->jumlah
                : -(int) $row->jumlah);
    }
}
