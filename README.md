# MATCH MOOV

## Technical Stack

- Backend: PHP 8.3 with PDO
- Database: MariaDB 12+
- Frontend: HTML5, CSS3, JavaScript

## Project Structure

- frontend/: canonical frontend container
- frontend/assets/: frontend CSS and JavaScript containers
- frontend/pages/: future frontend page entry points
- backend/: canonical backend container
- backend/src/: future backend source container
- backend/api/: future backend endpoint container
- backend/database/: future backend database container
- database/schema.sql: canonical database schema for enterprise deployment
- src/: business logic, security, and infrastructure classes
- api/: REST endpoints
- assets/css/: shared frontend styles
- assets/js/: shared frontend scripts
- legacy PHP pages: current UI templates and routes maintained for backward compatibility

The frontend/ and backend/ directories are now populated with grouped copies of the working project files to make navigation easier while preserving the root-level runtime.

## Environment

1. Copy .env.example to .env
2. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

## One-Shot Installation

1. Run PowerShell from project root
2. Execute: PowerShell -ExecutionPolicy Bypass -File install_one_shot.ps1
3. Optional secure root credential input:
   - $credential = Get-Credential
   - PowerShell -ExecutionPolicy Bypass -File install_one_shot.ps1 -DbRootCredential $credential

## Manual Deployment

1. Create database and import schema:
   - mariadb -u root -e "CREATE DATABASE bd_matchmoove CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   - mariadb -u root bd_matchmoove -e "source database/schema.sql"
2. Start web server:
   - php -S localhost:8000
3. Open application:
   - http://localhost:8000

## Security Baseline

- Password hashing uses BCRYPT
- PDO prepared statements are required for SQL operations
- Session cookie uses HttpOnly and SameSite flags
- Explicit RGPD consent is required at registration
- Critical actions are logged in audit_logs

## UX Constraint

Key actions must remain within three clicks maximum:
- join an activity
- create an activity
- view badge progression
