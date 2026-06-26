# Installation Guide

## Requirements

- PHP 8.x
- MySQL 8.x
- Modern browser
- Web server capable of serving PHP files

Docker is not required for the MVP.

## Database Setup

Create the database and import the schema:

```bash
mysql -u root -p < database/schema.sql
```

Optional demo data:

```bash
mysql -u root -p openmep < database/seed_demo.sql
```

## Application Setup

Copy the example configuration:

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` and set your MySQL credentials.

## Run Locally

From the repository root:

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

## Recommended First Smoke Test

1. Open the application.
2. Create or select a project.
3. Add layout elements and save.
4. Add resources and save.
5. Add or validate process operations.
6. Run a simulation.
7. Review the Results Dashboard.
