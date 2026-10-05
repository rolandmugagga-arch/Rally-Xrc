# RALLY X

Official RC rally game of Uganda. A member registration, event management, and admin dashboard platform.

## Features

- **Member registration** (youth & adult leagues)
- **Event management** (create, list, manage)
- **Media gallery** (photos & videos with captions)
- **News posts** (announce race updates)
- **Admin dashboard** with full control
- **Passkey authentication** (fingerprint & face unlock for admin)
- **SQLite database** (no external database needed)
- **CSRF token protection** (secure forms)
- **Password hashing** (bcrypt)

## Requirements

- PHP 8.1+
- SQLite extension enabled
- Web server (Apache, Nginx) or PHP built-in server
- Chrome 67+ (for passkey/biometric support)

## Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/rolandmugagga-arch/Rally-Xrc.git
cd Rally-Xrc
```

### 2. Create directories

```bash
mkdir -p uploads/seed data
```

### 3. Run PHP dev server

```bash
php -S localhost:8000
```

### 4. Open in browser

```
http://localhost:8000/index.php
```

## Default Admin Account

- **Email**: rolandmugagga@gmail.com
- **Password**: Roland12

After first login, you can change the admin password and enable passkey authentication.

## Usage

### Homepage

- View latest news, events, and member grid
- Browse photo/video gallery
- Register as new member (youth or adult league)

### Member Registration

1. Click **Register now** on homepage
2. Choose your role (Driver, Co-driver, Mechanic, etc.)
3. Select your league (Youth or Adult)
4. Create account with email and password (8+ characters)
5. Profile appears in the drivers grid

### Admin Dashboard

Access at `/admin.php`

**Admin account management:**
- Edit your profile (name, email)
- Change your password
- Enable/disable passkey authentication (fingerprint or face unlock)

**Content management:**
- Publish news posts
- Upload photos/videos with captions
- Create and manage race events
- Set site banner announcement
- Manage member accounts

## Passkey Authentication (Admin Only)

Passkeys provide secure passwordless login using:
- **Fingerprint** (on supported devices)
- **Face unlock** (on supported devices)
- **PIN/pattern** (fallback)

### Enable Passkeys

1. Log in as admin
2. Go to Admin Panel → Your admin account
3. Click **Enable fingerprint & face unlock**
4. Follow browser prompt to register your device
5. Next login: use fingerprint/face instead of password

### Supported Browsers

- Chrome 67+
- Edge 79+
- Firefox 60+
- Safari 13+ (macOS 10.15+)

## Directory Structure

```
rally-xrc/
├── inc.php          # Database setup, utilities, HTML header/footer
├── index.php        # Homepage
├── auth.php         # Login/registration
├── admin.php        # Admin dashboard
├── README.md        # This file
├── .gitignore       # Git ignore rules
├── uploads/         # Media storage
│   ├── seed/        # Demo images
│   └── [user files]
└── data/            # SQLite database
    └── site.sqlite
```

## Database

SQLite database auto-creates on first run with tables:
- `users` - Members and admin
- `posts` - News articles
- `events` - Race events
- `media` - Photos/videos
- `settings` - Site config (banner, etc.)

## Security

- Session-based authentication
- CSRF tokens on all forms
- Password hashing (bcrypt)
- Passkeys (WebAuthn) for admin
- SQL prepared statements (injection prevention)
- File upload validation

## Deployment

### Production Server (Apache/Nginx)

1. Clone repo to web root
2. Ensure `/data` and `/uploads` are writable:
   ```bash
   chmod 755 data uploads
   ```
3. Set document root to project directory
4. Enable mod_rewrite (Apache) or equivalent
5. Create `.htaccess` or nginx config to serve PHP files

### Environment Variables

No environment setup needed. All config is in `inc.php`.

Optional: Change default admin credentials before first run in `inc.php`:
```php
'rolandmugagga@gmail.com',           // Email
password_hash('Roland12', ...)        // Password
```

## Troubleshooting

### Database error "unable to open database file"

Ensure `/data` directory exists and is writable:
```bash
mkdir -p data
chmod 777 data
```

### Upload fails

Ensure `/uploads` directory exists and is writable:
```bash
mkdir -p uploads
chmod 777 uploads
```

### Passkey not working

- Use Chrome 67+, Edge 79+, Firefox 60+, or Safari 13+
- Ensure your device supports biometric authentication
- Clear browser cache and try again
- Fallback to password login if passkey fails

### Session expires

Sessions last 30 days by default. Log in again if your session expires.

## License

MIT

## Support

For issues, visit: https://github.com/rolandmugagga-arch/Rally-Xrc/issues
