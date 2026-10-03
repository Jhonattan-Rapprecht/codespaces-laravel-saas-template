<x-layouts.app
    :title="__('Organizations').' - '.config('app.name')"
    :heading="__('Organizations')"
    :subheading="__('Manage tenants, sign-in and modules.')"
    :badge="__('Superadmin back office')"
>
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">{{ __('Sign out') }}</button>
        </form>
    </x-slot:actions>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col">{{ __('Organization') }}</th>
                    <th scope="col">{{ __('Slug') }}</th>
                    <th scope="col">{{ __('Tenant domains') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($organizations as $organization)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $organization->name }}</td>
                        <td><code class="text-xs">{{ $organization->slug }}</code></td>
                        <td>{{ $organization->domains_count }}</td>
                        <td>
                            <span class="badge {{ $organization->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ ucfirst($organization->status) }}</span>
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.organizations.saml.edit', $organization) }}" class="btn-secondary">{{ __('Configure SAML') }}</a>
                                <a href="{{ route('admin.organizations.modules.edit', $organization) }}" class="btn-secondary">{{ __('Configure modules') }}</a>
                                <form method="POST" action="{{ route('admin.organizations.status', $organization) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="status-{{ $organization->getKey() }}" class="sr-only">{{ __('Status') }}</label>
                                    <select id="status-{{ $organization->getKey() }}" name="status" class="field-input !w-auto !py-1.5">
                                        <option value="active" @selected($organization->status === 'active')>{{ __('Active') }}</option>
                                        <option value="suspended" @selected($organization->status === 'suspended')>{{ __('Suspended') }}</option>
                                    </select>
                                    <button type="submit" class="btn !py-1.5">{{ __('Save') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500">{{ __('No organizations have been provisioned.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $organizations->links() }}</div>
</x-layouts.app>
