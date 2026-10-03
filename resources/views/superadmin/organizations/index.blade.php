<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Organizations') }} - {{ config('app.name') }}</title>
</head>
<body>
    <header>
        <h1>{{ __('Superadmin back office') }}</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">{{ __('Sign out') }}</button>
        </form>
    </header>

    <main>
        <h2>{{ __('Organizations') }}</h2>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th scope="col">{{ __('Organization') }}</th>
                    <th scope="col">{{ __('Slug') }}</th>
                    <th scope="col">{{ __('Tenant domains') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr>
                        <td>{{ $organization->name }}</td>
                        <td>{{ $organization->slug }}</td>
                        <td>{{ $organization->domains_count }}</td>
                        <td>{{ ucfirst($organization->status) }}</td>
                        <td>
                            <a href="{{ route('admin.organizations.saml.edit', $organization) }}">{{ __('Configure SAML') }}</a>
                            <form method="POST" action="{{ route('admin.organizations.status', $organization) }}">
                                @csrf
                                @method('PATCH')
                                <label for="status-{{ $organization->getKey() }}">{{ __('Status') }}</label>
                                <select id="status-{{ $organization->getKey() }}" name="status">
                                    <option value="active" @selected($organization->status === 'active')>{{ __('Active') }}</option>
                                    <option value="suspended" @selected($organization->status === 'suspended')>{{ __('Suspended') }}</option>
                                </select>
                                <button type="submit">{{ __('Save') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">{{ __('No organizations have been provisioned.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $organizations->links() }}
    </main>
</body>
</html>
