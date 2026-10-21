<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class FakturPembelianTest extends TestCase
{
    public function test_faktur_form_shows_recent_purchases(): void
    {
        $query = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $query->shouldReceive('orderByDesc')->twice()->withAnyArgs()->andReturnSelf();
        $query->shouldReceive('limit')->once()->with(20)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id' => 7,
                'nomor_faktur' => 'FP-20261016-007',
                'tanggal' => '2026-10-16',
                'nama_rekanan' => 'CV Sumber Rejeki',
                'keterangan' => 'Pembelian bahan',
            ],
        ]));
        $connection->shouldReceive('table')->once()
            ->with('t_faktur_pembelian')->andReturn($query);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/faktur-pembelian')
            ->assertOk()
            ->assertSee('Faktur Pembelian')
            ->assertSee('Detail Barang')
            ->assertSee('Kode Barang')
            ->assertSee('Jumlah')
            ->assertSee('FP-20261016-007')
            ->assertDontSee('Harga')
            ->assertDontSee('Total Transaksi');
    }

    public function test_saving_faktur_records_items_and_increases_stock(): void
    {
        $headerQuery = Mockery::mock();
        $detailQuery = Mockery::mock();
        $movementQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $connection->shouldReceive('selectOne')->times(3)->andReturn(
            (object) ['id' => 5],
            (object) ['id' => 11],
            (object) ['id' => 30]
        );
        $headerQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id'] === 5
                && $row['nomor_faktur'] === 'FP-20261016-005'
                && $row['kode_transaksi'] === 'FAKTUR'
                && $row['tanggal'] === '2026-10-16'
                && $row['nama_rekanan'] === 'CV Sumber Rejeki'
        ))->andReturnTrue();
        $detailQuery->shouldReceive('insert')->once()->with([
            'id' => 11,
            'faktur_id' => 5,
            'kode_barang' => 'BRG001',
            'nama_barang' => 'Besi Hollow 4x4',
            'satuan' => 'Pcs',
            'jumlah' => 10,
        ])->andReturnTrue();
        $movementQuery->shouldReceive('insert')->once()->with([
            'id' => 30,
            'nama_barang' => 'Besi Hollow 4x4',
            'jenis_transaksi' => 'masuk',
            'jumlah' => 10,
            'tanggal' => '2026-10-16 00:00:00',
            'keterangan' => 'Faktur FP-20261016-005 - CV Sumber Rejeki',
        ])->andReturnTrue();
        $connection->shouldReceive('table')->with('t_faktur_pembelian')->andReturn($headerQuery);
        $connection->shouldReceive('table')->with('t_detail_faktur_pembelian')->andReturn($detailQuery);
        $connection->shouldReceive('table')->with('t_data_barang')->andReturn($movementQuery);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/faktur-pembelian', [
            'kode_transaksi' => 'FAKTUR',
            'tanggal' => '2026-10-16',
            'nama_rekanan' => ' CV Sumber Rejeki ',
            'keterangan' => 'Pembelian bahan',
            'items' => [
                [
                    'kode_barang' => 'BRG001',
                    'nama_barang' => 'Besi Hollow 4x4',
                    'satuan' => 'Pcs',
                    'jumlah' => 10,
                ],
            ],
        ])
            ->assertRedirect(route('faktur-pembelian.show', 5))
            ->assertSessionHas('success', 'Faktur pembelian berhasil disimpan dan stok telah diperbarui.');
    }

    public function test_faktur_requires_at_least_one_valid_item(): void
    {
        $this->from('/faktur-pembelian')
            ->post('/faktur-pembelian', [
                'kode_transaksi' => 'FAKTUR',
                'tanggal' => '2026-10-16',
                'nama_rekanan' => 'CV Sumber Rejeki',
                'items' => [],
            ])
            ->assertRedirect('/faktur-pembelian')
            ->assertSessionHasErrors('items');
    }

    public function test_saved_faktur_can_be_viewed_without_price_or_totals(): void
    {
        $headerQuery = Mockery::mock();
        $itemsQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $headerQuery->shouldReceive('where')->once()->with('id', 5)->andReturnSelf();
        $headerQuery->shouldReceive('first')->once()->andReturn((object) [
            'id' => 5,
            'nomor_faktur' => 'FP-20261016-005',
            'kode_transaksi' => 'FAKTUR',
            'tanggal' => '2026-10-16',
            'operator_insert' => 'admin',
            'tanggal_insert' => '2026-10-16 08:15:00',
            'nama_rekanan' => 'CV Sumber Rejeki',
            'keterangan' => null,
        ]);
        $itemsQuery->shouldReceive('where')->once()->with('faktur_id', 5)->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $itemsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow 4x4',
                'satuan' => 'Pcs',
                'jumlah' => 10,
            ],
        ]));
        $connection->shouldReceive('table')
            ->with('t_faktur_pembelian')->andReturn($headerQuery);
        $connection->shouldReceive('table')
            ->with('t_detail_faktur_pembelian')->andReturn($itemsQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/faktur-pembelian/5')
            ->assertOk()
            ->assertSee('FP-20261016-005')
            ->assertSee('Besi Hollow 4x4')
            ->assertSee('Pcs')
            ->assertSee('Cetak Faktur')
            ->assertDontSee('Harga')
            ->assertDontSee('Total Transaksi');
    }
}
