# Smart Garbage Collection & Inventory Management System

A professional full-stack **PHP MVC** web application for waste management in Ghana.

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+, MVC Architecture |
| Database | MySQL with PDO |
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| CSS Framework | Bootstrap 5 |
| Charts | Chart.js |
| Alerts | SweetAlert2 |
| Animations | AOS (Animate On Scroll) |
| Icons | Font Awesome 6 |
| PDF | Dompdf |
| Server | XAMPP (Apache + MySQL) |

## Project Structure (MVC)

```
finalyearproject/
├── config/           # App & database configuration
├── controllers/      # MVC Controllers
├── models/           # MVC Models (PDO)
├── views/            # MVC Views (Bootstrap 5)
│   ├── layouts/
│   ├── partials/
│   ├── auth/
│   ├── resident/
│   ├── collector/
│   ├── inventory/
│   ├── admin/
│   └── finance/
├── includes/         # Router, Auth, CSRF, Helpers
├── assets/           # CSS, JS, uploads
├── database/         # MySQL schema
└── index.php         # Front controller
```

## Setup

### 1. Start XAMPP
Enable **Apache** and **MySQL**.

### 2. Import Database
Import `database/schema.sql` via phpMyAdmin or:
```bash
mysql -u root < database/schema.sql
```

If collector seed failed, also run `database/fix_collector_seed.sql`.

### 3. Install PHP Dependencies (optional, for PDF)
```bash
composer install
```

### 4. Access Application
Open: **http://localhost/finalyearproject/**

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@smartwaste.gh | password |
| Finance Manager | finance@smartwaste.gh | password |
| Inventory Manager | inventory@smartwaste.gh | password |
| Collector | collector@smartwaste.gh | password |

Residents register at `/index.php?url=auth/register`

## Features

- Animated landing page with floating garbage bins
- Secure authentication with RBAC (5 roles)
- Resident registration with bin selection & pricing
- Collection scheduling & simulated payments
- Collector routes, QR scan, field reports, offline sync
- Inventory management with low-stock alerts
- Admin dashboards with Chart.js analytics
- Finance payment verification & pricing config
- CSV/PDF report export
- CSRF protection, password hashing, audit logs

## Security

- `password_hash()` / `password_verify()`
- PDO prepared statements
- CSRF tokens on all forms
- Input sanitization (`htmlspecialchars`)
- Session timeout (1 hour)
- Role-based access control
- Activity audit trail

## Note on React Frontend

The `frontend/` folder contains an earlier React prototype. The **primary application** is the PHP MVC version at the project root.

## License

Final Year Project — Academic Use
