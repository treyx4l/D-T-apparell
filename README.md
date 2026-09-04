# D&T Apparels

Premium apparel e-commerce platform built with PHP, Tailwind CSS, and Supabase.

## Requirements

- PHP 8.1+
- Composer
- Node.js 18+ (for Tailwind CSS build)
- Supabase project
- Apache with `mod_rewrite` (or equivalent Nginx rules)

## Setup

### 1. Install dependencies

```bash
composer install
npm install
npm run build:css
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` with your Supabase credentials:

| Variable | Description |
|----------|-------------|
| `SUPABASE_URL` | Project URL from Supabase dashboard |
| `SUPABASE_ANON_KEY` | Public anon key |
| `SUPABASE_SERVICE_ROLE_KEY` | Service role key (server-only) |
| `APP_ENV` | `development` or `production` |
| `BASE_URL` | App URL e.g. `http://localhost:8000` (or leave empty for relative paths) |
| `SESSION_SECRET` | Random 32+ character string |
| `ADMIN_PATH` | `dt-admin` (hidden admin route) |
| `GOOGLE_OAUTH_REDIRECT_URL` | `{YOUR_SITE}/auth/callback.php` |
| `STORAGE_BUCKET` | Supabase Storage bucket name (default: `product-images`) |

### 3. Set up Supabase

1. Create a new Supabase project
2. Run [`supabase/schema.sql`](supabase/schema.sql) in the SQL Editor
3. Run [`supabase/migrations/002_storage_quantity.sql`](supabase/migrations/002_storage_quantity.sql) for storage policies, quantity, and stock triggers
4. Run [`supabase/migrations/003_fix_product_image_urls.sql`](supabase/migrations/003_fix_product_image_urls.sql) if product images return HTTP 400
5. Enable **Email/Password** auth and turn on **Confirm email** under Authentication → Providers
6. Enable **Google** OAuth and configure **Authentication → URL Configuration**:
   - **Site URL:** `http://localhost:8000` (dev) or `https://yourdomain.com` (prod)
   - **Redirect URLs** (add every URL you use):
     - `http://localhost:8000/auth/callback.php`
     - `http://localhost:8000/auth/verify-email.php`
     - `https://yourdomain.com/auth/callback.php`
     - `https://yourdomain.com/auth/verify-email.php`
   
   If the callback URL is missing, Google sign-in lands on `http://localhost:8000/#access_token=...` and sign-in will fail. The app includes a recovery redirect, but adding the URLs above is required.
6. Create a Storage bucket named `product-images` (public read) if not created by the migration
7. Promote an admin user (must sign in with Google at `/dt-admin/`):

```sql
UPDATE public.profiles SET role = 'admin' WHERE id = '<user-uuid>';
UPDATE auth.users
SET raw_user_meta_data = raw_user_meta_data || '{"role":"admin"}'::jsonb
WHERE id = '<user-uuid>';
```

### 4. Deploy

Point your web server document root to this directory. Ensure PHP sessions work and HTTPS is enabled in production.

## Project Structure

```
index.php          # Homepage with hero carousel
store.php          # Full store with cart & checkout
auth/              # Customer login, signup, OAuth callback
dt-admin/          # Hidden admin panel (not linked in public nav)
includes/          # PHP config, auth, CSRF, layouts
api/               # Session bridge endpoints
js/                # Supabase client & page scripts
css/app.css        # Compiled Tailwind (run npm run build:css)
supabase/          # Database schema & RLS policies
```

## Admin Access

The admin panel is **not linked** from the public site. Access it directly at:

```
https://yourdomain.com/dt-admin/
```

**Admin sign-in is Google OAuth only.** The Google account must have `role = 'admin'` in Supabase (`profiles` table and user metadata).

## Customer Email Verification

- Email/password sign-ups receive a verification link
- Users are redirected to `/auth/check-email.php` with instructions to check **inbox and spam**
- Unverified users cannot sign in until they click the verification link (`/auth/verify-email.php`)
- Google sign-in users are verified by Google and signed in directly

## Product Images & Inventory

- Admins upload product photos to **Supabase Storage** from the dashboard (no external URLs required)
- Each product has a **quantity** field; stock decrements automatically when orders are placed
- Items with quantity `0` are marked **sold out** and hidden from the store
- **Only sold-out products can be deleted** (removes DB record and storage image)

## Security Features

- HTTP-only, SameSite=Strict session cookies
- CSRF protection on form submissions
- Input sanitization and output escaping
- Server-side RBAC for admin routes
- `.env` secrets never exposed to the browser
- Row Level Security (RLS) on all Supabase tables

## Development

Watch Tailwind CSS for changes:

```bash
npm run watch:css
```

Run locally with PHP built-in server:

```bash
php -S localhost:8000
```

## License

Proprietary — D&T Apparels
