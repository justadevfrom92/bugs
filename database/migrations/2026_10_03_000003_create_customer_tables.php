<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Corral: customer accounts, their billing activity, notes, work queues and web messages. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('account')->unique();
            $table->string('ticket')->unique();
            $table->string('name');
            $table->string('type');                  // Residential | Small Business
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('zip', 10);
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->string('esiid')->nullable()->index();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->string('exception')->nullable()->index();
            $table->string('source');
            $table->string('enrollment_type')->default('Switch');
            $table->date('start_date')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('autopay')->default(false);
            $table->boolean('paperless')->default(false);
            $table->boolean('peak_perks')->default(false);
            $table->unsignedInteger('stars')->default(0);
            $table->timestamps();
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'customer_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('paid_on');
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('source');
            $table->string('status');               // Success | Failed | Reversed
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->date('billed_on');
            $table->unsignedInteger('kwh');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author');               // "System" or the user's name at the time
            $table->string('disposition')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();        // key from config('admin.queues')
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('summary')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('topic');
            $table->text('message');
            $table->string('ip')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'work_items', 'notes', 'bills', 'payments', 'bookmarks', 'customers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
