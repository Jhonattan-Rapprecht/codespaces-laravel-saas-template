<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSamlConnection;
use App\Models\Role;
use App\Models\SamlAuthenticationRequest;
use App\Models\User;
use App\Saml\SamlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OneLogin\Saml2\Error as SamlError;
use OneLogin\Saml2\ValidationError;
use Symfony\Component\HttpFoundation\Response;

class SamlController extends Controller
{
    public function login(SamlService $saml): RedirectResponse
    {
        [$organization, $connection] = $this->activeConnection();
        $relayState = Str::random(64);
        $auth = $saml->auth($organization, $connection);
        $redirectUrl = $auth->login($relayState, [], false, false, true);
        $requestId = $auth->getLastRequestID();

        abort_unless(is_string($redirectUrl) && $redirectUrl !== '' && is_string($requestId) && $requestId !== '', 500);

        SamlAuthenticationRequest::query()->create([
            'state_hash' => hash('sha256', $relayState),
            'organization_id' => $organization->getKey(),
            'request_id' => $requestId,
            'expires_at' => now()->addMinutes(5),
        ]);

        return redirect()->away($redirectUrl);
    }

    public function assertionConsumer(Request $request, SamlService $saml): RedirectResponse
    {
        [$organization, $connection] = $this->activeConnection();

        $validated = $request->validate([
            'SAMLResponse' => ['required', 'string', 'max:1048576'],
            'RelayState' => ['required', 'string', 'size:64'],
        ]);

        $stateHash = hash('sha256', $validated['RelayState']);
        $requestId = DB::connection(config('tenancy.database.central_connection'))->transaction(function () use ($organization, $stateHash): string {
            $state = SamlAuthenticationRequest::query()
                ->where('state_hash', $stateHash)
                ->where('organization_id', $organization->getKey())
                ->lockForUpdate()
                ->first();

            abort_unless(
                $state && $state->consumed_at === null && $state->expires_at->isFuture(),
                419,
                __('The SAML sign-in request has expired or was already used.'),
            );

            $state->consumed_at = now();
            $state->save();

            return $state->request_id;
        });

        $auth = $saml->auth($organization, $connection);
        $_POST['SAMLResponse'] = $validated['SAMLResponse'];

        try {
            $auth->processResponse($requestId);
        } catch (SamlError|ValidationError $exception) {
            Log::warning('Rejected malformed SAML response.', [
                'organization_id' => $organization->getKey(),
                'error_code' => $exception->getCode(),
            ]);

            abort(403, __('The identity provider response could not be verified.'));
        } finally {
            unset($_POST['SAMLResponse']);
        }

        if ($auth->getErrors() !== []) {
            Log::warning('Rejected invalid SAML response.', [
                'organization_id' => $organization->getKey(),
                'errors' => $auth->getErrors(),
            ]);

            abort(403, __('The identity provider response could not be verified.'));
        }

        $attributes = $auth->getAttributes();
        $email = $this->firstAttribute($attributes, $connection->email_attribute);
        $name = $this->firstAttribute($attributes, $connection->name_attribute);
        $subject = $auth->getNameId();

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($subject) || trim($subject) === '' || mb_strlen($subject) > 255) {
            Log::warning('Rejected SAML identity with missing required attributes.', [
                'organization_id' => $organization->getKey(),
            ]);

            abort(403, __('The identity provider response is missing required identity attributes.'));
        }

        $user = User::query()->where('saml_subject', $subject)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if ($user && $user->saml_subject !== null) {
                abort(403, __('This email address is linked to a different SAML identity.'));
            }

            if (! $user) {
                $user = new User([
                    'name' => is_string($name) && trim($name) !== '' ? trim($name) : $email,
                    'email' => $email,
                    'password' => Str::random(64),
                ]);
            }

            $user->saml_subject = $subject;
            $user->save();

            if ($user->roles()->doesntExist()) {
                $memberRole = Role::query()->where('key', User::ROLE_MEMBER)->first();
                $memberRole && $user->roles()->attach($memberRole->id);
            }
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->to($saml->endpoint($organization, ''));
    }

    public function metadata(SamlService $saml): Response
    {
        [$organization, $connection] = $this->activeConnection();
        $metadata = $saml->auth($organization, $connection)->getSettings()->getSPMetadata();

        return response($metadata, 200, ['Content-Type' => 'application/samlmetadata+xml']);
    }

    /**
     * @return array{Organization, OrganizationSamlConnection}
     */
    private function activeConnection(): array
    {
        $organization = Organization::query()->findOrFail(tenant('id'));
        $connection = $organization->samlConnection;

        abort_unless($connection && $connection->enabled, 404);

        return [$organization, $connection];
    }

    private function firstAttribute(array $attributes, string $key): ?string
    {
        $value = $attributes[$key] ?? null;
        $value = is_array($value) ? ($value[0] ?? null) : $value;

        return is_string($value) ? trim($value) : null;
    }
}
