-- Run this in Supabase SQL editor.

create table if not exists public.roles (
  id uuid primary key default gen_random_uuid(),
  email text not null unique,
  role text not null check (role in ('student', 'ta_professor')),
  created_at timestamptz not null default now()
);

create unique index if not exists roles_email_lower_idx on public.roles (lower(email));

alter table public.roles enable row level security;

drop policy if exists "roles_select_own_or_ta" on public.roles;
create policy "roles_select_own_or_ta"
on public.roles
for select
to authenticated
using (
  lower(email) = lower(auth.jwt() ->> 'email')
  or exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'ta_professor'
  )
);

drop policy if exists "roles_insert_ta_only" on public.roles;
create policy "roles_insert_ta_only"
on public.roles
for insert
to authenticated
with check (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'ta_professor'
  )
);

drop policy if exists "roles_update_ta_only" on public.roles;
create policy "roles_update_ta_only"
on public.roles
for update
to authenticated
using (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'ta_professor'
  )
)
with check (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'ta_professor'
  )
);

drop policy if exists "roles_delete_ta_only" on public.roles;
create policy "roles_delete_ta_only"
on public.roles
for delete
to authenticated
using (
  exists (
    select 1
    from public.roles r
    where lower(r.email) = lower(auth.jwt() ->> 'email')
      and r.role = 'ta_professor'
  )
);

-- Seed initial TA/professor allowlist
insert into public.roles (email, role)
values
  ('cdd9sb@virginia.edu', 'ta_professor'),
  ('xfw9vp@virginia.edu', 'ta_professor'),
  ('uhu5nr@virginia.edu', 'ta_professor'),
  ('khg5bj@virginia.edu', 'student'),
  ('studenttest@virginia.edu', 'student')
on conflict (email) do update
set role = excluded.role;
