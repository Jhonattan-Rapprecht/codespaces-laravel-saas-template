# Architecture

The application has a central Laravel application database and one MySQL database
per organization. The central database holds organizations, domains, and future
superadministration, billing, module, and SAML configuration records. Tenant
databases hold users, authorization roles, layouts, and tenant module data.

`stancl/tenancy` v3.10 is installed through Composer and accepted Laravel 12's
framework constraint during installation. `Organization` is its tenant model.
Tenant routes initialize the organization before Laravel resolves tenant models.

The central database connection is the normal `DB_*` connection. Redis is
configured through Laravel's normal `REDIS_*` settings and tenant-aware cache
keys are enabled by the tenancy bootstrapper.
