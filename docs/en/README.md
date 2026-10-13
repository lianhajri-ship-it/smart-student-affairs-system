# Smart Student Affairs System (STD)

A web platform for the **Deanship of Student Affairs at the University of Bisha**. Students submit and track official requests, browse services, guides, forms and FAQs, and get instant answers from an AI assistant built on Google Gemini with retrieval-augmented generation (RAG).

It is a graduation project from the Artificial Intelligence program, College of Computing and Information Technology.

> Other documents in this folder:
> - [ARCHITECTURE.md](ARCHITECTURE.md): folder layout, database schema, request lifecycle, AI/RAG pipeline, security model
> - [PAGES.md](PAGES.md): every page and backend endpoint, grouped by role

---

## 1. Technology Stack

| Layer | Technology | Notes |
|---|---|---|
| Server language | **PHP 8+** | Plain PHP with no framework. Uses PHP 8 features such as `match`, typed properties, arrow functions and `str`/`mb_` functions |
| Database | **MySQL / MariaDB** | Database `std_system`, `utf8mb4`, InnoDB with foreign keys. Dump: `std_system.sql` |
| DB access | **PDO** | Prepared statements, exception error mode, `FETCH_ASSOC`, emulated prepares disabled |
| Web server | **Apache (XAMPP)** | App served under `/STD/`. `includes/.htaccess` blocks direct access to includes |
| UI framework | **Bootstrap 5.3.3 (RTL build)** | Loaded from jsDelivr CDN |
| Icons | **Bootstrap Icons 1.11.3** | CDN |
| Fonts | **Google Fonts: Tajawal and Cairo** | Arabic typography |
| Client scripting | **Vanilla JavaScript** (`assets/js/main.js`) | Chatbot client (Fetch API), alerts, table search, previews, animations |
| Styling | Custom CSS (`assets/css/style.css`) | Theme color `#1a5f7a`, cache-busted using `filemtime` |
| AI generation | **Google Gemini API** (`gemini-2.5-flash` by default) | Called with cURL from `includes/gemini.php` |
| AI embeddings | **Gemini Embeddings** (`gemini-embedding-001` / `gemini-embedding-2-preview`) | Vectors are stored as JSON in MySQL |
| Vector search | **Cosine similarity in PHP** | `findRelevantChunks()` in `includes/functions.php` |
| Security | Sessions, bcrypt (`password_hash`), CSRF tokens, `htmlspecialchars` output escaping, role guards | See ARCHITECTURE.md §6 |

The whole UI is Arabic and right-to-left (`<html lang="ar" dir="rtl">`).

---

## 2. User Roles

| Role (`user_types.title`) | Arabic | Landing page | Summary |
|---|---|---|---|
| `student` | طالب | `/student/dashboard.php` | Uses services, the AI chatbot, requests and profile |
| `admin` | إداري | `/admin/dashboard.php` | Handles requests, manages content and the knowledge base, reviews the chatbot |
| `manager` | مدير | `/manager/dashboard.php` | Everything an admin can do, plus users, academic structure, reports and system settings |
| `academic_advisor` | مرشد أكاديمي | `/advisor/dashboard.php` | Handles requests routed to them by their own students, sees their students and works on meeting minutes |

`isAdmin()` returns true for **both** `admin` and `manager`. A manager therefore passes every admin-only check.

---

## 3. Features

### 3.1 Public website
- **Landing page** (`index.php`): hero section, statistics strip, features, about the university and the deanship, the team, a call to action and a footer.
- **About page** (`about.php`): what the system is, the university, the deanship's missions, six project goals, the tech stack and the development team.
- **Sign-up** (`signup.php`): student self-registration. College, degree, major and advisor are cascading dropdowns driven by JavaScript. Validation covers email format, duplicate email, duplicate national ID, a password of at least 8 characters and a matching confirmation.
- **Login** (`login.php`): two modes. Students sign in with email **or** student number. Staff (admin, manager, advisor) sign in with email. After login, users are redirected according to their role.

### 3.2 AI chatbot (RAG)
- A chat UI for students with conversation history (last 10 conversations), a typing indicator and Markdown rendering for bold text, bullets and download links.
- **Retrieval-augmented generation**: the question is embedded, compared with every knowledge chunk by cosine similarity, and the top *N* chunks (setting `max_chunks_retrieve`, default 5) are passed to Gemini with an Arabic system prompt.
- **Several fallback layers**:
  1. No API key → plain keyword search over FAQ, then services.
  2. Embedding fails → SQL `LIKE` search on chunk text.
  3. No chunks found → `LIKE` search on FAQ questions and keywords.
  4. Any exception → keyword fallback, and the question is flagged as unknown.
- **Low-confidence detection**: if the top similarity is below **0.55**, or the answer contains phrases such as "لا توجد معلومات" (no information is available), the bot suggests submitting a formal request.
- **Unknown questions loop**: low-confidence questions are saved to `unknown_questions` (deduplicated over 5 minutes) and every active admin and manager is notified. From the unknown questions page, staff can turn a question into a knowledge base entry, which marks it `added_to_kb`.
- **Download links in answers**: guides and forms put their file links into the knowledge base, so the bot can answer with a direct download link.
- **Chatbot can be switched on and off** from Settings (`chatbot_enabled`).

### 3.3 Knowledge base
- CRUD for entries typed as `service`, `faq`, `guide`, `form`, `policy` or `info`, with a status of published, draft or archived.
- Content is split into chunks (setting `chunk_size`, default 500 characters) and each chunk gets an embedding.
- **Automatic sync**: saving a service, FAQ, guide or form creates or updates its knowledge base entry.
- **Batch embedding generation** (`generate_embeddings.php`): processes up to 50 pending chunks per run, with a 200 ms delay between calls to respect rate limits. Progress is shown on the Settings page and the admin dashboard.

### 3.4 Requests (tickets)
- Students choose a service, then submit a request with a title, description, priority and an optional attachment (pdf, doc, docx, jpg, jpeg or png, up to 5 MB).
- **Smart routing** uses the service's `assigned_role`:
  - `admin`: all admins and managers are notified.
  - `advisor`: the request is assigned to the student's own academic advisor, who is notified.
  - `both`: assigned to the advisor, and admins are notified as well.
- **Statuses**: `new` → `under_review` → `replied` → `completed` / `rejected` / `closed`.
- **Priorities**: low, medium, high.
- **Threaded replies** with attachments. A staff reply sets the status to `replied` automatically.
- Staff can change status, priority and assignee. Advisors can only act on requests assigned to them.
- The student is notified on each status change and each reply.

### 3.5 Meeting minutes and recommendations
- Admins, managers and advisors can open the **deanship committee meetings** module. Each meeting has a title, date and notes.
- Each meeting holds **recommendations** with:
  - Decision: approved, rejected, other or notes (empty means pending)
  - Support type: loan, prize, fixed grant, marriage grant or other
  - Support amount in SAR
  - Disbursement status: disbursed or not disbursed
- A meeting's status is **calculated automatically**. It is `complete` when it has at least one recommendation and none of them are pending.
- The meeting page shows a progress bar and a count per status.
- In the UI, only admins and managers can create, edit or delete meetings and delete recommendations. Advisors can view meetings and add or edit recommendations. Note that `backend/save_meeting.php` itself also accepts advisors, so this limit is enforced only by the UI.

### 3.6 Content management
- **Services**: title, description, department, conditions, required documents, steps, keywords, icon, status and routing role.
- **Guides** and **Forms**: downloadable files plus a description, linked to a service or department.
- **FAQ**: question, answer, keywords, sort order and links to a service or department.
- Students browse services (filtered by department, with search), guides and forms (tabs with search) and the FAQ.

### 3.7 User and academic structure management (manager)
- Students: search, filter by college or status, add, edit, change status, assign an advisor, delete.
- Admins and academic advisors: CRUD.
- Activate or suspend users (`toggle_user_status.php`). A suspended user is logged out on their next page load, because `requireLogin()` checks the user's status on every request.
- Colleges, degrees, majors (each linked to a college and a degree), departments and user types.

### 3.8 Notifications and activity log
- In-app notifications with a bell badge, a dropdown showing the latest 5, deep links and a "mark all as read" action.
- `activity_logs` records important actions (login, request submission, replies, content saves, deletions, settings changes) together with the client IP.

### 3.9 Reports (manager)
- Summary cards: students, requests, conversations and knowledge entries.
- Requests by status, requests by service (top 10), requests per month for the last 6 months.
- Chatbot statistics: conversations, messages, student questions and average messages per conversation.
- User activity per day over the last 14 days, and users per role.
- Charts are drawn with Bootstrap progress bars. No charting library is used.

### 3.10 System settings (manager)
- Site name (Arabic and English), university name, support email and phone, and maintenance mode.
- Logo upload and removal (png, jpg, jpeg, svg or webp). The logo appears in the navbar.
- AI settings: Gemini API key, chat model (`gemini-2.5-flash`, `2.5-pro`, `2.5-flash-lite` or `2.0-flash`), embedding model, chatbot on/off, chunk size and number of chunks to retrieve.
- Embedding progress, with a button that generates the remaining embeddings.

### 3.11 Profiles
- Every role has a profile page for editing personal details, uploading a profile picture and changing the password. Students have additional academic fields.
- Default avatars depend on gender (`avatar-male.svg` / `avatar-female.svg`).

---

## 4. Installation (Local, XAMPP)

1. Copy the project to `C:\xampp\htdocs\STD`. The folder name **must** be `STD`, because paths such as `/STD/...` are hard-coded.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. In phpMyAdmin, create a database named `std_system` with collation `utf8mb4_general_ci`, then import `std_system.sql`.
4. If your credentials are not XAMPP's defaults (`root` with no password), edit `includes/db.php`.
5. Make sure the PHP `curl`, `pdo_mysql` and `mbstring` extensions are enabled.
6. Open `http://localhost/STD/`.
7. Sign in as a manager, go to **Settings**, enter **your own** Gemini API key from [Google AI Studio](https://aistudio.google.com/apikey), then generate embeddings from the knowledge base or Settings page.

### Migration scripts (`backend/migrate*.php`)
These one-off scripts upgrade older databases: they add colleges, degrees and majors, convert advisors into users, and add the meeting, request routing and unknown questions tables. **A fresh import of `std_system.sql` already includes all of these changes.**

### Diagnostics
`backend/test_gemini.php` (manager only) lists the available Gemini models and tests the chat and embedding endpoints with the configured key.

---

## 5. Configuration Keys (`system_settings`)

| Key | Default | Purpose |
|---|---|---|
| `site_name` / `site_name_en` | نظام عمادة شؤون الطلاب / Student Affairs System | Branding |
| `university_name` | جامعة بيشة | Shown on public pages |
| `support_email`, `support_phone` | | Contact information |
| `site_logo` | | Path to the uploaded logo |
| `maintenance_mode` | `0` | Stored, but **not enforced** anywhere yet |
| `primary_color`, `logo` | | Old keys that are no longer used |
| `gemini_api_key` | | Required for AI features |
| `gemini_model` | `gemini-2.5-flash` | Chat model |
| `gemini_embedding_model` | `gemini-embedding-001` | Embedding model |
| `chatbot_enabled` | `1` | Turns the bot on or off |
| `chunk_size` | `500` | Maximum characters per chunk |
| `max_chunks_retrieve` | `5` | Top-K chunks retrieved per question |

---

## 6. Development Team

The team members' names, as they appear in `about.php`:

لينا مشرع الهاجري · نوال فلاح القحطاني · لارا وهيطان · ياسمين حمد · نوت عبدالله · ريما سعد القرني · جود عبدالله القرني · أفنان محمد القرني · رباب ظفير الشمراني · منار سعد الشهراني

AI program, College of Computing and Information Technology, University of Bisha.
