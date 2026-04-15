# SmartQueue

SmartQueue is a queue and appointment management platform that helps institutions organize services, reduce waiting time, and improve citizen experience.

This project is developed as a backend API-first system using Laravel 12 and PostgreSQL, with a separate Vue.js frontend application.

---

## Table of Contents

- [Project Overview](#project-overview)
- [Key Innovation](#key-innovation)
- [Core Features](#core-features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Repository Structure](#repository-structure)
- [Backend Quick Start](#backend-quick-start)
- [Environment Configuration](#environment-configuration)
- [Run and Development Commands](#run-and-development-commands)
- [Authentication and Roles](#authentication-and-roles)
- [API Overview](#api-overview)
- [Response Format](#response-format)
- [Testing](#testing)
- [API Documentation (OpenAPI)](#api-documentation-openapi)
- [Architecture Notes](#architecture-notes)
- [Diagrams](#diagrams)
- [Docker Notes](#docker-notes)
- [Academic Context](#academic-context)

---

## Project Overview

SmartQueue backend exposes an API-first architecture for:

- User registration and authentication (Laravel Sanctum)
- Role-based access control (admin, institution, citizen)
- Institution and service management
- Appointment booking system
- Intelligent queue management system
- Messaging between users and institutions
- Notifications, ratings, and analytics modules

---

## Key Innovation

The core of SmartQueue is its intelligent queue management system:

- Automatic queue positioning
- Waiting time estimation
- Real-time queue updates
- Handling appointment cancellations and reordering

This system goes beyond traditional appointment booking by introducing dynamic and data-driven queue optimization.

---

## Core Features

- API-first backend with consistent JSON responses
- Service Layer architecture (business logic separated from controllers)
- Role-based access control system
- Automated queue management system
- Messaging system between users and institutions
- Notifications system
- Ratings system
- Analytics module
- Automated testing with Pest
- OpenAPI (Swagger) documentation

---

## Tech Stack

- Backend framework: Laravel 12
- Language: PHP 8.2 (FPM)
- Auth: Laravel Sanctum
- Database: PostgreSQL
- Testing: Pest + PHPUnit
- API documentation: Swagger (OpenAPI)
- Frontend: Vue.js (separate application)
- Styling: Tailwind CSS

---

## Requirements

- PHP 8.2 (FPM)
- Composer
- Node.js & npm
- PostgreSQL
- Git

---

## Repository Structure

```text
.
├── backend/                 # Laravel backend API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Requests/
│   │   │   └── Middleware/
│   │   ├── Models/
│   │   └── Services/
│   ├── routes/
│   ├── database/
│   ├── tests/
│   └── storage/api-docs/
├── docker/                  # Optional Docker setup
├── diagrams/                # Project diagrams
└── README.md
```

---

## Backend Quick Start

From project root:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

API will be available at:

```
http://127.0.0.1:8000/api
```

Alternatively, use the setup script:

```bash
cd backend
composer run setup
```

---

## Environment Configuration

Default setup uses PostgreSQL. Update `.env`:

```env
APP_NAME=SmartQueue
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smartqueue_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## Run and Development Commands

From `backend/` directory:

- Start server: `php artisan serve`
- Run migrations: `php artisan migrate`
- Run tests: `php artisan test`
- Generate OpenAPI docs: `composer run docs:generate`
- Run full dev stack (server, queue, logs, vite): `composer run dev`
- Build assets: `npm run build`
- Dev mode assets: `npm run dev`

---

## Authentication and Roles

Authentication uses **Laravel Sanctum** (token-based authentication).

**Roles:**
- `admin`
- `institution` (internal roles: `manager`, `employee`)
- `citizen`

**Auth header:**

```http
Authorization: Bearer <token>
```

---

## API Overview

**Public endpoints:**

```
GET  /api/home
GET  /api/institutions
GET  /api/institutions/{institution}
GET  /api/services
GET  /api/services/{service}
POST /api/auth/register
POST /api/auth/login
```

**Protected endpoints** (require authentication):

```
POST /api/auth/logout
GET  /api/dashboard (admin only)
POST /api/appointments
GET  /api/queues
GET  /api/conversations
POST /api/messages
GET  /api/notifications
POST /api/ratings
GET  /api/analytics (admin, institution)
AND MORE... (see backend/routes/api.php)
```

---

## Response Format

All API responses use a consistent envelope format.

**Success response:**

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

**Error response:**

```json
{
  "success": false,
  "message": "Error message",
  "data": null
}
```

---

## Testing

Run all tests:

```bash
cd backend
php artisan test
```

**Tests cover:**

- Authentication
- API access control
- Appointments and queue logic
- Messaging system
- Ratings
- Analytics
- Admin modules

---

## API Documentation (OpenAPI)

Generate OpenAPI/Swagger documentation:

```bash
cd backend
composer run docs:generate
```

**Output file:**

```
backend/storage/api-docs/openapi.yaml
```

---

## Architecture Notes

- **MVC + Service Layer:** Business logic separated from controllers
- **Thin Controllers:** Controllers delegate to service classes
- **Service Layer:** Core logic in `backend/app/Services/`
- **Form Requests:** Validation handled via `backend/app/Http/Requests/`
- **Role Middleware:** Custom `EnsureRole` middleware with role aliasing
- **Eloquent Models:** Domain entities in `backend/app/Models/`
- **REST API Design:** Clean, consistent API with proper HTTP verbs

---

## Diagrams

Located in: `diagrams/`

**Includes:**

- Class diagram
- Use case diagram

---

## Docker Notes

Docker setup is available in `docker/` directory.

```bash
cd docker
docker compose up --build -d
```

**Services:**
- Laravel app (PHP 8.2 FPM)
- Nginx (port 8000)
- PostgreSQL (port 5434)
- pgAdmin (port 5051)

⚠️ **Note:** Ensure volume paths in `docker-compose.yml` match your project structure.

---

## Academic Context

SmartQueue is developed as a **final year project (File Rouge)**, aiming to solve real-world queue management problems in Moroccan public and local services.