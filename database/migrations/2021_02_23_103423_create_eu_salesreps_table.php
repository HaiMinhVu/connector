<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEuSalesrepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('eu_salesreps')) {
            Schema::create('eu_salesreps', function (Blueprint $table) {
                $table->integer('nsid')->primary();
                $table->string('email')->nullable();
                $table->string('name')->nullable();
                $table->boolean('active')->nullable()->default(1);
                $table->string('lastModifiedDate')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eu_salesreps');
    }
}
