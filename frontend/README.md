# Frontend

This directory is the canonical frontend container for MATCH MOOV.

## Purpose

- host HTML templates and page entry points
- host shared CSS and JavaScript assets
- support a progressive migration from legacy root-level PHP pages

## Current Mapping

- frontend/assets/css/: enterprise frontend styles
- frontend/assets/js/: enterprise frontend scripts
- frontend/assets/images/: frontend image assets
- frontend/pages/home/: landing page
- frontend/pages/auth/: authentication pages
- frontend/pages/activities/: activity discovery and participation pages
- frontend/pages/profile/: profile, settings, badges, notifications
- frontend/pages/social/: friends and messaging pages
- frontend/pages/legal/: legal and privacy pages
- frontend/pages/admin/: moderation and administration pages
- frontend/pages/shared/: shared layout fragments

## Legacy Compatibility

The current application still serves root-level PHP pages. These pages remain active while the project migrates toward this frontend structure.

## Source of Truth

The files copied here come from the currently working root-level application so the team can browse the frontend by functional area without breaking runtime compatibility.
