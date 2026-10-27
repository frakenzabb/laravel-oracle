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

        $query->shouldReceive('leftJoin')->once()
            ->with('m_rekanan', 'gd_terima_beli_m.id_rekanan', '=', 'm_rekanan.id')
            ->andReturnSelf();
        $query->shouldReceive('select')->once()->with(Mockery::type('array'))->andReturnSelf();
        $query->shouldReceive('orderByDesc')->twice()->withAnyArgs()->andReturnSelf();
        $query->shouldReceive('limit')->once()->with(20)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id' => 7,
                'id_transaksi' => 7,
                'no_transaksi' => 'TB261000007',
                'nomor_faktur' => 'TB261000007',
                'tanggal' => '2026-10-16',
                'nama_rekanan' => 'CV Sumber Rejeki',
                'keterangan' => 'Pembelian bahan',
            ],
        ]));
        $rekananListQuery = Mockery::mock();
        $rekananListQuery->shouldReceive('orderBy')->with('nama_rekanan')->andReturnSelf();
        $rekananListQuery->shouldReceive('get')->andReturn(collect([
            (object) [
                'id' => 1,
                'kode_rekanan' => 'SPL001',
                'nama_rekanan' => 'CV Sumber Rejeki',
                'alamat' => 'Jl. Merdeka',
                'no_telp' => '08123456789',
            ],
        ]));

        $itemListQuery = Mockery::mock();
        $itemListQuery->shouldReceive('leftJoin')->with('m_satuan', 'm_item.id_satuan', '=', 'm_satuan.id')->andReturnSelf();
        $itemListQuery->shouldReceive('select')->with(Mockery::type('array'))->andReturnSelf();
        $itemListQuery->shouldReceive('orderBy')->with('m_item.nama_barang')->andReturnSelf();
        $itemListQuery->shouldReceive('get')->andReturn(collect([
            (object) [
                'id' => 1,
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow 4x4',
                'satuan' => 'Pcs',
            ],
        ]));

        $connection->shouldReceive('table')->with('gd_terima_beli_m')->andReturn($query);
        $connection->shouldReceive('table')->with('m_rekanan')->andReturn($rekananListQuery);
        $connection->shouldReceive('table')->with('m_item')->andReturn($itemListQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/faktur-pembelian')
            ->assertOk()
            ->assertSee('Faktur Pembelian')
            ->assertSee('Detail Barang')
            ->assertSee('Kode Barang')
            ->assertSee('Jumlah')
            ->assertSee('TB261000007')
            ->assertDontSee('Harga')
            ->assertDontSee('Total Transaksi');
    }

    public function test_saving_faktur_records_items_and_increases_stock(): void
    {
        $headerQuery = Mockery::mock();
        $detailQuery = Mockery::mock();
        $movementQuery = Mockery::mock();
        $rekananQuery = Mockery::mock();
        $satuanQuery = Mockery::mock();
        $itemQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        // Sequence calls:
        // 1. SEQ_GD_TERIMA_M for header id_transaksi
        // 2. SEQ_GD_TERIMA_D for detail id_detail
        // 3. T_DATA_BARANG_ID_SEQ for stock movement
        $connection->shouldReceive('selectOne')
            ->with('SELECT "SEQ_GD_TERIMA_M".NEXTVAL AS "id" FROM DUAL')
            ->once()
            ->andReturn((object) ['id' => 5]);

        $connection->shouldReceive('selectOne')
            ->with('SELECT "SEQ_GD_TERIMA_D".NEXTVAL AS "id" FROM DUAL')
            ->once()
            ->andReturn((object) ['id' => 11]);

        $connection->shouldReceive('selectOne')
            ->with('SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL')
            ->once()
            ->andReturn((object) ['id' => 30]);

        // Resolve lookup queries:
        $rekananQuery->shouldReceive('whereRaw')->once()->withAnyArgs()->andReturnSelf();
        $rekananQuery->shouldReceive('orWhereRaw')->once()->withAnyArgs()->andReturnSelf();
        $rekananQuery->shouldReceive('first')->once()->andReturn((object) ['id' => 2]);

        $satuanQuery->shouldReceive('whereRaw')->once()->withAnyArgs()->andReturnSelf();
        $satuanQuery->shouldReceive('first')->once()->andReturn((object) ['id' => 2]);

        $itemQuery->shouldReceive('whereRaw')->once()->withAnyArgs()->andReturnSelf();
        $itemQuery->shouldReceive('first')->once()->andReturn((object) ['id_barang' => 2]);

        // Header insert into gd_terima_beli_m
        $headerQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_transaksi'] === 5
                && $row['no_transaksi'] === 'TB261000005'
                && $row['no_transaksi_kode'] === 'TB'
                && $row['no_transaksi_counter'] === '00005'
                && str_starts_with($row['tgl_transaksi'], '2026-10-16')
                && $row['id_rekanan'] === 2
        ))->andReturnTrue();

        // Detail insert into gd_terima_beli_d
        $detailQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_detail'] === 11
                && $row['id_transaksi'] === 5
                && $row['no_urut'] === 1
                && $row['id_barang'] === 2
                && $row['id_satuan'] === 2
                && $row['jumlah'] === 10
                && $row['is_stok'] === 'Y'
        ))->andReturnTrue();

        // Stock movement insert into t_data_barang
        $movementQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id'] === 30
                && $row['nama_barang'] === 'Besi Hollow 4x4'
                && $row['jenis_transaksi'] === 'masuk'
                && $row['jumlah'] === 10
                && str_starts_with($row['tanggal'], '2026-10-16')
                && str_contains($row['keterangan'], 'TB261000005')
        ))->andReturnTrue();

        $connection->shouldReceive('table')->with('m_rekanan')->andReturn($rekananQuery);
        $connection->shouldReceive('table')->with('m_satuan')->andReturn($satuanQuery);
        $connection->shouldReceive('table')->with('m_item')->andReturn($itemQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_m')->andReturn($headerQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_d')->andReturn($detailQuery);
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

    public function test_saving_faktur_supports_multiple_items_with_sequences_and_relations(): void
    {
        $headerQuery = Mockery::mock();
        $detailQuery = Mockery::mock();
        $movementQuery = Mockery::mock();
        $rekananQuery = Mockery::mock();
        $satuanQuery = Mockery::mock();
        $itemQuery = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        // Sequence calls:
        // 1 for header
        // 2 for details (SEQ_GD_TERIMA_D)
        // 2 for stock movements (T_DATA_BARANG_ID_SEQ)
        $connection->shouldReceive('selectOne')
            ->with('SELECT "SEQ_GD_TERIMA_M".NEXTVAL AS "id" FROM DUAL')
            ->once()
            ->andReturn((object) ['id' => 9]);

        $connection->shouldReceive('selectOne')
            ->with('SELECT "SEQ_GD_TERIMA_D".NEXTVAL AS "id" FROM DUAL')
            ->twice()
            ->andReturn(
                (object) ['id' => 101],
                (object) ['id' => 102]
            );

        $connection->shouldReceive('selectOne')
            ->with('SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL')
            ->twice()
            ->andReturn(
                (object) ['id' => 201],
                (object) ['id' => 202]
            );

        $rekananQuery->shouldReceive('whereRaw')->once()->withAnyArgs()->andReturnSelf();
        $rekananQuery->shouldReceive('orWhereRaw')->once()->withAnyArgs()->andReturnSelf();
        $rekananQuery->shouldReceive('first')->once()->andReturn((object) ['id' => 3]);

        $satuanQuery->shouldReceive('whereRaw')->twice()->withAnyArgs()->andReturnSelf();
        $satuanQuery->shouldReceive('first')->twice()->andReturn(
            (object) ['id' => 2],
            (object) ['id' => 3]
        );

        $itemQuery->shouldReceive('whereRaw')->twice()->withAnyArgs()->andReturnSelf();
        $itemQuery->shouldReceive('first')->twice()->andReturn(
            (object) ['id_barang' => 2],
            (object) ['id_barang' => 3]
        );

        $headerQuery->shouldReceive('insert')->once()->with(Mockery::on(
            fn (array $row): bool => $row['id_transaksi'] === 9
                && $row['no_transaksi'] === 'TB261000009'
                && $row['id_rekanan'] === 3
        ))->andReturnTrue();

        $detailInserts = [];
        $detailQuery->shouldReceive('insert')->twice()->with(Mockery::on(function (array $row) use (&$detailInserts): bool {
            $detailInserts[] = $row;
            return $row['id_transaksi'] === 9; // foreign key to header
        }))->andReturnTrue();

        $movementQuery->shouldReceive('insert')->twice()->with(Mockery::type('array'))->andReturnTrue();

        $connection->shouldReceive('table')->with('m_rekanan')->andReturn($rekananQuery);
        $connection->shouldReceive('table')->with('m_satuan')->andReturn($satuanQuery);
        $connection->shouldReceive('table')->with('m_item')->andReturn($itemQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_m')->andReturn($headerQuery);
        $connection->shouldReceive('table')->with('gd_terima_beli_d')->andReturn($detailQuery);
        $connection->shouldReceive('table')->with('t_data_barang')->andReturn($movementQuery);

        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/faktur-pembelian', [
            'kode_transaksi' => 'FAKTUR',
            'tanggal' => '2026-10-16',
            'nama_rekanan' => 'PT Megah Baja Nusantara',
            'keterangan' => 'Pembelian multiple barang',
            'items' => [
                [
                    'kode_barang' => 'BRG001',
                    'nama_barang' => 'Besi Hollow 4x4',
                    'satuan' => 'Pcs',
                    'jumlah' => 10,
                ],
                [
                    'kode_barang' => 'BRG002',
                    'nama_barang' => 'Semen Tiga Roda',
                    'satuan' => 'Zak',
                    'jumlah' => 25,
                ],
            ],
        ])
            ->assertRedirect(route('faktur-pembelian.show', 9))
            ->assertSessionHas('success');

        $this->assertCount(2, $detailInserts);
        $this->assertEquals(101, $detailInserts[0]['id_detail']);
        $this->assertEquals(9, $detailInserts[0]['id_transaksi']);
        $this->assertEquals(1, $detailInserts[0]['no_urut']);
        $this->assertEquals(102, $detailInserts[1]['id_detail']);
        $this->assertEquals(9, $detailInserts[1]['id_transaksi']);
        $this->assertEquals(2, $detailInserts[1]['no_urut']);
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

        $headerQuery->shouldReceive('leftJoin')->once()
            ->with('m_rekanan', 'gd_terima_beli_m.id_rekanan', '=', 'm_rekanan.id')
            ->andReturnSelf();
        $headerQuery->shouldReceive('select')->once()->with(Mockery::type('array'))->andReturnSelf();
        $headerQuery->shouldReceive('where')->once()->with('gd_terima_beli_m.id_transaksi', 5)->andReturnSelf();
        $headerQuery->shouldReceive('first')->once()->andReturn((object) [
            'id' => 5,
            'id_transaksi' => 5,
            'no_transaksi' => 'TB261000005',
            'nomor_faktur' => 'TB261000005',
            'kode_transaksi' => 'TB',
            'tanggal' => '2026-10-16',
            'operator_insert' => 'admin',
            'tanggal_insert' => '2026-10-16 08:15:00',
            'nama_rekanan' => 'CV Sumber Rejeki',
            'kode_rekanan' => 'SPL001',
            'alamat_rekanan' => 'Jl. Merdeka No. 1',
            'no_telp_rekanan' => '08123456789',
            'keterangan' => null,
        ]);

        $itemsQuery->shouldReceive('leftJoin')->once()
            ->with('m_item', 'gd_terima_beli_d.id_barang', '=', 'm_item.id_barang')
            ->andReturnSelf();
        $itemsQuery->shouldReceive('leftJoin')->once()
            ->with('m_satuan', 'gd_terima_beli_d.id_satuan', '=', 'm_satuan.id')
            ->andReturnSelf();
        $itemsQuery->shouldReceive('select')->once()->with(Mockery::type('array'))->andReturnSelf();
        $itemsQuery->shouldReceive('where')->once()->with('gd_terima_beli_d.id_transaksi', 5)->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()->with('gd_terima_beli_d.no_urut')->andReturnSelf();
        $itemsQuery->shouldReceive('orderBy')->once()->with('gd_terima_beli_d.id_detail')->andReturnSelf();
        $itemsQuery->shouldReceive('get')->once()->andReturn(collect([
            (object) [
                'id' => 11,
                'id_detail' => 11,
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Besi Hollow 4x4',
                'satuan' => 'Pcs',
                'jumlah' => 10,
            ],
        ]));

        $connection->shouldReceive('table')
            ->with('gd_terima_beli_m')->andReturn($headerQuery);
        $connection->shouldReceive('table')
            ->with('gd_terima_beli_d')->andReturn($itemsQuery);
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->get('/faktur-pembelian/5')
            ->assertOk()
            ->assertSee('TB261000005')
            ->assertSee('Besi Hollow 4x4')
            ->assertSee('Pcs')
            ->assertSee('Cetak Faktur')
            ->assertDontSee('Harga')
            ->assertDontSee('Total Transaksi');
    }

    public function test_saving_faktur_rolls_back_and_surfaces_database_failure(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                // Simulate an exception inside the transaction
                throw new \RuntimeException('Database constraint error on insert');
            });
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database constraint error on insert');

        $this->withoutExceptionHandling()->post('/faktur-pembelian', [
            'kode_transaksi' => 'FAKTUR',
            'tanggal' => '2026-10-16',
            'nama_rekanan' => 'CV Sumber Rejeki',
            'items' => [
                [
                    'kode_barang' => 'BRG001',
                    'nama_barang' => 'Besi Hollow 4x4',
                    'satuan' => 'Pcs',
                    'jumlah' => 10,
                ],
            ],
        ]);
    }
}
