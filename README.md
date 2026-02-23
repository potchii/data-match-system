# 📌 PROJECT: Data Match System

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
 ├── Imports/
 │    └── RecordImport.php
 └── Console/Commands/
      └── BackfillOriginBatchData.php
```

---

## 🛠️ ARTISAN COMMANDS

### Data Management Commands

#### Backfill Origin Batch Data
Backfills `origin_batch_id` and `origin_match_result_id` for existing main_system records. This command is useful when you need to retroactively link existing records to their source batch files.

```bash
php artisan data:backfill-origin-batch
```

**What it does:**
- Finds all main_system records without origin batch information
- Locates the first match result where each record was created (status = NEW RECORD)
- Updates records with their origin batch ID and match result ID
- Displays progress and summary of updated records

**When to use:**
- After adding origin tracking to existing data
- When migrating from a system without batch tracking
- To restore batch lineage after data imports

#### Remove Duplicate Records
Identifies and removes duplicate records from the main_system table based on name and date of birth, keeping only the oldest record in each duplicate group.

```bash
# Preview duplicates without deleting (dry run)
php artisan duplicates:remove --dry-run

# Actually remove duplicates
php artisan duplicates:remove
```

**What it does:**
- Finds duplicate groups by matching: last_name, first_name, middle_name, and birthday
- Keeps the oldest record (lowest ID) in each group
- Deletes all newer duplicates
- Shows detailed output of what will be/was deleted

**When to use:**
- After discovering duplicate records in the system
- When cleaning up data quality issues
- Before running reports that require unique records

#### Purge Database Data
Clears all data from the database while preserving the table structure. Useful for testing and development.

```bash
# Purge all data including user accounts
php artisan data:purge

# Purge data but keep user accounts
php artisan data:purge --keep-users
```

**What it does:**
- Truncates match_results, upload_batches, and main_system tables
- Optionally preserves user accounts and authentication data
- Safely handles foreign key constraints
- Provides confirmation prompt before deletion

**When to use:**
- Resetting test environment
- Clearing development data
- Starting fresh without re-running migrations

---

## 📊 FEATURES IMPLEMENTED

### Match Results Tracking
- Each match result stores uploaded record details (name fields)
- Displays full name and origin information for matched records
- Shows batch source and row ID for complete data lineage
- Format: `Name (Row ID: X from Batch #Y: filename.xlsx)`

### Date of Birth Field Variations
The matching algorithm supports multiple DOB field name variations:
- `birthday`, `dob`, `DOB`
- `date_of_birth`, `dateOfBirth`, `DateOfBirth`, `dateofbirth`
- `birthdate`, `BirthDate`, `birth_date`, `Birthday`, `Birthdate`

All variations are automatically normalized to `Y-m-d` format before matching.

### Origin Batch Tracking
- Main system records track which batch file originally created them
- Links back to the specific match result row
- Enables full audit trail of data sources
- Supports data lineage and compliance requirements