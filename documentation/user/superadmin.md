# Superadmin back office

The central back office is available at `/admin`. Superadmin accounts are
separate from tenant users and are stored in the central database. No default
superadmin credentials are seeded.

Run `php artisan migrate` against the central database, then create the first
account interactively:

```sh
php artisan superadmin:create
```

The command prompts for a name, email, and password. Passwords must be at least
12 characters and are stored as hashes. Sign in at `/admin/login` to review
organizations and activate or suspend tenant access. Suspending an organization
does not delete its database or tenant data.
