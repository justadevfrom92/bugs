<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use App\Models\AiConversation;
use App\Models\AiModel;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Deputy: Claude and downloaded local models, the agents on them, and 30 days of (fictional) conversations. */
class DeputySeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(7);
        $m = [];
        foreach ([
            ['opus', 'Claude Opus 5.5', 'anthropic', 'claude-opus-5-5', null, null, null, 1000000, 'available'],
            ['sonnet', 'Claude Sonnet 5.5', 'anthropic', 'claude-sonnet-5-5', null, null, null, 1000000, 'available'],
            ['haiku', 'Claude Haiku 5.5', 'anthropic', 'claude-haiku-5-5', null, null, null, 1000000, 'available'],
            ['llama', 'Llama 3.1 8B', 'local', 'llama3.1:8b', 'ollama pull llama3.1:8b', 4.9, 'Q4_K_M', 131072, 'available'],
            ['mistral', 'Mistral 7B', 'local', 'mistral:7b', 'ollama pull mistral:7b', 4.1, 'Q4_0', 32768, 'available'],
            ['billing', 'Billing Assistant (fine-tuned)', 'local', 'billing-assistant:7b', '/models/billing-assistant-7b-q4_k_m.gguf (fine-tuned on sample billing questions)', 4.4, 'Q4_K_M', 32768, 'available'],
            ['qwen', 'Qwen 2.5 14B', 'local', 'qwen2.5:14b', 'ollama pull qwen2.5:14b', 9.0, 'Q4_K_M', 131072, 'downloading'],
        ] as [$k, $name, $provider, $id, $source, $size, $quant, $ctx, $status]) {
            $m[$k] = AiModel::create(['name' => $name, 'provider' => $provider, 'model_id' => $id, 'source' => $source, 'size_gb' => $size, 'quantization' => $quant,
                'context_window' => $ctx, 'status' => $status, 'checked_at' => now()->subHours(mt_rand(2, 30))]);
        }

        $base = 'You are a customer service agent for a Texas retail electricity provider. Be friendly, brief and accurate. Never guess account details; if you are unsure or the customer is upset, hand the conversation to a person.';
        $agents = [];
        foreach ([
            ['phone', 'Phone Agent', 'phone', 'Answers the main phone line first', 'opus', 'sonnet', 'medium', 4000, true],
            ['sms', 'Text Responder', 'sms', 'Replies to customer texts', 'haiku', 'llama', 'low', 1000, true],
            ['chat', 'My Account Chat', 'chat', 'The chat bubble in My Account', 'sonnet', 'haiku', 'medium', 2000, true],
            ['email', 'Email Drafter', 'email', 'Drafts replies to Web Messages for a person to send', 'opus', null, 'high', 8000, true],
            ['queues', 'Queue Triage', 'queues', 'Suggests the right exception queue for new orders', 'billing', 'haiku', 'low', 1000, true],
            ['surveys', 'Survey Tagger', 'surveys', 'Tags survey comments by topic and mood', 'mistral', 'haiku', 'low', 500, true],
            ['reports', 'Report Summarizer', 'reports', 'Writes a short summary of finished Walker reports', 'sonnet', 'qwen', 'medium', 2000, false],
        ] as [$k, $name, $activity, $desc, $model, $fallback, $effort, $max, $active]) {
            $agents[$k] = AiAgent::create(['name' => $name, 'activity' => $activity, 'description' => $desc, 'ai_model_id' => $m[$model]->id,
                'fallback_model_id' => $fallback ? $m[$fallback]->id : null, 'effort' => $effort, 'max_tokens' => $max, 'active' => $active,
                'system_prompt' => $base."\n\nYour job: ".lcfirst($desc).'.']);
        }

        // Conversations, like the phone call log: issue, transcript, length, outcome and who took over
        $issues = [
            'High bill' => ['Why is my bill so high this month?', 'Your usage went up about 30% with the heat, and the rate is the same. I can set up a payment arrangement if that helps.'],
            'Payment arrangement' => ['Can I split this bill into two payments?', 'Yes. I can split it over your next two bills; the first half is due on the usual date.'],
            'Moving' => ['I am moving next month, what do I do?', 'I can start service at your new address and stop it at the old one on the same day. What is the new address?'],
            'Outage' => ['My power is out.', 'Outages are handled by your utility. I am texting you their outage number and will check back when power is restored.'],
            'Renewal' => ['My contract is ending, what are my options?', 'You have three renewal plans available. The 12-month plan keeps your rate within half a cent.'],
            'AutoPay' => ['How do I turn on AutoPay?', 'I can turn it on now with the card ending in your saved payment method. You will also earn AutoPay stars each month.'],
            'Rewards' => ['How many stars do I have?', 'You have stars ready to redeem for a bill credit or a gift card. Want me to redeem them?'],
            'Deposit' => ['Why do I need a deposit?', 'The credit check asked for a deposit. You can pay it now, or enroll in AutoPay and paperless billing to lower it.'],
        ];
        $customers = Customer::inRandomOrder()->limit(60)->get();
        $csrs = User::whereHas('role', fn ($q) => $q->where('perms', 'like', '%corral%'))->where('active', true)->get();
        $plan = ['phone' => [70, 'Caller'], 'sms' => [60, 'Customer'], 'chat' => [55, 'Customer'], 'email' => [30, 'Customer']];
        foreach ($plan as $k => [$count, $who]) {
            $agent = $agents[$k];
            for ($i = 0; $i < $count; $i++) {
                $topic = array_rand($issues);
                [$q, $a] = $issues[$topic];
                $roll = mt_rand(1, 100);
                $outcome = $roll <= 72 ? 'resolved' : ($roll <= 92 ? 'handed_off' : 'abandoned');
                // A few answered by the fallback model
                $model = mt_rand(1, 12) === 1 && $agent->fallback_model_id ? $agent->fallback_model_id : $agent->ai_model_id;
                $c = $customers->random();
                $lines = ["Agent: Hi {$c->first_name}, thanks for reaching out. How can I help?", "$who: $q", 'Agent: Let me look at your account.', "Agent: $a"];
                $handed = null;
                if ($outcome === 'handed_off') {
                    $handed = $csrs->random();
                    $lines[] = "$who: I would rather talk to a person.";
                    $lines[] = "Agent: Of course. I am connecting you with {$handed->name} now and passing along what we covered.";
                } elseif ($outcome === 'resolved') {
                    $lines[] = "$who: That works, thank you.";
                    $lines[] = 'Agent: You are welcome. Have a great day!';
                }
                $sec = $k === 'phone' ? mt_rand(60, 540) : mt_rand(20, 300);
                AiConversation::create(['ai_agent_id' => $agent->id, 'ai_model_id' => $model, 'customer_id' => $c->id, 'channel' => $k, 'topic' => $topic,
                    'outcome' => $outcome, 'handed_to_id' => $handed?->id, 'transcript' => implode("\n", $lines), 'duration_sec' => $sec,
                    'input_tokens' => mt_rand(900, 4200), 'output_tokens' => mt_rand(120, 900),
                    'started_at' => now()->subDays(mt_rand(0, 29))->setTime(mt_rand(7, 21), mt_rand(0, 59))->min(now()->subMinutes(mt_rand(5, 90)))]);
            }
        }
        // Internal agents: no customer on the line
        foreach ([['queues', 40, 'Order triage', 'System: New order 1219… has no ESIID on file.', 'Agent: Suggested queue: No ESIID. The service address matched two meters.'],
            ['surveys', 35, 'Survey comment', 'System: Comment: "Faster answers on the phone."', 'Agent: Topic: Customer Service. Mood: mildly negative.']] as [$k, $count, $topic, $in, $out]) {
            for ($i = 0; $i < $count; $i++) {
                AiConversation::create(['ai_agent_id' => $agents[$k]->id, 'ai_model_id' => $agents[$k]->ai_model_id, 'channel' => 'internal', 'topic' => $topic,
                    'outcome' => mt_rand(1, 20) === 1 ? 'failed' : 'resolved', 'transcript' => "$in\n$out", 'duration_sec' => mt_rand(1, 6),
                    'input_tokens' => mt_rand(200, 800), 'output_tokens' => mt_rand(20, 120), 'started_at' => now()->subDays(mt_rand(0, 29))->setTime(mt_rand(0, 23), mt_rand(0, 59))->min(now()->subMinutes(10))]);
            }
        }
    }
}
