# Kristina Institute — Updates / News / Advertisement

This is a ready-to-use website (frontend) + PHP backend (admin upload + MySQL storage).

## 1) Put this folder under XAMPP
Folder: `c:/xampp/htdocs/kristina-updates`

## 2) Create MySQL database + user
Update `config/db.php`:
- `$db_name`
- `$db_user`
- `$db_pass`

Then load any page (or open `admin/dashboard.php`) to auto-create the `posts` table.

## 3) Configure admin login
Update `config/admin.php`:
- `$admin_username`
- `$admin_password`

## 4) Upload & manage content
Open:
- Website: `http://localhost/kristina-updates/`
- Admin: `http://localhost/kristina-updates/admin/`

Admin lets you create:
- News
- Updates
- Advertisement

Each item supports:
- Title
- Author name (optional)
- Content
- Image (optional)
- Attachment (optional)

## Notes
- Uploaded files are stored under: `assets/uploads/`
- API endpoints:
  - `api/list.php?type=news|updates|advertisement`
  - `api/get.php?type=...&id=...`

