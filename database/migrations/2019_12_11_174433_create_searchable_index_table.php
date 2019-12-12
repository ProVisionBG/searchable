<?php
/**
 * Copyright (c) ProVision Media Group Ltd. <https://provision.bg>. All Rights Reserved, 2019
 * Written by Venelin Iliev <venelin@provision.bg>
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateSearchableIndexTable
 */
class CreateSearchableIndexTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('searchable.db_connection'))
            ->create(config('searchable.table_name'), function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->morphs('searchable');

                $table->text('title');
                $table->text('content');

                $table->unique(['searchable_type', 'searchable_id']);

                $table->timestamps();
            });

        DB::connection(config('searchable.db_connection'))
            ->statement('ALTER TABLE ' . config('searchable.table_name') . ' ADD FULLTEXT fulltext_title(title)');

        DB::connection(config('searchable.db_connection'))
            ->statement('ALTER TABLE ' . config('searchable.table_name') . ' ADD FULLTEXT fulltext_title_content(title, content)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('searchable.db_connection'))
            ->drop(config('searchable.table_name'));
    }
}
