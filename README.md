# Rally XRC

The official RC rally game website for Uganda. It links drivers, co-drivers, teams, mechanics, marshals and fans, in both youth and adult leagues.

**GitHub Pages preview:** serves the static `index.html` only; it does not run PHP or show the live database, admin, or account features. Use PHP hosting for the full site.

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
docker compose up --build
```
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in the environment before first startup.
Open `http://localhost:8080`.

## Admin login
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` as environment variables **before the first run**. If you do not, the defaults in `inc.php` are used, so change the password in the admin panel (Admin account, at the bottom) straight away. Open the site and click **Admin**.

## Put it online
- **cPanel/PHP hosting:** deploy with Git Version Control using `.cpanel.yml`, or upload the files manually. Follow [HOSTING.txt](HOSTING.txt) before opening the site for the first time.
- **Railway or Render:** deploy this repo using the Dockerfile and attach ONE persistent volume mounted at `/persist`. The database and uploaded photos and videos are stored there, outside the public web folder, so they survive redeploys.
- **GitHub Pages:** static `index.html` preview only. Dynamic PHP features require PHP hosting.

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
| `index.html` | Static GitHub Pages preview; the live PHP site uses `index.php` |

## Limits
Large video uploads depend on your host's `upload_max_filesize`, `post_max_size`, and execution-time limits. On cPanel, configure these in MultiPHP INI Editor; Docker configures 512 MB upload limits. Under-18 sign-ups may need parental consent wording for your event.

## Licence
MIT, see `LICENSE`.
