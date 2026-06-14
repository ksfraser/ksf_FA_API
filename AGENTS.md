# AGENTS.md - ksf_FA_API#

## Architecture Overview#

**FA Module** for REST API - exposes FA data via RESTful endpoints for external integrations.

### Core Principles#
- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**#

## Repository Structure#

```
ksf_FA_API/
├── src/#
│   ├── Controllers/#
│   │   ├── CustomerController.php#
│   │   ├── InvoiceController.php#
│   │   └── ...#
│   ├── Middleware/#
│   │   ├── AuthMiddleware.php#
│   │   └── RateLimitMiddleware.php#
│   └── Models/#
├── routes/#
│   └── api_routes.php#
├── hooks.php#
├── composer.json#
└── ProjectDocs/#
```

## Dependencies#

- **ksf_FA_API_Core** (business logic)#
- **FrontAccounting 2.4+**#

## Development Workflow

All development is done in the **devel tree** (`~/Documents/ksf_FA_API`). Do **not** edit files in the UAT bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature branches preferred)
2. **Test**: run repo-appropriate tests
3. **Lint**: `php -l` on modified PHP files (no syntax errors)
4. **Commit** and **Push** branch to GitHub
5. **Merge** to `master` when ready
6. **Push** `master` to GitHub
7. **Deploy** to UAT by pulling in the Infrastructure bind point:

   ```
   cd ~/ksf_Infrastructure/fa_modules/ksf_FA_API
   git stash -u
   git pull origin master
   git stash pop
   ```

### UAT Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_API` | Devel tree — all development, testing, commits |
| `~/ksf_Infrastructure/fa_modules/ksf_FA_API` | UAT bind point — deployment target, integration testing (if mirrored) |

