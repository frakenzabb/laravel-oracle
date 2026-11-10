<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BonKeluarTest extends TestCase
{
    public function test_bon_keluar_form_loads_master_items(): void
    {
        $itemsQuery = Mockery::mock();
        $stocksQuery = Mockery::mock();
        $outgoingItemsQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $itemsQuery->shouldReceive('leftJoin')->once()
            ->with('m_satuan as unit', 'item.id_satuan', '=', 'unit.id')
            ->andReturnSelf();
        $itemsQuery->shouldReceive('select')->once()
            ->with(Mockery::type('array'))->andReturnSelf();
        $itemsQuery->shouldReceive('selectRaw')->once()
            ->with(Mockery::type('string'))->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()
            ->with('item.nama_barang')->andReturnSelf();
        $itemsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id' => 9,
                'code' => 'BRG009',
                'name' => 'Baut M10',
                'unit_id' => 2,
                'unit' => 'Pcs',
            ],
        ]));

        $stocksQuery->shouldReceive('selectRaw')->once()
            ->with(Mockery::type('string'), ['masuk'])->andReturnSelf();
        $stocksQuery->shouldReceive('whereRaw')->once()
            ->with('LOWER(nama_barang) IN (?)', ['baut m10'])->andReturnSelf();
        $stocksQuery->shouldReceive('groupByRaw')->once()
            ->with('LOWER(nama_barang)')->andReturnSelf();
        $stocksQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) ['nama_key' => 'baut m10', 'stock' => 20],
        ]));

        $outgoingItemsQuery->shouldReceive('join')->once()
            ->with('gd_bon_keluar_d as detail', 'header.id_transaksi', '=', 'detail.id_transaksi')
            ->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('leftJoin')->twice()->withAnyArgs()->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('select')->once()
            ->with(Mockery::type('array'))->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('selectRaw')->once()
            ->with(Mockery::type('string'))->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('orderByDesc')->twice()
            ->withAnyArgs()->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('orderBy')->once()
            ->with('detail.no_urut')->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('limit')->once()->with(100)->andReturnSelf();
        $outgoingItemsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id_transaksi' => 2,
                'no_transaksi' => 'BK261100002',
                'tgl_transaksi' => '2026-11-04',
                'transaction_description' => 'Divisi: Engineering',
                'no_urut' => 1,
                'id_barang' => 9,
                'jumlah' => 3,
                'kode_barang' => 'BRG009',
                'nama_barang' => 'Baut M10',
                'unit' => 'Pcs',
            ],
        ]));

        $connection->shouldReceive('table')->with('m_item as item')->andReturn($itemsQuery);
        $connection->shouldReceive('table')->with('t_data_barang')->andReturn($stocksQuery);
        $connection->shouldReceive('table')->with('gd_bon_keluar_m as header')->andReturn($outgoingItemsQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/barang-keluar')
            ->assertOk()
            ->assertSee('Barang Keluar')
            ->assertSee('Barang Masuk (Input)')
            ->assertSee('Barang Tersedia')
            ->assertSee('/penerimaan-barang/input')
            ->assertSee('#availableStockTable')
            ->assertSee('Baut M10')
            ->assertSee('Tambah Barang')
            ->assertSee('Simpan Bon Keluar')
            ->assertSee('Stok dicek ulang saat transaksi disimpan')
            ->assertSee('Riwayat Barang Keluar')
            ->assertSee('BK261100002')
            ->assertSee('Jumlah Keluar')
            ->assertSee('Baut M10');
    }

    public function test_bon_keluar_saves_header_details_and_stock_movements(): void
    {
        $itemsQuery = Mockery::mock();
        $movementsQuery = Mockery::mock();
        $headerQuery = Mockery::mock();
        $detailsQuery = Mockery::mock();
        $stockMovementQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $itemsQuery->shouldReceive('whereIn')->once()
            ->with('id_barang', ['9'])->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()
            ->with('id_barang')->andReturnSelf();
        $itemsQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $itemsQuery->shouldReceive('get')->once()
            ->with(['id_barang', 'nama_barang', 'id_satuan'])
            ->andReturn(collect([
                (object) ['id_barang' => 9, 'nama_barang' => 'Baut M10', 'id_satuan' => 2],
            ]));

        $movementsQuery->shouldReceive('whereRaw')->once()
            ->with('LOWER(nama_barang) IN (?)', ['baut m10'])->andReturnSelf();
        $movementsQuery->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $movementsQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $movementsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) ['nama_barang' => 'Baut M10', 'jenis_transaksi' => 'masuk', 'jumlah' => 25],
        ]));

        $headerQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_transaksi'] === 31
                && $row['no_transaksi'] === 'BK261100031'
                && $row['no_transaksi_kode'] === null
                && $row['status'] === '1'
                && $row['keterangan'] === "Divisi: Engineering\nKeperluan: Perbaikan mesin produksi"
                && $row['tgl_transaksi'] === '2026-11-04 00:00:00'
                && $row['id_divisi_group'] === null
        ))->andReturnTrue();
        $detailsQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_detail'] === 41
                && $row['id_transaksi'] === 31
                && $row['no_urut'] === 1
                && $row['id_barang'] === 9
                && $row['jumlah'] === 5
                && ! array_key_exists('jumlah2', $row)
                && ! array_key_exists('id_satuan2', $row)
                && ! array_key_exists('is_stok', $row)
        ))->andReturnTrue();
        $stockMovementQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['nama_barang'] === 'Baut M10'
                && $row['jenis_transaksi'] === 'keluar'
                && $row['jumlah'] === 5
                && $row['keterangan'] === 'Bon Keluar BK261100031'
        ))->andReturnTrue();

        $connection->shouldReceive('table')->with('m_item')->andReturn($itemsQuery);
        $connection->shouldReceive('table')->with('t_data_barang')
            ->andReturn($movementsQuery, $stockMovementQuery);
        $connection->shouldReceive('table')->with('gd_bon_keluar_m')->andReturn($headerQuery);
        $connection->shouldReceive('table')->with('gd_bon_keluar_d')->andReturn($detailsQuery);
        $connection->shouldReceive('selectOne')->andReturn(
            (object) ['id' => 31],
            (object) ['id' => 41],
            (object) ['id' => 51]
        );
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/barang-keluar', [
            'tanggal' => '2026-11-04',
            'divisi' => ' Engineering ',
            'keperluan' => 'Perbaikan mesin produksi',
            'items' => [
                ['id_barang' => 9, 'qty' => 5],
            ],
        ])
            ->assertRedirect(route('bon-keluar.index'))
            ->assertSessionHas('success', 'Bon Keluar berhasil disimpan dan stok gudang diperbarui.');
    }

    public function test_bon_keluar_rejects_quantity_above_available_stock(): void
    {
        $itemsQuery = Mockery::mock();
        $movementsQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $itemsQuery->shouldReceive('whereIn')->once()
            ->with('id_barang', ['9'])->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()
            ->with('id_barang')->andReturnSelf();
        $itemsQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $itemsQuery->shouldReceive('get')->once()
            ->with(['id_barang', 'nama_barang', 'id_satuan'])
            ->andReturn(collect([
                (object) ['id_barang' => 9, 'nama_barang' => 'Baut M10', 'id_satuan' => 2],
            ]));

        $movementsQuery->shouldReceive('whereRaw')->once()
            ->with('LOWER(nama_barang) IN (?)', ['baut m10'])->andReturnSelf();
        $movementsQuery->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $movementsQuery->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $movementsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) ['nama_barang' => 'Baut M10', 'jenis_transaksi' => 'masuk', 'jumlah' => 3],
        ]));

        $connection->shouldReceive('table')->with('m_item')->andReturn($itemsQuery);
        $connection->shouldReceive('table')->with('t_data_barang')->andReturn($movementsQuery);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->from('/barang-keluar')
            ->post('/barang-keluar', [
                'tanggal' => '2026-11-04',
                'divisi' => 'Engineering',
                'keperluan' => 'Perbaikan',
                'items' => [
                    ['id_barang' => 9, 'qty' => 4],
                ],
            ])
            ->assertRedirect('/barang-keluar')
            ->assertSessionHasErrors('items');
    }
}
