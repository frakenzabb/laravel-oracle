<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'oracle';

    public function up(): void
    {
        // Cek apakah kolom id di tabel MIGRATIONS sudah punya default/identity, 
        // atau kita tambahkan sequence & trigger khusus untuk tabel migrations
        $connection = DB::connection($this->connection);
        
        // Buat sequence untuk migrations jika belum ada
        $seqExists = (int) $connection->selectOne(
            'SELECT COUNT(*) AS cnt FROM USER_SEQUENCES WHERE SEQUENCE_NAME = ?',
            ['MIGRATIONS_SEQ']
        )->cnt > 0;

        if (! $seqExists) {
            $connection->statement('CREATE SEQUENCE MIGRATIONS_SEQ START WITH 1 INCREMENT BY 1');
        }

        // Buat trigger untuk auto-fill id migrations
        $connection->statement('
            CREATE OR REPLACE TRIGGER MIGRATIONS_TRG
            BEFORE INSERT ON MIGRATIONS
            FOR EACH ROW
            BEGIN
              IF :NEW.ID IS NULL THEN
                SELECT MIGRATIONS_SEQ.NEXTVAL INTO :NEW.ID FROM DUAL;
              END IF;
            END;
        ');
    }

    public function down(): void
    {
        // Opsional saat rollback
    }
};