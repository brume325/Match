# Project Structure Status

## Canonical frontend locations currently populated

- frontend/assets/css/style.css
- frontend/pages/auth/register.php
- frontend/pages/activities/recherche.php
- frontend/pages/activities/participer.php
- frontend/pages/activities/cree_activite.php
- frontend/pages/profile/parametres.php
- frontend/pages/shared/_nav.php

## Canonical backend locations currently populated

- backend/src/Legacy/config.php
- backend/src/Legacy/badges.php
- backend/src/Config/Env.php
- backend/src/Config/Database.php
- backend/src/Http/JsonResponse.php
- backend/src/Security/AuthService.php
- backend/src/Security/SessionManager.php
- backend/src/Services/ActivityService.php
- backend/src/Services/ModerationService.php
- backend/src/Support/AuditLogger.php
- backend/api/auth/login.php
- backend/api/auth/register.php
- backend/api/activities/list.php
- backend/api/activities/create.php
- backend/api/activities/join.php
- backend/api/moderation/report.php
- backend/database/schema.sql
- backend/database/legacy/schema_match_moov_mariadb.sql
- backend/database/legacy/seed_demo_matchmoov.sql

## Legacy files still empty and requiring restoration

A significant subset of legacy root-level PHP, CSS, and SQL files is still empty. The canonical frontend/backend structure now makes it easier to restore them file by file without losing track of responsibilities.

## Recommended next step

Restore the remaining empty legacy files from local history or recreate them from requirements, then replace root-level runtime usage with frontend/ and backend/ entry points.
