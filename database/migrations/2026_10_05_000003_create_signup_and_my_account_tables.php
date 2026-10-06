<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Website sign-up (checkout) and the customer My Account portal. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password')->nullable();               // My Account login (with username or email)
            $table->rememberToken();
            $table->decimal('deposit_due', 10, 2)->default(0);
            $table->string('referral_code')->nullable()->unique();
            $table->string('referred_by')->nullable();            // referral code used at sign-up
        });

        // Sign-ups in progress: "Save and finish later" sends the token link
        Schema::create('signups', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('step');
            $table->json('data');
            $table->string('email')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_password_resets', function (Blueprint $table) {
            $table->foreignId('customer_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('token');                              // hashed
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_resets');
        Schema::dropIfExists('signups');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['password', 'remember_token', 'deposit_due', 'referral_code', 'referred_by']));
    }
};
