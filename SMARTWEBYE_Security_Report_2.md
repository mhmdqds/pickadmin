# 🔒 تقرير الفحص الأمني الشامل - SMARTWEBYE
## Laravel Application Security Audit Report

**تاريخ الفحص:** 2026-06-19  
**الأداة:** Cline Security Auditor  
**نوع المشروع:** Laravel 12 Application  
**المسار:** `c:\xampp\htdocs\pickadmin`

---

## 📋 ملخص تنفيذي

بناءً على الفحص الأمني الشامل الذي أُجري على جميع ملفات المشروع، تم تقييم الحالة الأمنية بشكل تفصيلي. **الملاحظة الإيجابية الرئيسية هي وجود دلائل واضحة على أن المشروع مر بعملية تنظيف أمني سابقة** (تم إزالة باب خلفي يستخدم `eval()` سابقاً كما هو موثق في تعليق الكود). ومع ذلك، لا تزال هناك بعض المخاطر التي تحتاج للمعالجة.

### مستوى المخاطرة: 🟡 متوسط (Medium)

| التصنيف | العدد | الحالة |
|---------|-------|--------|
| أبواب خلفية (Backdoors) مؤكدة | 0 | ✅ نظيف |
| أكواد مشفرة مشبوهة | 15+ | ⚠️ يراقب |
| ثغرات تنفيذ أوامر محتملة | 1 | ⚠️ يحتاج مراجعة |
| ثغرات رفع ملفات محتملة | 1 | ⚠️ يحتاج مراجعة |
| ثغرات File Inclusion | 0 | ✅ نظيف |
| ثغرات XSS/SQLi | 0 | ✅ نظيف |

---

## 🚨 النتائج التفصيلية

### 1. فحص الأكواد المشفرة والمخفية (Obfuscated Code)

#### 🔴 الاكتشاف رقم #1: إشارة لباب خلفي تمت إزالته سابقاً
- **الملف:** `app\Http\Controllers\Admin\System\AddonController.php`
- **السطر:** 27
- **الكود:**
```php
// Removed insecure eval() backdoor pattern
```
- **التحليل:** هذا التعليق يشير بوضوح إلى وجود باب خلفي سابقاً يستخدم `eval()` وتمت إزالته. هذا دليل إيجابي على أن المشروع مر بعملية تنظيف، لكن يجب التأكد من أن جميع الاستدعاءات المتعلقة بهذا الباب الخلفي تمت إزالتها بالكامل من جميع الملفات.

#### 🟡 الاكتشاف رقم #2: استخدامات `base64_decode` المكثفة
تم العثور على **15+** استخدام لـ `base64_decode`، معظمها في سياق نظام التفعيل/الترخيص الخاص بـ 6amtech:

**ملفات منطق التفعيل (غير خطرة - سلوك معتاد):**
| الملف | السطر | السياق |
|-------|-------|--------|
| `app\Traits\ActivationClass.php` | 68-82 | إرسال بيانات التفعيل لخادم 6amtech |
| `app\Http\Controllers\Admin\System\AddonController.php` | 92-116 | التحقق من شراء الإضافات |
| `app\Http\Middleware\ActivationCheckMiddleware.php` | 27 | توجيه صفحة التفعيل |
| `app\Http\Middleware\InstallationMiddleware.php` | 19 | رسالة خطأ التثبيت |
| `app\Services\AddonService.php` | 87 | نوع البرنامج الافتراضي |

**استخدامات أخرى (تتطلب مراجعة):**
| الملف | السطر | السياق |
|-------|-------|--------|
| `app\Http\Controllers\Admin\FileManagerController.php` | 42, 50, 171, 207 | فك تشفير مسارات الملفات |
| `app\Http\Controllers\HomeController.php` | 437, 448, 529 | فك تشفير معرّفات |
| `app\Http\Controllers\InstallController.php` | 105, 106 | معالجة بيانات التثبيت |
| `app\Http\Controllers\Api\V1\Auth\CustomerAuthController.php` | 751 | فك تشفير JWT claims |
| `app\Http\Controllers\Api\V1\Auth\SocialAuthController.php` | 511 | فك تشفير JWT claims |
| `resources\views\admin-views\file-manager\index.blade.php` | 48, 141 | عرض مسارات الملفات |

#### 🟢 الاكتشاف رقم #3: دوال فك التشفير الأخرى
- `gzinflate`: ❌ لم يتم العثور عليها
- `str_rot13`: ❌ لم يتم العثور عليها
- `assert`: ❌ لم يتم العثور عليها
- `create_function`: ❌ لم يتم العثور عليها
- `ReflectionClass/ReflectionFunction`: ❌ لم يتم العثور عليها
- `preg_replace /e`: ❌ لم يتم العثور عليها

---

### 2. فحص دوال تنفيذ الأوامر (Command Execution)

#### 🟡 الاكتشاف رقم #4: استخدام `exec()`
- **الملف:** `app\Http\Controllers\Admin\BusinessSettingsController.php`
- **السطر:** 469
- **الكود:**
```php
if (function_exists('exec')) {
    $scriptPath = base_path('script.sh');
    if (file_exists($scriptPath) && is_readable($scriptPath)) {
        exec('sh ' . escapeshellarg($scriptPath));
    }
}
```
- **التقييم:** 🟢 آمن نسبياً - يستخدم `escapeshellarg()` لتطهير المدخلات، ويتحقق من وجود الملف.
- **التوصية:** يُفضل استخدام Laravel Scheduler بدلاً من `exec()` لتشغيل السكربتات.

#### 🟡 الاكتشاف رقم #5: استخدامات `shell_exec()`
- **الملفات:**
  - `Modules\ReelsModule\Http\Requests\Admin\ReelStoreRequest.php` (الأسطر 177-182)
  - `Modules\ReelsModule\Http\Requests\Admin\ReelUpdateRequest.php` (الأسطر 177-182)
  - `Modules\ReelsModule\Http\Requests\Api\V1\Vendor\ReelStoreRequest.php` (الأسطر 172-177)
  - `Modules\ReelsModule\Http\Requests\Api\V1\Vendor\ReelUpdateRequest.php` (الأسطر 172-177)

- **الكود المشترك:**
```php
if (function_exists('shell_exec')) {
    $ffprobePath = trim((string) @shell_exec('command -v ffprobe'));
    $command = "$ffprobePath -v error -select_streams v:0 -show_entries stream=duration -of default=noprint_wrappers=1:nokey=1 $filePath";
    $duration = trim((string) @shell_exec($command));
}
```
- **التقييم:** 🟡 خطر متوسط - `$filePath` قد يحتوي على مدخلات غير مُطهَّرة من المستخدم.
- **الثغرة المحتملة:** **Command Injection** إذا كان `$filePath` يحتوي على أحرف خاصة مثل `;`, `&&`, `|`.
- **التوصية:** استخدم `escapeshellarg()` لتطهير `$filePath` قبل تمريره لـ `shell_exec()`.

---

### 3. فحص حقن الملفات والأبواب الخلفية (File Inclusion & Backdoors)

#### 🟡 الاكتشاف رقم #6: ثغرة رفع ملفات محتملة في FileManagerController
- **الملف:** `app\Http\Controllers\Admin\FileManagerController.php`
- **الدوال:** `upload()`
- **الكود المشبوه:**
```php
// السطر 141
Madzipper::make($file)->extractTo('storage/app/'.$request->path);
```
- **التحليل:**
  - ❌ لا يتم فحص محتويات ملف ZIP قبل الاستخراج
  - ❌ يمكن أن يحتوي ZIP على ملفات PHP أو .htaccess أو سكربتات أخرى
  - ❌ لا يتم التحقق من المسارات بعد الاستخراج
- **الثغرة:** **Path Traversal + Arbitrary File Upload** - قد يسمح للمهاجم برفع ملفات PHP خبيثة عبر ملف ZIP يحتوي على مسارات نسبية (`../../public/shell.php`).
- **التوصية:** إضافة فحص كامل لمحتويات ZIP قبل الاستخراج (مشابه للفحص الموجود في AddonController.php).

#### 🟡 الاكتشاف رقم #7: رفع صور بدون التحقق الكافي من المحتوى
- **الملف:** `app\Http\Controllers\Admin\FileManagerController.php`
- **السطر:** 103
```php
Storage::disk($disk)->put($request->path . '/' . $name, file_get_contents($image));
```
- **التحليل:** يتم رفع الملفات بأسماءها الأصلية، مما قد يسمح برفع ملفات PHP مخفية كصور.

#### 🟢 الاكتشاف رقم #8: مجلد public/
- **النتيجة:** ✅ نظيف - يحتوي فقط على `index.php` (الملف القياسي لـ Laravel).

#### 🟢 الاكتشاف رقم #9: مجلد storage/
- **النتيجة:** ✅ نظيف - لا توجد ملفات PHP غير متوقعة في `storage/app/` أو `storage/uploads/`.

#### 🟢 الاكتشاف رقم #10: مجلد routes/
- **النتيجة:** ✅ نظيف - لا توجد مسارات غير موثقة أو مشبوهة في `routes/web.php`.

---

### 4. فحص التبعيات (Dependencies)

#### 🟢 الاكتشاف رقم #11: ملف `composer.json`
- **النتيجة:** ✅ نظيف
- **التحليل:** جميع الحزم معروفة وموثقة:
  - `laravel/framework: ^12.0`
  - `laravel/passport: ^12.0`
  - `barryvdh/laravel-dompdf: ^3.1`
  - `guzzlehttp/guzzle: ^7.10`
  - `stripe/stripe-php: ^10.10`
  - `razorpay/razorpay: ^2.8`
  - وغيرها من الحزم القياسية
- **ملاحظة:** هناك حزمة مخصصة من `phonepe/phonepe-pg-php-sdk` مُحمَّلة من URL خارجي (`https://phonepe.mycloudrepo.io/...`) - يجب التأكد من موثوقية هذا المصدر.

---

### 5. فحص ثغرات أخرى

#### 🟢 الاكتشاف رقم #12: ثغرات XSS
- **النتيجة:** ✅ لم يتم العثور على استخدامات مباشرة لـ `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE` مع `echo` أو `print` بدون تطهير.

#### 🟢 الاكتشاف رقم #13: ثغرات SQL Injection
- **النتيجة:** ✅ لم يتم العثور على استخدامات مباشرة لـ `DB::raw()` أو `->whereRaw()` مع مدخلات المستخدم بدون تطهير.

#### 🟢 الاكتشاف رقم #14: ثغرات File Inclusion (LFI/RFI)
- **النتيجة:** ✅ لم يتم العثور على استخدامات `include()`, `require()` مع مدخلات المستخدم.

#### 🟡 الاكتشاف رقم #15: استخدام `parse_str()`
- **الملف:** `app\Traits\HasProductVideoPreview.php`
- **السطر:** 235
```php
parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
```
- **التقييم:** 🟢 آمن - يستخدم لتحليل URL وليس لمعالجة مدخلات المستخدم مباشرة.

---

## 📊 ملخص الإصلاحات الموصى بها

| الأولوية | الثغرة | الملفات المتأثرة | خطوات الإصلاح |
|----------|--------|-----------------|---------------|
| 🔴 عالية | **Command Injection** في ReelsModule | `Modules\ReelsModule\Http\Requests\Admin\ReelStoreRequest.php` | استخدم `escapeshellarg($filePath)` قبل تمريره لـ `shell_exec()` |
| 🔴 عالية | **Arbitrary File Upload** عبر ZIP | `app\Http\Controllers\Admin\FileManagerController.php` | أضف فحص كامل لمحتويات ZIP قبل الاستخراج |
| 🟡 متوسطة | **Unsafe File Upload** | `app\Http\Controllers\Admin\FileManagerController.php` | تحقق من نوع MIME الفعلي للملفات |
| 🟡 متوسطة | باب خلفي سابق (`eval()`) | `app\Http\Controllers\Admin\System\AddonController.php` | تحقق من أن جميع آثار الباب الخلفي تمت إزالتها من جميع الملفات |
| 🟢 منخفضة | استخدام `exec()` | `app\Http\Controllers\Admin\BusinessSettingsController.php` | استبدل بـ Laravel Scheduler |
| 🟢 منخفضة | تبعية خارجية | `composer.json` | تحقق من مصدر `phonepe/phonepe-pg-php-sdk` |

---

## 🔧 خطوات التنظيف والتأمين

### الخطوة 1: إصلاح Command Injection
في جميع ملفات ReelsModule، استبدل:
```php
$command = "$ffprobePath -v error ... $filePath";
$duration = trim((string) @shell_exec($command));
```
بـ:
```php
$safePath = escapeshellarg($filePath);
$command = escapeshellcmd($ffprobePath) . " -v error -select_streams v:0 -show_entries stream=duration -of default=noprint_wrappers=1:nokey=1 " . $safePath;
$duration = trim((string) @shell_exec($command));
```

### الخطوة 2: تأمين رفع ملفات ZIP
أضف فحصاً مشابهاً للموجود في `AddonController.php`:
```php
$blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'htaccess', 'sh', 'exe', 'bat'];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $fileName = $zip->getNameIndex($i);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (in_array($extension, $blockedExtensions) || str_contains($fileName, '..')) {
        $zip->close();
        throw new \Exception('Suspicious file detected');
    }
}
```

### الخطوة 3: التحقق من عدم وجود آثار للباب الخلفي
ابحث في جميع الملفات عن أي استخدامات سابقة لـ `eval()` أو استدعاءات مشبوهة:
```bash
grep -rn "eval(" app/ Modules/ routes/
grep -rn "base64_decode.*eval" app/ Modules/ routes/
```

### الخطوة 4: مراجعة صلاحيات المجلدات
```bash
chmod 755 storage/app/public
chmod 644 public/index.php
chmod 755 bootstrap/cache
```

---

## ✅ الخلاصة

المشروع في حالة أمنية **جيدة نسبياً** مع وجود ثغرات **قابلة للإصلاح** بسهولة. أهم الإيجابيات:
- ✅ لا توجد أبواب خلفية مؤكدة حالياً
- ✅ لا توجد ثغرات LFI/RFI أو XSS أو SQLi واضحة
- ✅ المشروع يستخدم Laravel Framework مع أفضل الممارسات في معظم الأحيان
- ✅ تم تنظيف باب خلفي سابق (إشارة إيجابية)

الثغرات التي تحتاج إصلاحاً فورياً:
1. **Command Injection** في ReelsModule
2. **Arbitrary File Upload** في FileManagerController

**الفحص أُجري بواسطة:** Cline Security Auditor  
**التاريخ:** 19 يونيو 2026