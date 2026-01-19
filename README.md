# Yii2 API Skeleton

**Yii2 API Skeleton** is a starter project for building RESTful APIs using Yii2. It provides a ready-to-use structure, helper scripts, and example configurations to accelerate your API development.

---

## 1. Install Yii2

```bash
composer create-project --prefer-dist yiisoft/yii2-app-basic ./
```

## 2. Add the repository and package to `composer.json`

Open your project's `composer.json` and add the following sections:

### Add this to `composer.json` `repositories`
```json
{
    "type": "vcs",
    "url": "https://github.com/rahmatsyaparudin/yii2-api-skeleton.git"
}
```

### Add this to `composer.json` `require-dev`
```json
"rahmatsyaparudin/yii2-api-skeleton": "dev-main"
```

### Add this to `composer.json` `scripts`
```json
"skeleton-update": [
    "composer update rahmatsyaparudin/yii2-api-skeleton --ignore-platform-reqs",
    "php scripts/install-skeleton.php"
],
"skeleton-copy-examples": [
    "php scripts/copy-examples.php"
]
```

## 3. Dependensi Composer Utama

Skeleton will add the following dependencies to your `composer.json`:

- `yiisoft/db-pgsql`: `^1.0`
- `mongodb/mongodb`: `^1.20`
- `firebase/php-jwt`: `^6.10`
- `paragonie/sodium_compat`: `^2.0`
- `vlucas/phpdotenv`: `^5.6`

## 4. Update Composer untuk yii2-api-skeleton

Run composer update for update:

```bash
composer update --ignore-platform-reqs
```

## 5. Copy skeleton scripts

Make directory `scripts` and Copy the `scripts` folder from the package to your project root:

```bash
mkdir scripts; cp -r -Force vendor/rahmatsyaparudin/yii2-api-skeleton/scripts/* ./scripts
```

## 6. Install the skeleton

Run the custom Composer script to install the skeleton files:

```bash
composer skeleton-update
```

This command will set up the necessary folder structure and example configurations in your project.

## 7. Copy example files (first-time setup only)

Run this command only the first time you set up the skeleton:
This will copy example configuration and code files to your project for reference and customization.

```bash
composer skeleton-copy-examples
```
Skeleton will add the following dependencies to your `composer.json`:

- `yiisoft/db-pgsql`: `^1.0`
- `mongodb/mongodb`: `^1.20`
- `firebase/php-jwt`: `^6.10`
- `paragonie/sodium_compat`: `^2.0`
- `vlucas/phpdotenv`: `^5.6`

### Apply updates or re-install skeleton components without affecting your existing project code.

## 8. Update Composer Dependencies
Update all dependencies in `composer.json`:

```bash
composer update --ignore-platform-reqs
```

## Notes

This package is meant for development only, so it is added under require-dev.

Make sure to adjust your configuration files after copying examples to match your environment.
Apply updates or re-install skeleton components without affecting your existing project code.