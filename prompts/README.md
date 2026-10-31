# AI Prompt Log & Development Artifacts

This folder documents the AI-assisted workflows, prompts, architectural blueprints, and screen recording references used during the development of the **Mallow Store Order & Inventory Management System**, in accordance with the assignment brief's transparency guidelines.

---

## Artifact Index

| Artifact | Type | Description |
|---|---|---|
| [`plan_prompt.png`](./plan_prompt.png) | Screenshot | Claude prompt analyzing the assignment PDF (`Laravel_Developer_Mini_Task.pdf`) to generate the comprehensive implementation plan. |
| [`mallow_plan.md`](./mallow_plan.md) | Document | The complete architectural master plan generated during planning (schema, concurrency locking, API contracts, Livewire UI). |
| [`code_prompt.png`](./code_prompt.png) | Screenshot | Claude prompt translating the master plan document into the core project codebase scaffolding. |
| [`Vijay Prasath - Mallow-store -2026-10-30.mp4`](./Vijay%20Prasath%20-%20Mallow-store%20-2026-10-30.mp4) | Video | Narrated walkthrough video demonstrating architecture, database schema, API endpoints, concurrency tests, and Livewire billing UI. |

---

## Narrated Walkthrough Video

- **File**: `Vijay Prasath - Mallow-store -2026-10-30.mp4` (~35 MB)
- **Topics Covered**:
  1. Application Overview & Requirements Breakdown
  2. Database Schema & Immutable Price/Tax Snapshots
  3. API Demonstration (`POST /api/orders`, Order History, Low-Stock Alert)
  4. Concurrency Safety (`lockForUpdate` & Transaction Rollbacks)
  5. Livewire Reactive Billing Screen Walkthrough

---

## AI-Assisted Workflow Breakdown

### Phase 1: Requirements Analysis & Master Plan
* **Tool**: Claude 3.5 Sonnet / ChatGPT
* **Screenshots**: [`plan_prompt.png`](./plan_prompt.png)
* **Output**: [`mallow_plan.md`](./mallow_plan.md)
* **Prompt**:
  > *"analyse the requirements and make a plan_document.md file"*
* **Key Decisions Formulated**:
  1. **Schema Design**: Normalized schema across `customers`, `products`, `orders`, and `order_items`. Saved immutable `unit_price` and `tax_percentage` snapshots on line items to ensure order history remains intact regardless of future product catalog changes.
  2. **Concurrency Safety**: Designed pessimistic locking (`lockForUpdate()`) within a database transaction to prevent inventory overselling during concurrent checkouts.
  3. **Asynchronous Architecture**: Dispatched `SendOrderConfirmationEmail` job to a queue to keep order placement responses sub-second.
  4. **Livewire Billing UI**: Planned reactive interface matching the wireframe with real-time subtotal, GST tax calculations, and cash change computation.

---

### Phase 2: Architectural Scaffolding & Code Generation
* **Tool**: Claude 3.5 Sonnet
* **Screenshot**: [`code_prompt.png`](./code_prompt.png)
* **Prompt**:
  > *"Analyse the plan document file and give me codes"*
* **Output**:
  - Eloquent Models & Migrations (`Product`, `Customer`, `Order`, `OrderItem`)
  - Domain Service (`OrderService`) isolating transaction & inventory lock logic
  - JSON API Controllers (`OrderController`, `CustomerOrderController`, `ProductController`)
  - Livewire Billing Component (`App\Livewire\NewOrder`)
  - Automated Pest Test Suite (`tests/Feature/`)

---

### Phase 3: Developer Review, Verification & Refinement
AI generation was actively reviewed, integrated, and validated with engineering rigor:
- **Environment & Runtime Setup**: Configured PHP 8.4 runtime, extensions (`pdo_mysql`, `curl`, `intl`, `mbstring`, `zip`), and Composer CLI.
- **Database & Concurrency Validation**: Configured local MySQL instance to verify that row-level locking behaves accurately under real transactional conditions (unlike SQLite in-memory).
- **Frontend Asset Resolution**: Built and verified production assets in `public/build` and resolved hot-reloading configurations.
- **Comprehensive Testing**: Executed Pest test suites verifying successful orders, validation errors, and the insufficient-stock rollback edge case.
