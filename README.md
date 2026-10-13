<div align="center">

# Smart Student Affairs System
### نظام إدارة شؤون الطلاب الذكي

A web platform for the **Deanship of Student Affairs at the University of Bisha**. It digitizes student services and includes an AI assistant built on **Google Gemini** that answers questions using retrieval-augmented generation (RAG).

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3_RTL-7952B3?logo=bootstrap&logoColor=white)
![Gemini](https://img.shields.io/badge/Google-Gemini_AI-4285F4?logo=google&logoColor=white)

[English](#english) · [العربية](#arabic)

</div>

---

<a id="english"></a>

## Overview

Students use the system to submit official requests to the deanship and track them, browse services, guides, forms and FAQs, and get instant answers from an AI assistant. Staff use it to handle requests, manage the content the assistant learns from, record committee meetings and their recommendations, and view reports.

It is a graduation project from the Artificial Intelligence program at the College of Computing and Information Technology, University of Bisha.

## Key Features

- **AI assistant (RAG).** Each question is embedded with Gemini and matched against a knowledge base by cosine similarity. The best matches are sent to Gemini, which writes the answer in Arabic. The assistant can include download links for forms and guides.
- **Confidence detection.** When the assistant is not confident in an answer, it suggests submitting a formal request. It also logs the question and notifies staff, who can add the missing information to the knowledge base.
- **Request management.** Students submit requests with attachments. Requests are routed automatically to admins or to the student's academic advisor depending on the service. The system supports statuses, priorities, threaded replies and notifications.
- **Knowledge base.** Services, FAQs, guides and forms are synced to the knowledge base automatically. Content is split into chunks, and embeddings can be generated in batches.
- **Meeting minutes.** Committee meetings hold recommendations with a decision, support type, amount and disbursement status. A meeting is marked complete automatically when all its recommendations have a decision.
- **Chatbot quality review.** Staff can rate conversations and work through a queue of questions the assistant could not answer.
- **User and academic structure management.** Students, admins, advisors, colleges, degrees, majors and departments.
- **Reports and analytics.** Requests by status, service and month, chatbot usage and user activity.
- **Settings.** Branding, logo, Gemini model selection, chunk size, retrieval depth and a switch to turn the chatbot on or off.
- **Arabic-first interface.** The UI is fully right-to-left and responsive.

## User Roles

| Role | Capabilities |
|---|---|
| **Student** | Services, AI assistant, submitting and tracking requests, profile |
| **Admin** | Request handling, content and knowledge base, chatbot review, meeting minutes |
| **Manager** | Everything an admin can do, plus user management, academic structure, reports and system settings |
| **Academic Advisor** | Requests routed from their own students, list of their students, meeting recommendations |

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 (no framework), PDO |
| Database | MySQL / MariaDB (`utf8mb4`) |
| Frontend | Bootstrap 5.3 RTL, Bootstrap Icons, vanilla JavaScript |
| AI | Google Gemini (`gemini-2.5-flash`) and Gemini Embeddings |
| Vector search | Cosine similarity over JSON-stored embeddings |
| Security | bcrypt passwords, CSRF tokens, prepared statements, role-based access guards |

## Getting Started

1. Install [XAMPP](https://www.apachefriends.org/) (PHP 8+ with the `curl`, `pdo_mysql` and `mbstring` extensions).
2. Clone the repository into `htdocs`. The folder **must be named `STD`**:
   ```bash
   git clone <repo-url> C:/xampp/htdocs/STD
   ```
3. Start Apache and MySQL. Create a database named `std_system`, then import `std_system.sql`.
4. If your MySQL credentials are not the defaults, update `includes/db.php`.
5. Open `http://localhost/STD/`.
6. Sign in as a manager. Open **Settings** and add your Gemini API key from [Google AI Studio](https://aistudio.google.com/apikey). Then open **Knowledge Base** and click **Generate Embeddings**.

## Documentation

| | English | العربية |
|---|---|---|
| Overview and features | [docs/en/README.md](docs/en/README.md) | [docs/ar/README.md](docs/ar/README.md) |
| Architecture, database, AI pipeline, security | [docs/en/ARCHITECTURE.md](docs/en/ARCHITECTURE.md) | [docs/ar/ARCHITECTURE.md](docs/ar/ARCHITECTURE.md) |
| Pages and endpoints | [docs/en/PAGES.md](docs/en/PAGES.md) | [docs/ar/PAGES.md](docs/ar/PAGES.md) |

## Project Structure

```
STD/
├── index.php, about.php, login.php, signup.php   Public pages
├── student/    Student pages
├── admin/      Admin pages
├── manager/    Manager pages and meeting minutes
├── advisor/    Academic advisor pages
├── backend/    Form handlers, chatbot endpoint, migrations
├── includes/   DB, auth, helpers, Gemini client, layout
├── assets/     CSS, JS, images, uploads
├── docs/       Documentation (EN / AR)
└── std_system.sql
```

---

<a id="arabic"></a>

<div dir="rtl">

## نبذة عن المشروع

**نظام إدارة شؤون الطلاب الذكي** منصة ويب لـ**عمادة شؤون الطلاب في جامعة بيشة**، تحوّل خدمات الطلاب إلى خدمات رقمية، وفيها مساعد ذكاء اصطناعي مبني على **Google Gemini** يجيب عن الأسئلة بتقنية التوليد المعزّز بالاسترجاع (RAG).

يقدّم الطلاب من خلالها طلباتهم الرسمية ويتابعونها، ويتصفحون الخدمات والأدلة والنماذج والأسئلة الشائعة، ويحصلون على إجابات فورية من المساعد الذكي. ويستخدمها الموظفون لمعالجة الطلبات، وإدارة المحتوى الذي يتعلّم منه المساعد، وتسجيل محاضر اجتماعات اللجان وتوصياتها، والاطلاع على التقارير.

المشروع مشروع تخرج من تخصص الذكاء الاصطناعي في كلية الحاسبات وتقنية المعلومات بجامعة بيشة.

## أبرز المميزات

- **مساعد ذكي (RAG).** يُحوَّل كل سؤال إلى متجه عبر Gemini ويُطابَق مع قاعدة المعرفة بتشابه جيب التمام. تُرسَل أفضل النتائج إلى Gemini فيكتب الإجابة بالعربية، ويمكنه إرفاق روابط تحميل النماذج والأدلة.
- **اكتشاف الثقة المنخفضة.** إذا لم يكن المساعد واثقاً من إجابته يقترح تقديم طلب رسمي، ويسجّل السؤال ويُشعر الموظفين ليضيفوا المعلومة الناقصة إلى قاعدة المعرفة.
- **إدارة الطلبات.** يقدّم الطالب طلبه مع مرفقات، ويُوجَّه الطلب تلقائياً إلى الإداريين أو إلى المرشد الأكاديمي للطالب حسب الخدمة. يدعم النظام الحالات والأولويات والردود المتسلسلة والإشعارات.
- **قاعدة المعرفة.** تُزامَن الخدمات والأسئلة الشائعة والأدلة والنماذج معها تلقائياً، ويُقسَّم المحتوى إلى مقاطع، ويمكن توليد المتجهات على دفعات.
- **محاضر الاجتماعات.** لكل اجتماع توصيات فيها القرار ونوع الدعم والمبلغ وحالة الصرف، ويُحدَّد اكتمال الاجتماع تلقائياً عندما يصبح لكل توصياته قرار.
- **مراجعة جودة المحادثات.** يقيّم الموظفون المحادثات ويتابعون قائمة الأسئلة التي لم يجد لها المساعد إجابة.
- **إدارة المستخدمين والبنية الأكاديمية.** الطلاب والإداريون والمرشدون والكليات والدرجات والتخصصات والأقسام.
- **التقارير والإحصائيات.** الطلبات حسب الحالة والخدمة والشهر، واستخدام الشات بوت، ونشاط المستخدمين.
- **الإعدادات.** اسم النظام وشعاره، واختيار نموذج Gemini، وحجم المقطع، وعدد المقاطع المسترجعة، وتفعيل الشات بوت أو إيقافه.
- **واجهة عربية بالكامل.** من اليمين لليسار ومتجاوبة مع الجوال.

## الأدوار

| الدور | الصلاحيات |
|---|---|
| **الطالب** | الخدمات، والمساعد الذكي، وتقديم الطلبات ومتابعتها، والملف الشخصي |
| **الإداري** | معالجة الطلبات، والمحتوى وقاعدة المعرفة، ومراجعة المحادثات، ومحاضر الاجتماعات |
| **المدير** | كل صلاحيات الإداري، ومعها إدارة المستخدمين والبنية الأكاديمية والتقارير وإعدادات النظام |
| **المرشد الأكاديمي** | الطلبات الموجّهة من طلابه، وقائمة طلابه، وتوصيات الاجتماعات |

## التقنيات

PHP 8 و MySQL و PDO و Bootstrap 5 RTL و JavaScript و Google Gemini AI، مع بحث دلالي بالمتجهات.

## التشغيل

1. ثبّت XAMPP، وضع المشروع في `C:/xampp/htdocs/STD` (يجب أن يكون اسم المجلد `STD`).
2. أنشئ قاعدة بيانات `std_system` واستورد الملف `std_system.sql`.
3. افتح `http://localhost/STD/`، وسجّل الدخول كمدير، وأضف مفتاح Gemini API من صفحة **الإعدادات**، ثم ولّد المتجهات من صفحة **قاعدة المعرفة**.

التوثيق الكامل بالعربية موجود في مجلد [docs/ar](docs/ar/README.md).

## فريق التطوير

لينا مشرع الهاجري · نوال فلاح القحطاني · لارا وهيطان · ياسمين حمد · نوت عبدالله · ريما سعد القرني · جود عبدالله القرني · أفنان محمد القرني · رباب ظفير الشمراني · منار سعد الشهراني

تخصص الذكاء الاصطناعي، كلية الحاسبات وتقنية المعلومات، جامعة بيشة.

</div>
