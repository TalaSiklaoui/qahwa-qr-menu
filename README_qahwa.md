# Qahwa QR Menu

Qahwa is a digital menu I built for a coffee shop using PHP and MySQL. Customers can scan a QR code to browse the menu on their phones, while the shop can update products and categories from an admin dashboard.

**Live menu:** https://qahwa-menu.infinityfree.io/

## What it does

- Shows products by category, with photos, prices and short descriptions.
- Hides unavailable products from the customer menu.
- Lets admins add, edit and delete products and categories.
- Supports image uploads, product search and display order for categories.
- Lets admins change the shop name, tagline and logo.
- Provides a QR code to download or print for customers.

## Built with

PHP, MySQL, HTML, CSS, JavaScript, Bootstrap and Bootstrap Icons.

I developed the project locally with XAMPP and deployed it using InfinityFree.

## Running it locally

1. Place the project folder in `xampp/htdocs/`.
2. Start Apache and MySQL in XAMPP.
3. Create a MySQL database and import your own database export (the production database is not included in this repository).
4. Copy `config/database.example.php` to `config/database.php` and enter your local database credentials.
5. Open `http://localhost/qahwa-qrmenu/` in your browser.

The admin area is at `/admin/login.php`. You will need to create your own admin account in your local database.

## Notes

This repository does not include database passwords or the live database. Some uploaded product and category images may also be excluded.

Developed by Tala Siklaoui.
