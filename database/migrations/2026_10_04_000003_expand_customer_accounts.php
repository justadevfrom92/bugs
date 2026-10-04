<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Everything the original Corral account page shows: contact and meter details,
 * flags, products, credits/debits, payment methods, plan and address change logs,
 * ERCOT transactions, queue log, files, contact log, stars, API log and phone calls.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_type')->nullable();
            $table->string('language')->default('English');
            $table->string('username')->nullable();
            $table->unsignedSmallInteger('tec_score')->nullable();
            $table->string('ssn_last4', 4)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ip_location')->nullable();
            $table->string('unit')->nullable();
            $table->string('meter_number')->nullable();
            $table->string('load_profile')->nullable();
            $table->string('load_zone')->nullable();
            $table->string('meter_type')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_unit')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state', 2)->nullable();
            $table->string('billing_zip', 10)->nullable();
            $table->string('move_switch')->default('switch');
            $table->date('requested_start')->nullable();
            $table->date('actual_start')->nullable();
            $table->string('status_info')->nullable();
            $table->string('rate_class')->nullable();
            $table->string('channel')->default('1 - Organic (default)');
            $table->string('msid')->default('1001 - Organic');
            $table->string('promo_code')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('marketing_opt_in')->default(true);
            $table->json('authorized_users')->nullable();
            $table->json('linked_accounts')->nullable();
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('action')->nullable();
            $table->string('priority')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->time('paid_time')->nullable();
            $table->string('kind')->default('Balance Payment');   // Balance Payment, Pre-Payment, Purchase, Account Verification
            $table->foreignId('payment_method_id')->nullable();
            $table->string('confirmation')->nullable();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('bill_type')->default('original');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_on')->nullable();
            $table->date('paid_on')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_after', 10, 2)->default(0);
            $table->string('invoice')->nullable();
            $table->boolean('ontime')->default(true);
        });

        Schema::create('customer_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('flag');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->json('data')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('kind');              // credit | debit
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->string('status')->default('pending');   // pending | applied | deleted
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type');              // Credit Card, Bank Account
            $table->string('last4', 4)->nullable();
            $table->string('expires')->nullable();
            $table->string('nickname')->nullable();
            $table->string('vendor');            // Stripe, Auth.net
            $table->boolean('autopay')->default(false);
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rate_class');
            $table->decimal('energy_charge', 10, 8);   // $/kWh
            $table->decimal('rate_2000', 8, 3);        // ¢/kWh average at 2,000 kWh
            $table->timestamp('ordered_at');
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('status')->default('not started');   // current | not started | ended
        });

        Schema::create('service_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('esiid')->nullable();
            $table->string('street');
            $table->string('city');
            $table->string('zip', 10);
            $table->timestamp('ordered_at');
            $table->date('service_start')->nullable();
            $table->date('service_end')->nullable();
        });

        Schema::create('ercot_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('esiid')->nullable();
            $table->date('trans_date');
            $table->string('purpose');           // request | response
            $table->date('scheduled_on')->nullable();
            $table->string('trans_type');        // 814_16, 814_05, 867_03 …
            $table->string('label');             // MoveIn, Switch, MoveIn Response …
            $table->string('tracking')->nullable();
            $table->string('status')->default('linked');   // linked | unlinked | cancelled
            $table->timestamps();
        });

        Schema::create('queue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('queue');             // e.g. QueueCartFulfilledFlowing
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('customer_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind');              // efl, yrac, tos, welcome, invoice, upload
            $table->string('path')->nullable();  // storage path for uploads
            $table->timestamps();
        });

        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('channel');           // Email | SMS
            $table->string('template');
            $table->text('body')->nullable();
            $table->string('status')->default('sent');   // sent | queued | not sent
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('star_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->decimal('stars', 8, 1);      // + earned, - spent
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('api');               // Experian, Stripe, Utilibill …
            $table->string('action');
            $table->string('status');            // 200, 4xx …
            $table->unsignedInteger('response_ms')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('phonecalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent_id')->nullable();
            $table->string('phone');
            $table->string('direction');         // inbound | outbound
            $table->timestamp('started_at');
            $table->unsignedInteger('duration_sec')->default(0);
            $table->string('disposition')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['phonecalls', 'api_logs', 'star_entries', 'contact_logs', 'customer_files', 'queue_logs', 'ercot_transactions', 'service_addresses', 'plan_terms', 'payment_methods', 'ledger_entries', 'customer_products', 'customer_flags'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
