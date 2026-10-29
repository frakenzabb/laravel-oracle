<?php

namespace App\Http\Controllers;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FakturPembelianController extends Controller
{
    public function index(Request $request): View
    {
        $dbError = null;
        $purchaseOrders = collect();
        $suppliers = collect();
        $ringkasan = [
            'purchase_orders' => 0,
            'items' => 0,
            'qty' => 0,
            'transactions' => 0,
            'received_items' => 0,
            'received_qty' => 0,
        ];
        $tanggalAwal = $request->query('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
        $tanggalAkhir = $request->query('tanggal_akhir', now()->format('Y-m-d'));

        $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
        ]);

        try {
            $connection = DB::connection('oracle');
            $receiptSummary = $connection->table('gd_terima_beli_m as receipt')
                ->leftJoin('gd_terima_beli_d as detail', 'receipt.id_transaksi', '=', 'detail.id_transaksi')
                ->whereBetween('receipt.tgl_transaksi', [
                    Carbon::parse($tanggalAwal)->startOfDay(),
                    Carbon::parse($tanggalAkhir)->endOfDay(),
                ])
                ->selectRaw('COUNT(DISTINCT receipt.id_transaksi) as transaction_count, COUNT(detail.id_detail) as item_count, NVL(SUM(detail.jumlah), 0) as received_qty')
                ->first();

            $ringkasan['transactions'] = (int) ($receiptSummary->transaction_count ?? 0);
            $ringkasan['received_items'] = (int) ($receiptSummary->item_count ?? 0);
            $ringkasan['received_qty'] = (float) ($receiptSummary->received_qty ?? 0);

            $purchaseOrders = $connection->table('pmb_purchorder_m as po')
                ->join('m_rekanan as r', 'po.id_rekanan', '=', 'r.id')
                ->select([
                    'po.id_transaksi as id',
                    'po.no_transaksi',
                    'po.tgl_transaksi as tanggal',
                    'po.status',
                    'po.id_rekanan',
                    'r.nama_rekanan',
                    'r.kode_rekanan',
                ])
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('pmb_purchorder_d as pod')
                        ->whereColumn('pod.id_transaksi', 'po.id_transaksi')
                        ->whereRaw('NVL(NULLIF(pod.jumlah2, 0), NVL(pod.jumlah, 0)) > NVL(pod.qty_bpb, 0)');
                })
                ->whereBetween('po.tgl_transaksi', [
                    Carbon::parse($tanggalAwal)->startOfDay(),
                    Carbon::parse($tanggalAkhir)->endOfDay(),
                ])
                ->orderByDesc('po.tgl_transaksi')
                ->orderByDesc('po.id_transaksi')
                ->get();

            if ($purchaseOrders->isNotEmpty()) {
                $lines = $this->getOpenPurchaseOrderLines(
                    $connection,
                    $purchaseOrders->pluck('id')->all()
                );
                $linesByOrder = $lines->groupBy('po_id');

                foreach ($purchaseOrders as $purchaseOrder) {
                    $orderLines = $linesByOrder->get((string) $purchaseOrder->id, collect());
                    $purchaseOrder->item_count = $orderLines->count();
                    $purchaseOrder->qty_remaining = $orderLines->sum('remaining_qty');
                    $purchaseOrder->received_lines = $orderLines->filter(
                        fn (object $line): bool => (float) $line->received_qty > 0
                    )->count();
                }

                $suppliers = $purchaseOrders
                    ->unique('id_rekanan')
                    ->map(fn (object $order): array => [
                        'id' => (string) $order->id_rekanan,
                        'name' => $order->nama_rekanan,
                    ])
                    ->values();

                $ringkasan = array_merge($ringkasan, [
                    'purchase_orders' => $purchaseOrders->count(),
                    'items' => $lines->count(),
                    'qty' => $lines->sum('remaining_qty'),
                ]);
            }
        } catch (\Throwable $e) {
            $purchaseOrders = collect();
            $suppliers = collect();
            $dbError = $e->getMessage();
        }

        $purchaseOrderOptions = $purchaseOrders->map(fn (object $purchaseOrder): array => [
            'id' => (string) $purchaseOrder->id,
            'supplier_id' => (string) $purchaseOrder->id_rekanan,
            'number' => $purchaseOrder->no_transaksi,
        ])->values();

        return view('receipt-dashboard', compact(
            'purchaseOrders',
            'purchaseOrderOptions',
            'suppliers',
            'ringkasan',
            'tanggalAwal',
            'tanggalAkhir',
            'dbError'
        ));
    }

    public function purchaseOrderDetails(int $id): JsonResponse
    {
        $connection = DB::connection('oracle');
        $purchaseOrder = $connection->table('pmb_purchorder_m as po')
            ->join('m_rekanan as r', 'po.id_rekanan', '=', 'r.id')
            ->select([
                'po.id_transaksi as id',
                'po.no_transaksi',
                'po.id_rekanan',
                'r.nama_rekanan',
            ])
            ->where('po.id_transaksi', $id)
            ->first();

        abort_if($purchaseOrder === null, 404);

        $items = $this->getOpenPurchaseOrderLines($connection, [$id]);
        abort_if($items->isEmpty(), 404);

        return response()->json([
            'id' => (string) $purchaseOrder->id,
            'number' => $purchaseOrder->no_transaksi,
            'supplier_id' => (string) $purchaseOrder->id_rekanan,
            'supplier' => $purchaseOrder->nama_rekanan,
            'items' => $items->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'no_sj' => is_string($request->input('no_sj'))
                ? (trim($request->input('no_sj')) ?: null)
                : $request->input('no_sj'),
            'keterangan' => is_string($request->input('keterangan'))
                ? (trim($request->input('keterangan')) ?: null)
                : $request->input('keterangan'),
        ]);

        $validated = $request->validate([
            'po_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
            'no_sj' => ['nullable', 'string', 'max:50'],
            'tgl_sj' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.po_detail_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        if (collect($validated['items'])->every(
            fn (array $item): bool => (float) $item['qty'] <= 0
        )) {
            throw ValidationException::withMessages([
                'items' => 'Masukkan jumlah terima lebih dari 0 pada minimal satu barang.',
            ]);
        }

        $connection = DB::connection('oracle');
        $connection->transaction(function () use ($connection, $validated): void {
            $purchaseOrder = $connection->table('pmb_purchorder_m')
                ->where('id_transaksi', $validated['po_id'])
                ->lockForUpdate()
                ->first();

            abort_if($purchaseOrder === null, 404);

            $purchaseOrderLines = $connection->table('pmb_purchorder_d')
                ->where('id_transaksi', $validated['po_id'])
                ->orderBy('no_urut')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (object $line): string => (string) $line->id_detail);

            $selectedLines = [];
            $seenDetails = [];
            foreach ($validated['items'] as $item) {
                $detailId = (string) $item['po_detail_id'];
                $qty = (float) $item['qty'];

                if ($qty <= 0) {
                    continue;
                }

                if (isset($seenDetails[$detailId])) {
                    throw ValidationException::withMessages([
                        'items' => 'Detail barang PO yang sama tidak boleh dikirim lebih dari satu kali.',
                    ]);
                }
                $seenDetails[$detailId] = true;

                $line = $purchaseOrderLines->get($detailId);
                if ($line === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Barang yang dipilih tidak sesuai dengan Purchase Order.',
                    ]);
                }

                $orderedQty = (float) (($line->jumlah2 ?? 0) > 0
                    ? $line->jumlah2
                    : ($line->jumlah ?? 0));
                $receivedQty = (float) ($line->qty_bpb ?? 0);
                $remainingQty = max(0, $orderedQty - $receivedQty);

                if ($qty > $remainingQty) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah penerimaan melebihi sisa PO untuk detail {$detailId} ({$remainingQty}).",
                    ]);
                }

                $selectedLines[$detailId] = [
                    'line' => $line,
                    'qty' => $qty,
                    'conversion' => (float) ($line->qty_satuan ?? 0) > 0
                        ? (float) $line->qty_satuan
                        : 1,
                ];
            }

            if ($selectedLines === []) {
                throw ValidationException::withMessages([
                    'items' => 'Masukkan jumlah terima lebih dari 0 pada minimal satu barang.',
                ]);
            }

            $lineDetails = $this->getOpenPurchaseOrderLines(
                $connection,
                [$validated['po_id']]
            )->keyBy(fn (object $line): string => (string) $line->po_detail_id);

            $tanggal = Carbon::parse($validated['tanggal'])->format('Y-m-d');
            $tanggalSuratJalan = Carbon::parse($validated['tgl_sj'])->format('Y-m-d');
            $transactionId = (int) $connection->selectOne(
                'SELECT "SEQ_GD_TERIMA_M".NEXTVAL AS "id" FROM DUAL'
            )->id;
            $counter = str_pad((string) $transactionId, 5, '0', STR_PAD_LEFT);
            $transactionNumber = 'TB'.Carbon::parse($tanggal)->format('ym').$counter;

            $connection->table('gd_terima_beli_m')->insert([
                'id_transaksi' => $transactionId,
                'no_transaksi' => $transactionNumber,
                'no_transaksi_kode' => 'TB',
                'no_transaksi_counter' => $counter,
                'tgl_transaksi' => $tanggal.' 00:00:00',
                'opr_insert' => auth()->user()?->name ?? 'admin',
                'tgl_insert' => now()->format('Y-m-d H:i:s'),
                'keterangan' => $validated['keterangan'] ?? null,
                'jns_transaksi' => '03',
                'no_sj' => $validated['no_sj'] ?? '-',
                'tgl_sj' => $tanggalSuratJalan.' 00:00:00',
                'id_rekanan' => (int) $purchaseOrder->id_rekanan,
                'id_po' => (int) $purchaseOrder->id_transaksi,
            ]);

            $rowNumber = 0;
            foreach ($selectedLines as $detailId => $selected) {
                $line = $selected['line'];
                $detail = $lineDetails->get($detailId);
                if ($detail === null) {
                    throw ValidationException::withMessages([
                        'items' => 'Detail PO tidak lagi tersedia untuk diterima.',
                    ]);
                }
                if (empty($line->id_barang) || empty($line->id_satuan)) {
                    throw ValidationException::withMessages([
                        'items' => "Data barang atau satuan pada detail PO {$detailId} belum lengkap.",
                    ]);
                }

                $qty = $selected['qty'];
                $baseQty = $qty * $selected['conversion'];
                $receiptDetailId = (int) $connection->selectOne(
                    'SELECT "SEQ_GD_TERIMA_D".NEXTVAL AS "id" FROM DUAL'
                )->id;
                $rowNumber++;

                $connection->table('gd_terima_beli_d')->insert([
                    'id_detail' => $receiptDetailId,
                    'id_transaksi' => $transactionId,
                    'no_urut' => $rowNumber,
                    'id_barang' => (int) $line->id_barang,
                    'id_satuan' => (int) $line->id_satuan,
                    'jumlah' => $baseQty,
                    'jumlah2' => $qty,
                    'id_satuan2' => (int) ($line->id_satuan2 ?? $line->id_satuan),
                    'id_po' => (int) $purchaseOrder->id_transaksi,
                    'id_po_detail' => (int) $line->id_detail,
                    'id_pesan' => $line->id_pesan,
                    'is_stok' => 'Y',
                    'is_project' => $line->is_project ?: 'N',
                    'id_project' => $line->id_project,
                ]);

                $connection->table('pmb_purchorder_d')
                    ->where('id_detail', $line->id_detail)
                    ->update(['qty_bpb' => (float) ($line->qty_bpb ?? 0) + $qty]);

                $movementId = (int) $connection->selectOne(
                    'SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL'
                )->id;

                $connection->table('t_data_barang')->insert([
                    'id' => $movementId,
                    'nama_barang' => $detail->nama_barang ?: 'Barang #'.$line->id_barang,
                    'jenis_transaksi' => 'masuk',
                    'jumlah' => $baseQty,
                    'tanggal' => $tanggal.' 00:00:00',
                    'keterangan' => "Penerimaan {$transactionNumber} - PO {$purchaseOrder->no_transaksi}",
                ]);
            }
        });

        return redirect()->route('barang.index')
            ->with('success', 'Penerimaan barang dari Purchase Order berhasil disimpan.');
    }

    /**
     * @param  array<int, int|string>  $purchaseOrderIds
     * @return Collection<int, object>
     */
    private function getOpenPurchaseOrderLines(
        ConnectionInterface $connection,
        array $purchaseOrderIds
    ): Collection {
        if ($purchaseOrderIds === []) {
            return collect();
        }

        return $connection->table('pmb_purchorder_d as pod')
            ->leftJoin('m_item as item', 'pod.id_barang', '=', 'item.id_barang')
            ->leftJoin('m_satuan as unit', 'pod.id_satuan', '=', 'unit.id')
            ->leftJoin('m_satuan as item_unit', 'item.id_satuan', '=', 'item_unit.id')
            ->select([
                'pod.id_transaksi as po_id',
                'pod.id_detail as po_detail_id',
                'pod.no_urut',
                'pod.id_barang',
                'pod.id_satuan',
                'pod.id_satuan2',
                'pod.id_pesan',
                'pod.id_project',
                'pod.is_project',
                'pod.qty_satuan as conversion_qty',
                'pod.jumlah as po_qty_base',
                'pod.jumlah2 as po_qty',
                'pod.qty_bpb as received_qty',
                'item.kode_barang',
                'item.nama_barang',
            ])
            ->selectRaw("NVL(unit.kode_satuan, NVL(unit.nama_satuan, NVL(item_unit.kode_satuan, NVL(item_unit.nama_satuan, 'Unit ' || TO_CHAR(pod.id_satuan))))) as unit")
            ->selectRaw('NVL(NULLIF(pod.jumlah2, 0), NVL(pod.jumlah, 0)) as ordered_qty')
            ->selectRaw('GREATEST(NVL(NULLIF(pod.jumlah2, 0), NVL(pod.jumlah, 0)) - NVL(pod.qty_bpb, 0), 0) as remaining_qty')
            ->whereIn('pod.id_transaksi', $purchaseOrderIds)
            ->whereRaw('NVL(NULLIF(pod.jumlah2, 0), NVL(pod.jumlah, 0)) > NVL(pod.qty_bpb, 0)')
            ->orderBy('pod.no_urut')
            ->get();
    }
}
