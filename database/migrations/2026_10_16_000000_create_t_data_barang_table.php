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

        // Gunakan huruf kapital agar konsisten dengan standar Oracle
        if (! $schema->hasTable('T_DATA_BARANG')) {
            $schema->create('T_DATA_BARANG', function (Blueprint $table): void {
                $table->integer('id')->primary();
                $table->string('nama_barang', 150);
                $table->string('jenis_transaksi', 10);
                $table->unsignedInteger('jumlah');
                $table->date('tanggal');
                $table->string('keterangan', 1000)->nullable();
                $table->timestamps();
            });
        }

        $connection = DB::connection($this->connection);
        
        // Cek sequence dengan huruf kapital
        $sequenceExists = (int) $connection->selectOne(
            'SELECT COUNT(*) AS "sequence_count" FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?',
            ['T_DATA_BARANG_ID_SEQ']
        )->sequence_count > 0;

        if (! $sequenceExists) {
            $connection->statement(
                'CREATE SEQUENCE "T_DATA_BARANG_ID_SEQ" START WITH 1 INCREMENT BY 1'
            );
        }
    }

    public function down(): void
    {
        $connection = DB::connection($this->connection);
        
        $sequenceExists = (int) $connection->selectOne(
            'SELECT COUNT(*) AS "sequence_count" FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?',
            ['T_DATA_BARANG_ID_SEQ']
        )->sequence_count > 0;

        if ($sequenceExists) {
            $connection->statement('DROP SEQUENCE "T_DATA_BARANG_ID_SEQ"');
        }

        // Drop tabel dengan huruf kapital
        Schema::connection($this->connection)->dropIfExists('T_DATA_BARANG');
    }
};