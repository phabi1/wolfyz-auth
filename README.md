# Wolf Auth

Standalone OpenID Connect provider written in plain PHP (no framework such as
Symfony or Laravel). It only relies on two small libraries:

- [`league/oauth2-server`](https://oauth2.thephpleague.com/) — implements the
  OAuth2 Authorization Code (+ PKCE) and Refresh Token grants.
- [`firebase/php-jwt`](https://github.com/firebase/php-jwt) — used to sign the
  OIDC `id_token`.

Everything else (routing, HTTP request/response, DB access, controllers,
session-based login page) is hand-written under [`src/`](src).

## Endpoints

| Method | Path                                   | Purpose                              |
|--------|-----------------------------------------|---------------------------------------|
| GET/HEAD | `/`                                   | Public account homepage               |
| GET    | `/.well-known/openid-configuration`     | OIDC discovery document               |
| GET    | `/.well-known/jwks.json`                | Public signing key (JWKS)             |
| GET    | `/authorize`                            | Authorization endpoint (code + PKCE)  |
| GET/POST | `/signin`                              | Login form                            |
| GET/POST | `/auth/password/forgot`               | Request a password reset link         |
| GET/POST | `/auth/password/reset`                | Set a new password using a reset token |
| GET    | `/logout`                               | Clears the session (end_session)      |
| POST   | `/token`                                | Token endpoint                        |
| GET/POST | `/oidc/userinfo`                      | OIDC UserInfo endpoint                |

### Homepage

The root route (`auth-home`) renders a responsive, translated Tailwind homepage.
Guests can sign in, create an account or request a password reset. Signed-in
users see links to their profile and sign out instead. The page does not consume
any pending OAuth authorization request and is served with `Cache-Control:
no-store` because its links depend on the session. Other methods return 405.

On an existing installation, remove the generated `cache/services.php` so the
new `auth.home` controller registration is loaded.

Run the homepage tests with:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php --do-not-cache-result tests/Auth/HomeControllerTest.php
```

### Password reset

The sign-in page links to the forgot-password form. Both forms require a session
CSRF token on POST. Reset links expire after one hour, are stored only as SHA-256
hashes and can be used once. A successful reset invalidates all password-reset
tokens for that user; it does not automatically log the user in. Passwords must
contain 8 to 72 bytes (the existing password encoder uses bcrypt).

Apply the `CREATE TABLE IF NOT EXISTS auth_user_token` statement from
`db/schema.sql` on an existing installation. Remove the generated
`cache/services.php` and `cache/entities.php` so the next request rebuilds the
service and entity definitions.

Email delivery uses the existing `mailer` service (`App\Core\Mail\Mailer`).
Configure `mail.from` and `mail.smtp` in the active parameters file, following
`config/parameters.example.php`: `MAIL_FROM_EMAIL`, `MAIL_FROM_NAME`, `SMTP_HOST`,
`SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, and `SMTP_ENCRYPTION`.
The reset email has subject, HTML and text templates under
`views/mails/auth/password/`.

Without a configured sender and SMTP server, valid forgot-password submissions
return HTTP 503 without creating a token or claiming that an email was sent.
No account lookup occurs in this case. Failed delivery also revokes the newly
created token and returns HTTP 503.
Links use the configured `oauth2.issuer` (never the incoming Host header).
With delivery enabled, known and unknown emails receive the same success message.
Requests are limited to one per minute per session; add IP-based rate limiting at
the reverse proxy before exposing this publicly. Delivery failures are logged
without including email addresses or tokens.

Run the password-reset tests with:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php --do-not-cache-result tests/Auth
```

### UserInfo

Send the access token (not the ID token) in the `Authorization: Bearer` header:

```sh
curl -H "Authorization: Bearer $ACCESS_TOKEN" \
  https://your-issuer.example/oidc/userinfo
```

The endpoint validates the token's signature, validity period and revocation
status, then loads the current user. The `openid` scope is required. The JSON
response always includes `sub`, matching the subject of the ID token:

- `profile` adds `name`, and `given_name` / `family_name` when available.
- `email` adds `email` and the boolean `email_verified` from the stored user
  verification status. An email is unverified unless explicitly marked as
  verified. The ID token uses the same status when the `email` scope is granted.

Missing, invalid, expired or revoked tokens, tokens without a user, and deleted
users return HTTP 401 with a Bearer challenge. Missing `openid` returns HTTP 403
with `insufficient_scope`. Other HTTP methods return 405. Responses are not cached.

Run the focused UserInfo tests with:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php --do-not-cache-result tests/Oidc
```

When updating service definitions in an existing installation, remove the
generated `cache/services.php` file so the next request rebuilds it.

## Local setup

Inside Docker, `docker-entrypoint.sh` runs `composer install`, waits for MySQL
and applies `db/schema.sql` automatically. It also generates the RSA signing
keypair (`OAUTH2_PRIVATE_KEY` / matching `-public.key`) on first boot if it
doesn't already exist.

## Configuration

### Translations

Translation keys stay in English in controllers and templates. French messages
are stored in `translations/default.fr_FR.php`, the default catalogue loaded by
the existing translator (`fr_FR`). This includes form labels, errors, profile
links and password-reset email subjects and bodies. Placeholders such as
`{siteName}` must remain unchanged in the catalogue values.

Run the focused catalogue and placeholder tests with:

```sh
php vendor/bin/phpunit --bootstrap vendor/autoload.php --do-not-cache-result tests/Translation
```

### Frontend assets (Vite)

Vite requires Node.js 20.19+ or 22.12+; Compose uses Node.js 22.
The entry point is `assets/js/index.js`. Both web layouts load it through the
`vite` view helper; email templates do not load frontend scripts.

From the sibling `docker/` directory, start the development server with:

```sh
docker compose up -d auth auth-vite
```

Compose installs locked dependencies with `npm ci` in the dedicated
`auth_node_modules` volume and mounts the complete auth project, including the
Vite configuration. Traefik must be running on `traefik_webgateway` with HTTPS
and a locally trusted certificate for both hosts:

- Application: `https://auth-wolfzy.docker.localhost`
- Vite assets and WebSocket HMR: `https://auth-vite-wolfzy.docker.localhost`

`VITE_DEV_SERVER_URL` must match in the PHP and Vite containers.
`VITE_APP_ORIGIN` restricts asset CORS to the application origin.
`VITE_USE_POLLING=true` enables file watching for Docker/WSL bind mounts.
After adding the view helper on an existing installation, remove the generated
`cache/services.php` file to rebuild the service definitions.

Without Docker, run `npm ci && npm run dev` from the auth directory. The defaults
use `http://localhost:5173` for Vite and `http://localhost` for PHP; set
`VITE_APP_ORIGIN` if PHP uses a different origin and set `VITE_DEV_SERVER_URL`
in both processes if Vite uses a different public URL.

For production, run `npm ci && npm run build` before deploying with
`APP_ENV=production`. Deploy `public/dist/`, including `manifest.json`, alongside
the PHP application. Production pages load hashed files under `/dist/` and do
not require a running Vite server. Missing manifests or entries report an
explicit error.

The GitHub deployment workflow sets up Node.js 22, installs locked frontend
dependencies with `npm ci --include=dev` (including the Tailwind build plugin),
and runs `npm run build` before synchronizing files. A failed install or build
stops deployment. Rsync transfers `public/dist/` and its manifest, but excludes
`node_modules/`; the remote server does not need Node.js to serve the assets.

Run `npm run test:vite` to check the Docker dev-server settings, served assets,
CORS restrictions and production output configuration.

### Tailwind CSS

Tailwind CSS 4 is compiled by `@tailwindcss/vite`, without a CDN or a separate
CSS watcher. `assets/js/index.js` imports `assets/css/style.css`; the PHP Vite
helper loads the generated stylesheet from the production manifest.

Use complete, literal utility classes in the PHP web templates under
`views/auth/`, `views/layouts/` and `views/user/`, or in `assets/js/`.
These directories are explicitly scanned with `@source`; email templates are
excluded and keep their existing styling. Do not construct class names by
concatenating PHP strings, because Tailwind cannot detect them.

The auth forms and profile page use responsive cards and visible keyboard-focus
styles. Form actions, fields, validation and CSRF tokens remain unchanged.
After updating dependencies, restart the Compose `auth-vite` service so its
startup `npm ci` installs Tailwind. `npm run test:vite` also checks CSS generation
in development and production.

Configuration is read from `config/parameters.<APP_ENV>.php` (see
`config/parameters.example.php`), all values coming from environment
variables already declared in `docker/docker-compose.yml`:
`DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`,
`OAUTH2_ISSUER`, `OAUTH2_PRIVATE_KEY`, `OAUTH2_PASSPHRASE`,
`OAUTH2_ENCRYPTION_KEY`.

## Notes

- The `club` Angular app is pre-registered as a public (PKCE) client with
  redirect URI `http://localhost:4200` — see `db/schema.sql`.
- The OIDC `nonce` is carried from `/authorize` to the issued `id_token` via
  `App\OAuth\NonceContext`, a request-scoped static holder (safe because one
  PHP process handles exactly one HTTP request).
