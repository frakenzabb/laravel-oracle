<?php

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FakturPembelianController extends Controller
{
    public function index(): View
    {
        $dbError = null;
        $rekananList = collect();
        $itemList = collect();

        try {
            $connection = DB::connection('oracle');

            $faktur = $connection
                ->table('gd_terima_beli_m')
                ->leftJoin('m_rekanan', 'gd_terima_beli_m.id_rekanan', '=', 'm_rekanan.id')
                ->select([
                    'gd_terima_beli_m.id_transaksi as id',
                    'gd_terima_beli_m.id_transaksi',
                    'gd_terima_beli_m.no_transaksi',
                    'gd_terima_beli_m.no_transaksi as nomor_faktur',
                    'gd_terima_beli_m.tgl_transaksi as tanggal',
                    'gd_terima_beli_m.opr_insert as operator_insert',
                    'gd_terima_beli_m.tgl_insert as tanggal_insert',
                    'gd_terima_beli_m.keterangan',
                    'm_rekanan.nama_rekanan',
                    'm_rekanan.kode_rekanan',
                ])
                ->orderByDesc('gd_terima_beli_m.tgl_transaksi')
                ->orderByDesc('gd_terima_beli_m.id_transaksi')
                ->limit(20)
                ->get();

            try {
                $rekananList = $connection->table('m_rekanan')
                    ->orderBy('nama_rekanan')
                    ->get();
            } catch (\Throwable) {
                $rekananList = collect();
            }

            try {
                $itemList = $connection->table('m_item')
                    ->leftJoin('m_satuan', 'm_item.id_satuan', '=', 'm_satuan.id')
                    ->select([
                        'm_item.id_barang as id',
                        'm_item.kode_barang',
                        'm_item.nama_barang',
                        'm_item.id_satuan',
                        'm_satuan.kode_satuan as satuan',
                    ])
                    ->orderBy('m_item.nama_barang')
                    ->get();
            } catch (\Throwable) {
                $itemList = collect();
            }
        } catch (\Throwable $e) {
            $faktur = collect();
            $dbError = $e->getMessage();
        }

        return view('faktur-pembelian.index', compact('faktur', 'rekananList', 'itemList', 'dbError'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'nama_rekanan' => is_string($request->input('nama_rekanan'))
                ? trim($request->input('nama_rekanan'))
                : $request->input('nama_rekanan'),
            'keterangan' => is_string($request->input('keterangan'))
                ? (trim($request->input('keterangan')) === '' ? null : trim($request->input('keterangan')))
                : $request->input('keterangan'),
        ]);

        $items = $request->input('items');
        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach (['kode_barang', 'nama_barang', 'satuan'] as $field) {
                    if (is_string($item[$field] ?? null)) {
                        $items[$index][$field] = trim($item[$field]);
                    }
                }

                if (($items[$index]['kode_barang'] ?? null) === '') {
                    $items[$index]['kode_barang'] = null;
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'kode_transaksi' => ['required', 'string'],
            'tanggal' => ['required', 'date'],
            'nama_rekanan' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.kode_barang' => ['nullable', 'string', 'max:50'],
            'items.*.nama_barang' => ['required', 'string', 'max:150'],
            'items.*.satuan' => ['required', 'string', 'max:30'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);

        $connection = DB::connection('oracle');
        $transaksiId = $connection->transaction(function () use ($connection, $validated): int {
            $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
            $id = (int) $connection->selectOne(
                'SELECT "SEQ_GD_TERIMA_M".NEXTVAL AS "id" FROM DUAL'
            )->id;

            $counter = str_pad((string) $id, 5, '0', STR_PAD_LEFT);
            $kodeTransaksi = 'TB';
            $noTransaksi = $kodeTransaksi.Carbon::parse($tanggal)->format('ym').$counter;
            $tanggalInsert = now()->format('Y-m-d H:i:s');

            $idRekanan = $this->resolveRekananId($connection, $validated['nama_rekanan']);

            $connection->table('gd_terima_beli_m')->insert([
                'id_transaksi' => $id,
                'no_transaksi' => $noTransaksi,
                'no_transaksi_kode' => $kodeTransaksi,
                'no_transaksi_counter' => $counter,
                'tgl_transaksi' => $tanggal.' 00:00:00',
                'opr_insert' => auth()->user()?->name ?? 'admin',
                'tgl_insert' => $tanggalInsert,
                'keterangan' => isset($validated['keterangan'])
                    ? trim($validated['keterangan'])
                    : null,
                'jns_transaksi' => '03',
                'no_sj' => '-',
                'tgl_sj' => $tanggal.' 00:00:00',
                'id_rekanan' => $idRekanan,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $detailId = (int) $connection->selectOne(
                    'SELECT "SEQ_GD_TERIMA_D".NEXTVAL AS "id" FROM DUAL'
                )->id;
                $jumlah = (int) $item['jumlah'];
                $namaBarang = trim($item['nama_barang']);
                $kodeBarang = isset($item['kode_barang']) ? trim($item['kode_barang']) : null;
                $satuan = trim($item['satuan']);

                $itemInfo = $this->resolveBarangItem($connection, $kodeBarang, $namaBarang, $satuan);
                $idBarang = $itemInfo['id_barang'];
                $idSatuan = $itemInfo['id_satuan'];

                $connection->table('gd_terima_beli_d')->insert([
                    'id_detail' => $detailId,
                    'id_transaksi' => $id,
                    'no_urut' => $index + 1,
                    'id_barang' => $idBarang,
                    'id_satuan' => $idSatuan,
                    'jumlah' => $jumlah,
                    'is_stok' => 'Y',
                    'is_project' => 'N',
                ]);

                $movementId = (int) $connection->selectOne(
                    'SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
                )->id;

                $connection->table('t_data_barang')->insert([
                    'id' => $movementId,
                    'nama_barang' => $namaBarang,
                    'jenis_transaksi' => 'masuk',
                    'jumlah' => $jumlah,
                    'tanggal' => $tanggal.' 00:00:00',
                    'keterangan' => "Faktur {$noTransaksi} - ".trim($validated['nama_rekanan']),
                ]);
            }

            return $id;
        });

        return redirect()->route('faktur-pembelian.show', $transaksiId)
            ->with('success', 'Faktur pembelian berhasil disimpan dan stok telah diperbarui.');
    }

    public function show(int $id): View
    {
        $connection = DB::connection('oracle');
        $faktur = $connection->table('gd_terima_beli_m')
            ->leftJoin('m_rekanan', 'gd_terima_beli_m.id_rekanan', '=', 'm_rekanan.id')
            ->select([
                'gd_terima_beli_m.id_transaksi as id',
                'gd_terima_beli_m.id_transaksi',
                'gd_terima_beli_m.no_transaksi',
                'gd_terima_beli_m.no_transaksi as nomor_faktur',
                'gd_terima_beli_m.no_transaksi_kode as kode_transaksi',
                'gd_terima_beli_m.tgl_transaksi as tanggal',
                'gd_terima_beli_m.opr_insert as operator_insert',
                'gd_terima_beli_m.tgl_insert as tanggal_insert',
                'gd_terima_beli_m.keterangan',
                'm_rekanan.nama_rekanan',
                'm_rekanan.kode_rekanan',
                'm_rekanan.alamat as alamat_rekanan',
                'm_rekanan.no_telp as no_telp_rekanan',
            ])
            ->where('gd_terima_beli_m.id_transaksi', $id)
            ->first();

        abort_if($faktur === null, 404);

        $items = $connection->table('gd_terima_beli_d')
            ->leftJoin('m_item', 'gd_terima_beli_d.id_barang', '=', 'm_item.id_barang')
            ->leftJoin('m_satuan', 'gd_terima_beli_d.id_satuan', '=', 'm_satuan.id')
            ->select([
                'gd_terima_beli_d.id_detail as id',
                'gd_terima_beli_d.id_detail',
                'gd_terima_beli_d.id_transaksi as faktur_id',
                'gd_terima_beli_d.id_transaksi',
                'gd_terima_beli_d.no_urut',
                'gd_terima_beli_d.jumlah',
                'm_item.kode_barang',
                'm_item.nama_barang',
                'm_satuan.kode_satuan as satuan',
            ])
            ->where('gd_terima_beli_d.id_transaksi', $id)
            ->orderBy('gd_terima_beli_d.no_urut')
            ->orderBy('gd_terima_beli_d.id_detail')
            ->get();

        return view('faktur-pembelian.show', compact('faktur', 'items'));
    }

    protected function resolveRekananId(ConnectionInterface $connection, string $namaRekanan): int
    {
        $cleanName = trim($namaRekanan);
        $cleanNoDot = str_replace('.', '', mb_strtolower($cleanName));

        $rekanan = $connection->table('m_rekanan')
            ->whereRaw("LOWER(TRIM(REPLACE(nama_rekanan, '.', ''))) = ?", [$cleanNoDot])
            ->orWhereRaw('LOWER(TRIM(kode_rekanan)) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($rekanan !== null) {
            return (int) $rekanan->id;
        }

        $id = (int) $connection->selectOne(
            'SELECT "M_REKANAN_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
        )->id;

        $connection->table('m_rekanan')->insert([
            'id' => $id,
            'kode_rekanan' => 'SPL-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
            'nama_rekanan' => $cleanName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    protected function resolveSatuanId(ConnectionInterface $connection, string $satuan): int
    {
        $cleanSatuan = trim($satuan);
        $satuanRow = $connection->table('m_satuan')
            ->whereRaw('LOWER(TRIM(kode_satuan)) = ?', [mb_strtolower($cleanSatuan)])
            ->first();

        if ($satuanRow !== null) {
            return (int) $satuanRow->id;
        }

        $dasar = $connection->table('m_satuan_dasar')
            ->whereRaw('LOWER(TRIM(kode_satuan)) = ?', [mb_strtolower($cleanSatuan)])
            ->first();

        if ($dasar !== null) {
            $idDasar = (int) $dasar->id;
        } else {
            $idDasar = (int) $connection->selectOne(
                'SELECT "M_SATUAN_DASAR_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
            )->id;

            $connection->table('m_satuan_dasar')->insert([
                'id' => $idDasar,
                'kode_satuan' => strtoupper(substr($cleanSatuan, 0, 20)),
                'nama_satuan' => $cleanSatuan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $idSatuan = (int) $connection->selectOne(
            'SELECT "M_SATUAN_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
        )->id;

        $connection->table('m_satuan')->insert([
            'id' => $idSatuan,
            'id_satuan_dasar' => $idDasar,
            'kode_satuan' => $cleanSatuan,
            'nama_satuan' => $cleanSatuan,
            'konversi' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $idSatuan;
    }

    protected function resolveBarangItem(
        ConnectionInterface $connection,
        ?string $kodeBarang,
        string $namaBarang,
        string $satuan
    ): array {
        $cleanKode = ! empty($kodeBarang) ? trim($kodeBarang) : null;
        $cleanNama = trim($namaBarang);

        $itemQuery = $connection->table('m_item');
        if (! empty($cleanKode)) {
            $itemQuery->whereRaw('LOWER(TRIM(kode_barang)) = ?', [mb_strtolower($cleanKode)]);
        } else {
            $itemQuery->whereRaw('LOWER(TRIM(nama_barang)) = ?', [mb_strtolower($cleanNama)]);
        }

        $itemRow = $itemQuery->first();
        if ($itemRow !== null) {
            $idSatuan = ! empty($itemRow->id_satuan)
                ? (int) $itemRow->id_satuan
                : $this->resolveSatuanId($connection, $satuan);

            return [
                'id_barang' => (int) $itemRow->id_barang,
                'id_satuan' => $idSatuan,
            ];
        }

        $idSatuan = $this->resolveSatuanId($connection, $satuan);

        $id = (int) $connection->selectOne(
            'SELECT "M_ITEM_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
        )->id;

        $connection->table('m_item')->insert([
            'id_barang' => $id,
            'kode_barang' => ! empty($cleanKode) ? $cleanKode : 'BRG'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
            'nama_barang' => $cleanNama,
            'id_satuan' => $idSatuan,
            'harga_satuan' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id_barang' => $id,
            'id_satuan' => $idSatuan,
        ];
    }
}
