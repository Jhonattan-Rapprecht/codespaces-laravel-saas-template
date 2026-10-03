<x-layouts.app
    :title="__('Team').' - '.$organization->name"
    :heading="__('Team')"
    :subheading="__('People in your organization and their roles.')"
    :badge="$organization->name"
    :back="app(\App\Tenancy\TenantUrlGenerator::class)->to($organization)"
    :backLabel="__('Dashboard')"
>
    <div class="card overflow-hidden">
        <table class="data-table">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Roles') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @foreach ($member->roles as $role)
                                <span class="badge bg-indigo-50 text-indigo-700">{{ $role->name }}</span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-500">{{ __('No team members are available.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
