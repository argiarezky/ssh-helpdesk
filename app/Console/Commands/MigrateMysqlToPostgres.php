<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class MigrateMysqlToPostgres extends Command
{
    protected $signature = 'app:migrate-mysql-to-postgres';

    protected $description = 'Memindahkan data HelpDesk dari MySQL ke PostgreSQL';

    public function handle(): int
    {
        $tables = [
            'users',
            'tickets',
            'ticket_activities',
            'ticket_attachments',
            'ticket_imports',
            'notifications',
            'response_templates',
        ];

        $mysql = DB::connection('mysql');
        $pgsql = DB::connection('pgsql');

        try {
            $mysql->getPdo();
            $pgsql->getPdo();

            $this->info('Koneksi MySQL dan PostgreSQL berhasil.');

            // Pastikan kedua database memiliki lock tiket.
            $sourceLock = $mysql->table('ticket_number_locks')
                ->where('id', 1)->first();

            $targetLock = $pgsql->table('ticket_number_locks')
                ->where('id', 1)->first();

            if (!$sourceLock || !$targetLock) {
                throw new RuntimeException(
                    'Data ticket_number_locks dengan id 1 harus tersedia di kedua database.'
                );
            }

            // Validasi seluruh tabel sebelum mulai menyalin.
            foreach ($tables as $table) {
                if (
                    !Schema::connection('mysql')->hasTable($table) ||
                    !Schema::connection('pgsql')->hasTable($table)
                ) {
                    throw new RuntimeException(
                        "Tabel {$table} tidak ditemukan di salah satu database."
                    );
                }

                $sourceColumns = Schema::connection('mysql')
                    ->getColumnListing($table);
                $targetColumns = Schema::connection('pgsql')
                    ->getColumnListing($table);

                sort($sourceColumns);
                sort($targetColumns);

                if ($sourceColumns !== $targetColumns) {
                    throw new RuntimeException(
                        "Kolom tabel {$table} berbeda. Tidak ada data yang disalin."
                    );
                }

                if ($pgsql->table($table)->exists()) {
                    throw new RuntimeException(
                        "Tabel {$table} di PostgreSQL tidak kosong. Migrasi dibatalkan."
                    );
                }
            }

            $this->info('Validasi kolom berhasil.');
            $this->info('Seluruh tabel tujuan kosong.');

            $pgsql->beginTransaction();

            try {
                foreach ($tables as $table) {
                    $sourceCount = $mysql->table($table)->count();

                    $this->info(
                        "Menyalin {$table}: {$sourceCount} baris."
                    );

                    $mysql->table($table)
                        ->orderBy('id')
                        ->chunk(200, function ($rows) use ($pgsql, $table) {
                            $data = $rows->map(
                                fn ($row) => (array) $row
                            )->all();

                            if ($data !== []) {
                                $pgsql->table($table)->insert($data);
                            }
                        });

                    $targetCount = $pgsql->table($table)->count();

                    if ($sourceCount !== $targetCount) {
                        throw new RuntimeException(
                            "Jumlah data {$table} tidak cocok. "
                            . "MySQL: {$sourceCount}, PostgreSQL: {$targetCount}."
                        );
                    }
                }

                // Pertahankan versi lock yang sudah ada di MySQL.
                $pgsql->table('ticket_number_locks')
                    ->where('id', 1)
                    ->update([
                        'lock_version' => $sourceLock->lock_version,
                    ]);

                // Sesuaikan sequence ID agar insert berikutnya tidak bentrok.
                foreach ([
                    'users',
                    'tickets',
                    'ticket_activities',
                    'ticket_attachments',
                    'ticket_imports',
                    'response_templates',
                ] as $table) {
                    $result = $pgsql->selectOne(
                        "SELECT pg_get_serial_sequence(?, 'id') AS sequence",
                        [$table]
                    );

                    if ($result && $result->sequence !== null) {
                        $sequence = $result->sequence;

                        $pgsql->selectOne(
                            "SELECT setval(
                                ?::regclass,
                                COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1),
                                EXISTS (SELECT 1 FROM \"{$table}\")
                            )",
                            [$sequence]
                        );
                    }
                }

                $pgsql->commit();
            } catch (Throwable $e) {
                $pgsql->rollBack();
                throw $e;
            }

            $this->newLine();
            $this->info('Migrasi berhasil. Jumlah data PostgreSQL:');

            foreach ($tables as $table) {
                $this->line(
                    "{$table}: " . $pgsql->table($table)->count()
                );
            }

            $this->info('MySQL tetap utuh sebagai cadangan.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Migrasi dibatalkan: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
