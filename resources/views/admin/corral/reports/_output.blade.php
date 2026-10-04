{{-- Output choice + Run button shared by every Corral report --}}
<div class="field"><label for="output">Output</label><select id="output" name="output">
    @foreach (['screen' => 'Output - On-Screen', 'summary' => 'Output - On-Screen - Summary', 'csv' => 'Output - CSV', 'csv-summary' => 'Output - CSV - Summary'] as $v => $l)
        <option value="{{ $v }}" @selected($f['output'] === $v)>{{ $l }}</option>
    @endforeach
</select></div>
<button class="btn" name="run" value="1">Run Report</button>
