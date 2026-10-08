<?php

/*
| Deputy — AI agents on open-weight models. Two ways to run a model, both set up in
| Sheriff → APIs → Configure:
|  - local:  downloaded to your own server and served by Ollama (LOCAL_MODELS_URL, its /api/chat)
|  - hosted: any OpenAI-compatible endpoint (OPEN_MODELS_URL + OPEN_MODELS_API_KEY), e.g. vLLM,
|            llama.cpp server, LM Studio, OpenRouter, Together or Moonshot (Kimi)
*/
return [
    'providers' => [
        'local' => 'Local server (Ollama)',
        'hosted' => 'Hosted (OpenAI-compatible)',
    ],

    // What each agent does in the business
    'activities' => [
        'phone' => 'Answers phone calls',
        'sms' => 'Replies to texts',
        'chat' => 'My Account chat',
        'email' => 'Drafts email replies',
        'queues' => 'Triages exception queues',
        'surveys' => 'Tags survey comments',
        'reports' => 'Summarizes reports',
    ],

    'channels' => ['phone' => 'Phone', 'sms' => 'Text', 'chat' => 'Chat', 'email' => 'Email', 'internal' => 'Internal', 'test' => 'Test'],
    'outcomes' => ['resolved' => 'Resolved', 'handed_off' => 'Handed to a person', 'abandoned' => 'Customer left', 'failed' => 'Model error'],
];
