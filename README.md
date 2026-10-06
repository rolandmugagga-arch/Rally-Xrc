# Rally XRC

The official RC rally game website for Uganda. It links drivers, co-drivers, teams, mechanics, marshals and fans, in both youth and adult leagues.

**Live design preview:** enable GitHub Pages (see below). The preview keeps data in the visitor's browser only.

## Features
- Home page with hero, news, events, leagues, member grid, photo gallery with full-screen view, and videos
- Registration and login. Accounts are saved in a database and sessions last 30 days
- Admin panel: dashboard counts, site banner, events, news, drag-and-drop photo and video upload, caption editing, delete and restore photos, edit and delete members, change admin password

## Stack
PHP 7.4+ with SQLite (no database setup). Plain HTML, CSS and JavaScript on the front end.

## Run it on your computer
1. Install [XAMPP](https://www.apachefriends.org) and start Apache.
2. Copy this folder into `htdocs/rallyx`.
3. Open `http://localhost/rallyx`.

The database is created on the first visit.

## Run it with Docker
```
cp .env.example .env     # then edit the admin email and password
docker compose up --build
```
Open `http://localhost:8080`.

## Admin login
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` as environment variables **before the first run**. If you do not, the defaults in `inc.php` are used, so change the password in the admin panel (Admin account, at the bottom) straight away. Open the site and click **Admin**.

## Put it online
- **PHP hosting:** upload the folder by FTP or Git. Make `data/` and `uploads/` writable.
- **Railway or Render:** deploy this repo using the Dockerfile and attach ONE persistent volume mounted at `/persist`. The database and uploaded photos and videos are stored there, outside the public web folder, so they survive redeploys.
- **GitHub Pages (preview only):** Settings, Pages, Source: GitHub Actions. The workflow publishes the `docs/` folder.

## Folder guide
| Path | Purpose |
|---|---|
| `index.php` | Home page |
| `auth.php` | Register, log in, log out |
| `admin.php` | Admin panel |
| `inc.php` | Database, sessions, shared header and footer |
| `style.css` | Design |
| `uploads/` | Photos and videos (`seed/` holds the starting photos) |
| `data/` | SQLite database when running locally (blocked from web access, not committed). In Docker the database lives in `/persist` |
| `docs/` | Static preview for GitHub Pages |

## Limits
Large video uploads depend on your host's `upload_max_filesize` and `post_max_size` (set to 512M in `.htaccess`, `.user.ini` and Docker). Under-18 sign-ups may need parental consent wording for your event.

## Licence
MIT, see `LICENSE`.
