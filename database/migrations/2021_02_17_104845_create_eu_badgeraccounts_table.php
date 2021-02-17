<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEuBadgeraccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('eu_badgeraccounts')) {
            Schema::create('eu_badgeraccounts', function (Blueprint $table) {
                $table->integer('nsid')->primary();
                $table->string('company_name')->nullable();
                $table->string('sale_rep')->nullable();
                $table->string('status')->nullable();
                $table->string('territory')->nullable();
                $table->string('shipping_address1')->nullable();
                $table->string('shipping_address2')->nullable();
                $table->string('shipping_city')->nullable();
                $table->string('shipping_country')->nullable();
                $table->string('shipping_zip')->nullable();
                $table->string('primary_contact')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('fax')->nullable();
                $table->string('alt_contact')->nullable();
                $table->string('office_phone')->nullable();
                $table->string('license_required')->nullable();
                $table->string('billing_address1')->nullable();
                $table->string('billing_address2')->nullable();
                $table->string('billing_city')->nullable();
                $table->string('billing_state')->nullable();
                $table->string('billing_zip')->nullable();
                $table->string('billing_country')->nullable();
                $table->string('account_category')->nullable();
                $table->string('bg_tax_number')->nullable();
                $table->string('business_model')->nullable();
                $table->string('change_type')->nullable()->default('U');
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
        Schema::dropIfExists('eu_badgeraccounts');
    }
}
