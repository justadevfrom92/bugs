@extends('admin.layouts.app')

@section('crumb', $site->exists ? 'Edit Site' : 'New Site')

@section('content')
    @include('admin.partials.page-head', ['title' => $site->exists ? 'Edit Site: '.$site->name : 'New Site',
        'sub' => 'Point the domain\'s DNS at this server; Laravel serves each site\'s pages by the host name the visitor uses.'])

    <form method="post" action="{{ $site->exists ? route('lando.sites.update', $site) : route('lando.sites.store') }}" class="panel"><div class="panel-body">
        @csrf @if ($site->exists) @method('put') @endif
        <div class="form-grid">
            <label for="name">Site Name</label><input id="name" name="name" required value="{{ old('name', $site->name) }}">
            <label for="domain">Domain</label><input id="domain" name="domain" required class="mono" value="{{ old('domain', $site->domain) }}" placeholder="www.example.com">
            <label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $site->phone) }}" placeholder="Leave blank to use the brand phone">
            <label for="address_box_title">Address Box Title</label><input id="address_box_title" name="address_box_title" value="{{ old('address_box_title', $site->address_box_title) }}" placeholder="Heading of the zip code box; blank: “Find your plan”">
            <label for="status">Status</label>
            <select id="status" name="status">@foreach (['Active', 'Inactive'] as $s)<option @selected(old('status', $site->status) === $s)>{{ $s }}</option>@endforeach</select>
        </div>
        <div class="actions" style="margin-top:20px"><button class="btn cyan">Save Site</button><a class="btn ghost" href="{{ route('lando.sites.index') }}">Cancel</a></div>
    </div></form>
@endsection
