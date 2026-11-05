# AGENTS.md

## Project Overview
This project is an internal school survey application for SMK Nurul Islam Affandiyah (SMK NIS).

The application is used by:
- Admin
- Teachers
- Students

## Technology Stack
- Laravel 12
- PHP
- MySQL
- Blade
- Tailwind CSS
- Vite

## General Rules
1. Read the documentation in `docs/` before implementing features.
2. Follow the existing project structure and coding conventions.
3. Do not add features that are not included in the agreed requirements.
4. Do not install additional packages without a clear reason.
5. Keep implementations simple and maintainable.
6. Do not duplicate business logic.
7. Use Laravel validation, authentication, authorization, and Eloquent relationships appropriately.
8. Never store plain-text passwords.
9. Never expose sensitive respondent information in public survey results.
10. Do not modify unrelated files.

## Database Rules
1. Follow `docs/DATABASE.md` as the database reference.
2. Use migrations for all database changes.
3. Define foreign keys and appropriate unique constraints.
4. Do not change table relationships without reviewing the impact.
5. Use database transactions when saving survey responses involving multiple tables.

## Authentication & Authorization
- Use one `users` table.
- Supported roles: `admin`, `teacher`, `student`.
- All users authenticate using username and password.
- Students and teachers have separate profile tables.
- Admin does not require a teacher or student profile.
- Enforce role authorization on the backend.

## Development Workflow
1. Inspect existing code before making changes.
2. Explain the implementation plan for significant changes.
3. Implement only the requested scope.
4. Run relevant tests after implementation.
5. Report changed files and test results.
6. Do not claim tests passed unless they were actually executed.

## Scope Control
The MVP is defined in `docs/FEATURES.md`.

Do not implement optional features unless explicitly requested.