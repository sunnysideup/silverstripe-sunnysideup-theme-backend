## Fixed
- composer.json: PHP constraint raised from >=8.1 to >=8.3 (required by Silverstripe 6).
- src/Extensions/PageControllerExtension.php: `unserialize` of session data now uses `allowed_classes => false` (hardening).

## ⚠️ Upgrade work still to do
- composer.json: several dependencies are `dev-main`; confirm those branches support Silverstripe 6.
