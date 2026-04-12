# AINSystem: Trending Analysis — Web Application Documentation

**Version:** 1.0  
**Date:** April 2026  
**Department:** Information Technology Division — System Development Department  
**Company:** Ain Medicare Sdn. Bhd.

---

## 1. Overview

The **Trending Analysis** web application is a departmental tool used to record, monitor, and visualise **bioburden test results** for products manufactured at Ain Medicare. It replaces the previous plain-PHP portal with a modern, user-friendly interface.

### What It Does

- **Records bioburden test results** — lab technicians can enter individual test results or upload them in bulk via CSV/Excel files.
- **Tracks trending data** — monitors bioburden levels (TAMC & TYMC colony counts) across production lines over time.
- **Visual dashboards** — provides at-a-glance charts for each production line, with drill-down detail views.
- **Access control** — only authorised employees can log in, and access is controlled per program via the AINSystem access management tables.
- **Light & Dark theme** — supports both display modes for user comfort.

### Production Lines

The system currently tracks the following production lines (Small Volume Parenteral):

| Code | Description |
|------|-------------|
| SVP1 | SVP Production Line 1 |
| SVP2 | SVP Production Line 2 |
| SVP3 | SVP Production Line 3 |

More lines can be added as needed.

---

## 2. Database & Table Summary

The application uses **two databases**:

### A. MySQL — Local Database (`amsb21`)

Used for session management and application-level storage.

| Table | Purpose |
|-------|---------|
| `sessions` | Stores active user sessions (who is logged in) |
| `cache` | Application cache storage |
| `sy_0103` | Access log — records each time a user logs in (employee no, timestamp) |

### B. MSSQL — AINData Database (`194.100.1.249`)

The main data source. All business data lives here.

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| **SY_0050** | Employee master list (login accounts) | `EmpNo` (Employee ID), `Pass` (password) |
| **SY_0100** | Employee details (names) | `empno`, `prefername` |
| **SY_0055N** | Program access permissions | `EmpNo`, `ProgID`, `ALevel` (access level) |
| **SY_0061** | Program definitions | `ProgNo`, `ProgName`, `Description` |
| **TS_0010** | Bioburden test results (main data) | See below |
| **TS_0011** | Monthly remarks / observations | `prodline`, `monthyear`, `remark` |
| **PD_0010** | Product batch master | `Batch`, `PCode` |
| **PD_0030** | Product brand names | `NCODE`, `PBrand` |

### TS_0010 — Bioburden Test Results (Main Table)

This is the core table where all test data is stored.

| Column | Type | Description |
|--------|------|-------------|
| `id` | int | Auto-increment primary key |
| `prodline` | varchar | Production line code (SVP1, SVP2, SVP3) |
| `batch` | varchar | Batch number |
| `prodname` | varchar | Product name |
| `datetested` | date | Date the test was performed |
| `runno` | varchar | Run number |
| `tamcr1` | int | TAMC (Total Aerobic Microbial Count) — Replicate 1 |
| `tamcr2` | int | TAMC — Replicate 2 |
| `tymcr1` | int | TYMC (Total Yeast & Mould Count) — Replicate 1 |
| `tymcr2` | int | TYMC — Replicate 2 |
| `resultavg` | float | Average result (CFU) |
| `limit` | float | Specification limit (default: 10 CFU) |
| `AddDate` | date | Date the record was added |
| `AddTime` | time | Time the record was added |
| `AddUser` | varchar | Name of the user who added the record |
| `Status` | varchar | Record status — `ACTIVE` or `INACTIVE` |

### How Tables Interact

```
Login Flow:
  SY_0050 (verify employee credentials)
    → SY_0100 (get display name)
    → SY_0055N + SY_0061 (check program access for system "AINCCS")
    → sy_0103 [MySQL] (log the access)

Data Flow:
  Upload CSV/XLSX → TS_0010 (insert test results)
  Manual Entry    → TS_0010 (insert single result)
  Dashboard       → TS_0010 (read & aggregate for charts)
  Remarks         → TS_0011 (store monthly observations)
```

---

## 3. Dashboard Summary

### Main Dashboard (`/dashboard`)

The main dashboard shows **one card per production line**. Each card contains:

- A **sparkline chart** showing the average bioburden trend over the last 6 months
- **Summary stats**: total samples, average CFU, and maximum CFU recorded
- A red dashed reference line at the **specification limit** (10 CFU)
- Values exceeding the limit are highlighted in **red**

Click any card to open the **Detail Dashboard** for that production line.

### Detail Dashboard (`/dashboard/{prodline}`)

When you click a production line card, the detail view shows:

- **Tab navigation** — switch between production lines without going back
- **4 stat cards** — Total Samples, Average CFU, Max CFU, Latest Test Date
- **Monthly Trend Chart** — line chart of average bioburden over the last 12 months with the spec limit line
- **Batch Results Chart** — bar chart showing individual batch results from the last 60 days; bars turn red when they exceed the limit
- **Recent Entries Table** — the last 15 test records with full detail (date, product, batch, run, TAMC R1/R2, TYMC R1/R2, average, added by)

---

## 4. How to Use

### Logging In

1. Open the application in your web browser.
2. Enter your **Employee No** and **Password** (same as your AINSystem credentials).
3. Click **LOGIN**.
4. You will be redirected to the Home page if your account has access to the system.

> **Note:** Access is managed centrally. Contact IT if you cannot log in.

### Navigating the App

The top menu bar contains:

| Menu | Description |
|------|-------------|
| **Dashboard** | View trending charts for all production lines |
| **Programs** | View available program modules |
| **Upload** | Upload test result files |
| **Theme Toggle** (moon/sun icon) | Switch between Light and Dark mode |
| **Logout** | Sign out of the application |

### Uploading Test Results

1. Click **Upload** in the top menu.
2. Select the **Production Line** (SVP1, SVP2, or SVP3).
3. Drag & drop your file into the upload area, or click **browse** to select it.
4. Accepted formats: `.csv`, `.xlsx`, `.xls` (max 5MB).
5. For CSV files, a **preview** of the first 10 rows will be shown.
6. Click **Upload & Import**.
7. A success notification will appear showing how many records were imported.

#### Expected File Format

The file must have a header row with these columns:

```
datetested, prodname, batch, tamcr1, tamcr2, tymcr1, tymcr2, resultavg, limit
```

- **datetested** — Date in `dd/mm/yyyy` or `yyyy-mm-dd` format
- **prodname** — Product name (e.g., HYPROMELLOSE, SALBUNEB)
- **batch** — Batch number
- **tamcr1 / tamcr2** — TAMC replicate counts
- **tymcr1 / tymcr2** — TYMC replicate counts
- **resultavg** — Average result (auto-calculated if left blank)
- **limit** — Specification limit (defaults to 10 if left blank)

Optional column: `runno` (run number, defaults to "1").

### Viewing the Dashboard

1. Click **Dashboard** in the top menu.
2. You will see a card for each production line that has data.
3. Click on a card to see detailed charts and data for that production line.
4. Use the **tab buttons** at the top of the detail page to switch between production lines.

### Adding a Single Record

1. Navigate to the Bioburden page for a production line (via Programs).
2. Click the **Add** button.
3. Fill in the batch, run number, date, TAMC/TYMC counts, and result average.
4. Click **Submit**.

### Removing a Record

1. On the Bioburden data table, find the record to remove.
2. Click the **Remove** button next to it.
3. The record is soft-deleted (marked as INACTIVE) — it is not permanently erased.

---

## 5. Technical Notes (For IT Reference)

| Item | Detail |
|------|--------|
| Framework | Laravel 13 (PHP 8.3) |
| Frontend | Bootstrap 4, jQuery, DataTables, amCharts 4 |
| Local DB | MariaDB 10.4.28 at 127.0.0.1:3306 |
| Main DB | MSSQL at 194.100.1.249 (AINData) |
| System Name | AINCCS |
| Project Path | `D:\Development\trends-laravel` |
| Font | Inter (Google Fonts) |
| Theme | CSS variables with Light/Dark mode support |

---

*Document generated April 2026 — System Development Department, Ain Medicare Sdn. Bhd.*
