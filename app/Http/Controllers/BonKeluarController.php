<?php

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BonKeluarController extends Controller
{
    public function index(): View
    {
        $connection = DB::connection('oracle');
        $items = $connection->table('m_item as item')
            ->leftJoin('m_satuan as unit', 'item.id_satuan', '=', 'unit.id')
            ->select([
                'item.id_barang as id',
                'item.kode_barang as code',
                'item.nama_barang as name',
                'item.id_satuan as unit_id',
            ])
            ->selectRaw("NVL(unit.kode_satuan, NVL(unit.nama_satuan, '')) as unit")
            ->orderBy('item.nama_barang')
            ->get();

        $stocks = $this->getStocks($connection, $items->pluck('name')->all());
        $items->each(function (object $item) use ($stocks): void {
            $item->stock = $stocks->get(mb_strtolower(trim($item->name)), 0);
        });

        $outgoingItems = $connection->table('gd_bon_keluar_m as header')
            ->join('gd_bon_keluar_d as detail', 'header.id_transaksi', '=', 'detail.id_transaksi')
            ->leftJoin('m_item as item', 'detail.id_barang', '=', 'item.id_barang')
            ->leftJoin('m_satuan as unit', 'detail.id_satuan', '=', 'unit.id')
            ->select([
                'header.id_transaksi',
                'header.no_transaksi',
                'header.tgl_transaksi',
                'header.keterangan as transaction_description',
                'detail.no_urut',
                'detail.id_barang',
                'detail.jumlah',
                'item.kode_barang',
                'item.nama_barang',
            ])
            ->selectRaw("NVL(unit.kode_satuan, NVL(unit.nama_satuan, '')) as unit")
            ->orderByDesc('header.tgl_transaksi')
            ->orderByDesc('header.id_transaksi')
            ->orderBy('detail.no_urut')
            ->limit(100)
            ->get();

        return view('bon-keluar', compact('items', 'outgoingItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'divisi' => is_string($request->input('divisi'))
                ? trim($request->input('divisi'))
                : $request->input('divisi'),
            'keperluan' => is_string($request->input('keperluan'))
                ? trim($request->input('keperluan'))
                : $request->input('keperluan'),
        ]);

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'divisi' => ['required', 'string', 'max:150'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_barang' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);

        $itemIds = array_values(array_unique(array_map(
            fn (array $item): string => (string) $item['id_barang'],
            $validated['items']
        )));

        $connection = DB::connection('oracle');
        $connection->transaction(function () use ($connection, $validated, $itemIds): void {
            $items = $connection->table('m_item')
                ->whereIn('id_barang', $itemIds)
                ->orderBy('id_barang')
                ->lockForUpdate()
                ->get(['id_barang', 'nama_barang', 'id_satuan'])
                ->keyBy(fn (object $item): string => (string) $item->id_barang);

            if ($items->count() !== count($itemIds)) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu barang yang dipilih tidak lagi tersedia.',
                ]);
            }
            if ($items->contains(fn (object $item): bool => empty($item->id_satuan))) {
                throw ValidationException::withMessages([
                    'items' => 'Data satuan pada salah satu barang belum lengkap.',
                ]);
            }

            $itemNames = $items->map(fn (object $item): string => $item->nama_barang)->all();
            $movements = $this->lockMovements($connection, $itemNames);
            $stocks = $this->calculateStocks($movements);
            $selectedItems = [];

            foreach ($validated['items'] as $input) {
                $item = $items->get((string) $input['id_barang']);
                $key = mb_strtolower(trim($item->nama_barang));
                $qty = (int) $input['qty'];
                $available = max(0, $stocks->get($key, 0));

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$item->nama_barang} hanya {$available}.",
                    ]);
                }

                $selectedItems[] = [
                    'item' => $item,
                    'qty' => $qty,
                ];
                $stocks->put($key, $available - $qty);
            }

            $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
            $transactionId = (int) $connection->selectOne(
                'SELECT "SEQ_GD_BON_KELUAR_M".NEXTVAL AS "id" FROM DUAL'
            )->id;
            $counter = str_pad((string) $transactionId, 5, '0', STR_PAD_LEFT);
            $transactionNumber = 'BK'.Carbon::parse($tanggal)->format('ym').$counter;
            $description = "Divisi: {$validated['divisi']}\nKeperluan: {$validated['keperluan']}";

            $connection->table('gd_bon_keluar_m')->insert([
                'id_transaksi' => $transactionId,
                'opr_insert' => auth()->user()?->name ?? 'admin',
                'tgl_insert' => now()->format('Y-m-d H:i:s'),
                'keterangan' => $description,
                'status' => '1',
                'tgl_transaksi' => $tanggal.' 00:00:00',
                'no_transaksi' => $transactionNumber,
                'no_transaksi_kode' => null,
                'no_transaksi_counter' => $counter,
                'jns_transaksi' => null,
                'kode_transaksi' => null,
                'id_divisi_group' => null,
                'id_project_pakai' => null,
                'subkon_pakai' => null,
                'id_gudang' => null,
                'no_cutting' => null,
            ]);

            foreach ($selectedItems as $index => $selected) {
                $item = $selected['item'];
                $qty = $selected['qty'];
                $detailId = (int) $connection->selectOne(
                    'SELECT "SEQ_GD_BON_KELUAR_D".NEXTVAL AS "id" FROM DUAL'
                )->id;

                $connection->table('gd_bon_keluar_d')->insert([
                    'id_detail' => $detailId,
                    'id_transaksi' => $transactionId,
                    'no_urut' => $index + 1,
                    'id_barang' => (int) $item->id_barang,
                    'id_satuan' => (int) $item->id_satuan,
                    'jumlah' => $qty,
                ]);

                $movementId = (int) $connection->selectOne(
                    'SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
                )->id;
                $connection->table('t_data_barang')->insert([
                    'id' => $movementId,
                    'nama_barang' => $item->nama_barang,
                    'jenis_transaksi' => 'keluar',
                    'jumlah' => $qty,
                    'tanggal' => $tanggal.' 00:00:00',
                    'keterangan' => "Bon Keluar {$transactionNumber}",
                ]);
            }
        });

        return redirect()->route('bon-keluar.index')
            ->with('success', 'Bon Keluar berhasil disimpan dan stok gudang diperbarui.');
    }

    /**
     * @param  array<int, string>  $itemNames
     * @return Collection<int, object>
     */
    private function lockMovements(ConnectionInterface $connection, array $itemNames): Collection
    {
        if ($itemNames === []) {
            return collect();
        }

        $normalizedNames = array_values(array_map(
            fn (string $name): string => mb_strtolower(trim($name)),
            $itemNames
        ));
        $placeholders = implode(', ', array_fill(0, count($normalizedNames), '?'));

        return $connection->table('t_data_barang')
            ->whereRaw("LOWER(nama_barang) IN ({$placeholders})", $normalizedNames)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  array<int, string>  $itemNames
     * @return Collection<string, int>
     */
    private function getStocks(ConnectionInterface $connection, array $itemNames): Collection
    {
        if ($itemNames === []) {
            return collect();
        }

        $normalizedNames = array_values(array_map(
            fn (string $name): string => mb_strtolower(trim($name)),
            $itemNames
        ));
        $placeholders = implode(', ', array_fill(0, count($normalizedNames), '?'));
        $rows = $connection->table('t_data_barang')
            ->selectRaw('LOWER(nama_barang) as nama_key, SUM(CASE WHEN jenis_transaksi = ? THEN jumlah ELSE -jumlah END) as stock', ['masuk'])
            ->whereRaw("LOWER(nama_barang) IN ({$placeholders})", $normalizedNames)
            ->groupByRaw('LOWER(nama_barang)')
            ->get();

        return $rows->mapWithKeys(fn (object $row): array => [
            $row->nama_key => max(0, (int) $row->stock),
        ]);
    }

    /**
     * @return Collection<string, int>
     */
    private function calculateStocks(Collection $movements): Collection
    {
        return $movements->groupBy(
            fn (object $movement): string => mb_strtolower(trim($movement->nama_barang))
        )->map(fn (Collection $rows): int => (int) $rows->sum(
            fn (object $movement): int => $movement->jenis_transaksi === 'masuk'
                ? (int) $movement->jumlah
                : -(int) $movement->jumlah
        ));
    }
}
