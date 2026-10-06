@php [$label, $tone] = \App\Models\ReportRun::stateLabel($run->state()); @endphp
@include('admin.partials.pill', ['text' => $label, 'tone' => $tone])
