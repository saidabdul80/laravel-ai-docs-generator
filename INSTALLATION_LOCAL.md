# Local Installation Guide

This guide explains how to install the AI Documentation Generator package from a local path during development.

## Method 1: Using Composer Repositories (Recommended)

### Step 1: Add Repository to composer.json

In your Laravel project's `composer.json`, add the package repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../ai-service/packages/ai-documentation-generator",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "schooltry/ai-documentation-generator": "@dev"
    }
}
```

**Note:** Adjust the `url` path relative to your project root. If your structure is:
```
/Repo/Schooltry/
├── schooltry-tertiary/          (your Laravel app)
└── ai-service/
    └── packages/
        └── ai-documentation-generator/
```

Then use: `"url": "../ai-service/packages/ai-documentation-generator"`

### Step 2: Install the Package

```bash
cd schooltry-tertiary
composer require schooltry/ai-documentation-generator:@dev
```

The `@dev` version constraint tells Composer to use the local development version.

### Step 3: Verify Installation

```bash
php artisan vendor:publish --tag=ai-docs-config
```

If this works, the package is installed correctly!

## Method 2: Direct Symlink (Alternative)

If Method 1 doesn't work, you can create a direct symlink:

```bash
cd schooltry-tertiary/vendor
mkdir -p schooltry
ln -s ../../../ai-service/packages/ai-documentation-generator schooltry/ai-documentation-generator
```

Then manually register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    SchoolTry\AIDocumentationGenerator\Providers\AIDocumentationServiceProvider::class,
],
```

## Method 3: Publish to Private Repository

For production use, publish the package to a private Composer repository:

### Option A: Private Packagist

1. Sign up at https://packagist.com
2. Create a private package
3. Push your package to a Git repository
4. Add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://repo.packagist.com/your-org/"
        }
    ]
}
```

### Option B: Satis (Self-Hosted)

1. Install Satis: https://github.com/composer/satis
2. Configure your packages
3. Build the repository
4. Add to `composer.json`:

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "https://your-satis-server.com"
        }
    ]
}
```

## Troubleshooting

### Error: "could not be resolved to an installable set of packages"

**Solution:** Make sure the path in `repositories.url` is correct relative to your project root.

```bash
# Check if the path exists
ls -la ../ai-service/packages/ai-documentation-generator/composer.json
```

### Error: "Package schooltry/ai-documentation-generator has no version"

**Solution:** Use `@dev` version constraint:

```bash
composer require schooltry/ai-documentation-generator:@dev
```

### Error: "requires illuminate/support ^10.0|^11.0|^12.0"

**Solution:** This is now fixed. Make sure you have the latest version of the package with Laravel 12 support.

### Symlink Not Working

**Solution:** On Windows, you may need administrator privileges to create symlinks. Alternatively, use:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../ai-service/packages/ai-documentation-generator",
            "options": {
                "symlink": false
            }
        }
    ]
}
```

This will copy files instead of symlinking.

## Updating the Package

When you make changes to the package:

### If Using Symlink (symlink: true)

Changes are immediately available. Just clear cache:

```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### If Not Using Symlink (symlink: false)

You need to update the package:

```bash
composer update schooltry/ai-documentation-generator
```

## Verifying Installation

Check if the package is loaded:

```bash
php artisan list | grep ai-docs
```

You should see:
```
ai-docs:build-vector-db    Build vector database from generated documentation
ai-docs:generate           Generate AI-powered documentation for Vue.js components
ai-docs:upload             Upload documentation to AI service
```

## Next Steps

After successful installation:

1. Publish configuration: `php artisan vendor:publish --tag=ai-docs-config`
2. Configure your AI provider in `config/ai-docs.php`
3. Generate documentation: `php artisan ai-docs:generate`
4. Build vector database: `php artisan ai-docs:build-vector-db`
5. Upload to AI service: `php artisan ai-docs:upload`

## Support

If you encounter issues:

1. Check the path in `composer.json` repositories
2. Verify Laravel version compatibility (10.x, 11.x, or 12.x)
3. Ensure PHP version is 8.1, 8.2, or 8.3
4. Check composer.json in the package for correct dependencies
5. Try `composer clear-cache` and reinstall

