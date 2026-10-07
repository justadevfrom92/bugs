@php $stage = $e->stage(); @endphp
@include('admin.partials.pill', ['text' => \App\Http\Controllers\Admin\Rodeo\EmailController::STAGES[$stage] ?? ucfirst($stage), 'tone' => ['clicked' => 'ok', 'opened' => 'ok', 'sent' => 'info', 'dropped' => 'bad', 'not sent' => 'warn', 'suppressed' => 'bad'][$stage] ?? ''])
