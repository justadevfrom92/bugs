<?php

use App\Reports\BillsReport;
use App\Reports\ContactsReport;
use App\Reports\ErcotReport;
use App\Reports\ExceptionsReport;
use App\Reports\NotesReport;
use App\Reports\OrdersReport;
use App\Reports\PaymentsReport;
use App\Reports\PhonecallsReport;
use App\Reports\ReceivablesReport;
use App\Reports\StarsReport;

/*
|--------------------------------------------------------------------------
| Walker (reporting)
|--------------------------------------------------------------------------
| Every report the admin can run. Each is a class in app/Reports, used by
| Walker and by Corral's report screens. 'model' is the record type it reads
| (the same model names the history tracker uses).
*/

return [

    'reports' => [
        'orders' => ['title' => 'Orders', 'class' => OrdersReport::class, 'model' => 'TicketCustomer_model', 'category' => 'Customers',
            'description' => 'New orders (accounts) by order date and status.'],
        'notes' => ['title' => 'Notes', 'class' => NotesReport::class, 'model' => 'ItemNote_model', 'category' => 'Customers',
            'description' => 'Account notes by agent, date, text and priority.'],
        'phonecalls' => ['title' => 'Phonecalls', 'class' => PhonecallsReport::class, 'model' => 'ItemCall_model', 'category' => 'Customers',
            'description' => 'Calls by date, account, agent id or phone number.'],
        'contacts' => ['title' => 'Emails & Texts', 'class' => ContactsReport::class, 'model' => 'ItemEmailSalesforce_model', 'category' => 'Customers',
            'description' => 'Emails and texts sent to customers, with opens and clicks.'],
        'exceptions' => ['title' => 'Exception Queues', 'class' => ExceptionsReport::class, 'model' => 'TicketQueue_model', 'category' => 'Customers',
            'description' => 'Work items in the exception queues and how long they have been open.'],
        'payments' => ['title' => 'Payments', 'class' => PaymentsReport::class, 'model' => 'ItemPayment_model', 'category' => 'Billing',
            'description' => 'Payments by date, status and source.'],
        'bills' => ['title' => 'Bills', 'class' => BillsReport::class, 'model' => 'ItemFileBill_model', 'category' => 'Billing',
            'description' => 'Bills issued, with usage and amounts.'],
        'receivables' => ['title' => 'Accounts Receivable Aging', 'class' => ReceivablesReport::class, 'model' => 'TicketCustomer_model', 'category' => 'Billing',
            'description' => 'Open balances aged Current, 1-30, 31-60, 61-90 and 90+ days past due.'],
        'ercot' => ['title' => 'ERCOT Transactions', 'class' => ErcotReport::class, 'model' => 'ItemErcot81405_model', 'category' => 'Market',
            'description' => 'Market transactions (814s, 867s) by date, type and status.'],
        'stars' => ['title' => 'Rewards Stars', 'class' => StarsReport::class, 'model' => 'ItemProductReward_model', 'category' => 'Rewards',
            'description' => 'Stars earned and redeemed.'],
    ],

    // A run still "running" after this many minutes is shown as "Did not finish"
    'timeout_minutes' => 60,

    // Categories for uploaded reports
    'upload_categories' => ['Finance', 'Operations', 'Marketing', 'Regulatory', 'Utility / ERCOT', 'Other'],

];
