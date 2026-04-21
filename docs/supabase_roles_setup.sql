-- Run this in Supabase SQL editor.

drop table if exists public.roles cascade;

create table if not exists public.roles (
  id uuid primary key default gen_random_uuid(),
  email text not null unique,
  role text not null check (role in ('student', 'ta', 'professor')),
  created_at timestamptz not null default now()
);

create unique index if not exists roles_email_lower_idx on public.roles (lower(email));

alter table public.roles enable row level security;

drop policy if exists "roles_select_own_or_staff" on public.roles;
create policy "roles_select_own_or_staff"
on public.roles
for select
to authenticated
using (
  lower(email) = lower(auth.jwt() ->> 'email')
  or exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role in ('ta', 'professor')
  )
);

drop policy if exists "roles_insert_prof_only" on public.roles;
create policy "roles_insert_prof_only"
on public.roles
for insert
to authenticated
with check (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'professor'
  )
);

drop policy if exists "roles_update_prof_only" on public.roles;
create policy "roles_update_prof_only"
on public.roles
for update
to authenticated
using (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'professor'
  )
)
with check (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'professor'
  )
);

drop policy if exists "roles_delete_prof_only" on public.roles;
create policy "roles_delete_prof_only"
on public.roles
for delete
to authenticated
using (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'professor'
  )
);

-- Seed initial professor and TA allowlist (align with useAuthProfile.js defaults)
insert into public.roles (email, role)
values
  ('cdd9sb@virginia.edu', 'professor'),
  ('amm8km@virginia.edu', 'professor'),
  ('xfw9vp@virginia.edu', 'ta'),
  ('uhu5nr@virginia.edu', 'ta'),
  ('amq6tv@virginia.edu', 'ta'),
  ('studenttest@virginia.edu', 'student')
on conflict (email) do update
set role = excluded.role;
