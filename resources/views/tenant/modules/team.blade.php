@php($urls = app(\App\Tenancy\TenantUrlGenerator::class))
<x-layouts.app
    :title="__('Team').' - '.$organization->name"
    :heading="__('Team')"
    :subheading="$canManage ? __('Manage people in your organization and their roles.') : __('People in your organization and their roles.')"
    :badge="$organization->name"
    :back="$urls->to($organization)"
    :backLabel="__('Dashboard')"
>
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Role') }}</th>
                    @if ($canManage)
                        <th scope="col">{{ __('Manage') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="font-medium text-slate-900">{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>
                            @if ($canManage)
                                <form method="POST" action="{{ $urls->to($organization, 'team/users/'.$member->id.'/role') }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="role-{{ $member->id }}" class="sr-only">{{ __('Role') }}</label>
                                    <select id="role-{{ $member->id }}" name="role" class="field-input !w-auto !py-1.5">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->key }}" @selected($member->hasRole($role->key))>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-secondary !py-1.5">{{ __('Save') }}</button>
                                </form>
                            @else
                                @foreach ($member->roles as $role)
                                    <span class="badge bg-indigo-50 text-indigo-700">{{ $role->name }}</span>
                                @endforeach
                            @endif
                        </td>
                        @if ($canManage)
                            <td>
                                <form method="POST" action="{{ $urls->to($organization, 'team/users/'.$member->id.'/password') }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label for="password-{{ $member->id }}" class="sr-only">{{ __('New password') }}</label>
                                    <input id="password-{{ $member->id }}" type="password" name="password" minlength="12" required autocomplete="new-password" placeholder="{{ __('New password') }}" class="field-input !w-44 !py-1.5">
                                    <button type="submit" class="btn-secondary !py-1.5">{{ __('Set password') }}</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManage ? 4 : 3 }}" class="text-center text-slate-500">{{ __('No team members are available.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($canManage)
        <form method="POST" action="{{ $urls->to($organization, 'team/users') }}" class="card mt-8 grid gap-5 p-6 sm:grid-cols-2">
            @csrf
            <h2 class="text-sm font-semibold text-slate-900 sm:col-span-2">{{ __('Add a user') }}</h2>
            <div>
                <label for="new-name" class="field-label">{{ __('Name') }}</label>
                <input id="new-name" name="name" type="text" value="{{ old('name') }}" required class="field-input">
            </div>
            <div>
                <label for="new-email" class="field-label">{{ __('Email') }}</label>
                <input id="new-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="off" class="field-input">
            </div>
            <div>
                <label for="new-role" class="field-label">{{ __('Role') }}</label>
                <select id="new-role" name="role" class="field-input">
                    @foreach ($roles as $role)
                        <option value="{{ $role->key }}" @selected(old('role', 'member') === $role->key)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="new-password" class="field-label">{{ __('Initial password') }}</label>
                <input id="new-password" name="password" type="password" minlength="12" required autocomplete="new-password" class="field-input">
                <p class="field-hint">{{ __('At least 12 characters. Share it securely; users who sign in with SSO do not use it.') }}</p>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="btn">{{ __('Create user') }}</button>
            </div>
        </form>
    @endif
</x-layouts.app>
