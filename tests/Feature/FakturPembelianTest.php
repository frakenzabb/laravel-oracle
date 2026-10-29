<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class FakturPembelianTest extends TestCase
{
    public function test_homepage_lists_existing_purchase_orders_with_remaining_items(): void
    {
        $receiptSummaryQuery = Mockery::mock();
        $purchaseOrdersQuery = Mockery::mock();
        $linesQuery = $this->mockOpenLinesQuery();
        $connection = Mockery::mock(ConnectionInterface::class);

        $receiptSummaryQuery->shouldReceive('leftJoin')->once()
            ->with('gd_terima_beli_d as detail', 'receipt.id_transaksi', '=', 'detail.id_transaksi')
            ->andReturnSelf();
        $receiptSummaryQuery->shouldReceive('whereBetween')->once()
            ->with('receipt.tgl_transaksi', Mockery::type('array'))->andReturnSelf();
        $receiptSummaryQuery->shouldReceive('selectRaw')->once()
            ->with(Mockery::type('string'))->andReturnSelf();
        $receiptSummaryQuery->shouldReceive('first')->once()->andReturn((object) [
            'transaction_count' => 4,
            'item_count' => 11,
            'received_qty' => 18774,
        ]);

        $purchaseOrdersQuery->shouldReceive('join')->once()
            ->with('m_rekanan as r', 'po.id_rekanan', '=', 'r.id')
            ->andReturnSelf();
        $purchaseOrdersQuery->shouldReceive('select')->once()
            ->with(Mockery::type('array'))->andReturnSelf();
        $purchaseOrdersQuery->shouldReceive('whereExists')->once()
            ->with(Mockery::type('Closure'))->andReturnSelf();
        $purchaseOrdersQuery->shouldReceive('whereBetween')->once()
            ->with('po.tgl_transaksi', Mockery::on(
                fn (array $range): bool => $range[0]->format('Y-m-d H:i:s') === '2026-10-01 00:00:00'
                    && $range[1]->format('Y-m-d H:i:s') === '2026-10-29 23:59:59'
            ))
            ->andReturnSelf();
        $purchaseOrdersQuery->shouldReceive('orderByDesc')->twice()
            ->withAnyArgs()->andReturnSelf();
        $purchaseOrdersQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id' => 20,
                'no_transaksi' => 'PO-2026-0020',
                'tanggal' => '2026-10-20',
                'status' => '2',
                'id_rekanan' => 4,
                'nama_rekanan' => 'CV Sumber Rejeki',
                'kode_rekanan' => 'SPL004',
            ],
        ]));

        $linesQuery->shouldReceive('whereIn')->once()
            ->with('pod.id_transaksi', [20])->andReturnSelf();
        $linesQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'po_id' => 20,
                'po_detail_id' => 201,
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow',
                'unit' => 'Pcs',
                'ordered_qty' => 15,
                'received_qty' => 5,
                'remaining_qty' => 10,
            ],
        ]));

        $connection->shouldReceive('table')->with('gd_terima_beli_m as receipt')->andReturn($receiptSummaryQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_m as po')->andReturn($purchaseOrdersQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_d as pod')->andReturn($linesQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/?tanggal_awal=2026-10-01&tanggal_akhir=2026-10-29')
            ->assertOk()
            ->assertSee('Purchase Order Tersedia')
            ->assertSee('PO-2026-0020')
            ->assertSee('CV Sumber Rejeki')
            ->assertSee('Jumlah Transaksi')
            ->assertSee('Total Baris Item')
            ->assertSee('Total Qty Diterima')
            ->assertSee('18.774')
            ->assertSee('Qty Belum Diterima')
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('value="2026-10-29"', false)
            ->assertSee('Pilih PO')
            ->assertSee('tidak menyediakan pembuatan atau perubahan PO')
            ->assertDontSee('Tambah PO');
    }

    public function test_purchase_order_details_endpoint_returns_only_remaining_lines(): void
    {
        $purchaseOrderQuery = Mockery::mock();
        $linesQuery = $this->mockOpenLinesQuery();
        $connection = Mockery::mock(ConnectionInterface::class);

        $purchaseOrderQuery->shouldReceive('join')->once()
            ->with('m_rekanan as r', 'po.id_rekanan', '=', 'r.id')
            ->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('select')->once()
            ->with(Mockery::type('array'))->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('where')->once()
            ->with('po.id_transaksi', 20)->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('first')->once()->andReturn((object) [
            'id' => 20,
            'no_transaksi' => 'PO-2026-0020',
            'id_rekanan' => 4,
            'nama_rekanan' => 'CV Sumber Rejeki',
        ]);
        $linesQuery->shouldReceive('whereIn')->once()
            ->with('pod.id_transaksi', [20])->andReturnSelf();
        $linesQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'po_id' => 20,
                'po_detail_id' => 201,
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow',
                'unit' => 'Pcs',
                'ordered_qty' => 15,
                'received_qty' => 5,
                'remaining_qty' => 10,
            ],
        ]));

        $connection->shouldReceive('table')->with('pmb_purchorder_m as po')->andReturn($purchaseOrderQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_d as pod')->andReturn($linesQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->getJson('/purchase-orders/20/details')
            ->assertOk()
            ->assertJsonPath('number', 'PO-2026-0020')
            ->assertJsonPath('supplier', 'CV Sumber Rejeki')
            ->assertJsonPath('items.0.po_detail_id', 201)
            ->assertJsonPath('items.0.remaining_qty', 10);
    }

    public function test_receiving_purchase_order_writes_receipt_and_updates_received_quantity(): void
    {
        $purchaseOrderQuery = Mockery::mock();
        $lockedLinesQuery = Mockery::mock();
        $openLinesQuery = $this->mockOpenLinesQuery();
        $headerQuery = Mockery::mock();
        $receiptDetailsQuery = Mockery::mock();
        $movementQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $purchaseOrderQuery->shouldReceive('where')->once()
            ->with('id_transaksi', '20')->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('first')->once()->andReturn((object) [
            'id_transaksi' => 20,
            'no_transaksi' => 'PO-2026-0020',
            'id_rekanan' => 4,
        ]);

        $poLine = (object) [
            'id_transaksi' => 20,
            'id_detail' => 201,
            'no_urut' => 1,
            'id_barang' => 9,
            'id_satuan' => 2,
            'id_satuan2' => 2,
            'id_pesan' => null,
            'id_project' => null,
            'is_project' => 'N',
            'qty_satuan' => 3,
            'jumlah' => 45,
            'jumlah2' => 15,
            'qty_bpb' => 5,
        ];
        $lockedLinesQuery->shouldReceive('where')->once()
            ->with('id_transaksi', '20')->andReturnSelf();
        $lockedLinesQuery->shouldReceive('orderBy')->once()
            ->with('no_urut')->andReturnSelf();
        $lockedLinesQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $lockedLinesQuery->shouldReceive('get')->once()->andReturn(collect([$poLine]));

        $openLinesQuery->shouldReceive('whereIn')->once()
            ->with('pod.id_transaksi', ['20'])->andReturnSelf();
        $openLinesQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'po_id' => 20,
                'po_detail_id' => 201,
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow',
                'unit' => 'Pcs',
                'ordered_qty' => 15,
                'received_qty' => 5,
                'remaining_qty' => 10,
            ],
        ]));

        $headerQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_transaksi'] === 31
                && $row['id_po'] === 20
                && $row['id_rekanan'] === 4
                && $row['no_sj'] === 'SJ-123'
                && $row['tgl_sj'] === '2026-10-21 00:00:00'
        ))->andReturnTrue();
        $receiptDetailsQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_transaksi'] === 31
                && $row['id_po'] === 20
                && $row['id_po_detail'] === 201
                && $row['jumlah'] === 6.0
                && $row['jumlah2'] === 2.0
                && $row['is_stok'] === 'Y'
        ))->andReturnTrue();
        $lockedLinesQuery->shouldReceive('where')->once()
            ->with('id_detail', 201)->andReturnSelf();
        $lockedLinesQuery->shouldReceive('update')->once()
            ->with(['qty_bpb' => 7.0])->andReturn(1);
        $movementQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['nama_barang'] === 'Besi Hollow'
                && $row['jenis_transaksi'] === 'masuk'
                && $row['jumlah'] === 6.0
        ))->andReturnTrue();

        $connection->shouldReceive('table')->with('pmb_purchorder_m')->andReturn($purchaseOrderQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_d')->andReturn($lockedLinesQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_d as pod')->andReturn($openLinesQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_m')->andReturn($headerQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_d')->andReturn($receiptDetailsQuery);
        $connection->shouldReceive('table')->with('t_data_barang')->andReturn($movementQuery);
        $connection->shouldReceive('selectOne')->andReturn(
            (object) ['id' => 31],
            (object) ['id' => 41],
            (object) ['id' => 51]
        );
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/penerimaan-barang', [
            'po_id' => 20,
            'tanggal' => '2026-10-20',
            'no_sj' => 'SJ-123',
            'tgl_sj' => '2026-10-21',
            'keterangan' => 'Penerimaan sebagian',
            'items' => [
                ['po_detail_id' => 201, 'qty' => 2],
            ],
        ])
            ->assertRedirect(route('barang.index'))
            ->assertSessionHas('success', 'Penerimaan barang dari Purchase Order berhasil disimpan.');
    }

    public function test_receipt_quantity_cannot_exceed_remaining_po_quantity(): void
    {
        $purchaseOrderQuery = Mockery::mock();
        $lockedLinesQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $purchaseOrderQuery->shouldReceive('where')->once()
            ->with('id_transaksi', '20')->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $purchaseOrderQuery->shouldReceive('first')->once()->andReturn((object) [
            'id_transaksi' => 20,
            'no_transaksi' => 'PO-2026-0020',
            'id_rekanan' => 4,
        ]);
        $lockedLinesQuery->shouldReceive('where')->once()
            ->with('id_transaksi', '20')->andReturnSelf();
        $lockedLinesQuery->shouldReceive('orderBy')->once()
            ->with('no_urut')->andReturnSelf();
        $lockedLinesQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $lockedLinesQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id_detail' => 201,
                'jumlah' => 15,
                'jumlah2' => 15,
                'qty_bpb' => 5,
            ],
        ]));
        $connection->shouldReceive('table')->with('pmb_purchorder_m')->andReturn($purchaseOrderQuery);
        $connection->shouldReceive('table')->with('pmb_purchorder_d')->andReturn($lockedLinesQuery);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->from('/')
            ->post('/penerimaan-barang', [
                'po_id' => 20,
                'tanggal' => '2026-10-20',
                'tgl_sj' => '2026-10-20',
                'items' => [
                    ['po_detail_id' => 201, 'qty' => 11],
                ],
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('items');
    }

    public function test_legacy_faktur_page_redirects_home_and_no_detail_route_exists(): void
    {
        $this->get('/faktur-pembelian')->assertRedirect('/');
        $this->get('/faktur-pembelian/5')->assertNotFound();
    }

    private function mockOpenLinesQuery(): Mockery\MockInterface
    {
        $query = Mockery::mock();
        $query->shouldReceive('leftJoin')->times(3)->withAnyArgs()->andReturnSelf();
        $query->shouldReceive('select')->once()->with(Mockery::type('array'))->andReturnSelf();
        $query->shouldReceive('selectRaw')->times(3)->with(Mockery::type('string'))->andReturnSelf();
        $query->shouldReceive('whereRaw')->once()->with(Mockery::type('string'))->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('pod.no_urut')->andReturnSelf();

        return $query;
    }
}
