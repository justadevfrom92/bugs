<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Product catalog shared by the website and the admin: markets (TDSPs), delivery
 * fees, plans, plan groups, rates and the pricing modifiers Astro manages.
 * Rates and TDSP fees keep history: the current value is the latest effective_on <= today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // e.g. TX-E-ONCOR
            $table->string('short');
            $table->string('description');
            $table->string('region');               // column in the term discount grid
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('zip_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('zip_from');
            $table->unsignedInteger('zip_to');
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
        });

        Schema::create('tdsp_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->decimal('per_kwh', 8, 4);       // ¢/kWh
            $table->decimal('per_bill', 8, 2);      // $ per bill
            $table->date('effective_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['market_id', 'effective_on']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('type', 8);               // Resi | Biz
            $table->unsignedSmallInteger('term');    // months
            $table->string('name');
            $table->string('internal')->unique();    // internal name / plan code
            $table->string('slug');
            $table->string('rolloff')->default('None');
            $table->string('etf')->default('-');
            $table->decimal('mrc', 8, 2)->default(0);
            $table->unsignedTinyInteger('green')->default(100);
            $table->boolean('active')->default(true);
            $table->json('tags')->nullable();        // website filters: fixed, green, flex, month
            $table->json('perks')->nullable();       // website bullets
            $table->timestamps();
        });

        Schema::create('plan_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('plan_group_plan', function (Blueprint $table) {
            $table->foreignId('plan_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['plan_group_id', 'plan_id']);
        });

        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->decimal('energy', 8, 3);        // ¢/kWh energy charge
            $table->date('effective_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['plan_id', 'market_id', 'effective_on']);
        });

        Schema::create('term_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('term');
            $table->string('region');
            $table->decimal('discount', 6, 2);      // ¢/kWh; positive lowers the rate
            $table->unique(['term', 'region']);
        });

        Schema::create('term_etfs', function (Blueprint $table) {
            $table->unsignedSmallInteger('term')->primary();
            $table->unsignedInteger('amount');      // $
        });

        Schema::create('byop_products', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();         // matches data-product on the website
            $table->string('name');
            $table->string('model');
            $table->string('type');
            $table->string('step');
            $table->decimal('rate_adj', 6, 2)->default(0);  // ¢/kWh
            $table->decimal('monthly', 8, 2)->default(0);   // $
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['byop_products', 'term_etfs', 'term_discounts', 'rates', 'plan_group_plan', 'plan_groups', 'plans', 'tdsp_fees', 'zip_ranges', 'markets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
