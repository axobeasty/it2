<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BaseSeeder extends Seeder
{
    /**
     * Вставить строку, если записи с таким уникальным набором колонок ещё нет.
     * Если запись уже есть и передан $onDuplicateUpdate — обновить перечисленные поля (created_at не трогаем).
     *
     * @param  array<string, mixed>  $unique
     * @param  array<string, mixed>  $row  данные для новой строки (дополняют $unique)
     * @param  array<string, mixed>  $onDuplicateUpdate
     */
    protected function seedOnce(
        string $table,
        array $unique,
        array $row,
        array $onDuplicateUpdate = []
    ): void {
        $existsQuery = DB::table($table);
        foreach ($unique as $column => $value) {
            $existsQuery->where($column, $value);
        }

        if ($existsQuery->exists()) {
            if ($onDuplicateUpdate === []) {
                return;
            }
            $upd = $onDuplicateUpdate;
            unset($upd['created_at']);
            if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $upd)) {
                $upd['updated_at'] = now();
            }
            $updateQuery = DB::table($table);
            foreach ($unique as $column => $value) {
                $updateQuery->where($column, $value);
            }
            $updateQuery->update($upd);

            return;
        }

        $insert = array_merge($unique, $row);
        $now = now();
        if (Schema::hasColumn($table, 'created_at') && ! array_key_exists('created_at', $insert)) {
            $insert['created_at'] = $now;
        }
        if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $insert)) {
            $insert['updated_at'] = $now;
        }
        DB::table($table)->insert($insert);
    }

    /**
     * Вставить или обновить одну строку по уникальному ключу (повторный db:seed не плодит дубликаты).
     *
     * @param  array<string, mixed>  $unique
     * @param  array<string, mixed>  $values  поля при insert и update (кроме created_at при update)
     */
    protected function seedUpsert(string $table, array $unique, array $values): void
    {
        $existsQuery = DB::table($table);
        foreach ($unique as $column => $value) {
            $existsQuery->where($column, $value);
        }

        $now = now();
        if ($existsQuery->exists()) {
            $upd = $values;
            unset($upd['created_at']);
            if (Schema::hasColumn($table, 'updated_at')) {
                $upd['updated_at'] = $now;
            }
            $updateQuery = DB::table($table);
            foreach ($unique as $column => $value) {
                $updateQuery->where($column, $value);
            }
            $updateQuery->update($upd);

            return;
        }

        $insert = array_merge($unique, $values);
        if (Schema::hasColumn($table, 'created_at') && ! array_key_exists('created_at', $insert)) {
            $insert['created_at'] = $now;
        }
        if (Schema::hasColumn($table, 'updated_at') && ! array_key_exists('updated_at', $insert)) {
            $insert['updated_at'] = $now;
        }
        DB::table($table)->insert($insert);
    }
}
