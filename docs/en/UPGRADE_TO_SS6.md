# Upgrade to Silverstripe CMS 6

## Requirements

⚠️ **BREAKING CHANGE:** Update `composer.json` constraints.

| Package | Before | After |
|---|---|---|
| `php` | `>=8.1` | `>=8.3` |
| `silverstripe/cms` | `^4.0 \|\| ^5.0` | `^6.0` |
| `silverstripe/userforms` | `^5.0 \|\| ^6.0` | `^7.0` |

Silverstripe CMS 4 and 5 are no longer supported by this module. Upgrade PHP to 8.3 or later first.

### Sunnysideup dependencies

⚠️ **BREAKING CHANGE:** Every `sunnysideup/*` dependency (`require` and `require-dev`) moved from `5.x-dev` to `dev-main`. Update any project-level constraints or aliases that pin the `5.x-dev` branches.

Affected packages: `googleanalyticsbasics`, `metatags`, `webpack_requirements_backend`, `simple-template-caching`, `sunnysideup-theme`, `phone_field`, `email_address_database_field`, `silverstripe-verbosefields`, `templateoverview`, `prettyphoto`, `typography`, `sswebpack_engine_only`, `config_manager`.

🚨 **CRITICAL REVIEW REQUIRED: All of these dependencies are on `dev-main`. Confirm that each `main` branch actually supports Silverstripe 6 and tag stable releases before production use. The module's own review notes flag this as unfinished.**

## API changes

### `SiteConfigExtras`

⚠️ **BREAKING CHANGE:** Rename `requireDefaultRecords()` to `onRequireDefaultRecords()` in `src/Extensions/SiteConfigExtras.php`, following the Silverstripe 6 extension hook naming. If you subclass or override this extension, rename your method too. Otherwise the default-record logic won't run on `dev/build`.

🚨 **CRITICAL REVIEW REQUIRED: Run `dev/build` and check that the SiteConfig defaults are still created or updated as expected.**

## Security hardening

### `PageControllerExtension`

Session data for `randomImages` is now unserialized with `allowed_classes => false`. Only scalars and arrays are restored; any objects stored under this key become `__PHP_Incomplete_Class`.

🚨 **RISKY: If your project stores objects in the `randomImages` session value, this will break. Store plain arrays instead.**

## Housekeeping

- `.agents/` added to `.gitignore`.
