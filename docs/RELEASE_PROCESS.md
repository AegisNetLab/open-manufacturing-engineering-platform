# Release Process

OpenMEP releases are distributed as source archives that can be published on GitHub Releases.
The release package excludes local configuration, generated release files, dependencies and temporary runtime data.

## Build a Release Archive

```bash
php scripts/build-release.php
```

The command creates:

- `releases/open-manufacturing-engineering-platform-<version>.zip`
- `releases/open-manufacturing-engineering-platform-<version>.zip.sha256`

The version is read from the `VERSION` file. A specific version can be supplied explicitly:

```bash
php scripts/build-release.php --version=0.2.0
```

## Recommended Release Checklist

1. Run the local quality gate.

   ```bash
   sh scripts/run-quality-checks.sh
   ```

2. Confirm the application version in `VERSION`.
3. Update `CHANGELOG.md`.
4. Build the release archive.
5. Verify the SHA-256 checksum.
6. Create a GitHub Release and attach both files.

## Excluded Files and Directories

The release builder excludes:

- `.git/`
- `node_modules/`
- `vendor/`
- `releases/`
- `config/config.php`
- `.env`
- `tmp/`
- `storage/`

`config/config.example.php` remains included so users can create their own local configuration.

## Design Notes

The release builder is intentionally lightweight and does not require Composer, Node package managers or Docker.
This keeps the MVP easy to install for SMEs, students and educational users.
