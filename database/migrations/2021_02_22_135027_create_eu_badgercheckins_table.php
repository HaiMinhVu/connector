<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEuBadgercheckinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('eu_badgercheckins')) {
            Schema::create('eu_badgercheckins', function (Blueprint $table) {
                $table->Increments('id');
                $table->string('rep_email')->nullable();
                $table->string('account_name')->nullable();
                $table->string('account_address')->nullable();
                $table->string('date')->nullable();
                $table->string('time')->nullable();
                $table->string('local_time')->nullable();
                $table->string('timezone')->nullable();
                $table->string('type')->nullable();
                $table->string('comments')->nullable();
                $table->string('customer_id')->nullable();
                $table->string('decision_maker_name')->nullable();
                $table->string('next_step')->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_processed')->nullable()->default(0);
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
        Schema::dropIfExists('eu_badgercheckins');
    }
}
