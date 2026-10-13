<div dir="rtl">

# البنية المعمارية

## 1. بنية المجلدات

</div>

```
STD/
├── index.php              الصفحة الرئيسية العامة
├── about.php              صفحة "عن النظام"
├── login.php              نموذج الدخول (وضع الطالب ووضع الموظفين)
├── signup.php             تسجيل الطالب الذاتي
├── std_system.sql         نسخة كاملة من قاعدة البيانات (الهيكل والبيانات الأولية)
│
├── includes/              الكود المشترك (الوصول المباشر ممنوع عبر .htaccess)
│   ├── db.php             اتصال PDO والدالة getSetting()
│   ├── auth.php           الجلسة، وفحص الأدوار، ودوال require*()، والدخول والخروج، والتوجيه حسب الدور
│   ├── functions.php      دوال مساعدة: رسائل الفلاش، والإشعارات، وسجل النشاط، والرفع،
│   │                      والتسميات، وترقيم الصفحات، وCSRF، والتقسيم، وتشابه جيب التمام، والاسترجاع
│   ├── gemini.php         الكلاس GeminiAPI (المتجهات وتوليد الإجابات عبر cURL)
│   ├── header.php         رأس HTML، وشريط التنقل، وقائمة الإشعارات، وقائمة المستخدم
│   ├── sidebar.php        القائمة الجانبية لكل دور
│   └── footer.php         إغلاق التخطيط وتحميل Bootstrap JS و main.js
│
├── student/               صفحات الطالب
├── admin/                 صفحات الإداري (يستخدمها المدير أيضاً)
├── manager/               صفحات المدير، ومعها صفحات محاضر الاجتماعات المشتركة
├── advisor/               صفحات المرشد الأكاديمي
├── backend/               نقاط تنفيذ الإجراءات POST/GET (save_* و delete_* والشات بوت والترحيل)
│
└── assets/
    ├── css/style.css      التنسيق العام
    ├── js/main.js         دوال الواجهة وكائن Chatbot
    ├── images/            الشعار والصور الافتراضية
    └── uploads/           ملفات المستخدمين: forms/ و guides/ و logos/ و profiles/ و requests/
```

<div dir="rtl">

### نمط التصميم
التطبيق مبني بنمط **Page Controller**، أي أن كل صفحة تتحكم بنفسها، بلا إطار عمل ولا موجّه (Router):

- كل صفحة في `student/` أو `admin/` أو `manager/` أو `advisor/` تمر بالخطوات نفسها: تبدأ الجلسة، وتستدعي ملفات `db` و`auth` و`functions`، وتستدعي دالة الحماية `require*()`، وتنفّذ الاستعلامات، وتحدد `$pageTitle`، وتستدعي `header.php`، وتطبع HTML، ثم تستدعي `footer.php`.
- كل نموذج يُرسَل إلى سكربت في `backend/`. السكربت يتحقق من رمز CSRF ومن الدور، ويكتب في قاعدة البيانات، ويسجّل النشاط، ويرسل الإشعارات، ويضبط رسالة فلاش، ثم **يعيد التوجيه** (نمط Post/Redirect/Get).
- الشات بوت هو نقطة JSON/AJAX الوحيدة (`backend/chatbot_process.php`).
- في `admin/*` و`manager/*` نسخ شبه متطابقة من صفحات المحتوى (الخدمات، والأدلة، والنماذج، والأسئلة الشائعة، وقاعدة المعرفة، والطلبات، ومراجعة المحادثات)، ليبقى كل دور داخل قائمته الجانبية. سكربتات الـ Backend تستخدم `isManager()` لتحديد الصفحة التي يعود إليها المستخدم.

---

## 2. مخطط قاعدة البيانات (`std_system`)

### المستخدمون والبنية الأكاديمية
| الجدول | الأعمدة الرئيسية | ملاحظات |
|---|---|---|
| `user_types` | id, title, arabic_title | 1 طالب، 2 إداري، 3 مدير، 4 مرشد أكاديمي |
| `users` | user_type_id, student_number, full_name, national_id, phone, email, password (bcrypt), date_of_birth, gender, college_id, degree_id, major_id, **academic_advisor_id** (يشير إلى `users.id`), profile_image, status (active/inactive/suspended), activation, last_login | جدول واحد لجميع الأدوار، والمرشدون مستخدمون فيه |
| `colleges` | title, status | 7 كليات في البيانات الأولية |
| `degrees` | title, status | بكالوريوس، ماجستير، دكتوراه، دبلوم |
| `majors` | title, college_id, degree_id, status | يغذّي القوائم المتتابعة في صفحة التسجيل |
| `departments` | title, description, status | وحدات العمادة، مثل الإرشاد النفسي ووحدة الموهوبين |

### الخدمات والمحتوى
| الجدول | الأعمدة الرئيسية |
|---|---|
| `services` | title, description, department_id, conditions, required_documents, steps, keywords, icon, status, **assigned_role** (admin/advisor/both), created_by |
| `guides` | title, description, file_path, service_id, department_id, status |
| `forms` | title, description, file_path, service_id, department_id, status |
| `faq` | question, answer, service_id, department_id, keywords, sort_order, status |

### الطلبات
| الجدول | الأعمدة الرئيسية |
|---|---|
| `requests` | student_id, service_id, title, description, attachment, **status** (new/under_review/replied/completed/rejected/closed), **assigned_to** (مستخدم), **priority** (low/medium/high) |
| `request_replies` | request_id, user_id, reply_message, attachment |

### الذكاء الاصطناعي والشات بوت
| الجدول | الأعمدة الرئيسية |
|---|---|
| `knowledge_base` | title, content, type (service/faq/guide/form/policy/info), related_id (السجل المصدر), service_id, department_id, keywords, status |
| `knowledge_chunks` | knowledge_id, chunk_text, chunk_order, **embedding_json** (مصفوفة أرقام بصيغة JSON في LONGTEXT), embedding_model |
| `chatbot_conversations` | student_id, session_title (أول 50 حرفاً من السؤال الأول) |
| `chatbot_messages` | conversation_id, sender_type (student/bot), message_text, matched_chunks (مصفوفة معرّفات JSON), confidence_score |
| `chatbot_reviews` | conversation_id, message_id, reviewed_by, review_note, review_status (good/needs_improvement/bad) |
| `unknown_questions` | student_id, conversation_id, question_text, confidence_score, status (pending/reviewed/added_to_kb), reviewed_by, reviewed_at |

### الاجتماعات
| الجدول | الأعمدة الرئيسية |
|---|---|
| `meeting_minutes` | title, meeting_date, notes, status (complete/incomplete), created_by |
| `meeting_recommendations` | meeting_id, title, rec_status (approved/rejected/other/notes، أو NULL أي في الانتظار), rec_notes, support_type (loan/prize/fixed_grant/marriage_grant/other), support_amount, disbursement_status |

### النظام
| الجدول | الأعمدة الرئيسية |
|---|---|
| `system_settings` | setting_key (فريد), setting_value |
| `notifications` | user_id, title, message, type, link, is_read |
| `activity_logs` | user_id, action_type, description, ip_address |

### العلاقات بين الجداول (مبسّطة)

</div>

```
user_types 1──* users *──1 colleges / degrees / majors
users (advisor) 1──* users (students)          via academic_advisor_id
users (student) 1──* requests *──1 services *──1 departments
requests 1──* request_replies
users (student) 1──* chatbot_conversations 1──* chatbot_messages
knowledge_base 1──* knowledge_chunks
meeting_minutes 1──* meeting_recommendations
```

<div dir="rtl">

---

## 3. المصادقة والصلاحيات

- **قائمة على الجلسات**. الدالة `loginUser()` تحفظ في `$_SESSION` كلاً من `user_id` و`user_type` و`full_name` و`email` و`profile_image`.
- **كلمات المرور** تُشفَّر بـ `password_hash(..., PASSWORD_BCRYPT)` ويُتحقَّق منها بـ `password_verify`.
- **الدخول** (`backend/login.php`):
  - وضع الطالب: `email = ? OR student_number = ?`، ومقصور على نوع `student`.
  - وضع الموظفين: `email = ?`، ومقصور على الإداري أو المدير أو المرشد.
  - يجب أن يكون الحساب `activation = 1` و`status = 'active'`.
  - عند النجاح يُحدَّث `last_login`، ويُسجَّل النشاط، ثم تُستدعى `redirectByRole()`.
- **دوال الحماية** (في `includes/auth.php`):

| الدالة | تسمح لـ |
|---|---|
| `requireLogin()` | أي مستخدم مسجّل ما زالت حالته في قاعدة البيانات `active`. تتحقق من قاعدة البيانات **في كل طلب**، فيسري الإيقاف فوراً |
| `requireStudent()` | الطالب |
| `requireAdmin()` | الإداري **أو** المدير |
| `requireManager()` | المدير فقط |
| `requireAdvisor()` | المرشد الأكاديمي |
| فحص مباشر `isAdmin() \|\| isAdvisor()` | صفحات الاجتماعات، وتحديث حالة الطلب، والحذف |

- **فحص على مستوى السجل**: الطالب لا يرى إلا طلباته ومحادثاته، والمرشد لا يرى ولا يعدّل إلا الطلبات التي `assigned_to` فيها هو معرّفه.

---

## 4. دورة حياة الطلب

</div>

```
يختار الطالب خدمة (student/services.php)
        │
        ▼
student/new_request.php ──POST──► backend/save_request.php
        │  • فحص CSRF، والحقول المطلوبة، ورفع مرفق اختياري (≤ 5 ميغابايت)
        │  • قراءة services.assigned_role
        │      admin   → assigned_to = NULL؛ إشعار جميع الإداريين والمديرين
        │      advisor → assigned_to = مرشد الطالب؛ إشعار المرشد
        │      both    → يُسنَد للمرشد؛ إشعار المرشد والإداريين والمديرين
        │  • إدراج الطلب (status = new)
        ▼
يفتح الموظف request_details.php
        ├─ رد     → backend/save_reply.php
        │            إدراج في request_replies؛ الحالة ← replied؛ إشعار الطالب
        └─ تحديث → backend/update_request_status.php
                     الحالة / الأولوية / المُسنَد إليه (للإداريين فقط)؛ إشعار الطالب
رد الطالب → save_reply.php (لا تتغير الحالة؛ إشعار المرشد و/أو الإداريين)
```

<div dir="rtl">

---

## 5. مسار الذكاء الاصطناعي (RAG)

### 5.1 الفهرسة (الكتابة في قاعدة المعرفة)

</div>

```
يحفظ الإداري خدمة / سؤالاً شائعاً / دليلاً / نموذجاً / مدخل معرفة
        │
        ▼
باني المحتوى (save_service.php و save_faq.php و save_guide.php و save_form.php و save_knowledge.php)
   • الأدلة والنماذج تضيف "[العنوان](/STD/assets/uploads/<الملف>)" ليتمكن البوت من ربط الملف
   • إنشاء أو تحديث سجل knowledge_base (type + related_id)
   • حذف المقاطع القديمة لهذا المدخل من knowledge_chunks
   • splitIntoChunks(content, chunk_size)   ← تقسيم على المسافات، حد أقصى N حرف لكل مقطع
   • save_knowledge.php: يولّد المتجهات فوراً
     save_service/faq/guide/form: يحفظ المقاطع بدون متجهات
        │
        ▼
backend/generate_embeddings.php (زر في صفحة قاعدة المعرفة أو الإعدادات)
   • حتى 50 مقطعاً في كل تشغيل، من المقاطع التي embedding_json فيها NULL
   • GeminiAPI::generateEmbedding() → مصفوفة JSON تُحفظ في embedding_json
   • انتظار 200 مللي ثانية بين الطلبات
```

<div dir="rtl">

### 5.2 الإجابة (`backend/chatbot_process.php`)

</div>

```
POST message (+ conversation_id)
   │
   ├─ chatbot_enabled != 1 → "المساعد غير متاح"، suggest_request = true
   ├─ لا يوجد مفتاح API    → simpleFallbackSearch (بحث LIKE في الأسئلة الشائعة ثم الخدمات)
   │
   ▼
1. embedding = Gemini.generateEmbedding(السؤال)
2. إذا نجح المتجه:
       تحميل كل المقاطع المنشورة التي لها متجه
       score = cosineSimilarity(q, chunk) لكل مقطع (يُحسب داخل PHP)
       أفضل K = max_chunks_retrieve
   وإلا:
       المقاطع حيث chunk_text LIKE %السؤال%   (score 0.5)
3. إذا لم توجد مقاطع: الأسئلة الشائعة حيث question/keywords LIKE   (score 0.6)
4. answer = Gemini.generateAnswer(السؤال، المقاطع، تعليمات نظام بالعربية)
       temperature 0.7، maxOutputTokens 8192، الأمان: BLOCK_MEDIUM_AND_ABOVE
5. suggest_request = topScore < 0.55  أو  الإجابة تحتوي عبارة "لا أعلم"
6. saveConversation(): إنشاء المحادثة أو تحديثها، وحفظ رسالة الطالب
   ورسالة البوت (مع matched_chunks و confidence_score)
7. إذا suggest_request: flagUnknownQuestion()
       يتجاهل السؤال إذا طرحه الطالب نفسه خلال آخر 5 دقائق
       إدراج في unknown_questions؛ إشعار جميع الإداريين والمديرين النشطين
8. JSON → { answer, suggest_request, conversation_id }
   عند أي استثناء: بحث احتياطي + تسجيل السؤال + suggest_request = true
```

<div dir="rtl">

### 5.3 جهة العميل (`assets/js/main.js` ← `Chatbot`)
- `init(convId)`: مفتاح Enter يرسل الرسالة، وShift+Enter يضيف سطراً جديداً.
- `sendMessage()`: يعرض الرسالة ومؤشر الكتابة، ويرسل `FormData` إلى نقطة الـ Backend، ثم يعرض الإجابة.
- `formatText()`: يهرّب HTML أولاً، ثم يعرض الروابط `[نص](/مسار)` (المسارات المحلية فقط)، و`**عريض**`، والنقاط `•`/`-`.
- `showRequestSuggestion()`: يضيف زر "تقديم طلب رسمي" عندما يُرجع الـ Backend القيمة `suggest_request`.
- النسخة المقابلة على الخادم هي `formatChatMessage()` في `functions.php`، وتعرض الرسائل المحفوظة.

### 5.4 تدخّل الإنسان في الحلقة
- **مراجعة المحادثات** (`admin|manager/chatbot_reviews.php`): تصفّح كل المحادثات، ورؤية أعلى درجة ثقة، وتقييمها (جيدة، أو تحتاج تحسيناً، أو سيئة) مع ملاحظة.
- **الأسئلة غير المُجابة** (`admin/unknown_questions.php`): تصفية حسب الحالة، أو تحديد السؤال كـ"تمت المراجعة"، أو فتح نموذج قاعدة معرفة مُعبّأ مسبقاً. الحفظ مع `uq_id` يحوّل الحالة إلى `added_to_kb`. تعرض القائمة الجانبية شارة حمراء بعدد الأسئلة المعلّقة.

---

## 6. الأمان

### الضوابط المطبّقة
- استعلامات PDO المُجهّزة مستخدمة في كل استعلام فيه مدخلات من المستخدم. أسماء الجداول في `delete_item.php` تأتي من قائمة ثابتة مسموحة.
- رموز CSRF (`generateCsrfToken` / `validateCsrfToken` مع `hash_equals`) تحمي كل سكربتات الـ Backend التي تغيّر البيانات. روابط الحذف ترسل الرمز في معامل GET.
- المخرجات تُهرَّب بـ `htmlspecialchars`، والمدخلات تمر عبر `clean()` (`strip_tags` و`trim` ثم الهروب).
- المدخلات ذات القيم المحددة (الحالة، والأولوية، والنوع) تُفحص بـ `in_array` مقابل القيم المسموحة.
- الملفات المرفوعة تُفحص بقائمة امتدادات مسموحة وحد 5 ميغابايت، وتُحفظ بأسماء عشوائية عبر `uniqid`.
- الملف `includes/.htaccess` (`Deny from all`) يمنع الوصول المباشر لملفات includes.
- لا يستطيع المستخدم حذف حسابه. حذف البنية الأكاديمية للمدير فقط، وحذف الاجتماعات للإداريين والمديرين فقط.

### مشاكل معروفة وتوصيات
| # | المشكلة | التوصية |
|---|---|---|
| 1 | مفتاح Gemini API يُخزَّن كنص صريح في `system_settings` (وهو فارغ في ملف التصدير المرفق) | لا ترفع أبداً ملف تصدير يحتوي على مفتاح، واضبط المفتاح من صفحة الإعدادات فقط |
| 2 | `CURLOPT_SSL_VERIFYPEER => false` في `gemini.php` و`test_gemini.php` | فعّل التحقق، واضبط ملف شهادات CA في PHP (`curl.cainfo`) |
| 3 | بيانات قاعدة البيانات مكتوبة في الكود (`root` بلا كلمة مرور) | انقلها إلى متغيرات بيئة أو ملف إعدادات خارج مجلد الويب، واستخدم مستخدم قاعدة بيانات مخصصاً |
| 4 | `chatbot_process.php` لا يتحقق من رمز CSRF الذي يرسله العميل | تحقّق من `csrf_token` كما في بقية النقاط |
| 5 | `maintenance_mode` يُحفظ لكنه لا يُفحص أبداً | أضف فحصاً في `header.php` أو `requireLogin()` |
| 6 | الملفات المرفوعة تُفحص بالامتداد فقط، لا بنوع MIME | أضف فحص MIME بـ `finfo_file`، وامنع تنفيذ السكربتات داخل `assets/uploads/` (`.htaccess`) |
| 7 | البحث الدلالي يحمّل **كل** المتجهات إلى PHP مع كل سؤال | مناسب لقاعدة معرفة صغيرة. للأحجام الأكبر استخدم كاشاً أو قاعدة متجهات (VECTOR في MySQL 9، أو pgvector، أو غيرهما) |
| 8 | إشعارات الإداريين والمديرين تستخدم `user_type_id IN (2,3)` الثابتة في بعض السكربتات | ابحث عن المعرّفات من `user_types.title` كما تفعل `flagUnknownQuestion()` |
| 9 | المسار `/STD/` مكتوب في كل مكان | استخدم ثابتاً مثل `BASE_URL` ليسهل نقل التطبيق |
| 10 | `signup.php` يوجّه المرشد المسجّل دخوله إلى لوحة الطالب | استخدم `redirectByRole()` |
| 11 | لا يوجد حد لمحاولات الدخول ولا قفل للحساب | أضف تحديداً لعدد المحاولات لكل IP ولكل حساب |
| 12 | سكربتات الترحيل والاختبار متاحة عبر الويب | احذفها أو احمِها في بيئة الإنتاج |
| 13 | `delete_item.php` يسمح **للمرشد الأكاديمي** بحذف سجلات `service` و`guide` و`form` و`faq` و`knowledge` و`recommendation`. الممنوع عليه فقط أنواع البنية الأكاديمية والمستخدمون والاجتماعات | اسمح للمرشد بـ `recommendation` فقط، واشترط `isAdmin()` لكل ما عداها |
| 14 | `save_meeting.php` يقبل المرشدين، مع أن الواجهة تخفي عنهم إنشاء الاجتماعات وتعديلها | أضف فحص `isAdmin()` إذا كان المرشد لا ينبغي أن يدير الاجتماعات |

</div>
