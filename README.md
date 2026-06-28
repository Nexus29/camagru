# Camagru — Custom Full-Stack Image Composition & Social Networking Application

An advanced, containerized, framework-free web application that allows users to record images using device cameras or local media uploads, superimpose high-resolution transparent alpha-channel PNG graphic masks dynamically over source images via a backend compositing core, and publish them to a public social stream with interactive pagination, commenting, liking, and automated email notifications.

This project is built entirely **without client-side monolithic JavaScript frameworks (such as React, Vue, or Angular)** or heavy backend packages. **The use of package managers like `npm` or `composer` is strictly forbidden**, relying entirely on native Web APIs and standard library tools.

---

## 📑 Table of Contents
1. [Functional Specifications](#-functional-specifications)
2. [Security Architecture & Penetration Defense Matrix](#-security-architecture--penetration-defense-matrix)
3. [System Architecture & Strict File Mapping](#-system-architecture--strict-file-mapping)

---

## ⚙️ Functional Specifications

### 1. User Authentication & Profile Lifecycle
* **Registration Workflow:** Captures unique username tokens, clean email structures, and complex hashed strings. New profiles are flagged as `is_active = FALSE` inside the database layer by default.
* **Double Opt-In Verification:** Sign-up triggers a cryptographic, time-limited activation token sent via an automated link directly to the target mailbox. Clicking this route verifies the identity and updates the state to active.
* **Stateful Secure Sessions:** Handles state isolation via standard cookie-backed parameters (`session_start()`). Includes a global, single-click logout wrapper that triggers an absolute memory wipe via `session_destroy()`.
* **Password Reset Loop:** An automated, token-driven recovery pipeline that dispatches expiring authorization tokens via mail to reset credentials safely.

### 2. Multimedia Real-Time Editing Studio
Access layers are strictly secured; anonymous requests attempting to load studio actions are intercepted and redirected via explicit HTTP 302 headers to the login view.

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

## 🔒 Security Architecture & Penetration Defense Matrix

The architecture provides built-in defenses against the precise exploitation tests performed by peer-evaluators:

| Grader Exploitation Scenario | Applied Codebase Remediation Mechanism |
| :--- | :--- |
| **Credential Extraction / Exposure** | Zero plaintext storage. Passwords pass through strong native cryptographic hashing algorithms (`PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`) integrating automatic salts before hitting disk storage. |
| **Childish Steps: XSS Injection Test** | *Grader Input check:* `<script type='text/javascript'>alert('THE GAME'); </script>` inside comment boxes or text inputs. <br>**Remediation:** Neutralized by running text through `htmlspecialchars()` with explicit multi-byte encoding flags before injecting variables back into browser DOM layouts, preventing raw execution. |
| **Human Steps: SQL Injection Test** | *Grader Input check:* Authenticating via login panels using `blahblah' OR 1='1` in credential strings. <br>**Remediation:** Completely blocked by routing all data communication through **PDO Parameterized Prepared Statements**. User parameters are parsed as string literals, completely separating input text from data query interpretation logic. |
| **Cross-Site Request Forgery (CSRF)** | Any data-altering state updates (profile updates, image uploads, comment submissions, or resource deletions) require a highly unpredictable, cryptographic session token (`bin2hex(random_bytes(32))`) validated server-side. |
| **Arbitrary Direct File Infiltration** | Rejects unvalidated client-asserted MIME descriptors or basic file extension checks. Ingested files pass through deep binary magic-number checks via the **PHP Fileinfo extension** (`finfo_file()`) to verify core byte signatures (e.g., confirming `89 50 4E 47` for PNG or `FF D8 FF` for JPEGs). |

---

## 🏛️ System Architecture & Strict File Mapping

The file structure is separated into logical divisions, ensuring configuration models and internal rendering blocks are stored outside direct web browser access paths, while Nginx handles the routing entry loops:

```text
camagru/
├── .env                          # Local private variables (Passwords, Host Routes) - GIT IGNORED
├── .env.example                  # Template file containing mock environment structures
├── .gitignore                    # Exclusion configuration rules preventing file leaks
├── Makefile                      # UNIX shortcut wrapper managing docker operations
├── docker-compose.yml            # Core system infrastructure orchestrator
│
├── docker/                       # Isolated environmental configurations
│   ├── nginx/
│   │   └── nginx.conf            # Direct reverse-proxy rules forwarding calls into index.php
│   └── php/
│       └── Dockerfile            # Custom compilation profile installing native GD and PDO extensions
│
└── src/                          # Dedicated application development volume mount
    ├── index.php                 # Entry point router enforcing HTTPS, parsing paths, & splitting views
    │
    ├── config/
    │   ├── database.php          # PDO instantiation script enforcing ERRMODE_EXCEPTION
    │   ├── setup.php             # Programmatic schema setup and test-data seeder 
    │   └── schema.sql            # Relational database layout tables declaration script
    │
    ├── models/                   # Data access layers separating raw SQL from application logic
    │   ├── UserModel.php         # Manages user accounts lifecycle, creation, queries, and tokens
    │   ├── SnapshotModel.php     # Handles database records persistence and pagination for posts
    │   └── InteractionModel.php  # Drives underlying database counters for likes and comments strings
    │
    ├── controllers/              # Application execution layers (Traffic management coordination)
    │   ├── AuthController.php    # Processes credential checking, security hashing, and active sessions
    │   ├── StudioController.php  # Directs custom backend multi-layer image processing via the GD extension
    │   └── GalleryController.php # Coordinates data payloads between interaction models and frontend scripts
    │
    ├── views/                    # Core user interface markup templates
    │   ├── templates/
    │   │   ├── header.php        # Persistent global top layout envelope with layout navigation links
    │   │   └── footer.php        # Persistent base layout frame closure block
    │   ├── login.php             # Interactive credential capture authentication panel
    │   ├── register.php          # Validation registration account creation panel
    │   ├── studio.php            # Native interface rendering layout for webcam control operations
    │   └── gallery.php           # Community interface feed layout printing global snapshots
    │
    ├── css/                    
    │   └── styles.css            # Enforces global layouts decoration and interface styles
    │
    ├── js/                       # Native client-side dynamic event controllers
    │   ├── camera.js             # Hooks mediaDevices.getUserMedia and posts data strings asynchronously
    │   └── gallery.js            # Dispatches dynamic network exchanges using asynchronous Fetch API
    │
    └── uploads/                  # (tempDirectory)Targeted server storage destination path
        └── overlays/             # Secure directory containing transparency PNG graphics templates
```
