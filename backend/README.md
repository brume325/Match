# Backend

This directory is the canonical backend container for MATCH MOOV.

## Purpose

- host PHP services and domain logic
- host REST endpoints
- host database assets used by the application lifecycle

## Current Mapping

- backend/src/: canonical backend source code
- backend/src/Legacy/: legacy PHP business helpers still used by root pages
- backend/api/: REST endpoints grouped by domain
- backend/database/: canonical backend database assets
- backend/database/legacy/: legacy SQL scripts kept for compatibility and data seeding

## Legacy Compatibility

The project currently uses root-level src/, api/, and database/ directories. They remain active while the application migrates toward this backend structure.

## Source of Truth

The files copied here mirror the working project structure so developers can browse backend logic, endpoints, and SQL assets from one place before the runtime is fully migrated.
