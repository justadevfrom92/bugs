<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use App\Models\AiConversation;
use App\Models\AiModel;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Deputy: open-weight models (hosted and downloaded), the agents on them, and 30 days of (fictional) conversations. */
class DeputySeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(7);
        $m = [];
        foreach ([
            // Hosted open-weight models, through the OpenAI-compatible open models API
            ['kimi', 'Kimi K2 Instruct', 'hosted', 'moonshotai/Kimi-K2-Instruct', 'Moonshot AI, open weights on Hugging Face', null, null, 131072, 'available'],
            ['qwen235', 'Qwen3 235B A22B', 'hosted', 'Qwen/Qwen3-235B-A22B-Instruct-2507', 'Alibaba Qwen, open weights on Hugging Face', null, null, 262144, 'available'],
            ['deepseek', 'DeepSeek V3', 'hosted', 'deepseek-ai/DeepSeek-V3', 'DeepSeek, open weights on Hugging Face', null, null, 131072, 'available'],
            // Downloaded to the local model server (Ollama)
            ['qwen8', 'Qwen3 8B', 'local', 'qwen3:8b', 'ollama pull qwen3:8b', 5.2, 'Q4_K_M', 40960, 'available'],
            ['qwen30', 'Qwen3 30B A3B', 'local', 'qwen3:30b-a3b', 'ollama pull qwen3:30b-a3b', 19.0, 'Q4_K_M', 40960, 'downloading'],
            ['gptoss', 'gpt-oss 20B', 'local', 'gpt-oss:20b', 'ollama pull gpt-oss:20b', 14.0, 'MXFP4', 131072, 'available'],
            ['llama', 'Llama 3.1 8B', 'local', 'llama3.1:8b', 'ollama pull llama3.1:8b', 4.9, 'Q4_K_M', 131072, 'available'],
            ['mistral', 'Mistral 7B', 'local', 'mistral:7b', 'ollama pull mistral:7b', 4.1, 'Q4_0', 32768, 'available'],
            ['billing', 'Billing Assistant (fine-tuned Qwen3 8B)', 'local', 'billing-assistant:8b', '/models/billing-assistant-8b-q4_k_m.gguf, fine-tuned on sample billing questions', 5.2, 'Q4_K_M', 40960, 'available'],
        ] as [$k, $name, $provider, $id, $source, $size, $quant, $ctx, $status]) {
            $m[$k] = AiModel::create(['name' => $name, 'provider' => $provider, 'model_id' => $id, 'source' => $source, 'size_gb' => $size, 'quantization' => $quant,
                'context_window' => $ctx, 'status' => $status, 'checked_at' => now()->subHours(mt_rand(2, 30))]);
        }

        $base = 'You are a customer service agent for a Texas retail electricity provider. Be friendly, brief and accurate. Never guess account details; if you are unsure or the customer is upset, hand the conversation to a person.';
        $agents = [];
        foreach ([
            ['phone', 'Phone Agent', 'phone', 'Answers the main phone line first', 'kimi', 'qwen235', 0.3, 2000, true],
            ['sms', 'Text Responder', 'sms', 'Replies to customer texts', 'qwen8', 'llama', 0.3, 600, true],
            ['chat', 'My Account Chat', 'chat', 'The chat bubble in My Account', 'qwen235', 'qwen8', 0.4, 1500, true],
            ['email', 'Email Drafter', 'email', 'Drafts replies to Web Messages for a person to send', 'deepseek', 'kimi', 0.5, 3000, true],
            ['queues', 'Queue Triage', 'queues', 'Suggests the right exception queue for new orders', 'billing', 'qwen8', 0.0, 500, true],
            ['surveys', 'Survey Tagger', 'surveys', 'Tags survey comments by topic and mood', 'mistral', 'gptoss', 0.1, 300, true],
            ['reports', 'Report Summarizer', 'reports', 'Writes a short summary of finished Walker reports', 'gptoss', 'qwen30', 0.3, 1500, false],
        ] as [$k, $name, $activity, $desc, $model, $fallback, $temperature, $max, $active]) {
            $agents[$k] = AiAgent::create(['name' => $name, 'activity' => $activity, 'description' => $desc, 'ai_model_id' => $m[$model]->id,
                'fallback_model_id' => $fallback ? $m[$fallback]->id : null, 'temperature' => $temperature, 'max_tokens' => $max, 'active' => $active,
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
