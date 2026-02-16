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
 └── Imports/
      └── UploadImport.php