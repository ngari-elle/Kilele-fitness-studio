# Kilele Fitness signup setup

The signup form saves records to a Supabase PostgreSQL database through the Supabase REST API. PHP 8.0 or later with the cURL extension is required.

## Configure Supabase

1. In the Supabase SQL Editor, run `database/schema.sql` to create the `signups` table and its public insert policy.
2. Copy the project URL and the project's anon/public API key from Supabase into `.env`:

   ```dotenv
   SUPABASE_URL=https://your-project-ref.supabase.co
   SUPABASE_ANON_KEY=your-supabase-anon-key
   ```

The anon key is intended for public client access. Keep the service role key out of this project; the table's row-level security policy permits inserts using the anon key.

## Run locally

From this directory, start PHP's built-in development server:

```text
php -S localhost:8000
```

Open `http://localhost:8000/signup.html`. Do not open the HTML file directly, because PHP must run on a web server to process submissions.

The `.env` file is ignored by Git. Set `SUPABASE_URL` and `SUPABASE_ANON_KEY` as environment variables on production hosts, and ensure the PHP cURL extension is enabled.