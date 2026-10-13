# Pages and Endpoints Reference

All URLs are relative to `http://localhost/STD/`.

## 1. Public Pages

| Page | File | Description |
|---|---|---|
| Landing | `index.php` | Navbar, hero section, statistics strip, feature cards, about the university and deanship, team section, call to action, footer |
| About | `about.php` | System overview, the University of Bisha, deanship missions, 6 project goals, tech stack badges, team cards |
| Login | `login.php` | Tabs for **Student** (email or student number) and **Staff** (email). Posts to `backend/login.php` |
| Sign-up | `signup.php` | Personal details (name, national ID, gender, phone), academic details (college, degree, major and advisor as cascading selects) and account details (email, password and confirmation). Posts to `backend/register.php` |

---

## 2. Student Pages (`student/`), guarded by `requireStudent()`

| Page | File | What it does |
|---|---|---|
| Dashboard | `dashboard.php` | Welcome banner, request statistics, latest requests, quick actions and featured services |
| AI Assistant | `chatbot.php` | Chat window with the last 10 conversations on the side. Open a past conversation with `?conv=ID` or start a new one. Uses `Chatbot` from `main.js` |
| Services | `services.php` | Service cards with a department filter and search. `?id=` shows details: conditions, required documents, steps, and a button to submit a request |
| Guides and Forms | `guides.php` | Two tabs (Guides / Forms) with search and file downloads |
| FAQ | `faq.php` | Accordion of published questions |
| My Requests | `my_requests.php` | List of the student's requests with status and priority badges |
| New Request | `new_request.php?service_id=` | Request form for the selected service: title, description, priority and attachment |
| Request Details | `request_details.php?id=` | Request information, attachment, reply thread and a reply form. Only the request's owner can open it |
| Profile | `profile.php` | Edit personal and academic details, upload a profile photo and change the password |

The top navbar also has quick links for students to Services, Guides and Forms, and FAQ.

---

## 3. Admin Pages (`admin/`), guarded by `requireAdmin()` (admin or manager)

| Page | File | What it does |
|---|---|---|
| Dashboard | `dashboard.php` | Statistics, embedding progress, latest requests, latest chatbot conversations and requests by status |
| Requests | `requests.php` | Requests list with filters and pagination |
| Request Details | `request_details.php?id=` | Thread, reply form, and status, priority and assignee update form |
| Services | `services.php` | Service CRUD (modal form) including the `assigned_role` routing field |
| Guides | `guides.php` | Guide CRUD with file upload |
| Forms | `forms.php` | Form CRUD with file upload |
| FAQ | `faq.php` | FAQ CRUD |
| Knowledge Base | `knowledge_base.php` | KB CRUD, chunk and embedding counts, and a "Generate embeddings" button |
| Chatbot Reviews | `chatbot_reviews.php` | Conversation list with search and max confidence. Open one with `?conv=` to read the messages and save a review |
| Unknown Questions | `unknown_questions.php` | Statistics for pending, reviewed and added-to-KB questions, a status filter, "Mark reviewed" and "Add to KB" (pre-filled modal) |
| Profile | `profile.php` | Edit profile and change password |

---

## 4. Manager Pages (`manager/`)

Guarded by `requireManager()` unless noted. The content pages are copies of the admin pages that use the manager sidebar.

| Page | File | Guard | What it does |
|---|---|---|---|
| Dashboard | `dashboard.php` | Manager | User counts per role (built from `user_types`), request statistics, requests by status, latest requests and latest users |
| Students | `students.php` | Manager | Search by name, email or number; filter by college or status; pagination; add and edit (`?edit=ID`); advisor assignment; activate or suspend; delete |
| Admins | `admins.php` | Manager | Admin user CRUD |
| Academic Advisors | `advisors.php` | Manager | Advisor CRUD (`save_advisor.php`, `delete_advisor.php`) |
| Colleges | `colleges.php` | Manager | CRUD |
| Degrees | `degrees.php` | Manager | CRUD |
| Majors | `majors.php` | Manager | CRUD, each major linked to a college and a degree |
| Departments | `departments.php` | Manager | CRUD |
| User Types | `user_types.php` | Manager | CRUD for roles and categories |
| Requests | `requests.php`, `request_details.php` | Admin | Same as admin |
| Services | `services.php` | Admin | Same as admin |
| Guides, Forms, FAQ, Knowledge Base | `guides.php`, `forms.php`, `faq.php`, `knowledge_base.php` | Admin | Same as admin |
| Chatbot Reviews | `chatbot_reviews.php` | Manager | Same as admin |
| **Meeting Minutes** | `meetings.php` | Admin or Advisor | Meetings list with recommendation progress. Admins and managers can add, edit and delete |
| **Meeting Details** | `meeting_details.php?id=` | Admin or Advisor | Meeting information, recommendation cards (decision, support type, amount, disbursement), summary sidebar, recommendation modal |
| Reports | `reports.php` | Manager | Analytics (see README §3.9) |
| Settings | `settings.php` | Manager | General, logo and AI settings, plus embedding status |
| Profile | `profile.php` | Manager | Edit profile |

The manager sidebar also links to `admin/unknown_questions.php`.

---

## 5. Academic Advisor Pages (`advisor/`), guarded by `requireAdvisor()`

| Page | File | What it does |
|---|---|---|
| Dashboard | `dashboard.php` | Number of assigned students, open requests from their students, recent students |
| Requests | `requests.php` | Requests where `assigned_to` is the advisor |
| Request Details | `request_details.php?id=` | Reply to the request and update its status or priority (no reassignment) |
| My Students | `students.php` | Students whose `academic_advisor_id` is this advisor |
| Profile | `profile.php` | Edit profile |
| Meeting Minutes | `../manager/meetings.php` | Shared module (view, add and edit recommendations) |

---

## 6. Backend Endpoints (`backend/`)

All POST endpoints check the CSRF token unless noted otherwise. They redirect to a page with a flash message. The chatbot endpoint returns JSON instead.

### Authentication and account
| Endpoint | Method | Who | Purpose |
|---|---|---|---|
| `login.php` | POST | Public | Authenticate and redirect by role. No CSRF check |
| `register.php` | POST | Public | Create a student account |
| `logout.php` | GET | Any | Destroy the session |
| `update_profile.php` | POST | Logged in | Update the profile, photo and password. Students also get academic fields |
| `mark_notifications_read.php` | GET | Logged in | Mark all of the user's notifications as read |

### Requests
| Endpoint | Who | Purpose |
|---|---|---|
| `save_request.php` | Student | Submit a request, route it and send notifications |
| `save_reply.php` | Student / Staff | Add a reply (status becomes `replied` when staff reply) and send notifications |
| `update_request_status.php` | Admin / Manager / Advisor | Change status and priority. Only admins and managers can change the assignee |

### Content and knowledge base
| Endpoint | Who | Purpose |
|---|---|---|
| `save_service.php` | Admin+ | Create or update a service and sync it to the KB |
| `save_guide.php` | Admin+ | Create or update a guide with a file and sync it to the KB (with download link) |
| `save_form.php` | Admin+ | Create or update a form with a file and sync it to the KB (with download link) |
| `save_faq.php` | Admin+ | Create or update an FAQ entry and sync it to the KB |
| `save_knowledge.php` | Admin+ | Create or update a KB entry, rebuild chunks and embed them now. Optional `uq_id` closes an unknown question |
| `generate_embeddings.php` | Admin+ (GET) | Embed up to 50 pending chunks |
| `delete_item.php?type=&id=&csrf=` | Admin+ / Advisor (see ARCHITECTURE §6, issue 13) | Generic delete for: service, guide, form, faq, knowledge, user, college, degree, major, user_type, department, meeting, recommendation |

### Chatbot
| Endpoint | Who | Purpose |
|---|---|---|
| `chatbot_process.php` | Logged in (POST, JSON) | RAG answer. Fields: `message`, `conversation_id`. Response: `{answer, suggest_request, conversation_id}` |
| `save_review.php` | Admin+ | Rate a conversation |
| `mark_unknown_reviewed.php` | Admin+ | Set an unknown question to `reviewed` |
| `test_gemini.php` | Manager | Diagnostic page for the Gemini API |

### Users and structure (manager)
| Endpoint | Purpose |
|---|---|
| `save_user.php` | Create or update a student or admin |
| `save_advisor.php` / `delete_advisor.php` | Create, update or delete an advisor (checks that email and national ID are unique) |
| `toggle_user_status.php` | Switch a user between active and suspended |
| `save_college.php`, `save_degree.php`, `save_major.php`, `save_department.php`, `save_user_type.php` | Academic structure CRUD |
| `save_settings.php` | Save settings, or upload or remove the logo (`action=upload_logo`) |

### Meetings
| Endpoint | Who | Purpose |
|---|---|---|
| `save_meeting.php` | Admin+ / Advisor | Create or update a meeting |
| `save_recommendation.php` | Admin+ / Advisor | Create or update a recommendation and recalculate the meeting status |

### Migrations (one-off)
`migrate.php`, `migrate_advisor.php`, `migrate_meeting_tables.php`, `migrate_requests.php`, `migrate_unknown_questions.php`. These are not needed after importing `std_system.sql`.

---

## 7. Shared Includes

| File | Main functions |
|---|---|
| `includes/db.php` | `$pdo`, `getSetting($key, $default)` |
| `includes/auth.php` | `isLoggedIn`, `getUserType`, `getUserId`, `isStudent/isAdmin/isManager/isAdvisor`, `require*`, `loginUser`, `logoutUser`, `redirectByRole` |
| `includes/functions.php` | `clean`, `setFlash/getFlash/showFlash`, `logActivity`, `sendNotification`, `getUnreadNotifications`, `getLatestNotifications`, `requestStatusLabel`, `priorityLabel`, `knowledgeTypeLabel`, `formatDate(Time)`, `uploadFile`, `formatChatMessage`, `splitIntoChunks`, `cosineSimilarity`, `findRelevantChunks`, `generateCsrfToken/validateCsrfToken`, `getUserAvatar`, `paginate`, `renderPagination` |
| `includes/gemini.php` | `class GeminiAPI { generateEmbedding(), generateAnswer(), callApi() }` |
| `includes/header.php` | Layout head, navbar, notifications, user menu, sidebar include, flash output |
| `includes/sidebar.php` | Menus for each role, active link highlighting, badge for pending unknown questions |
| `includes/footer.php` | Closes the layout and loads scripts |
