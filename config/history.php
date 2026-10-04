<?php

use App\Models\AdminApp;
use App\Models\Bill;
use App\Models\ByopProduct;
use App\Models\ContactMessage;
use App\Models\ContentBlock;
use App\Models\Customer;
use App\Models\Note;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Rate;
use App\Models\Role;
use App\Models\TdspFee;
use App\Models\TermDiscount;
use App\Models\TermEtf;
use App\Models\User;
use App\Models\WorkItem;

/*
|--------------------------------------------------------------------------
| History tracking
|--------------------------------------------------------------------------
| Which app models are recorded, the model name each is shown under (the
| original system's naming), and the log group it belongs to. Models listed
| here record every create, update and delete automatically.
*/

return [

    'models' => [
        Customer::class => ['TicketCustomer_model', 'Account Attributes'],
        Payment::class => ['ItemPayment_model', 'Payments'],
        Bill::class => ['ItemFileBill_model', 'Billing & Files'],
        Note::class => ['ItemNote_model', 'Notes'],
        WorkItem::class => ['TicketQueue_model', 'Queues'],
        ContactMessage::class => ['ItemContactMessage_model', 'Emails'],
        Plan::class => ['Plan_model', 'Admin Changes'],
        Rate::class => ['Rate_model', 'Admin Changes'],
        TdspFee::class => ['TdspFee_model', 'Admin Changes'],
        TermDiscount::class => ['TermDiscount_model', 'Admin Changes'],
        TermEtf::class => ['TermEtf_model', 'Admin Changes'],
        ByopProduct::class => ['ByopProduct_model', 'Admin Changes'],
        Page::class => ['Page_model', 'Admin Changes'],
        ContentBlock::class => ['ContentBlock_model', 'Admin Changes'],
        User::class => ['User_model', 'Admin Changes'],
        Role::class => ['Role_model', 'Admin Changes'],
        AdminApp::class => ['AdminApp_model', 'Admin Changes'],
    ],

    // Each log ticket on an account (its own page, like the original "Logs - Products"): key => [title, groups]
    'logs' => [
        'attributes' => ['Logs - Account Attributes', ['Account Attributes']],
        'products' => ['Logs - Products', ['Products']],
        'payments' => ['Logs - Payments', ['Payments']],
        'files' => ['Logs - Files & Usage', ['Billing & Files', 'Usage']],
        'ercot' => ['Logs - EDI Transactions', ['EDI Transactions']],
        'emails' => ['Logs - Emails', ['Emails']],
        'contents' => ['Logs - ItemContents', ['Content']],
        'notes' => ['Logs - Notes', ['Notes']],
        'contact' => ['Logs - Phone Calls', ['Contact']],
        'logins' => ['Logs - Logins', ['Logins']],
        'queues' => ['Logs - Queues', ['Queues']],
    ],

    // Display order of the log groups
    'groups' => [
        'Account Attributes', 'Products', 'Payments', 'Billing & Files', 'Usage', 'EDI Transactions',
        'Emails', 'Notes', 'Content', 'Contact', 'Logins', 'Queues', 'Admin Changes',
    ],

    // Fields never stored in snapshots or change lists
    'hidden' => ['password', 'remember_token'],

    // Fields left out of change lists (they change on every save)
    'ignore_changes' => ['updated_at', 'created_at', 'last_login_at'],
];
