# Architecture

## 1. Folder Structure

```
STD/
├── index.php              Public landing page
├── about.php              Public "About the system" page
├── login.php              Login form (student and staff modes)
├── signup.php             Student self-registration
├── std_system.sql         Full database dump (schema and seed data)
│
├── includes/              Shared code (direct HTTP access blocked by .htaccess)
│   ├── db.php             PDO connection and getSetting()
│   ├── auth.php           Session, role checks, require*() guards, login/logout, role redirect
│   ├── functions.php      Helpers: flash messages, notifications, activity log, uploads,
│   │                      labels, pagination, CSRF, chunking, cosine similarity, retrieval
│   ├── gemini.php         GeminiAPI class (embeddings and answer generation over cURL)
│   ├── header.php         HTML <head>, top navbar, notifications dropdown, user menu
│   ├── sidebar.php        Sidebar menu for each role
│   └── footer.php         Closes the layout and loads Bootstrap JS and main.js
│
├── student/               Student pages
├── admin/                 Admin pages (managers can use them too)
├── manager/               Manager pages, plus the shared meeting minutes pages
├── advisor/               Academic advisor pages
├── backend/               POST/GET action endpoints (save_*, delete_*, chatbot, migrations)
│
└── assets/
    ├── css/style.css      Theme
    ├── js/main.js         UI helpers and the Chatbot client object
    ├── images/            Logo and default avatars
    └── uploads/           User files: forms/, guides/, logos/, profiles/, requests/
```

### Design pattern
The app is a **page controller** design with no framework and no router:

- Each page under `student/`, `admin/`, `manager/` or `advisor/` follows the same steps: start the session, include `db`, `auth` and `functions`, call a `require*()` guard, run queries, set `$pageTitle`, include `header.php`, output HTML, then include `footer.php`.
- Each form posts to a script in `backend/`. The script validates the CSRF token, checks the role, writes to the database, logs the activity, sends notifications, sets a flash message and **redirects** (Post/Redirect/Get).
- The chatbot is the only JSON/AJAX endpoint (`backend/chatbot_process.php`).
- `admin/*` and `manager/*` contain nearly identical copies of the content pages (services, guides, forms, FAQ, knowledge base, requests, chatbot reviews), so each role keeps its own sidebar context. Backend scripts call `isManager()` to decide where to send the user afterwards.

---

## 2. Database Schema (`std_system`)

### Users and academic structure
| Table | Key columns | Notes |
|---|---|---|
| `user_types` | id, title, arabic_title | 1 student, 2 admin, 3 manager, 4 academic_advisor |
| `users` | user_type_id, student_number, full_name, national_id, phone, email, password (bcrypt), date_of_birth, gender, college_id, degree_id, major_id, **academic_advisor_id** (references `users.id`), profile_image, status (active/inactive/suspended), activation, last_login | One table holds every role. Advisors are users |
| `colleges` | title, status | 7 colleges seeded |
| `degrees` | title, status | Bachelor, Master, PhD, Diploma |
| `majors` | title, college_id, degree_id, status | Drives the cascading sign-up dropdowns |
| `departments` | title, description, status | Deanship units, such as psychological counseling and gifted students |

### Services and content
| Table | Key columns |
|---|---|
| `services` | title, description, department_id, conditions, required_documents, steps, keywords, icon, status, **assigned_role** (admin/advisor/both), created_by |
| `guides` | title, description, file_path, service_id, department_id, status |
| `forms` | title, description, file_path, service_id, department_id, status |
| `faq` | question, answer, service_id, department_id, keywords, sort_order, status |

### Requests
| Table | Key columns |
|---|---|
| `requests` | student_id, service_id, title, description, attachment, **status** (new/under_review/replied/completed/rejected/closed), **assigned_to** (user), **priority** (low/medium/high) |
| `request_replies` | request_id, user_id, reply_message, attachment |

### AI and chatbot
| Table | Key columns |
|---|---|
| `knowledge_base` | title, content, type (service/faq/guide/form/policy/info), related_id (source row), service_id, department_id, keywords, status |
| `knowledge_chunks` | knowledge_id, chunk_text, chunk_order, **embedding_json** (LONGTEXT JSON float array), embedding_model |
| `chatbot_conversations` | student_id, session_title (first 50 characters of the first question) |
| `chatbot_messages` | conversation_id, sender_type (student/bot), message_text, matched_chunks (JSON array of IDs), confidence_score |
| `chatbot_reviews` | conversation_id, message_id, reviewed_by, review_note, review_status (good/needs_improvement/bad) |
| `unknown_questions` | student_id, conversation_id, question_text, confidence_score, status (pending/reviewed/added_to_kb), reviewed_by, reviewed_at |

### Meetings
| Table | Key columns |
|---|---|
| `meeting_minutes` | title, meeting_date, notes, status (complete/incomplete), created_by |
| `meeting_recommendations` | meeting_id, title, rec_status (approved/rejected/other/notes, or NULL for pending), rec_notes, support_type (loan/prize/fixed_grant/marriage_grant/other), support_amount, disbursement_status |

### System
| Table | Key columns |
|---|---|
| `system_settings` | setting_key (unique), setting_value |
| `notifications` | user_id, title, message, type, link, is_read |
| `activity_logs` | user_id, action_type, description, ip_address |

### Entity relationships (simplified)
```
user_types 1──* users *──1 colleges / degrees / majors
users (advisor) 1──* users (students)          via academic_advisor_id
users (student) 1──* requests *──1 services *──1 departments
requests 1──* request_replies
users (student) 1──* chatbot_conversations 1──* chatbot_messages
knowledge_base 1──* knowledge_chunks
meeting_minutes 1──* meeting_recommendations
```

---

## 3. Authentication and Authorization

- **Session based.** `loginUser()` stores `user_id`, `user_type`, `full_name`, `email` and `profile_image` in `$_SESSION`.
- **Passwords** are hashed with `password_hash(..., PASSWORD_BCRYPT)` and checked with `password_verify`.
- **Login** (`backend/login.php`):
  - Student mode: `email = ? OR student_number = ?`, restricted to the `student` type.
  - Staff mode: `email = ?`, restricted to admin, manager or academic_advisor.
  - The account needs `activation = 1` and `status = 'active'`.
  - On success, the script updates `last_login`, writes an activity log entry and calls `redirectByRole()`.
- **Guards** (in `includes/auth.php`):
  | Guard | Allows |
  |---|---|
  | `requireLogin()` | Any logged-in user whose DB status is still `active`. It checks the database on **every request**, so a suspension takes effect immediately |
  | `requireStudent()` | student |
  | `requireAdmin()` | admin **or** manager |
  | `requireManager()` | manager only |
  | `requireAdvisor()` | academic_advisor |
  | Inline `isAdmin() \|\| isAdvisor()` | Meeting pages, request status updates, deletions |
- **Row-level checks**: students can only see their own requests and conversations. Advisors can only see and change requests where `assigned_to` is their own ID.

---

## 4. Request Lifecycle

```
Student picks a service (student/services.php)
        │
        ▼
student/new_request.php ──POST──► backend/save_request.php
        │  • CSRF check, required fields, optional upload (≤5 MB)
        │  • read services.assigned_role
        │      admin   → assigned_to = NULL; notify all admins and managers
        │      advisor → assigned_to = student's academic_advisor_id; notify the advisor
        │      both    → assigned to the advisor; notify the advisor, admins and managers
        │  • INSERT requests (status = new)
        ▼
Staff open request_details.php
        ├─ Reply   → backend/save_reply.php
        │            INSERT request_replies; status → replied; notify the student
        └─ Update  → backend/update_request_status.php
                     status / priority / assignee (admins only); notify the student
Student replies → save_reply.php (status unchanged; notify the advisor and/or admins)
```

---

## 5. AI / RAG Pipeline

### 5.1 Indexing (writing to the knowledge base)
```
Admin saves a Service / FAQ / Guide / Form / KB entry
        │
        ▼
Content builder (save_service.php, save_faq.php, save_guide.php, save_form.php, save_knowledge.php)
   • Guides and forms add "[title](/STD/assets/uploads/<file>)" so the bot can link the file
   • Upsert knowledge_base (type + related_id)
   • DELETE the old knowledge_chunks for that entry
   • splitIntoChunks(content, chunk_size)   ← splits on whitespace, max N chars each
   • save_knowledge.php: generate the embeddings right away
     save_service/faq/guide/form: store chunks without embeddings
        │
        ▼
backend/generate_embeddings.php (button in the KB page or Settings)
   • up to 50 chunks per run where embedding_json IS NULL
   • GeminiAPI::generateEmbedding() → JSON array stored in embedding_json
   • sleeps 200 ms between calls
```

### 5.2 Answering (`backend/chatbot_process.php`)
```
POST message (+ conversation_id)
   │
   ├─ chatbot_enabled != 1 → "assistant unavailable", suggest_request = true
   ├─ no API key           → simpleFallbackSearch (FAQ LIKE, then services LIKE)
   │
   ▼
1. embedding = Gemini.generateEmbedding(question)
2. if embedding:
       load every published chunk that has an embedding
       score = cosineSimilarity(q, chunk) for each chunk (computed in PHP)
       top K = max_chunks_retrieve
   else:
       chunks WHERE chunk_text LIKE %question%   (score 0.5)
3. if there are still no chunks: faq WHERE question/keywords LIKE   (score 0.6)
4. answer = Gemini.generateAnswer(question, chunks, Arabic system prompt)
       temperature 0.7, maxOutputTokens 8192, safety: BLOCK_MEDIUM_AND_ABOVE
5. suggest_request = topScore < 0.55  OR  answer contains an "I don't know" phrase
6. saveConversation(): create or update the conversation, store the student message
   and the bot message (with matched_chunks and confidence_score)
7. if suggest_request: flagUnknownQuestion()
       skip if the same student asked the same question within 5 minutes
       INSERT unknown_questions; notify all active admins and managers
8. JSON → { answer, suggest_request, conversation_id }
   on any exception: keyword fallback + flag + suggest_request = true
```

### 5.3 Client (`assets/js/main.js` → `Chatbot`)
- `init(convId)`: Enter sends the message and Shift+Enter adds a new line.
- `sendMessage()`: shows the message and a typing indicator, POSTs a `FormData` to the endpoint and shows the answer.
- `formatText()`: escapes HTML first, then renders `[text](/path)` links (local paths only), `**bold**` and `•`/`-` bullets.
- `showRequestSuggestion()`: adds a "submit a formal request" button when the backend sets `suggest_request`.
- The server-side copy of this formatter is `formatChatMessage()` in `functions.php`, which renders saved messages.

### 5.4 Human in the loop
- **Chatbot reviews** (`admin|manager/chatbot_reviews.php`): browse every conversation, see the max confidence and rate it good, needs improvement or bad, with a note.
- **Unknown questions** (`admin/unknown_questions.php`): filter by status, mark a question as reviewed, or open a pre-filled knowledge base form. Saving it with `uq_id` sets the status to `added_to_kb`. The sidebar shows a red badge with the number of pending questions.

---

## 6. Security

### Controls in place
- PDO prepared statements are used for every query with user input. Table names in `delete_item.php` come from a fixed whitelist.
- CSRF tokens (`generateCsrfToken` / `validateCsrfToken` with `hash_equals`) protect all state-changing backend scripts. Delete links send the token as a GET parameter.
- Output is escaped with `htmlspecialchars`, and input is passed through `clean()` (`strip_tags`, `trim`, escape).
- Enum-like inputs (status, priority, type) are checked with `in_array` against allowed values.
- Uploads are checked by extension whitelist and a 5 MB limit, and saved under random `uniqid` names.
- `includes/.htaccess` (`Deny from all`) blocks direct access to the includes.
- A user cannot delete their own account. Academic structure can only be deleted by a manager, and meetings only by admins and managers.

### Known issues and recommendations
| # | Issue | Recommendation |
|---|---|---|
| 1 | The Gemini API key is stored as plain text in `system_settings` (the shipped dump leaves it empty) | Never commit a dump that contains a key. Set the key only through the Settings page |
| 2 | `CURLOPT_SSL_VERIFYPEER => false` in `gemini.php` and `test_gemini.php` | Turn verification on and point PHP at a CA bundle (`curl.cainfo`) |
| 3 | DB credentials are hard-coded (`root` with an empty password) | Move them to environment variables or a config file outside the web root. Use a dedicated DB user |
| 4 | `chatbot_process.php` does not check the CSRF token sent by the client | Validate `csrf_token` the same way as the other endpoints |
| 5 | `maintenance_mode` is saved but never checked | Add a check in `header.php` / `requireLogin()` |
| 6 | Uploads are only checked by extension, not MIME type | Add `finfo_file` MIME checks, and stop scripts from running in `assets/uploads/` (`.htaccess`) |
| 7 | Vector search loads **every** embedding into PHP for each question | Fine for a small KB. For a larger one, cache the vectors or use a vector store (MySQL 9 VECTOR, pgvector, etc.) |
| 8 | Admin and manager notifications use hard-coded `user_type_id IN (2,3)` in some scripts | Look the IDs up by `user_types.title`, as `flagUnknownQuestion()` already does |
| 9 | `/STD/` is hard-coded throughout | Use a `BASE_URL` constant so the app can be moved |
| 10 | `signup.php` sends a logged-in advisor to the student dashboard | Use `redirectByRole()` |
| 11 | There are no login rate limits or account lockouts | Add throttling per IP and per account |
| 12 | Migration and test scripts are reachable over the web | Delete them or protect them in production |
| 13 | `delete_item.php` lets **academic advisors** delete `service`, `guide`, `form`, `faq`, `knowledge` and `recommendation` rows. Only structure types, users and meetings are blocked | Allow advisors only `recommendation`, and require `isAdmin()` for everything else |
| 14 | `save_meeting.php` accepts advisors, while the UI hides meeting create and edit from them | Add an `isAdmin()` check if advisors should not manage meetings |
