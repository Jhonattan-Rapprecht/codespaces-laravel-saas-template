-- Development only: the application creates one database and user per tenant,
-- so the app user needs administrative privileges on this local MySQL instance.
GRANT ALL PRIVILEGES ON *.* TO 'laravel'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
