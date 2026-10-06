<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Strongbox (finance), Bounty (rewards) and Rodeo (marketing). */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Strongbox ----------
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->string('kind')->default('balance');      // balance (credit balance) | deposit
            $table->string('method');                         // Check | Card | ACH
            $table->string('status')->default('requested');   // requested | approved | rejected | paid
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('deposit_held', 10, 2)->default(0);
        });

        // ---------- Bounty ----------
        Schema::create('reward_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->unsignedInteger('stars');
            $table->string('effect');                         // credit | gift | drawing | product
            $table->string('value')->nullable();              // credit amount or product name
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('reward_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->decimal('stars', 8, 2);                   // stars each time, or per $1 when per_dollar
            $table->boolean('per_dollar')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::table('star_entries', function (Blueprint $table) {
            $table->foreignId('reward_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fulfillment')->nullable();        // gift cards: pending | sent
        });

        // ---------- Rodeo ----------
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('subject');
            $table->longText('body');
            $table->timestamps();
        });
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel');                        // Email | SMS
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();              // SMS text
            $table->json('audience');
            $table->string('status')->default('draft');       // draft | sent
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->string('status')->default('active');      // active | closed
            $table->timestamps();
        });
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('type');                           // rating | choice | text
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
        });
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('answers');
            $table->timestamp('created_at');
        });
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->string('msid')->unique();
            $table->string('name');
            $table->string('type');                           // Organic | Paid | Partner | Broker | Agent
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->decimal('credit', 8, 2)->default(0);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['promo_codes', 'marketing_channels', 'survey_responses', 'survey_questions', 'surveys'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('contact_logs', fn (Blueprint $t) => $t->dropConstrainedForeignId('campaign_id'));
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('email_templates');
        Schema::table('star_entries', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reward_offer_id');
            $t->dropColumn('fulfillment');
        });
        Schema::dropIfExists('reward_rules');
        Schema::dropIfExists('reward_offers');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('deposit_held'));
        Schema::dropIfExists('refunds');
    }
};
