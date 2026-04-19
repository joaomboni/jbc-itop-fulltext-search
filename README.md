# jbc-itop-fulltext-search

AGPL iTop extension that speeds up console **global search** using an auxiliary table with a **FULLTEXT** index (InnoDB, MySQL/MariaDB), instead of the core’s per-class `LIKE` implementation.

## Installation

1. Copy the `extensions/jbc-itop-fulltext-search/` folder into iTop (already present in this repository).
2. Run iTop **Setup** and install the module **JBC iTop Fulltext search (MySQL)**.
3. Build the initial index (CLI, from the iTop root):

```bash
php extensions/jbc-itop-fulltext-search/script/populate-index.php
```

### Common CLI errors

- **`Could not find configuration file ... conf/production/config-itop.php`**  
  The script uses the same configuration file as the application (**production** environment by default). That file must exist **after Setup**. In development, if you only have `conf/toolkit/config-itop.php`, copy it to `conf/production/` (see the script’s own message).

- **`Undefined constant EVENT_DOWNLOAD_DOCUMENT`**  
  Usually appears **together** with a configuration failure: MetaModel bootstrap never defines datamodel events and the event service fails in cascade. Fixing the config path resolves it.

- **Many warnings `Deprecated: ... Implicitly marking parameter ... as nullable`**  
  These come from the iTop core on PHP 8.4+; the populate script lowers this noise via `error_reporting` for terminal output. To see them, comment that line in `script/populate-index.php` or adjust the CLI `php.ini`.

4. **Integration without patching core iTop (`pages/`, `sources/`):** set **once** in PHP for this virtual host:

   ```text
   auto_prepend_file = /ABSOLUTE_PATH/extensions/jbc-itop-fulltext-search/include/http-bootstrap.php
   ```

   (Apache: `php_admin_value auto_prepend_file ...`; PHP-FPM: `php_value[auto_prepend_file]` in the pool.)

   This file performs **302** redirects for:

   - `pages/exec.php?text=…` without `exec_module`;
   - `pages/UI.php?operation=full_text&text=…`.

   The backoffice magnifying-glass form is adjusted by **`JbcItopFulltextSearchEarlyScript`** (`iBackofficeEarlyScriptExtension`), without patching `TwigHelper`.

   **PHP-FPM:** use a dedicated pool for this iTop instance (with the prepend above) and point the Apache `SetHandler` / FastCGI socket to that pool’s `listen`. A single pool cannot define two different `auto_prepend_file` values; `phpinfo()` should show the bootstrap path under `auto_prepend_file` for requests to iTop.

5. Optional: after changing weights or class filters in `conf/*/config-itop.php` (`jbc-itop-fulltext-search`), run populate again (step 3).

### Backoffice: rebuild from the menu (admins only)

After Setup recompiles the datamodel, **System** → **Rebuild FULLTEXT search index** appears for **administrators only** (`enable_admin_only` + `UserRights::IsAdministrator()` on the page). The page explains the impact and asks for confirmation before running the same logic as `script/populate-index.php` (`JbcItopFulltextSearchPopulateRunner`).

## Configuration (`config-itop.php`)

Module key: `jbc-itop-fulltext-search`.

| Parameter | Description |
|-----------|-------------|
| `enabled` | If `false`, the magnifying-glass early script does not change the form `action` (stays on native `UI.php`), so the module is effectively unused. |
| `excluded_classes` | List of classes (final or parent) that are not indexed. |
| `included_classes` | If non-empty, only these classes (and subclasses) are indexed. |
| `object_weight_factor` | Map `ClassName => float` to multiply the FULLTEXT score for ordering. |
| `max_document_chars` | Truncates the consolidated text stored per object (default 65535). |

## How it works

- **Indexing**: classes in the `searchable` category, searchable scalar attributes (same idea as native fulltext), no foreign keys or external fields.
- **Updates**: `OnDBInsert` / `OnDBUpdate` / `OnDBDelete` keep the row in the `JBC_fulltext_doc` table.
- **Search**: with `enabled=true`, the injected JS changes the magnifying-glass form `action` to `exec.php` with `exec_module` / `exec_page`; legacy URLs or bookmarks (`UI.php?operation=full_text`, `exec.php?text=`) are handled by **`http-bootstrap.php`** via `auto_prepend_file`.
- **Permissions**: results are filtered with `UserRights::IsActionAllowed(..., UR_ACTION_READ, ...)`.

## Requirements

- MySQL/MariaDB with InnoDB **FULLTEXT** (MySQL 5.6+ / MariaDB 10.0.5+).

## Known limitations

- Simplified BOOLEAN MODE syntax; odd characters are stripped by the sanitizer.
- No background queue (synchronous indexing on save + CLI or backoffice rebuild for a full refresh).
