@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Users', 'sub' => 'Who can sign in to the admin tools.', 'actions' => '<button type="button" class="btn" data-add-user>Add User</button>'])
    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Apps</th><th>Last Sign-in</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach ($users as $u)
            <tr>
                <td><b>{{ $u->name }}</b>@if ($u->is(auth()->user())) <span class="muted">(you)</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td>
                    <form method="post" action="{{ route('sheriff.users.update', $u) }}" class="inline">@csrf @method('patch')
                        <select name="role_id" aria-label="Role for {{ $u->name }}" data-autosubmit @disabled($u->is(auth()->user()))>
                            @foreach ($roles as $r)<option value="{{ $r->id }}" @selected($u->role_id === $r->id)>{{ $r->name }}</option>@endforeach
                        </select>
                    </form>
                </td>
                <td>{{ collect($u->apps())->pluck('name')->implode(', ') ?: '—' }}</td>
                <td>{{ $u->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                <td>@include('admin.partials.pill', $u->active ? ['text' => 'Active', 'tone' => 'ok'] : ['text' => 'Disabled'])</td>
                <td><a class="btn sm ghost" href="{{ route('sheriff.users.history', $u) }}">History</a>
                    @unless ($u->is(auth()->user()))
                    <form method="post" action="{{ route('sheriff.users.update', $u) }}" class="inline">@csrf @method('patch')
                        <input type="hidden" name="active" value="{{ $u->active ? 0 : 1 }}"><button class="btn sm ghost">{{ $u->active ? 'Disable' : 'Enable' }}</button>
                    </form>
                @endunless</td>
            </tr>
        @endforeach</tbody>
    </table></div></div>

    <template id="add-user-form">
        <form class="modal" method="post" action="{{ route('sheriff.users.store') }}" role="dialog" aria-modal="true">
            @csrf
            <h2>Add User</h2>
            <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px">
                <div class="field"><label for="nu-name">Name</label><input id="nu-name" name="name" required></div>
                <div class="field"><label for="nu-email">Email</label><input id="nu-email" name="email" type="email" required></div>
                <div class="field"><label for="nu-role">Role</label><select id="nu-role" name="role_id">@foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                <div class="field"><label for="nu-pw">Temporary Password</label><input id="nu-pw" name="password" type="password" minlength="12" required autocomplete="new-password"><span class="help">At least 12 characters.</span></div>
            </div>
            <div class="actions" style="justify-content:flex-end;margin-top:16px"><button type="button" class="btn ghost" data-x>Cancel</button><button class="btn cyan">Add User</button></div>
        </form>
    </template>
@endsection
