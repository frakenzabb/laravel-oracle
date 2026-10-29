<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BarangTest extends TestCase
{
    public function test_incoming_movement_is_saved(): void
    {
        $query = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('selectOne')
            ->once()
            ->with('SELECT "T_DATA_BARANG_ID_SEQ".NEXTVAL AS "id" FROM DUAL')
            ->andReturn((object) ['id' => 1]);
        $query->shouldReceive('insert')->once()->with([
            'id' => 1,
            'nama_barang' => 'Kabel LAN',
            'jenis_transaksi' => 'masuk',
            'jumlah' => 10,
            'tanggal' => '2026-10-16 00:00:00',
            'keterangan' => 'Stok awal',
        ])->andReturnTrue();
        $connection->shouldReceive('table')->once()->with('t_data_barang')->andReturn($query);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/mutasi', [
            'nama_barang' => ' Kabel LAN ',
            'jenis_transaksi' => 'masuk',
            'jumlah' => 10,
            'tanggal' => '2026-10-16',
            'keterangan' => 'Stok awal',
        ])
            ->assertRedirect(route('barang.index'))
            ->assertSessionHas('success', 'Transaksi barang berhasil dicatat.');
    }

    public function test_outgoing_movement_cannot_exceed_available_stock(): void
    {
        $query = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $query->shouldReceive('whereRaw')
            ->once()
            ->with('LOWER(nama_barang) = ?', ['kabel lan'])
            ->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([
            (object) ['jenis_transaksi' => 'masuk', 'jumlah' => 3],
        ]));
        $connection->shouldReceive('table')->once()->with('t_data_barang')->andReturn($query);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->post('/mutasi', [
            'nama_barang' => 'Kabel LAN',
            'jenis_transaksi' => 'keluar',
            'jumlah' => 4,
            'tanggal' => '2026-10-16',
        ])->assertSessionHasErrors('jumlah');
    }

    public function test_unknown_movement_type_is_rejected(): void
    {
        $this->post('/mutasi', [
            'nama_barang' => 'Kabel LAN',
            'jenis_transaksi' => 'pindah',
            'jumlah' => 4,
            'tanggal' => '2026-10-16',
        ])->assertSessionHasErrors('jenis_transaksi');
    }

    public function test_incoming_movement_cannot_be_deleted_if_it_would_make_stock_negative(): void
    {
        $lookup = Mockery::mock();
        $query = Mockery::mock();
        $connection = Mockery::mock(ConnectionInterface::class);

        $movement = (object) [
            'id' => 42,
            'nama_barang' => 'Cutter',
            'jenis_transaksi' => 'masuk',
            'jumlah' => 10,
        ];

        $lookup->shouldReceive('where')->once()->with('id', 42)->andReturnSelf();
        $lookup->shouldReceive('first')->once()->andReturn($movement);
        $query->shouldReceive('whereRaw')
            ->once()
            ->with('LOWER(nama_barang) = ?', ['cutter'])
            ->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([
            $movement,
            (object) [
                'id' => 43,
                'nama_barang' => 'Cutter',
                'jenis_transaksi' => 'keluar',
                'jumlah' => 8,
            ],
        ]));
        $connection->shouldReceive('table')
            ->twice()
            ->with('t_data_barang')
            ->andReturn($lookup, $query);
        $connection->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback($connection));
        DB::shouldReceive('connection')->once()->with('oracle')->andReturn($connection);

        $this->delete('/mutasi/42')
            ->assertSessionHasErrors('transaksi');
    }
}
