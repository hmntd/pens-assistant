# PensAssistant — Pension Audit & Calculation Platform

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)
![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-emerald.svg)
![C++ Engine](https://img.shields.io/badge/C%2B%2B_Engine-gRPC-blue.svg)

> **Documentation Languages:** 🇬🇧 **English** (Default) | [🇺🇦 **Українська**](documents/README.UA.md)

**PensAssistant** is a microservice-based pension audit and calculation platform designed according to the statutory pension framework of Ukraine (Law No. 1058-IV *"On Mandatory State Pension Insurance"*). 

The system combines a reactive web orchestrator, high-precision template-based OCR for employment/tax documents, automated scrapers for official Pension Fund of Ukraine (PFU) data, and a high-performance C++ calculation engine with a sliding-window optimization algorithm for historical salary analysis.

---

## 🏛️ Microservice Architecture & Tech Stack

```
                                 ┌───────────────────────────────┐
                                 │   Vue 3 Frontend (Atomic UI)  │
                                 └──────────────┬────────────────┘
                                                │ Inertia.js / REST
                                                ▼
┌──────────────────────────┐    gRPC    ┌───────────────────────────────┐    gRPC    ┌──────────────────────────┐
│   Python OCR Engine      │ ◄────────► │   Laravel CRM Orchestrator    │ ◄────────► │  C++ Calculation Engine  │
│  (Template & Coordinate) │            │   (Redis, Horizon, Postgres)  │            │ (Sliding Window & Rules) │
└──────────────────────────┘            └──────────────┬────────────────┘            └──────────────────────────┘
                                                       │ Cron / Scrapers
                                                       ▼
                                        ┌───────────────────────────────┐
                                        │    Official PFU Web Sources   │
                                        └───────────────────────────────┘
```

### 1. Web Orchestrator & CRM (`/crm`)
* **Framework:** Laravel 13, Inertia.js v3, PHP 8.5
* **Frontend UI:** Vue 3 (Composition API), Atomic Design Architecture, Tailwind CSS, Lucide Icons, `vue-sonner` toast notifications.
* **Authentication:** Laravel Fortify & Socialite (OAuth2: Google, LinkedIn, GitHub, Microsoft Azure), 2FA, Passkeys (WebAuthn).
* **Queue & Async Processing:** Redis, Laravel Horizon.
* **PDF Report Generation:** Dompdf (`barryvdh/laravel-dompdf`), supporting bilingual (EN/UK) report output.

### 2. High-Performance C++ Calculation Engine (`/calc`)
* **Core:** C++20, gRPC, Protocol Buffers (`/protos`).
* **Design Patterns:** Strategy Pattern (`IBenefitStrategy`, `BenefitRulesEngine`), Sliding Window Search.
* **Purpose:** Computes 5-stage pension mathematical pipelines with microsecond latency.

### 3. Precision OCR Service (`/ocr`)
* **Core:** Python 3.12, gRPC.
* **Mechanism:** Coordinate and spatial template-based alignment for extraction of employment histories, income declarations, and tax forms without data loss.

### 4. Database & Infrastructure
* **Database:** PostgreSQL 16.
* **Caching & Queue Store:** Redis Alpine.
* **Containerization:** Docker & Docker Compose (`docker-compose.yml`).
* **CI/CD & Cloud Deployment:** GitHub Actions, AWS infrastructure with zero-downtime deployment (Deployer).

---

## ✨ Key Features & Mechanics

### 🧮 1. 5-Stage C++ Calculation Pipeline
The C++ calculation engine computes pensions using the statutory formula:
$$\text{Base Pension } (P_{\text{base}}) = Z_p \times K_z \times K_s$$

* **$Z_p$ (Macroeconomic Average Wage):** 3-year national average wage preceding the retirement application year.
* **$K_z$ (Wage Ratio Coefficient):** Weighted average ratio of the applicant's monthly earnings to the national average.
* **$K_s$ (Insurance Service Multiplier):** Computed as $\frac{\text{Total Service Months}}{1200}$.

### 🔍 2. Optimal 60-Month Pre-2000 Period Search (Sliding Window Algorithm)
Under Ukrainian pension legislation, applicants can include any continuous 60-month work period prior to July 1, 2000, to maximize $K_z$.
* The C++ engine applies a **Sliding Window Algorithm** across all historical tax and salary records prior to July 2000.
* It identifies the single continuous 5-year window that yields the highest possible wage coefficient $K_z$.

### ⏱️ 3. Overtime Service Surcharges
* Automatically computes extra service years exceeding statutory thresholds (35 years for men, 30 years for women).
* Adds $+1\%$ of the base pension or general subsistence minimum for each full year of extra service.

### 🎖️ 4. Extensible Benefits & Surcharges (Strategy Pattern)
Calculated dynamically against DB-stored statutory subsistence minimums (`subsistence_minimums`):
* **Combat Veteran (УБД — Учасник бойових дій):** $+25\%$ of the disabled person's subsistence minimum (Art. 12 Law *"On Status of War Veterans"*).
* **Honorary Donor of Ukraine (Почесний донор):** $+10\%$ of the general subsistence minimum (Art. 21 Law *"On Blood Donation"*).
* **Chornobyl Liquidator (Чорнобилець-ліквідатор):** $+30\%$ of the disabled person's subsistence minimum (Art. 13 Law *"On Status & Social Protection of Citizens Affected by Chornobyl"*).
* **Disabled Child Care (Догляд за дитиною з інвалідністю):** $+10\%$ of the disabled person's subsistence minimum.

### 🤖 5. Precision Template OCR Processing
* Utilizes predefined bounding-box templates and spatial coordinate mapping.
* Dispatched asynchronously to background queues managed by **Laravel Horizon**.

### 🔄 6. Automated PFU Data Synchronization (Cron Jobs)
* **Average Salaries Sync:** Cron task that scrapes the latest 3-year average wage figures published on the official PFU portal.
* **Statutory Minimums Scraper:** Cron task fetching statutory budget minimums for general and disabled demographics.
* **PFU News Scraper:** Cron job populating the platform with official updates.

### 📊 7. Admin Dashboard & System Error Monitoring
* **Analytics & Insights:** Charts for calculations count, demographic distribution, document processing stats.
* **User & Document Management:** Full RBAC, soft deletes, account suspensions.
* **System Error Log & Resolution Manager:** Captures 5xx runtime errors and client-side error reports with batch resolution controls.

### 📄 8. Bilingual PDF Report Generation
* Direct file download feature generating print-styled PDF summaries.
* Offers language-specific templates (`pension_calculation_report_uk.blade.php` and `pension_calculation_report_en.blade.php`) matching the user's active UI locale.

---

## 🚀 Installation & Local Setup

### Prerequisites
* Docker Engine `^24.0` & Docker Compose `^2.20`
* Git

### Step-by-Step Setup

1. **Clone the Repository:**
   ```bash
   git clone https://github.com/hmntd/pens-assistant.git
   cd pens-assistant
   ```

2. **Configure Environment File:**
   ```bash
   cp crm/.env.example crm/.env
   ```

3. **Spin Up Containers via Docker Compose:**
   ```bash
   docker compose up -d --build
   ```

4. **Install Backend Dependencies & Run Database Migrations:**
   ```bash
   docker compose exec crm composer install
   docker compose exec crm php artisan key:generate
   docker compose exec crm php artisan migrate --seed
   ```

5. **Build Frontend Production Assets:**
   ```bash
   docker compose exec crm npm install
   docker compose exec crm npm run build
   ```

6. **Access the Application:**
   * **Web Platform:** [http://localhost:8000](http://localhost:8000)
   * **Horizon Queue Monitor (Admin):** [http://localhost:8000/horizon](http://localhost:8000/horizon)

---

## 📁 Project Structure

```
pens-assistant/
├── README.md                           # Main English Documentation
├── documents/
│   └── README.UA.md                    # Ukrainian Documentation (Українська версія)
├── calc/                               # C++ Calculation Engine (gRPC)
│   ├── src/
│   │   ├── service/                    # Strategy Engine (IBenefitStrategy, BenefitRulesEngine)
│   │   └── stage/                      # 5-Stage Pension Calculation Pipeline
│   └── CMakeLists.txt
├── ocr/                                # Python Coordinate OCR Service (gRPC)
│   └── src/
│       └── main.py                     # OCR Processing & Template Engine
├── protos/                             # Protocol Buffers Definitions
│   ├── calc.proto
│   └── ocr.proto
├── crm/                                # Laravel 13 Web Orchestrator & Frontend
│   ├── app/
│   │   ├── Http/Controllers/           # Web & Admin Controllers
│   │   ├── Services/                   # PDF Service, Scrapers & gRPC Clients
│   │   └── Models/                     # Eloquent Models (CalculatedPension, User, etc.)
│   ├── resources/
│   │   ├── js/                         # Vue 3 Frontend (Atomic Design Components)
│   │   │   ├── components/             # Atoms, Molecules, Organisms, Admin Sections
│   │   │   ├── i18n/                   # Language Dictionaries (en.ts, uk.ts)
│   │   │   └── pages/                  # Inertia Pages
│   │   └── views/pdf/                  # PDF Report Templates (UK & EN)
│   └── routes/web.php
├── docker-compose.yml                  # Container Orchestration
└── deploy.php                          # Zero-downtime Deployment Configuration
```

---

## 📄 License & Attribution

Distributed under the MIT License. See `LICENSE` for details. Built for pension analysis and audit in accordance with Law of Ukraine No. 1058-IV.
