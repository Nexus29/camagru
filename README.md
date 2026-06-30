# 📸 Camagru — Decoupled Full-Stack Image Composition & Social Networking Application

An advanced, containerized, framework-free web application that allows users to record images using device cameras or local media uploads, superimpose high-resolution transparent alpha-channel PNG graphic masks dynamically over source images via a backend compositing core, and publish them to a public social stream with interactive pagination, commenting, liking, and automated email notifications.

This project is built entirely **without client-side monolithic JavaScript frameworks (such as React, Vue, or Angular)** or heavy backend framework packages. **The use of package managers like `npm` or `composer` is strictly forbidden**, relying entirely on native Web APIs and the PHP standard library.

---

## 📑 Table of Contents
1. [🚀 Quick Start & Deployment](#-quick-start--deployment)
2. [⚙️ Functional Specifications Checklist](#️-functional-specifications-checklist)
3. [🔒 Security Architecture & Peer-Evaluation Defense Matrix](#-security-architecture--peer-evaluation-defense-matrix)
4. [🏛️ Service-Oriented Architecture & Unified Routing Matrix](#️-service-oriented-architecture--unified-routing-matrix)

---

## 🚀 Quick Start & Deployment

This project is completely containerized utilizing Docker, Nginx, and a **PostgreSQL** database backend. The infrastructure is strictly divided into decoupled services to mirror modern software practices while meeting all core constraints.

### 1. Environmental Configuration
Duplicate the environment template file and update your local credentials. **Every single service variable (Database, Network Ports, and Mail Server) is driven dynamically by this single file**:
```bash
cp .env.example .env

```

Ensure your `.env` contains the required environment matrix:

```env
# --- Network / System Settings ---
APP_PORT=8080

# --- PostgreSQL Engine Credentials ---
POSTGRES_DB=camagru
POSTGRES_USER=camagru_admin
POSTGRES_PASSWORD=
DB_HOST=db
DB_PORT=5432

# --- SMTP Mailer System Settings ---
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USER=your_smtp_username
SMTP_PASS=your_smtp_password

```

### 2. Orchestration and Compilation

Build and launch the application containers using the included `Makefile`:

```bash
# Build system images and spin up containers in the background
make up

# Alternative if豪 Makefile is not utilized:
# docker-compose up --build -d

```

### 3. Automated PostgreSQL Initialization & Seeding

The structural relational schema and tables are initialized automatically upon container deployment. The orchestration layout mounts the PostgreSQL script directly into the database container's native entrypoint initialization directory:

```text
./backend/config/schema.sql -> /docker-entrypoint-initdb.d/schema.sql

```

* **Optional Data Seeding:** To inject mock user accounts, sample comments, or default interactions for instant evaluation grading, execute the CLI seeding routine:
```bash
docker-compose exec php php /var/www/html/backend/config/setup.php

```



---

## ⚙️ Functional Specifications Checklist

This application strictly implements all mandatory features specified in the project guidelines:

### 1. User Authentication & Profile Lifecycle

* **Registration Workflow:** Captures unique username tokens, clean email structures, and complex hashed strings. New profiles are flagged as `is_active = FALSE` inside the database layer by default.
* **Double Opt-In Verification:** Sign-up triggers a cryptographic, time-limited activation token sent via an automated link directly to the target mailbox. Clicking this route verifies the identity and updates the state to active.
* **Stateful Secure Sessions:** Handles state isolation via standard cookie-backed parameters (`session_start()`). Includes a global, single-click logout wrapper that triggers an absolute memory wipe via `session_destroy()`.
* **Password Reset Loop:** An automated, token-driven recovery pipeline that dispatches expiring authorization tokens via mail to reset credentials safely.

### 2. Multimedia Real-Time Editing Studio

Access layers are strictly secured; anonymous requests attempting to load studio actions are intercepted by the client-side router or backend middleware and rejected with appropriate authorization payloads.

* **Layout Geometry Rules:** Follows the strict spatial blueprint defined by the assignment:
* Persistent global **Header** navigation.
* **Main Workspace Framework:** Renders the active video camera stream viewport next to a grid selection array of transparent PNG mask options, anchored by a primary execution button.
* **Sidebar Tray:** A dedicated user history list showing chronological thumbnails of previous images captured exclusively by the active authenticated profile.
* Persistent global **Footer** container.


* **Interactive Element Locking:** The primary capture trigger is explicitly locked (`disabled`) until an asset frame from the transparent overlay selector grid is actively picked by the client browser.
* **Server-Side Rasterization Core:** The actual positioning math, alpha-channel transparent pixel layer blending, and structural combination operations **take place exclusively on the backend via server utilities (GD library)**. The browser transmits the image data, and the PHP processor creates the finalized composited output.
* **Local Ingestion Fallback:** If a host device lacks a functional webcam or blocks browser video stream permissions, the app provides a native multi-part upload field (`multipart/form-data`) to ingest an existing image (`JPEG`/`PNG`) as the baseline composition file.
* **Owner-Locked Resource Erasure:** Thumbnails inside the sidebar tray include active removal triggers. The backend verifies that the active session identifier matches the creator database records 100% before executing any file deletions.

### 3. Public Interactive Gallery Feed

* **Chronological Image Stream:** A completely public, unauthenticated feed displaying shared composite assets ordered by creation timestamps.
* **Social Interactions Validation:** Logged-in accounts can submit text commentary and toggle likes on any image. Anonymous users can read the stream, but trying to submit comments or likes forces an authentication redirect.
* **Automated Action Alerts:** Appending a text comment triggers a background script lookup of the image creator's notification preferences. If active (`notify_on_comment = TRUE`), an alert email is sent to their registered address.
* **Strict Viewport Pagination:** To optimize page loads and server weight, the feed enforces a structural display limit outputting **at least 5 composite elements per page view**, managing data sets via explicit offset math.

---

## 🔒 Security Architecture & Peer-Evaluation Defense Matrix

The architecture provides built-in defenses against the precise exploitation tests performed by peer-evaluators:

| Grader Exploitation Scenario | Applied Codebase Remediation Mechanism | Evaluation Test Method |
| --- | --- | --- |
| **Credential Extraction / Exposure** | Zero plaintext storage. Passwords pass through strong native cryptographic hashing algorithms (`PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`) integrating automatic salts before hitting disk storage. | Check the database `users` table via CLI to verify credentials are completely unreadable hashes. |
| **Childish Steps: XSS Injection Test** | Neutralized by running text through `htmlspecialchars()` with explicit multi-byte encoding flags before injecting variables back into browser DOM layouts, preventing raw execution. | *Grader Input check:* `<script type='text/javascript'>alert('THE GAME'); </script>` inside comment boxes or text inputs. Script will print safely as plain text instead of executing. |
| **Human Steps: SQL Injection Test** | Completely blocked by routing all data communication through **PDO Parameterized Prepared Statements**. User parameters are parsed as string literals, completely separating input text from data query interpretation logic. | *Grader Input check:* Authenticating via login panels using `blahblah' OR 1='1` in credential strings. Login attempt must fail securely with an error payload. |
| **Cross-Site Request Forgery (CSRF)** | Any data-altering state updates (profile updates, image uploads, comment submissions, or resource deletions) require a highly unpredictable, cryptographic session token (`bin2hex(random_bytes(32))`) validated server-side. | Attempt to intercept and resubmit a POST comment action without the hidden `X-CSRF-Token` header; the backend will drop the request with a `403 Forbidden` header. |
| **Arbitrary Direct File Infiltration** | Rejects unvalidated client-asserted MIME descriptors or basic file extension checks. Ingested files pass through deep binary magic-number checks via the **PHP Fileinfo extension** (`finfo_file()`) to verify core byte signatures. | Try changing a `.txt` or `.php` script file extension to `.png` and uploading it via the local fallback. The engine will catch the invalid signature (`89 50 4E 47` for real PNGs) and safely reject it. |

---

## 🏛️ Service-Oriented Architecture & Unified Routing Matrix

To maximize decoupling and adhere to modern full-stack engineering standards without utilizing third-party dependencies, Camagru is strictly divided into two distinct architectural ecosystems communicating exclusively via asynchronous JSON network streams.

```text
camagru/
├── .env                          # Unified private variables environment configuration file - GIT IGNORED
├── .env.example                  # Template file containing mock environment structures
├── .gitignore                    # Exclusion configuration rules preventing file leaks
├── Makefile                      # UNIX shortcut wrapper managing docker operations
├── docker-compose.yml            # Core system infrastructure orchestrator (Nginx, PHP, Postgres)
│
├── docker/                       # Isolated environmental configurations
│   ├── nginx/
│   │   └── nginx.conf            # Reverse-proxy configuration handling route splits
│   └── php/
│       └── Dockerfile            # Custom compilation profile installing native GD and PDO_PGSQL extensions
│
├── backend/                      # PURE HEADLESS BACK-END API SERVER
│   ├── index.php                 # Central API router parsing routes and formatting JSON envelopes
│   ├── config/
│   │   ├── database.php          # Postgres PDO instantiation script reading from $_ENV variables
│   │   ├── setup.php             # Programmatic mock test-data seeder script (Run via CLI)
│   │   └── schema.sql            # PostgreSQL database schema (Auto-loaded via docker-entrypoint)
│   ├── models/                   # Data access layers separating raw SQL from application logic
│   │   ├── UserModel.php         # Manages user accounts lifecycle, creation, queries, and tokens
│   │   ├── SnapshotModel.php     # Handles database records persistence and pagination for posts
│   │   └── InteractionModel.php  # Drives underlying database counters for likes and comments strings
│   └── controllers/              # Business logic layers (Parses parameters and returns JSON)
│       ├── AuthController.php    # Processes credential checking, security hashing, and sessions
│       ├── StudioController.php  # Directs backend multi-layer image processing via the GD extension
│       └── GalleryController.php # Coordinates data payloads between interaction models and api paths
│
└── frontend/                     # NATIVE STATIC FRONT-END SERVICE
    ├── index.html                # Main Single Page Application (SPA) DOM container shell
    ├── css/                    
    │   └── styles.css            # Enforces global responsive layouts and interface styles
    └── js/                       # Client side application logic controllers
        ├── app.js                # Client-side state router managing interface rendering states
        ├── api.js                # Central fetch network client wrapping authentication and CSRF headers
        ├── camera.js             # Hooks mediaDevices.getUserMedia and posts data strings asynchronously
        └── gallery.js            # Dispatches dynamic network exchanges using asynchronous Fetch API

```

### 1. The Headless Back-End API Server (`/backend`)

* **Strict JSON Delivery:** The PHP backend engine functions entirely as an isolated RESTful application server. It contains **zero HTML formatting blocks**. All outputs, including validation warnings, database query payloads, and security rejections, are normalized through standard HTTP response headers and `json_encode()` envelopes.
* **Encapsulated PostgreSQL Business Logic:** The traditional controller pattern handles incoming client requests, manages database state parameters through underlying SQL models using strict **PDO Prepared Statements**, processes binary imaging channels using the native **GD Engine**, and enforces session state parameters via secure cookies.

### 2. The Native Static Front-End (`/frontend`)

* **Frameworkless SPA Architecture:** Rendered entirely via standard HTML5, CSS3, and native ECMAScript modules. The application forbids monolithic template engines or compiler setups, opting instead to alter client UI views dynamically through DOM tree mutations managed by a centralized application router (`js/app.js`).
* **Asynchronous Networking:** User events, camera buffer snapshots, text comments, and login sequences are systematically converted into asynchronous payloads dispatched by the native browser **Fetch API** targeting the respective `/api` route targets.
