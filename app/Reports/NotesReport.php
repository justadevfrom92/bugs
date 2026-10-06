<?php

namespace App\Reports;

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Collection;

class NotesReport extends Report
{
    public function filters(): array
    {
        $priorities = [...config('corral.priorities'), 'System'];

        return ['user' => ['Username', 'select', User::orderBy('name')->pluck('name', 'id')->all()], 'start' => ['Start', 'date'], 'end' => ['End', 'date'],
            'contains' => ['Note Contains', 'text'], 'priorities' => ['Priority', 'multi', array_combine($priorities, $priorities)]];
    }

    public function defaults(): array
    {
        return ['user' => null, 'start' => today()->subDays(30)->toDateString(), 'end' => today()->toDateString(), 'contains' => null, 'priorities' => [...config('corral.priorities'), 'System']];
    }

    public function query(array $f): Collection
    {
        $wanted = $f['priorities'];

        return Note::with('customer')
            ->whereDate('created_at', '>=', $f['start'])->whereDate('created_at', '<=', $f['end'])
            ->when($f['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($f['contains'] ?? null, fn ($q, $text) => $q->where('body', 'like', '%'.addcslashes($text, '%_\\').'%'))
            ->where(function ($q) use ($wanted) {
                // "System" = notes written by the system rather than an agent
                $q->whereIn('priority', array_diff($wanted, ['System']));
                if (in_array('System', $wanted, true)) {
                    $q->orWhereNull('user_id');
                }
            })
            ->latest()->get();
    }

    public function columns(): array
    {
        return ['Date', 'Account', 'Customer', 'Author', 'Category', 'Action', 'Priority', 'Note'];
    }

    public function row($n): array
    {
        return [$n->created_at->format('Y-m-d H:i'), $n->customer?->account, $n->customer?->name, $n->author, $n->category, $n->action, $n->priority, $n->body];
    }

    public function summary(Collection $notes): array
    {
        return [['Author', 'Category', 'Notes'], $notes->groupBy(fn ($n) => ($n->author ?? 'System').'|'.($n->category ?? '—'))
            ->map(fn ($g, $k) => [...explode('|', $k), $g->count()])->sortBy(fn ($r) => $r[0].$r[1])->values()];
    }
}
