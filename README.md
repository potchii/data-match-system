# PROJECT: Data Match System (1-2 Weeks)

## Objective
* Match uploaded records (Excel/File) with existing database records.
* If no match is found → automatically insert as new record.

---

## 👥 TEAM STRUCTURE

### 🔹 Mason – Backend Lead (System Architect)
**Primary Focus:** Laravel setup, repository, matching logic, database structure

### 🔹 Ernest – Frontend & UI Developer
**Primary Focus:** AdminLTE design, upload interface, dashboard, user interaction

---

## 🚀 PHASE 1 – PROJECT SETUP (Week 1)

### 🧑‍💻 Mason Tasks (Backend Lead)
1. **Create GitHub Repository**
   * Create private repository: `data-match-system`
   * Initialize: `README.md`, `.gitignore` (Laravel default)
   * Add Ernest as collaborator
2. **Setup Laravel Project (Local – XAMPP)**
   * Install Laravel
   * Configure: `.env`, Database connection (MySQL via phpMyAdmin)
   * Push initial commit: Laravel base install, Clean folder structure
3. **Database Planning**
   * **Main System Table** (id, uid, last_name, first_name, middle_name, suffix, birthday, gender, civil_status, street_no, street, city, province, ...)
   * **Upload Logs Table** (`upload_batches`): id, file_name, uploaded_by, uploaded_at, status
   * **Match Results Table** (`match_results`): id, batch_id, uploaded_record_id, matched_system_id, match_status (matched / new / possible_duplicate), confidence_score

### 🎨 Ernest Tasks (Frontend)
1. **Install AdminLTE in Laravel**
   * Integrate AdminLTE template
   * Create: Sidebar, Dashboard layout, Navigation menu
2. **Create Pages**
   * 📂 Upload Page
   * 📊 Match Results Page
   * 📑 Batch History Page
3. **Create Upload Form**
   * Fields: File Upload (Excel / CSV), Submit Button
   * Validation messages

---

## 🚀 PHASE 2 – FILE PROCESSING (Week 2)

### 🧑‍💻 Mason – Data Processing
1. **Install Laravel Excel** (`maatwebsite/excel`)
2. **Map Uploaded Columns**
   * **Mapping Logic:**
     | Uploaded File | System DB |
     | :--- | :--- |
     | Surname | last_name |
     | FirstName | first_name |
     | MiddleName | middle_name |
     | Extension | suffix |
     | DOB | birthday |
     | Sex | gender |
     | Status | civil_status |
     | Address | street |
     | BrgyDescription | city or barangay |
     | RegsNo | uid |
   * Create Mapping Service: `App\Services\DataMappingService.php`

### 🧠 Core Logic: Matching Algorithm
* **Step 1 – Exact Match:** Match by last_name, first_name, middle_name, birthday.
* **Step 2 – Partial Match:** (If no exact) Match by last_name, first_name, birthday.
* **Step 3 – If No Match:** Insert new record.
* Create Service: `App\Services\DataMatchService.php`

---

## 🚀 PHASE 3 – MATCH ENGINE (Week 3)

### 🧑‍💻 Mason – Matching Rules
* **Confidence Scoring:**
  * Exact full name + DOB: **100%**
  * Full name only: **80%**
  * First + Last + DOB: **90%**
  * Similar name: **60–75%**
* **Statuses:** `MATCHED`, `POSSIBLE DUPLICATE`, `NEW RECORD`

### 🎨 Ernest – UI Enhancements
1. **Results Table**
   * Display: Uploaded Name, Matched Name, Confidence %, Status Badge
   * UI Colors: Green (Matched), Yellow (Possible Duplicate), Red (New Record)
2. **Batch Summary Widget**
   * Counters for: Total Uploaded, Total Matched, Total New, Total Duplicates

---

## 🚀 PHASE 4 – AUTO INSERT SYSTEM

### 🧑‍💻 Mason
* **Auto-insertion Logic:** If `match_status = NEW`, automatically insert into system database.
* **Cleanup:** Generate new UID, Normalize name casing, Trim whitespace, Validate DOB format.

---

## 🔐 VERSION CONTROL RULES

* **Mason:** Backend, Database migrations, Services, Core logic.
* **Ernest:** Blade files, AdminLTE layout, JS interactions, Frontend validations.
* **Rules:** * Create feature branches (e.g., `feature/match-engine`).
  * Pull Request required before merge to `main`.

---

## 📁 RECOMMENDED FOLDER STRUCTURE
```text
app/
 ├── Services/
 │    ├── DataMappingService.php
 │    └── DataMatchService.php
 └── Imports/
      └── UploadImport.php
```

---

## 🛠️ INSTALLATION GUIDE

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL/MariaDB
- XAMPP (recommended for Windows) or similar local server

### Step 1: Clone the Repository
```bash
git clone https://github.com/your-username/data-match-system.git
cd data-match-system
```

### Step 2: Install PHP Dependencies
```bash
composer install
```

**Note:** If you encounter missing PHP extensions (like `ext-gd` or `ext-zip`), you need to enable them in your `php.ini` file:
1. Open `C:\xampp\php\php.ini` (or your PHP config path)
2. Find and uncomment these lines (remove the `;`):
   ```ini
   extension=gd
   extension=zip
   ```
3. Restart your web server

### Step 3: Install Frontend Dependencies
```bash
npm install
```

### Step 4: Environment Configuration
```bash
# Copy the example environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Step 5: Configure Database
1. Create a MySQL database named `data_match_system` in phpMyAdmin
2. Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=data_match_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Step 6: Run Migrations
```bash
php artisan migrate
```

### Step 7: Build Frontend Assets
```bash
npm run build
```

### Step 8: Start Development Server
```bash
# Option 1: Use Laravel's built-in server
php artisan serve

# Option 2: Use the dev script (runs server + queue + vite)
composer dev
```

Visit `http://localhost:8000` in your browser.

---

## 🧪 DEVELOPMENT WORKFLOW

### Code Quality & Linting

Before committing code, ensure it passes all quality checks:

#### PHP Code Style (Laravel Pint)
```bash
# Fix PHP code style automatically
composer lint

# Or manually
./vendor/bin/pint
```

#### Frontend Formatting (Prettier)
```bash
# Format all frontend code
npm run format

# Check formatting without fixing
npm run format:check
```

#### Frontend Linting (ESLint)
```bash
# Lint and auto-fix JavaScript/TypeScript
npm run lint
```

#### TypeScript Type Checking
```bash
# Check types without building
npm run types
```

#### Run Tests
```bash
# Run PHP tests
composer test

# Or directly
./vendor/bin/phpunit
```

### Pre-Commit Checklist
Run these commands before committing:
```bash
composer lint
npm run format
npm run lint
npm run types
```

### CI/CD (GitHub Actions)

The project uses GitHub Actions for automated testing and linting:

- **`lint.yml`**: Runs on push/PR to check code style (PHP Pint, Prettier, ESLint)
- **`tests.yml`**: Runs PHPUnit tests on multiple PHP versions (8.4, 8.5)

These workflows automatically run when you:
- Push to `main`, `master`, `develop`, or `workos` branches
- Create a pull request to these branches

---

## 📝 AVAILABLE COMMANDS

| Command | Description |
|---------|-------------|
| `composer install` | Install PHP dependencies |
| `composer lint` | Fix PHP code style with Pint |
| `composer test` | Run PHPUnit tests |
| `npm install` | Install frontend dependencies |
| `npm run dev` | Start Vite dev server (hot reload) |
| `npm run build` | Build production assets |
| `npm run format` | Format frontend code with Prettier |
| `npm run lint` | Lint and fix JS/TS with ESLint |
| `npm run types` | Check TypeScript types |
| `php artisan serve` | Start Laravel development server |
| `php artisan migrate` | Run database migrations |
| `php artisan migrate:fresh` | Drop all tables and re-run migrations |

---

## 🐛 TROUBLESHOOTING

### Composer Install Fails (Missing Extensions)
If you see errors about missing `ext-gd` or `ext-zip`:
1. Open `php.ini` (usually at `C:\xampp\php\php.ini`)
2. Uncomment these lines:
   ```ini
   extension=gd
   extension=zip
   ```
3. Restart Apache/web server

### Database Connection Error
- Ensure MySQL is running in XAMPP
- Verify database name exists in phpMyAdmin
- Check `.env` credentials match your MySQL setup

### Port Already in Use
If port 8000 is taken:
```bash
php artisan serve --port=8001
```

### Node/NPM Issues
Clear cache and reinstall:
```bash
rm -rf node_modules package-lock.json
npm install
```

---

## 🤝 CONTRIBUTING

### Branch Strategy
- `main` - Production-ready code
- `develop` - Development branch
- `feature/*` - New features
- `bugfix/*` - Bug fixes

### Pull Request Process
1. Create a feature branch from `develop`
2. Make your changes
3. Run linting and tests locally
4. Push and create a PR to `develop`
5. Wait for CI checks to pass
6. Request review from team members

---

## 📄 LICENSE

This project is private and proprietary