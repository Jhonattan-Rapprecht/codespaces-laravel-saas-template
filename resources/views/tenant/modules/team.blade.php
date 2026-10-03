<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Team') }} - {{ $organization->name }}</title>
</head>
<body>
    <header>
        <h1>{{ __('Team') }}</h1>
        <a href="{{ app(\App\Tenancy\TenantUrlGenerator::class)->to($organization) }}">{{ __('Dashboard') }}</a>
    </header>

    <main>
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Roles') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->roles->pluck('name')->join(', ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">{{ __('No team members are available.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
