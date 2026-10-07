<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rodeo → Surveys: question categories, reusable answer sets, a question bank,
 * richer questions (category, answer set, required, help), survey open/close
 * dates and a thank-you message, and answers stored by question id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#00AEEF');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('survey_answer_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('group');                 // Satisfaction, Agreement, Likelihood, Frequency, Yes / No, Channels, Other
            $table->string('type');                  // scale (ordered, scored 1..n) | single | multi
            $table->json('options');
            $table->timestamps();
        });
        Schema::create('survey_bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->string('type');                  // rating | scale | single | multi | yes_no | text
            $table->foreignId('survey_answer_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('help')->nullable();
            $table->timestamps();
        });
        Schema::table('surveys', function (Blueprint $table) {
            $table->text('thank_you')->nullable()->after('intro');
            $table->date('opens_on')->nullable()->after('status');
            $table->date('closes_on')->nullable()->after('opens_on');
        });
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->foreignId('survey_category_id')->nullable()->after('survey_id')->constrained()->nullOnDelete();
            $table->foreignId('survey_answer_set_id')->nullable()->after('type')->constrained()->nullOnDelete();
            $table->foreignId('survey_bank_question_id')->nullable()->after('survey_answer_set_id')->constrained()->nullOnDelete();
            $table->boolean('required')->default(false)->after('options');
            $table->string('help')->nullable()->after('required');
        });
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('source')->nullable()->after('answers');   // email | website | my-account
        });

        // Answers were a list in question order; key them by question id so questions can be added, moved or removed
        foreach (DB::table('surveys')->pluck('id') as $surveyId) {
            $ids = DB::table('survey_questions')->where('survey_id', $surveyId)->orderBy('position')->orderBy('id')->pluck('id')->values();
            foreach (DB::table('survey_responses')->where('survey_id', $surveyId)->get(['id', 'answers']) as $r) {
                $answers = json_decode($r->answers, true) ?: [];
                if (array_is_list($answers)) {
                    $keyed = [];
                    foreach ($answers as $i => $a) {
                        if (isset($ids[$i])) {
                            $keyed[(string) $ids[$i]] = $a;
                        }
                    }
                    DB::table('survey_responses')->where('id', $r->id)->update(['answers' => json_encode((object) $keyed)]);
                }
            }
        }
        // "choice" questions are single-answer questions now
        DB::table('survey_questions')->where('type', 'choice')->update(['type' => 'single']);
    }

    public function down(): void
    {
        DB::table('survey_questions')->where('type', 'single')->update(['type' => 'choice']);
        Schema::table('survey_responses', fn (Blueprint $table) => $table->dropColumn('source'));
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('survey_category_id');
            $table->dropConstrainedForeignId('survey_answer_set_id');
            $table->dropConstrainedForeignId('survey_bank_question_id');
            $table->dropColumn(['required', 'help']);
        });
        Schema::table('surveys', fn (Blueprint $table) => $table->dropColumn(['thank_you', 'opens_on', 'closes_on']));
        Schema::dropIfExists('survey_bank_questions');
        Schema::dropIfExists('survey_answer_sets');
        Schema::dropIfExists('survey_categories');
    }
};
