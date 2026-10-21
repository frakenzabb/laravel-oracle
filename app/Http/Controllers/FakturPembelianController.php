<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FakturPembelianController extends Controller
{
    public function index(): View
    {
        $faktur = DB::connection('oracle')
            ->table('t_faktur_pembelian')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('faktur-pembelian.index', compact('faktur'));
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
            'kode_transaksi' => ['required', 'in:FAKTUR'],
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
        $fakturId = $connection->transaction(function () use ($connection, $validated): int {
            $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
            $id = (int) $connection->selectOne(
                'SELECT "T_FAKTUR_PEMBELIAN_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
            )->id;
            $nomorFaktur = 'FP-'.str_replace('-', '', $tanggal).'-'.str_pad(
                (string) $id,
                3,
                '0',
                STR_PAD_LEFT
            );
            $tanggalInsert = now()->format('Y-m-d H:i:s');

            $connection->table('t_faktur_pembelian')->insert([
                'id' => $id,
                'nomor_faktur' => $nomorFaktur,
                'kode_transaksi' => $validated['kode_transaksi'],
                'tanggal' => $tanggal,
                'operator_insert' => auth()->user()?->name ?? 'admin',
                'tanggal_insert' => $tanggalInsert,
                'nama_rekanan' => trim($validated['nama_rekanan']),
                'keterangan' => isset($validated['keterangan'])
                    ? trim($validated['keterangan'])
                    : null,
            ]);

            foreach ($validated['items'] as $item) {
                $detailId = (int) $connection->selectOne(
                    'SELECT "T_DETAIL_FP_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
                )->id;
                $jumlah = (int) $item['jumlah'];
                $namaBarang = trim($item['nama_barang']);

                $connection->table('t_detail_faktur_pembelian')->insert([
                    'id' => $detailId,
                    'faktur_id' => $id,
                    'kode_barang' => isset($item['kode_barang'])
                        ? trim($item['kode_barang'])
                        : null,
                    'nama_barang' => $namaBarang,
                    'satuan' => trim($item['satuan']),
                    'jumlah' => $jumlah,
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
                    'keterangan' => "Faktur {$nomorFaktur} - ".trim($validated['nama_rekanan']),
                ]);
            }

            return $id;
        });

        return redirect()->route('faktur-pembelian.show', $fakturId)
            ->with('success', 'Faktur pembelian berhasil disimpan dan stok telah diperbarui.');
    }

    public function show(int $id): View
    {
        $connection = DB::connection('oracle');
        $faktur = $connection->table('t_faktur_pembelian')->where('id', $id)->first();

        abort_if($faktur === null, 404);

        $items = $connection->table('t_detail_faktur_pembelian')
            ->where('faktur_id', $id)
            ->orderBy('id')
            ->get();

        return view('faktur-pembelian.show', compact('faktur', 'items'));
    }
}
