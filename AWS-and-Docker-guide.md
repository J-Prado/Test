# AWS & Docker — beginner guide (optional for the QA case)

> **Read this first.** Neither AWS nor Docker is required to complete or demo the
> QA case. The Chime tests mock AWS (no credentials needed) and the app runs on
> SQLite (no Docker needed). Finish the case with `qa-case-opcionyo/INSTALL.md`
> and `RUN.md` first. Come back here only to *learn* these tools or to go further.
>
> This guide is written for **Windows 11** (your machine), beginner level, with a
> verification step after everything so you know it worked.

---

# PART 1 — Docker

## What Docker is (in one paragraph)

Docker runs software inside isolated "containers" — like tiny, disposable virtual
computers. Instead of installing MySQL directly on Windows, you run a MySQL
*container*: it starts in seconds, can't mess up the rest of your PC, and you can
delete it cleanly. In this project, Docker is useful for one concrete thing:
running a **real MySQL** (the database OpcionYo uses in production) so you can test
against it instead of SQLite.

## Step 1 — Turn on WSL2 (Docker needs it)

WSL2 = "Windows Subsystem for Linux", the engine Docker Desktop uses.

1. Open **PowerShell as Administrator** (right-click Start → *Terminal (Admin)*).
2. Run:
   ```powershell
   wsl --install
   ```
3. **Restart your computer** when it finishes.

> If it says WSL is already installed, that's fine — move on.

## Step 2 — Install Docker Desktop

1. Download the installer: <https://www.docker.com/products/docker-desktop/>
   (click *Download for Windows – AMD64* unless you have an ARM PC).
2. Run the installer. On the options screen, **keep "Use WSL 2 instead of
   Hyper-V" checked**. Finish, and restart if it asks.
3. Launch **Docker Desktop** from the Start menu.
4. Accept the terms. Wait until the whale icon in the bottom-left says
   **"Engine running"** (green). This can take a minute the first time.

> Docker Desktop is **free** for personal use, education, and small businesses.

## Step 3 — Verify Docker works

Open a **normal** PowerShell (not admin) and run:

```powershell
docker --version
docker run hello-world
```

You should see a version number, then a friendly "Hello from Docker!" message.
If you see that, Docker is working. ✅

> If `docker` isn't recognized: make sure Docker Desktop is **open and running**
> (the whale icon). Close and reopen your terminal.

## Step 4 — (Optional) Use Docker with THIS project: run real MySQL

This is the payoff — run the app against MySQL like production, no MySQL install.

1. In the project root (`qa-case-opcionyo/`), create a file named
   **`docker-compose.yml`** with exactly this content:

   ```yaml
   services:
     mysql:
       image: mysql:8.0
       environment:
         MYSQL_DATABASE: opcionyo
         MYSQL_ROOT_PASSWORD: secret
       ports:
         - "3306:3306"
       volumes:
         - dbdata:/var/lib/mysql

   volumes:
     dbdata:
   ```

2. Start MySQL in the background:
   ```powershell
   docker compose up -d
   ```
   (First run downloads the MySQL image — give it a minute.)

3. Check it's running:
   ```powershell
   docker compose ps
   ```
   You should see the `mysql` service with state `running` / `healthy`.

4. Point the app at MySQL. In your `.env`, change these lines:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=opcionyo
   DB_USERNAME=root
   DB_PASSWORD=secret
   ```

5. Run migrations against MySQL and try the app:
   ```powershell
   php artisan migrate:fresh --seed
   php artisan serve
   ```

6. When you're done, stop the container (data is kept):
   ```powershell
   docker compose down
   ```
   To also delete the stored data: `docker compose down -v`.

> **Note for the case:** your test suite (`php artisan test`) still uses in-memory
> SQLite via `phpunit.xml`, on purpose — fast and zero-dependency. Switching `.env`
> to MySQL is for running the *app* and manual checks against a prod-like DB. This
> is also the setup you'd use to properly reproduce the double-booking race
> (BUG-001) with two parallel connections.

## Handy Docker commands

| Command | What it does |
|--------|--------------|
| `docker ps` | List running containers |
| `docker compose up -d` | Start services (from `docker-compose.yml`) in background |
| `docker compose down` | Stop and remove them |
| `docker compose logs -f mysql` | Watch MySQL logs live |
| `docker images` | List downloaded images |
| `docker system prune` | Free up space (removes unused stuff) |

---

# PART 2 — AWS (for real AWS Chime video)

> **You do NOT need this for the case.** The Chime tests use a mocked AWS SDK.
> Do this only if you want to see a real Chime meeting get created, or to learn
> AWS basics for the interview.
>
> ⚠️ **Creating an AWS account requires a credit card**, and Chime usage can cost
> money. Set a billing alarm (Step 5) and you'll be fine for light learning.

## What AWS / Chime is (one paragraph)

AWS is Amazon's cloud. **Chime SDK** is its building-block for video/audio calls —
your server asks Chime to "create a meeting" and "add an attendee", Chime returns
credentials, and your app (web/mobile) uses those to join the actual audio/video
session. Your **server code** (the part we test) just orchestrates meetings and
attendees; the media itself flows through Chime.

## Step 1 — Create an AWS account

1. Go to <https://aws.amazon.com/> → **Create an AWS Account**.
2. Enter email, account name, and a password.
3. Add payment info (credit card) and verify your phone.
4. Choose the **Basic (Free)** support plan.

## Step 2 — Create a limited user (don't use the root account for keys)

The account you just made is the "root" — too powerful to use day-to-day. Make a
dedicated user with only Chime permissions.

1. Sign in to the AWS Console. In the top search bar, type **IAM** and open it.
2. Left menu → **Users** → **Create user**.
3. User name: `opcionyo-chime`. Click **Next**.
4. Permissions → **Attach policies directly**. For simplicity, search and check
   **`AmazonChimeSDK`** (a ready-made policy for Chime SDK access).
   - *If you can't find it,* click **Create policy** → **JSON**, paste the policy
     below, name it `ChimeSDKMeetings`, then attach it:
     ```json
     {
       "Version": "2012-10-17",
       "Statement": [
         {
           "Effect": "Allow",
           "Action": [
             "chime:CreateMeeting",
             "chime:CreateAttendee",
             "chime:DeleteMeeting",
             "chime:GetMeeting"
           ],
           "Resource": "*"
         }
       ]
     }
     ```
5. Click **Next** → **Create user**.

## Step 3 — Create an access key (the credentials your app uses)

1. Click your new `opcionyo-chime` user → **Security credentials** tab.
2. Scroll to **Access keys** → **Create access key**.
3. Choose use case **Application running outside AWS** → Next → **Create**.
4. You'll see an **Access key ID** and a **Secret access key**.
   **Copy both now** — the secret is shown only once. (You can download the .csv.)

## Step 4 — Put the keys in the project

In your `.env` (never commit this file — it's gitignored):

```
CHIME_REGION=us-east-1
AWS_ACCESS_KEY_ID=AKIA...your-key-id...
AWS_SECRET_ACCESS_KEY=...your-secret...
```

## Step 5 — Set a billing alarm (do this once, save future-you)

1. Console search bar → **Billing and Cost Management** → **Budgets** →
   **Create budget**.
2. Choose **Zero spend budget** (alerts you the moment anything costs money) or a
   small monthly budget like **$5**. Enter your email. Create.

## Step 6 — (Optional) Prove real Chime works

With keys in `.env`, open a Laravel REPL from the project root:

```powershell
php artisan tinker
```

Then paste:

```php
$client = new \Aws\ChimeSDKMeetings\ChimeSDKMeetingsClient([
    'region'  => config('services.chime.region'),
    'version' => 'latest',
    'credentials' => [
        'key'    => config('services.chime.key'),
        'secret' => config('services.chime.secret'),
    ],
]);

$meeting = $client->createMeeting([
    'ClientRequestToken' => 'demo-'.uniqid(),
    'ExternalMeetingId'  => 'demo-1',
    'MediaRegion'        => config('services.chime.region'),
]);

$meeting['Meeting']['MeetingId'];   // <- a real Chime meeting id if it worked
```

If you get a meeting id back, your real AWS Chime integration works. Type `exit`.

> **Security:** if you ever paste a key somewhere public or commit it by mistake,
> go to IAM → your user → Security credentials → **deactivate/delete** that access
> key immediately and create a new one.

---

# How this maps back to the QA case (great interview answer)

- **Docker** = how you'd run the app against a prod-like **MySQL**, and where you'd
  reproduce concurrency bugs (double-booking) with parallel DB connections.
- **AWS Chime** = the real video service. In CI you **mock** it (fast,
  deterministic, no hardware) and verify the *orchestration logic*; real media gets
  validated on a **device farm** using the matrix in `tests/Chime/DEVICE_MATRIX.md`.

That "mock the logic in CI, verify real media on real devices" split is exactly the
reasoning the case is testing you on — so you can speak to AWS/Docker confidently
even though the suite itself needs neither.
```
