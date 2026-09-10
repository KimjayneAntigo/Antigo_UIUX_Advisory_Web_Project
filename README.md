# Antigo UI/UX Advisory Web Application

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![Apache](https://img.shields.io/badge/Apache-HTTP_Server-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![License](https://img.shields.io/badge/License-Proprietary-blue?style=for-the-badge)](#license)

A modern, full-stack advisory platform and project management portal designed for **Antigo UI/UX Advisory**. The application bridges public client engagement, consultation scheduling, project inquiries, and client delivery with an authenticated client workspace and comprehensive administrative back-office.

---

## 🌟 Key Features

### 🌐 Public Portal & Digital Showcase
- **Modern Landing Experience**: Hero portfolio, bespoke design advisory services (UI Design, UX Research, Design Systems, Mobile & Web Applications), pricing packages, and client testimonials.
- **Dynamic Light & Dark Theme**: Built-in CSS custom properties and instant theme switching with persistent client preferences.
- **Interactive Skillset & Portfolio**: Showcases design capabilities, core competencies, and recent case studies.

### 📅 Consultation Booking System
- **Real-Time Booking Flow**: Interactive scheduling interface allowing clients to pick consultation formats (Google Meet, Phone Call, In-Person Studio), durations (30 min / 60 min), dates, and time slots.
- **Dynamic Fee Calculator**: Calculates pricing dynamically with instant confirmation codes (e.g., `BKG-2001`).
- **Account Linking**: Automatically pairs guest bookings to authenticated user accounts during checkout or post-registration.

### 📝 Project Inquiries & Brief Submissions
- **Structured Intake Form**: Collects project scope, service category, timeline targets, budget range, and detailed design requirements.
- **Asset Attachment**: Secure file upload support for project briefs, style guides, and design wireframes (`.pdf`, `.fig`, `.zip`, images).
- **Automated Tracking Code**: Generates reference numbers (e.g., `INQ-1001`) for client tracking and CRM conversion.

### 💼 Dedicated Client Portal
- **Client Workspace**: Scoped strictly to the authenticated client (`user_id`) to maintain complete data privacy and prevent Insecure Direct Object Reference (IDOR).
- **Phase-Based Project Progress**: 5-stage visual milestone tracker (*Discovery & Research* ➔ *Wireframing* ➔ *UI/UX Design* ➔ *Prototyping* ➔ *Delivered*).
- **Secure Deliverables Vault**: Streamed file download endpoint with strict authentication checks and project ownership validation.
- **Two-Way Studio Messaging**: Direct project-specific communication thread between client and designer.
- **Profile & Credential Management**: In-app profile editing and secure password updating with validation.

### 🛠️ Administrator Command Center
- **Executive KPI Dashboard**: Overview of total active projects, incoming project inquiries, confirmed consultation bookings, and projected pipeline revenue.
- **One-Click Inquiry Conversion**: Convert incoming inquiries directly into active projects, automatically setting codes, milestones, and client accounts.
- **Project Lifecycle Control**:
  - Update project status, milestone phases (1–5), and percentage progress.
  - Upload client-facing deliverables and design packages.
  - Internal admin notes (hidden from clients).
  - Unified project discussion board.
- **Booking & Consultation Management**: Review, filter, confirm, or complete client appointments.

---

## 🔒 Security Architecture

The application adopts defense-in-depth principles:
- **CSRF Defense**: Cryptographic per-session CSRF tokens on all state-altering forms and AJAX endpoints (`inquiry`, `booking`, `login`, `register`, client profile/passwords, project files, and admin mutations).
- **Prepared Statements (PDO)**: Complete protection against SQL injection across all authentication, project, inquiry, and booking queries.
- **Role-Based Access Control (RBAC)**: Distinct authorization barriers separating guest users, verified clients, and administrators (`auth-check-admin.php` and `auth-check-client.php`).
- **IDOR Protection**: All client queries (`client/dashboard.php`, `client/project-detail.php`, and `download.php`) enforce ownership validation (`WHERE project_owner_id = session.user_id`).
- **Hardened File Uploads & Streaming**: Uploaded briefs and deliverables are validated with strict MIME inspection via `finfo_file` (not merely trusting file extensions). Files are served through `download.php` using mime-type detection, path resolution checks, and directory traversal mitigations. Furthermore, `uploads/.htaccess` disables script execution within upload directories.
- **Cookie & Session Hardening**: Protection against session fixation attacks using `session_regenerate_id(true)` upon successful authentication, with `HttpOnly`, `SameSite=Lax`, and secure cookie parameters.
- **XSS Sanitization**: Input normalization and HTML entity sanitization (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) across all user-rendered fields with zero double-encoding bugs.
- **Server Guard (`.htaccess`)**: Blocks public access to configuration files (`config/.htaccess`), database scripts (`.sql`), environment configurations, and disables directory indexes.
- **Graceful Error Recovery**: Custom branded `404.php` and `500.php` pages; database connection drops gracefully redirect or yield JSON responses without leaking internal stack traces.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend** | PHP 8.0+ (Vanilla, Object-Oriented PDO, Strict Types) |
| **Database** | MySQL / MariaDB (InnoDB, `utf8mb4_unicode_ci`) |
| **Web Server** | Apache (configured via `.htaccess` on XAMPP / Linux) |
| **Frontend** | HTML5, Vanilla JavaScript (ES6+), CSS3 with Variables |
| **Styling** | Custom CSS (`css/style.css`), Tailwind CSS (Utility CDN) |
| **Typography & Icons** | Google Fonts (Poppins), Iconify / Lucide Icons |

---

## 📂 Project Directory Structure

```text
Antigo_WebApp/
├── admin/                     # Admin-specific modules
│   ├── inquiries.php          # Inquiry CRM and review board
│   ├── project-detail.php     # Comprehensive project management hub
│   └── download.php           # Admin file download proxy
├── client/                    # Client Portal modules
│   ├── dashboard.php          # Client workspace and overview
│   ├── project-detail.php     # Client project tracking & messaging
│   └── download.php           # Client file download proxy
├── config/                    # System configuration & database files
│   ├── .htaccess              # Protects config directory from direct HTTP access
│   ├── antigo_advisory_db.sql # Full consolidated schema & seed dataset (Single-file import)
│   ├── schema-migration.sql   # Incremental relational migration script (for legacy databases)
│   ├── db.example.php         # Database configuration template
│   ├── db.php                 # Active database connection (Git-ignored)
│   └── session.php            # Hardened session initiator
├── css/                       # Stylesheets
│   └── style.css              # Custom styling, animations, light/dark themes
├── images/                    # Branding assets, logos, profile avatars
├── includes/                  # Reusable components & utilities
│   ├── auth-check-admin.php   # Admin route guard
│   ├── auth-check-client.php  # Client route guard
│   ├── functions.php          # CSRF tokens, sanitization, flash messages, formatters
│   ├── head-common.php        # Shared <head> meta, fonts, and scripts
│   ├── header-admin.php       # Admin navigation header
│   ├── header-client.php      # Client navigation header
│   ├── routing.php            # Dynamic role-based redirection helpers
│   ├── sidebar-admin.php      # Admin sidebar navigation
│   └── sidebar-client.php     # Client sidebar navigation
├── uploads/                   # Upload storage directories (Protected with script execution locks)
│   ├── .htaccess              # Apache directives preventing script execution in uploads
│   ├── inquiries/             # Attachments uploaded via inquiry form (.gitkeep)
│   └── projects/              # Project deliverable files & assets (.gitkeep)
├── .gitignore                 # Git ignore rules (protects credentials and upload files)
├── .htaccess                  # Apache server security & rewrite rules
├── 404.php                    # Custom 404 Not Found error page
├── 500.php                    # Custom 500 Server & Database error recovery page
├── admin-dashboard.php        # Admin overview, client directory, & metric analytics
├── admin-project.php          # Backward-compatible proxy to admin/project-detail.php
├── book-consultation.php      # Consultation booking scheduler
├── booking-handler.php        # Booking submission processor with CSRF check
├── download.php               # Central authenticated file streaming endpoint
├── home.php / index.php       # Main landing page & portfolio
├── inquiry.php                # Project intake questionnaire
├── inquiry-handler.php        # Inquiry intake processor with MIME & CSRF checks
├── login.php                  # Authentication gateway with CSRF guard
├── logout.php                 # Session termination
├── register.php               # New client account registration with CSRF guard
└── README.md                  # Project documentation
```

---

## 🚀 Installation & Local Setup

### Prerequisites
- **Web Server**: [XAMPP](https://www.apachefriends.org/) (recommended), WampServer, or LAMP stack with **PHP 8.0 or higher**.
- **Database**: MySQL 5.7+ or MariaDB 10.4+.
- **Version Control**: Git.

---

### Step 1: Clone the Repository
Clone the project repository into your local web server document root (for XAMPP on Windows, typically `C:/xampp/htdocs/`):

```bash
cd C:/xampp/htdocs/
git clone https://github.com/KimjayneAntigo/Antigo_UIUX_Advisory_Web_Project.git Antigo_WebApp
```

---

### Step 2: Set Up the Database
1. Launch **Apache** and **MySQL** via the XAMPP Control Panel.
2. Open your database administration tool (e.g., [phpMyAdmin](http://localhost/phpmyadmin/)).
3. Create a new database named:
   ```sql
   CREATE DATABASE antigo_advisory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import the schema and seed data (one-click full setup):
   - File: `config/antigo_advisory_db.sql`
   *(Note: `config/schema-migration.sql` is also provided if upgrading an existing legacy install.)*

---

### Step 3: Configure Database Connection
1. Navigate to the `config/` directory.
2. Duplicate `db.example.php` and rename it to `db.php`:
   ```bash
   cp config/db.example.php config/db.php
   ```
3. Open `config/db.php` and update your database credentials if necessary:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'antigo_advisory_db');
   define('DB_USER', 'root');         // Default XAMPP user
   define('DB_PASS', '');             // Default XAMPP password is empty
   define('DB_CHARSET', 'utf8mb4');
   ```

---

### Step 4: Verify Directory Permissions
Ensure that the web server process has write access to the uploads directories:
- `uploads/inquiries/`
- `uploads/projects/`

---

### Step 5: Launch the Application
Open your browser and navigate to:
```text
http://localhost/Antigo_WebApp/
```

---

## 🔑 Demo & Testing Credentials

The seed database includes pre-configured demo accounts for both roles (passwords are hashed using bcrypt):

| Role | Email Address | Password | Description |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@antigo.com` | `password` | Full system access, project controls, CRM, and analytics |
| **Client** | `demo@client.com` | `password` | Client portal workspace, deliverables, and messaging |

> **Note**: For production deployments, update these passwords immediately and configure unique administrative credentials.

---

## 📊 Database Schema Overview

- **`users`**: User records, roles (`admin`, `client`), encrypted passwords, and company affiliation.
- **`inquiries`**: Project requests with scope details, budget ranges, deadlines, and tracking codes (`INQ-xxxx`).
- **`bookings`**: Consultation appointments with service category, duration, date/time, format, and status (`BKG-xxxx`).
- **`projects`**: Active client projects, assigned stages (1–5), progress percentages, and due dates (`PRJ-xxxx`).
- **`project_files`**: Deliverable assets and files attached to projects with size and timestamp metadata.
- **`project_messages`**: Interactive communication logs between clients and designers on a specific project.

---

## 👤 Author & Credits

- **Kimberly Jayne Antigo**  
  *UI/UX Designer & Advisory Specialist*  
  GitHub: [@KimjayneAntigo](https://github.com/KimjayneAntigo)  
  Repository: [Antigo_UIUX_Advisory_Web_Project](https://github.com/KimjayneAntigo/Antigo_UIUX_Advisory_Web_Project)

---

## This repository and its assets are proprietary and created for **Antigo UI/UX Advisory**. All rights reserved.
