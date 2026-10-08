<?php

/*
| Deputy — AI agents. Claude models go through the Anthropic API (ANTHROPIC_API_KEY);
| custom downloaded models are served by a local runtime such as Ollama (LOCAL_MODELS_URL).
| Both are set from Sheriff → APIs → Configure.
*/
return [
    'default_model' => 'claude-opus-5-5',

    'providers' => [
        'anthropic' => 'Anthropic API',
        'local' => 'Local (downloaded)',
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
    'efforts' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'],

    // Claude models that take the server-side refusal fallback (beta)
    'fallback_models' => ['claude-opus-5-5', 'claude-sonnet-5-5', 'claude-opus-5', 'claude-fable-5-1'],
];
