# 📸 Camagru — Decoupled Production-Grade Full-Stack Image Studio

An advanced, containerized, framework-free web application that allows users to record images using device cameras or local media uploads, superimpose high-resolution transparent alpha-channel PNG graphic masks dynamically over source images via a backend compositing core, and publish them to a public social stream with interactive pagination, commenting, liking, and automated email notifications.

This project is built entirely **without client-side monolithic JavaScript frameworks (such as React, Vue, or Angular)** or heavy backend framework packages. **The use of package managers like `npm` or `composer` is strictly forbidden**, relying entirely on native Web APIs, vanilla ES6 modules, and the PHP standard library.

---

## 📑 Table of Contents
1. [🚀 Quick Start & Deployment](#-quick-start--deployment)
2. [⚙️ Functional Specifications Checklist](#️-functional-specifications-checklist)
3. [🔒 Security Architecture & Peer-Evaluation Defense Matrix](#-security-architecture--peer-evaluation-defense-matrix)
4. [🏛️ Service-Oriented Architecture & Production File Layout](#️-service-oriented-architecture--production-file-layout)

---

## 🚀 Quick Start & Deployment

This project is completely containerized utilizing Docker, Nginx, Adminer, and a **PostgreSQL 18+** database backend. The infrastructure is strictly divided into decoupled services communicating over an isolated virtual network bridge to prevent cross-container file leakage.

### 1. Environmental Configuration
Duplicate the environment template file and update your local credentials. **Every single service variable (Database, Network Ports, and Mail Server) is driven dynamically by this single file**:
```bash
cp .env.example .env

```

Ensure your `.env` contains the required environment matrix:

```env
# --- PostgreSQL Engine Credentials ---
POSTGRES_DB=camagru
POSTGRES_USER=camagru_admin
POSTGRES_PASSWORD=camagru_pass
DB_HOST=db

```

### 2. Orchestration and Compilation

Build and launch the application containers using the included `Makefile`. This cleans up stale volume locks and builds the isolated production images from scratch:

```bash
make re

```

Once the compilation displays a green status message, open your web browser and navigate to:

* 🌐 **Main Application Portal:** `https://localhost:8080`
* 🖥️ **Database Management Panel (Adminer):** `http://localhost:8081`

### 3. Automated PostgreSQL Initialization & Seeding

The structural relational schema and tables are initialized automatically upon container deployment. The orchestration layout mounts the PostgreSQL script directly into the database container's native entrypoint initialization directory using the modern PG 18+ directory specification:

```text
./backend/config/schema.sql -> /docker-entrypoint-initdb.d/schema.sql

```

* **To Log in via CLI:** If you prefer direct terminal database interaction instead of the Adminer interface, run this native command to bypass default access layers:

```bash
docker exec -it db_postgres psql -U camagru_admin -d camagru

```

---

## ⚙️ Functional Specifications Checklist

This application strictly implements all mandatory features specified in the project guidelines:

### 1. User Authentication & Profile Lifecycle

* **Registration Workflow:** Captures unique username tokens, clean email structures, and complex hashed strings. New profiles are flagged as `is_verified = FALSE` inside the database layer by default.
* **Double Opt-In Verification:** Sign-up triggers a cryptographic, time-limited activation token sent via an automated link directly to the target mailbox. Clicking this route verifies the identity and updates the state to active.
* **Stateful Secure Sessions:** Handles state isolation via client-side tokens transmitted inside standard authorization headers (`Authorization: Bearer <token>`). Includes a global, single-click logout wrapper that triggers an absolute memory wipe via localStorage clearance.
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
| **Credential Extraction / Exposure** | Zero plaintext storage. Passwords pass through strong native cryptographic hashing algorithms (`PASSWORD_BCRYPT`) integrating automatic salts before hitting disk storage. | Check the database `users` table via Adminer or CLI to verify credentials are completely unreadable hashes. |
| **Cross-Container Source Code Leakage** | The frontend container has **absolutely zero physical access** to your backend code files. They communicate purely over an isolated virtual network bridge (`network`). | Run `docker exec -it web_client sh` and check `/var/www/html/backend`. The folder does not exist; code cannot be leaked via the proxy. |
| **Childish Steps: XSS Injection Test** | Neutralized by running text through `htmlspecialchars()` with explicit flags before injecting variables back into browser DOM layouts, preventing raw script execution. | *Grader Input check:* `<script>alert('hack');</script>` inside comment boxes. Script will print safely as plain text instead of executing. |
| **Human Steps: SQL Injection Test** | Completely blocked by routing all data communication through **PDO Parameterized Prepared Statements**. User parameters are parsed as string literals, completely separating input text from query logic. | Authenticating via login panels using `blah' OR 1='1` in credential strings. Login attempt must fail securely with a standard error payload. |
| **Arbitrary Direct File Infiltration** | Rejects unvalidated client-asserted MIME descriptors or basic file extension checks. Ingested files pass through deep binary checks via the **PHP Fileinfo extension** (`finfo_file()`) to verify core byte signatures. | Try changing a `.txt` or `.php` script file extension to `.png` and uploading it via the local fallback. The engine catches the invalid signature and rejects it. |

---

## 🏛️ Service-Oriented Architecture & Production File Layout

To maximize decoupling and eliminate file-system crossover, Camagru completely isolates frontend static layers from backend execution layers. Nginx handles web routing and pushes requests to the PHP container purely via internal FastCGI network execution protocols on port `9000`.

```text
camagru/
├── .env                          # Unified private variables environment configuration file - GIT IGNORED
├── .env.example                  # Template file containing mock environment structures
├── .gitignore                    # Exclusion configuration rules preventing file leaks
├── Makefile                      # UNIX shortcut wrapper managing docker operations
├── docker-compose.yml            # Core production container infrastructure orchestrator
│
├── docker/                       # Isolated environmental docker configurations
│   ├── nginx/
│   │   ├── Dockerfile            # Bakes in Nginx configurations and static frontend assets
│   │   └── nginx.conf            # Reverse-proxy configuration handling network routing splits
│   └── php/
│       └── Dockerfile            # Custom compilation profile installing native GD and PDO_PGSQL extensions
│
├── backend/                      # PURE HEADLESS BACK-END API SERVER
│   ├── index.php                 # Central Front Controller routing engine parsing paths and returning JSON
│   ├── config/
│   │   ├── Database.php          # Postgres PDO optimization singleton connection class
│   │   ├── setup.php             # Programmatic mock test-data seeder script (Run via CLI)
│   │   └── schema.sql            # PostgreSQL database schema (Auto-loaded via docker-entrypoint)
│   ├── models/                   # Data access layers separating raw SQL from application logic
│   └── controllers/              # Business logic layers (Parses parameters and returns JSON)
│
└── frontend/                     # NATIVE STATIC FRONT-END SERVICE
    ├── index.html                # Main Single Page Application (SPA) entry DOM container shell
    ├── css/                    
    │   └── styles.css            # Enforces global layout structures and interface styles
    └── js/                       # Client side application logic modules
        ├── app.js                # Central ES6 Router engine managing history and interface rendering
        ├── api.js                # Centralized network client wrapper injecting auth headers automatically
        └── views/                # Modular dynamic page view controllers
            ├── gallery.js        # Public grid explorer stream rendering view
            ├── login.js          # Authentication interface overlay component
			├── register.js       # Register interface overlay component
            └── studio.js         # Realtime webcam interactive composition studio panel

```

### ⚓ Architectural Highlights

1. **The Headless Back-End Server (`/backend`):** The PHP backend engine functions entirely as an isolated RESTful application server. It contains **zero HTML formatting blocks**. All outputs are normalized through standard HTTP response headers and `json_encode()` envelopes.
2. **The Dynamic Frontend SPA (`/frontend`):** Rendered entirely via standard HTML5, CSS3, and native ECMAScript modules. The application forbids monolithic template engines, altering client UI views dynamically through DOM tree mutations managed by `js/app.js` using the browser's `history.pushState()` mechanism.
