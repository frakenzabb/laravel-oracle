<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'oracle';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('T_FAKTUR_PEMBELIAN')) {
            $schema->create('T_FAKTUR_PEMBELIAN', function (Blueprint $table): void {
                $table->integer('id')->primary();
                $table->string('nomor_faktur', 30);
                $table->string('kode_transaksi', 20);
                $table->date('tanggal');
                $table->string('operator_insert', 100);
                $table->dateTime('tanggal_insert');
                $table->string('nama_rekanan', 150);
                $table->string('keterangan', 1000)->nullable();
            });
        }

        if (! $schema->hasTable('T_DETAIL_FAKTUR_PEMBELIAN')) {
            $schema->create('T_DETAIL_FAKTUR_PEMBELIAN', function (Blueprint $table): void {
                $table->integer('id')->primary();
                $table->integer('faktur_id');
                $table->string('kode_barang', 50)->nullable();
                $table->string('nama_barang', 150);
                $table->string('satuan', 30);
                $table->unsignedInteger('jumlah');
            });
        }

        $connection = DB::connection($this->connection);

        foreach (['T_FAKTUR_PEMBELIAN_ID_SEQ', 'T_DETAIL_FP_ID_SEQ'] as $sequence) {
            $sequenceExists = (int) $connection->selectOne(
                'SELECT COUNT(*) AS "sequence_count" FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?',
                [$sequence]
            )->sequence_count > 0;

            if (! $sequenceExists) {
                $connection->statement(
                    'CREATE SEQUENCE "'.$sequence.'" START WITH 1 INCREMENT BY 1'
                );
            }
        }
    }

    public function down(): void
    {
        $connection = DB::connection($this->connection);

        Schema::connection($this->connection)->dropIfExists('T_DETAIL_FAKTUR_PEMBELIAN');
        Schema::connection($this->connection)->dropIfExists('T_FAKTUR_PEMBELIAN');

        foreach (['T_FAKTUR_PEMBELIAN_ID_SEQ', 'T_DETAIL_FP_ID_SEQ'] as $sequence) {
            $sequenceExists = (int) $connection->selectOne(
                'SELECT COUNT(*) AS "sequence_count" FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?',
                [$sequence]
            )->sequence_count > 0;

            if ($sequenceExists) {
                $connection->statement('DROP SEQUENCE "'.$sequence.'"');
            }
        }
    }
};
