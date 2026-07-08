# Branch Database and Deploy Rules

This project now enforces branch-safe deployment and branch-specific database usage.

## 1) Live deploy rule

- Auto-deploy runs only when the current branch is `main`.
- For all other branches (`test`, `submain`, `satabhisha`, `kallol`, and any future branch), deploy is skipped.

Where enforced:
- `.githooks/post-merge`
- `scripts/deploy_customer_portal.sh`

Optional override for manual cases:
- Set `DEPLOY_ALLOWED_BRANCH` before running deploy script if you ever need a different allowed branch.

Example:

```bash
DEPLOY_ALLOWED_BRANCH=main ./scripts/deploy_customer_portal.sh
```

## 2) Branch database clone rule

Use the helper script to clone data from your source database into a branch-specific database and switch local `.env` to the cloned DB.

Script:
- `scripts/setup_branch_database.sh`

Default behavior:
- On branch `test`, target DB becomes `<SOURCE_DB>_test`
- On any other branch, target DB becomes `<SOURCE_DB>_<branch_name>`

Typical usage:

```bash
./scripts/setup_branch_database.sh
```

Optional explicit source and target:

```bash
./scripts/setup_branch_database.sh wrkplan_db wrkplan_db_test
```

## 3) Notes

- The script updates `DB_DATABASE` inside `.env` for the current branch context.
- If MySQL CLI tools fail due to local auth plugin mismatch, the script automatically falls back to a PHP PDO clone path.
- Keep `.deploy.env` configured only for production deployment credentials.
