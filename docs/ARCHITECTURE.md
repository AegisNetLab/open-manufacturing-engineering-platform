# Architecture

OpenMEP follows a layered web architecture for maintainability and simple deployment on standard PHP/MySQL hosting.

## Layers

1. Presentation Layer: HTML5, Bootstrap 5, JavaScript ES2022.
2. Application Layer: PHP REST controllers.
3. Business Layer: PHP services.
4. Persistence Layer: Repository classes and MySQL.

## Request Flow

```text
Browser → JavaScript API client → PHP endpoint → Controller → Service → Repository → MySQL
```

## Rules

- Controllers validate request shape and delegate work.
- Services contain business logic.
- Repositories contain SQL and database access.
- API endpoints always return JSON.
- JavaScript communicates only through REST APIs.
