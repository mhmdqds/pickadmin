# SMARTWEBYE - ملخص الإصلاحات الأمنية
# Security Remediation Summary - PickAdmin Laravel Project

## معلومات المشروع
- **المشروع:** PickAdmin (6amTech StackFood Multi-Restaurant)
- **تاريخ الفحص:** 18 يونيو 2026
- **المسؤول:** SMARTWEBYE Security Team
- **الحالة:** ✅ تم الإصلاح

---

## ملخص التهديدات المصححة

### 1. CRITICAL - Backdoors عبر eval() [✅ FIXED]

#### الملفات المعنية:
| # | الملف | السطر | الوصف |
|---|-------|-------|-------|
| 1 | `app/Http/Controllers/PaymentController.php` | 21-42 | eval() backdoor مع توليد كلاس ديناميكي |
| 2 | `app/Http/Controllers/Admin/System/AddonController.php` | 25-46 | eval() backdoor مماثل |

#### السبب:
كان يُستخدم `eval()` لتوليد كلاس PHP ديناميكي مع `extends` و `use trait`. هذه تقنية مُستخدمة في الـ backdoors لأنها:
- تسمح بتنفيذ أي كود PHP مُمرر
- تُخفي النوايا الحقيقية تحت غطاء "dynamic class loading"
- تُمكّن من حقن كود عبر تلاعب المتغيرات

#### الإصلاح المُطبق:
```php
// تم إزالة:
private function extendWithPaymentGatewayTrait() {
    eval($this->generateExtendedControllerClass());
}

// تم استبداله بـ:
use Payment;

public function __construct() {
    // Payment trait functionality loaded securely via Laravel's use statement
}
```

#### النتيجة:
- ✅ تم حذف 2 backdoor eval()
- ✅ تم استبدالهم بـ `use` statement عادي
- ✅ لا يوجد أي استخدام لـ `eval()` في المشروع

---

### 2. CRITICAL - Remote Code Execution (RCE) [✅ FIXED]

#### الملف المعني:
| # | الملف | السطر | الوصف |
|---|-------|-------|-------|
| 1 | `app/Http/Controllers/Admin/BusinessSettingsController.php` | 468 | exec() بدون تطهير |

#### السبب:
```php
exec('sh ' . $scriptPath);  // scriptPath = 'script.sh' نسبي
```
- استخدام `exec()` مع مسار نسبي
- لا يوجد `escapeshellarg()`
- لا يوجد تحقق من وجود الملف

#### الإصلاح المُطبق:
```php
$scriptPath = base_path('script.sh');
if (file_exists($scriptPath) && is_readable($scriptPath)) {
    exec('sh ' . escapeshellarg($scriptPath));
}
```

#### النتيجة:
- ✅ تم تأمين تنفيذ الأوامر
- ✅ المسار مُطلق ومحصور
- ✅ تم تطهير المُدخلات

---

### 3. CRITICAL - Arbitrary File Upload → Remote Code Execution [✅ FIXED]

#### الملف المعني:
| # | الملف | الوظيفة | الوصف |
|---|-------|---------|-------|
| 1 | `app/Http/Controllers/Admin/System/AddonController.php` | `upload()` | رفع ZIP بدون فحص محتوى |

#### السبب:
- كان يسمح برفع ملف `.zip`
- يتم فك الضغط مباشرة إلى `Modules/`
- لا يوجد فحص للملفات الداخلية
- المهاجم يمكنه رفع PHP webshell داخل ZIP

#### الإصلاح المُطبق:
```php
// 1. تقييد الحجم
'file_upload' => 'required|file|mimes:zip|max:51200'

// 2. فحص محتوى ZIP قبل الفك
$blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'htaccess', 'sh', 'exe', 'bat'];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $fileName = $zip->getNameIndex($i);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (in_array($extension, $blockedExtensions)) {
        // رفض الملف
    }
}

// 3. تصريحات آمنة
File::chmod($expectedDir . '/Addon', 0755);
```

#### النتيجة:
- ✅ تم حظر الملفات التنفيذية داخل ZIP
- ✅ تم إضافة فحص الامتدادات
- ✅ تم تقليص التصريحات من 0777 إلى 0755

---

### 4. HIGH - Path Traversal / Directory Deletion [✅ FIXED]

#### الملفات المعنية:
| # | الملف | السطر | الوصف |
|---|-------|-------|-------|
| 1 | `AddonController.php` | `delete_theme()` | حذف أي مجلد عبر `$request->path` |
| 2 | `LanguageController.php` | `delete()` | حذف أي مجلد عبر `$lang` |
| 3 | `FileManagerController.php` | `destroy()` | حذف أي ملف عبر base64 |

#### السبب:
```php
// AddonController - delete_theme()
$full_path = base_path($path);  // ../../ أي مسار

// LanguageController - delete()
$dir = base_path('resources/lang/' . $lang);  // ../../../etc

// FileManagerController - destroy()
Storage::disk('local')->delete(base64_decode($file_path));  // أي مسار
```

#### الإصلاح المُطبق:
```php
// AddonController
$validator = Validator::make($request->all(), [
    'path' => 'required|string|regex:/^Modules\/[a-zA-Z0-9_-]+$/'
]);
$requestedPath = realpath(base_path($request->path));
if ($requestedPath === false || strpos($requestedPath, $allowedBase) !== 0) {
    return response()->json(['status' => 'error', 'message' => 'Invalid path']);
}

// LanguageController
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $lang)) {
    Toastr::error('Invalid language code!');
    return back();
}
$allowedBase = realpath(base_path('resources/lang'));
$dir = realpath(base_path('resources/lang/' . $lang));
if ($dir !== false && strpos($dir, $allowedBase) === 0 && $dir !== $allowedBase) {
    // السماح بالحذف
}

// FileManagerController
$decodedPath = base64_decode($file_path, true);
if ($decodedPath === false || str_contains($decodedPath, '..')) {
    Toastr::error('Invalid file path');
    return back();
}
```

#### النتيجة:
- ✅ تم التحقق من جميع المسارات
- ✅ تم رفض محاولات Path Traversal
- ✅ تم استخدام `realpath()` للتأكد من المسار الفعلي

---

### 5. HIGH - SQL Injection [✅ FIXED]

#### الملف المعني:
| # | الملف | السطر | الوصف |
|---|-------|-------|-------|
| 1 | `app/Traits/PlaceNewOrder.php` | 784 | توليد SQL ديناميكي |

#### السبب:
```php
->selectRaw('*, IF(((select count(*) from `store_schedule` where ... 
    `store_schedule`.`day` = ' . $schedule_at->format('w') . ' 
    and `store_schedule`.`opening_time` < "' . $schedule_at->format('H:i:s') . '" ...) > 0), true, false) as open')
```
- توليد SQL ديناميكي عبر string concatenation
- أي تلاعب بـ `$schedule_at` يُؤدي إلى SQL Injection

#### الإصلاح المُطبق:
```php
->selectRaw('*, IF(((select count(*) from `store_schedule` where 
    `stores`.`id` = `store_schedule`.`store_id` 
    and `store_schedule`.`day` = ? 
    and `store_schedule`.`opening_time` < ? 
    and `store_schedule`.`closing_time` > ?) > 0), true, false) as open', [
    $schedule_at->format('w'),
    $schedule_at->format('H:i:s'),
    $schedule_at->format('H:i:s')
])
```

#### النتيجة:
- ✅ تم استخدام Prepared Statements
- ✅ تم فصل البيانات عن الاستعلام
- ✅ Laravel Eloquent يتولى التطهير

---

## ملخص الملفات المُعدلة

| # | الملف | نوع التهديد | الإصلاح |
|---|-------|------------|---------|
| 1 | `app/Http/Controllers/PaymentController.php` | Backdoor (eval) | استبدال eval بـ use |
| 2 | `app/Http/Controllers/Admin/System/AddonController.php` | Backdoor + Path Traversal + Arbitrary Upload | إزالة eval + تأمين رفع ZIP + تأمين الحذف |
| 3 | `app/Http/Controllers/Admin/BusinessSettingsController.php` | Command Injection | escapeshellarg + مسار مطلق |
| 4 | `app/Traits/PlaceNewOrder.php` | SQL Injection | Prepared Statements |
| 5 | `app/Http/Controllers/Admin/LanguageController.php` | Path Traversal | Regex + realpath validation |
| 6 | `app/Http/Controllers/Admin/FileManagerController.php` | Path Traversal | base64 validation + '..' check |

---

## ملفات لا تزال تحتاج مراجعة

### ملاحظات هامة:

1. **AwgCloud Module (Modules/AwgCloud/)**
   - تم العثور على أكواد مشفرة بـ `base64_encode` و `base64_decode`
   - بعض الملفات تحتوي على ميكانيكية "callback validation" مشبوهة
   - **التوصية:** مراجعة كاملة للوحدة والتأكد من شرعية الـ license validation

2. **AddonController::activation()**
   - يحتوي على استدعاء HTTP خارجي لـ `6amtech.com`
   - يستخدم `base64_decode` للمتغيرات
   - هذا أمر طبيعي في أنظمة الترخيص لكن يجب مراقبته

3. **File Upload أماكن أخرى**
   - يُنصح بمراجعة جميع أماكن رفع الملفات في المشروع
   - التأكد من أن جميعها تستخدم `store()` أو `move()` بشكل آمن

---

## قائمة التحقق قبل النشر

- [x] إزالة جميع استخدامات `eval()`
- [x] تأمين جميع استخدامات `exec()` / `shell_exec()`
- [x] تأمين رفع الملفات (ZIP validation)
- [x] تأمين مسارات الحذف (Path Traversal)
- [x] إصلاح SQL Injection
- [ ] مراجعة AwgCloud Module
- [ ] فحص شاملة للـ Unit Tests
- [ ] اختبار الـ Integration
- [ ] مراجعة الـ Code Review

---

## نصائح أمنية مستقبلية

1. **استخدم Laravel Security Package**
   ```bash
   composer require --dev enlightn/security-checker
   ```

2. **تفعيل Content Security Policy (CSP)**
   ```php
   // في middleware
   response()->header('Content-Security-Policy', "default-src 'self'");
   ```

3. **تقييد دوال PHP الخطرة**
   ```
   disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
   ```

4. **استخدام Laravel Sanctum للـ API Authentication**

5. **تفعيل 2FA للـ Admin Panel**

---

## معلومات الاتصال

**SMARTWEBYE Security Team**
- التاريخ: 2026-06-18
- الإصدار: 1.0

---
*تم إنشاء هذا التقرير تلقائياً بعد إصلاح التهديدات الأمنية*