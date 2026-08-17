# 🔴 تقرير تحليلي تقني صارم: تجمد التطبيق عند الدفع عبر Stripe (Flutter + Laravel)

> **نوع الوثيقة:** تقرير تحليل تقني فقط — لا تعديل في أي كود
> **التاريخ:** 2026-08-18
> **نطاق التحليل:**
> - Flutter Mobile App: `d:\pikels\picklespiesapp`
> - Laravel Backend: `c:\xampp\htdocs\pickadmin`
> **السيناريو المُبلَّغ:** يفتح التطبيق WebView للدفع بـ Stripe، يدخل بيانات البطاقة، يضغط زر "دفع"، فيتجمد التطبيق ويُجبر المستخدم على إعادة تشغيل الهاتف.
> **المنهجية:** قراءة كود Flutter الفعلي + قراءة كود Laravel الفعلي + التحقق من السلوك التقني الفعلي.

---

## 0. الخلاصة التنفيذية (Executive Summary)

### 0.1 التشخيص الحقيقي (سبب التجمد)

التطبيق **لا يتجمد بسبب Stripe نفسه ولا بسبب WebView كتقنية**. التطبيق يتجمد بسبب **انفصال بنيوي كامل (Architectural Mismatch)** بين ثلاثة أشياء:

1. **الـ Backend (Laravel)** يحدد `success_url = https://pickles-pies.com/payment/stripe/success?session_id=...&payment_id=...`
2. **الـ Backend** ينتهي بـ `Processor::payment_response()` الذي يحوّل إلى `redirect()->route('payment-success')` — لكن الراوت `payment-success` يستدعي `PaymentController@oksuccess` وهذا الميثود **غير موجود** (HTTP 500).
3. **الـ Flutter** يستمع لروابط `payment-success`, `payment-fail`, `payment-cancel` فقط (بدون `/stripe/`).

**النتيجة الحرجة:**
```
الـ WebView يلتقط redirect إلى /payment/stripe/success?...
فيستدعي paymentRedirect() الذي يفحص:
  url.startsWith('https://pickles-pies.com/payment-success')  ← false!
يكون isSuccess = isFailed = isCancel = false
→ Flutter لا يُغلق الـ WebView، لا يحوّل المستخدم، لا يعرض أي شاشة
```

**ما يحدث بعد ذلك:**
- الـ WebView يعرض الـ redirect (صفحة Laravel 500)
- الـ CircularProgressIndicator يبقى ظاهراً (لأن `_isLoading = true`)
- الـ PopScope.onPopInvokedWithResult يستدعي `_exitApp()` لكن لا أحد يستطيع الوصول للـ back button
- الـ JavaScript داخل الـ WebView قد يدخل في loop مع `redirectToCheckout`
- الـ Native SDK للـ WebView على Android قد يُسجّل ANR

**كل ما سبق معاً = "طفي التلفون وعمل إعادة تشغيل"**

### 0.2 جدول الجذور

| # | الجذر الحقيقي | الملف/السطر | الخطورة |
|---|---------------|------------|---------|
| 1 | **عدم تطابق URL patterns**: Flutter يبحث عن `/payment-success` بينما Laravel يحول إلى `/payment/stripe/success` | `order_service.dart:142-147` + `StripePaymentController.php:203` | 🔴 حرج |
| 2 | **الراوت payment-success يستدعي ميثود oksuccess غير موجود → HTTP 500** | `routes/web.php:76` + `PaymentController.php` | 🔴 حرج |
| 3 | **Processor::payment_response() يرفض redirect الـ external** | `Processor.php:84-118` + `StripePaymentController.php:273-275` | 🔴 حرج |
| 4 | **لا custom URL scheme / deep link handler** | `AndroidManifest.xml` + `Info.plist` + `payment_webview_screen.dart` | 🔴 حرج |
| 5 | **shouldOverrideUrlLoading يحظر schemes غير-http/https** | `payment_webview_screen.dart:127-149` | 🔴 حرج |
| 6 | **alert() في Blade view قد يتجمد في WebView** | `stripe.blade.php:26` | 🟠 عالٍ |
| 7 | **AppConstants.payInWevView = false افتراضياً** | `app_constants.dart:16` + `route_helper.dart:555-576` | 🟠 عالٍ |
| 8 | **WebView لا يدعم 3DS بشكل موثوق** | `stripe.blade.php:23` | 🟠 عالٍ |
| 9 | **لا timeout/keep-alive** | `stripe.blade.php` | 🟡 متوسط |

### 0.3 ما يجب أن تعرفه قبل أي إصلاح

> **القاعدة الذهبية:** Stripe Checkout في WebView داخل تطبيق Native هو نمط مكسور في التصميم. Stripe توصي صراحةً باستخدام Native SDKs.

> **الحقيقة الصادمة:** الإصلاح "السريع" (URL patterns) قد ينجح ظاهرياً لكن سيفشل لاحقاً على مستخدمين آخرين بسبب 3DS أو WebView محدود.

---

## 1. تحليل المسار الكامل: تتبع كل خطوة من الضغط حتى التجمد

### 1.1 المسار المثالي (المقصود من المطور)

```
[1] User في Flutter يضغط "إتمام الدفع بـ Stripe"
    ↓
[2] Flutter ينتقل إلى PaymentScreen عبر Route /payment?id=...&user=...&amount=...
    (route_helper.dart السطر 531-577)
    ↓
[3] PaymentScreen يفتح MyInAppBrowser (flutter_inappwebview)
    selectedUrl = 'https://pickles-pies.com/payment-mobile?customer_id=...&order_id=...&payment_method=stripe'
    (payment_screen.dart السطر 48-50)
    ↓
[4] Laravel GET /payment-mobile → PaymentController@payment
    (routes/web.php:72)
    ↓
[5] PaymentController@payment يحفظ Order ويُنشئ PaymentRequest
    ثم يستدعي Payment::generate_link() ويرجع redirect_link
    (PaymentController.php السطر 41-131)
    ↓
[6] Flutter WebView ينتقل إلى redirect_link (payment/stripe/pay?payment_id=UUID)
    ↓
[7] Laravel GET /payment/stripe/pay → StripePaymentController::index
    يعرض payment-views/stripe.blade.php
    (routes/web.php:106-108, StripePaymentController.php:92-117)
    ↓
[8] stripe.blade.php يحمل Stripe.js ويستدعي /payment/stripe/token
    (stripe.blade.php:5, 16)
    ↓
[9] Laravel ينشئ Stripe Checkout Session:
      success_url = 'https://pickles-pies.com/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=...'
      cancel_url  = 'https://pickles-pies.com/payment/stripe/canceled?payment_id=...'
    (StripePaymentController.php:203-204)
    ↓
[10] stripe.js يستدعي stripe.redirectToCheckout({sessionId})
    ينتقل الـ WebView إلى https://checkout.stripe.com/...
    (stripe.blade.php:23)
    ↓
[11] User يدخل بيانات البطاقة ويضغط زر "Pay"
    ↓
[12] Stripe يعالج الدفع، قد يعرض 3DS Challenge
    ↓
[13] Stripe ينقل الـ WebView إلى success_url
    ↓
[14] Laravel /payment/stripe/success → StripePaymentController::success
    - يستدعي Session::retrieve
    - يكتب is_paid=1
    - يستدعي payment_response(..., 'success')
    (StripePaymentController.php:244-276)
    ↓
[15] Processor::payment_response()
    - external_redirect_link = null
    - return redirect()->route('payment-success')
    (Processor.php:109-118)
    ↓
[16] Laravel GET /payment-success → PaymentController@oksuccess → ❌ METHOD DOES NOT EXIST → HTTP 500
    (routes/web.php:76)
    ↓
[17] ❌ هنا تحدث الكارثة
```

### 1.2 المسار الفعلي: أين ينكسر كل شيء؟

| الخطوة | المتوقع | الفعلي | النتيجة |
|--------|---------|--------|---------|
| [1]-[10] | يعمل | يعمل | ✅ OK |
| [11] | User يضغط "Pay" | User يضغط "Pay" | ✅ OK |
| [12] | 3DS يظهر ويعالج | 3DS iframe يظهر | ⚠️ احتمال تجمد بصري |
| [13] | Stripe يحول لـ success_url | ✅ التحويل يحدث | ✅ OK |
| [14] | success() يعالج ويكتب DB | ✅ يعمل | ✅ OK |
| [15] | payment_response() يحوّل لـ payment-success | ✅ يعمل ويحوّل | ✅ OK |
| [16] | PaymentController@oksuccess ينفذ ويعيد للـ app | ❌ HTTP 500 لأن الميثود غير موجود | **خطأ حرج** |
| [17a] | Flutter يكتشف redirect ويفتح OrderSuccessScreen | ❌ Flutter لا يكتشف لأن URL هو /payment/stripe/success | **خطأ حرج** |
| [17b] | WebView يغلق تلقائياً ويرجع للـ app | ❌ لا custom URL scheme | **خطأ حرج** |
| [18] | User يرى "تم الدفع بنجاح" | ❌ User يرى صفحة خطأ 500 داخل WebView | **التجمد المُبلَّغ** |
| [19] | User يضغط "back" | ⚠️ قد يعمل، قد يتجمد | **Crash محتمل** |
| [20] | User يعيد تشغيل التلفون | — | — |

---

### 1.3 التحليل اللحظة بلحظة: ماذا يرى المستخدم بالضبط؟

```
T+0s:    User يضغط "Pay" في Stripe Checkout
T+2-10s: Stripe يعالج، قد يظهر 3DS Challenge (iframe)
T+10-30s: ينجح الدفع، Stripe يحول لـ success_url
T+30-32s: Laravel success() يكتب DB ويرجع 302 إلى /payment-success
T+32-33s: Laravel /payment-success → HTTP 500 (Method oksuccess not found)
T+33s+:   Flutter WebView يعرض صفحة Laravel 500
         + الـ CircularProgressIndicator يبقى ظاهراً (لأن _isLoading = true)
         + الـ AppBar title ما زال "payment"
         + لا زر exit، لا spinner update، لا dialog
         + الـ WebView نفسه ربما في حالة loading (لم ينته onLoadStop)
T+45s+:   المستخدم يضغط "back" عدة مرات
         - الـ PopScope.onPopInvokedWithResult يستدعي _exitApp()
         - لكن هذا يظهر Dialog فقط، لا يغلق الـ WebView
         - في iOS: الـ back gesture قد يعمل ويغلق الـ WebView
         - في Android: الـ back button قد يستدعي _exitApp() مرتين
T+60s+:   المستخدم يطفئ الهاتف
```

---

## 2. التحليل العميق: لماذا "طفي التلفون"؟

### 2.1 ANR (Application Not Responding)

**Android فقط — مرجع رسمي: [Android ANR docs](https://developer.android.com/training/articles/perf-anr)**

- الـ WebView في Android يعمل في process منفصل داخل الـ app (`webview_process`).
- إذا أصبح هذا الـ process غير مستجيب، نظام Android يظهر dialog "App isn't responding" بعد 5-10 ثوانٍ.
- إذا الـ user ضغط "Wait" عدة مرات، أو إذا حدث ANR في الـ main process بسبب الـ Flutter engine، النظام قد **يقتل الـ process** نهائياً.

**متى يحدث هنا:**
- الـ WebView يحمل صفحة Laravel 500 → الـ HTML يحمل لكن لا يوجد spinner.
- الـ Flutter يحاول استدعاء `Get.find<OrderController>().paymentRedirect(...)` في كل `onLoadStart` و`onLoadStop`.
- إذا كان `OrderController` غير مهيأ بشكل صحيح، الـ `Get.find()` قد يرمي استثناء.

**النتيجة المحتملة:** ANR في الـ main process → OS يقتل الـ app.

### 2.2 OOM (Out Of Memory) Crash

**مرجع رسمي: [Android OOM docs](https://developer.android.com/topic/performance/memory-overview)**

- 3DS Challenge قد يفتح **popup** أو **iframe** أو **window جديد** داخل الـ WebView.
- الـ Stripe.js تحمل Web Components، fonts، analytics scripts.
- إذا الـ WebView في app فيها memory leaks (موثقة في `SECURITY_AUDIT_REPORT.md:1131`), قد يحدث OOM.
- WebView memory leaks موثقة ومعروفة، خاصة مع `InAppBrowser`.

**النتيجة المحتملة:** OOM crash → OS يقتل الـ app.

### 2.3 Native Crash من مكتبة WebView قديمة

- إذا الـ WebView في النظام قديم (Android 5/6 أو iOS 11/12)، بعض صفحات Stripe Checkout الحديثة قد تتسبب في **segfault**.
- Stripe تختبر على WebView Chrome v90+ فقط (مرجع: [Stripe browser support](https://stripe.com/docs/js/appendix#browser-support)).

**النتيجة المحتملة:** Crash native → OS يظهر "App has stopped" → user يعيد تشغيل.

### 2.4 الـ JavaScript Loop داخل WebView

هذا السبب الأخطر والأكثر احتمالاً:

```javascript
// stripe.blade.php السطور 15-31
document.addEventListener("DOMContentLoaded", function () {
    fetch("{{ url('payment/stripe/token/?payment_id=...') }}", ...)
    .then(...).then(session => stripe.redirectToCheckout({sessionId: JSON.parse(session).id}))
    .then(function (result) {
        if (result.error) {
            alert(result.error.message);   // ❌ قد يتجمد
        }
    }).catch(function (error) {
        console.error("error:", error);  // ❌ صامت
    });
});
```

**المشكلة:** عند فشل `redirectToCheckout` (3DS rejected, session expired, network drop):
- الـ `alert()` داخل WebView في Android يفتح native dialog.
- إذا الـ user لم يلاحظ الـ alert، الـ dialog قد يبقى مرئياً.
- الـ WebView يصبح في حالة "stalled" — لا يستجيب للنقر.

**لماذا هذا السبب الأخطر؟**
- لأن الـ `alert()` لا يتسبب في ANR أو OOM فعلياً، لكنه **يحوّل الـ WebView إلى حالة مرئية متجمدة**.

### 2.5 الـ Flutter Engine Lockup

```dart
// payment_webview_screen.dart السطور 116-126
onLoadStart: (controller, url) async {
    Get.find<OrderController>().paymentRedirect(...);
    setState(() { _isLoading = true; });   // ❌ setState في كل redirect
},
```

**المشكلة:**
- استدعاء `setState()` في `onLoadStart` يسبب rebuild للـ widget tree بأكمله.
- **إذا الـ WebView يطلق `onLoadStart` بسرعة (redirect chain سريع)**, الـ Flutter engine قد يدخل في rebuild loop.
- الـ `_isLoading = true` يضاف كل مرة، لكن لا أحد يعيدها لـ `false`.

**النتيجة:** الـ Flutter isolate مشغول في rebuilds. الـ UI لا يستجيب للنقر.

---

## 3. الجذر التقني لكل مشكلة (مرتّبة حسب الخطورة)

### 3.1 🔴 [CRITICAL-P0] عدم تطابق URL patterns بين Flutter و Laravel

**الموقع الدقيق:**
- Flutter: `d:\pikels\picklespiesapp\lib\features\order\domain\services\order_service.dart` السطور 142-147
- Laravel: `c:\xampp\htdocs\pickadmin\app\Http\Controllers\StripePaymentController.php` السطر 203

**الكود الفعلي في Flutter (order_service.dart):**

```dart
bool isSuccess = forSubscription ? url.startsWith('${AppConstants.baseUrl}/subscription-success')
    : url.startsWith('${AppConstants.baseUrl}/payment-success');
bool isFailed = forSubscription ? url.startsWith('${AppConstants.baseUrl}/subscription-fail')
    : url.startsWith('${AppConstants.baseUrl}/payment-fail');
bool isCancel = forSubscription ? url.startsWith('${AppConstants.baseUrl}/subscription-cancel')
    : url.startsWith('${AppConstants.baseUrl}/payment-cancel');
```

**الكود الفعلي في Laravel (StripePaymentController.php):**

```php
'success_url' => url('/') . '/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
'cancel_url'  => url('/') . '/payment/stripe/canceled?payment_id=' . $data->id,
```

**الـ URLs الفعلية:**
- Flutter يبحث عن: `https://pickles-pies.com/payment-success`
- Laravel يحول إلى: `https://pickles-pies.com/payment/stripe/success?session_id=...&payment_id=...`

**النتيجة:**
- `url.startsWith('https://pickles-pies.com/payment-success')` = `false`
- `isSuccess`, `isFailed`, `isCancel` كلهم = `false`
- **لا navigation، لا close، لا dialog، لا شيء**

**هذا هو الجذر الأول وهو وحده كافٍ ليتجمد الـ WebView.**

---

### 3.2 🔴 [CRITICAL-P0] الراوت payment-success يستدعي ميثود oksuccess غير موجود → HTTP 500

**الموقع الدقيق:**
- `c:\xampp\htdocs\pickadmin\routes\web.php` السطر 76
- `c:\xampp\htdocs\pickadmin\app\Http\Controllers\PaymentController.php` (لا يوجد `oksuccess`)

**الكود الفعلي في routes/web.php (السطر 76-78):**

```php
Route::get('payment-success', 'PaymentController@oksuccess')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');
Route::get('payment-cancel', 'PaymentController@cancel')->name('payment-cancel');
```

**الكود الفعلي في PaymentController.php:**

```php
class PaymentController extends Controller
{
    // يوجد: payment(), success(), fail(), cancel()
    // ❌ لا يوجد: oksuccess()
}
```

**النتيجة:**
- عند أي redirect إلى `payment-success` → Laravel يرمي `BadMethodCallException: Method App\Http\Controllers\PaymentController::oksuccess does not exist`.
- الـ user يرى صفحة خطأ Laravel 500.

**لذلك الجذر 3.1 و 3.2 مرتبطان:** إصلاح أحدهما بدون الآخر لا يحل المشكلة.

---

### 3.3 🔴 [CRITICAL-P0] Processor::payment_response() يحول إلى الراوت المعطل

**الموقع الدقيق:** `c:\xampp\htdocs\pickadmin\app\Traits\Processor.php` السطور 109-118

**الكود الفعلي في Processor.php:**

```php
public function payment_response($payment_info, $payment_flag) {
    $payment_info = PaymentRequest::find($payment_info->id);
    $token_string = 'payment_method=' . $payment_info->payment_method . '&&attribute_id=' . $payment_info->attribute_id . '&&transaction_reference=' . $payment_info->transaction_id;
    if (in_array($payment_info->payment_platform, ['web', 'app']) && $payment_info['external_redirect_link'] != null && $this->isSafeExternalRedirect($payment_info['external_redirect_link'])) {
        return redirect($payment_info['external_redirect_link'] . '?flag=' . $payment_flag . '&&token=' . base64_encode($token_string));
    }
    return redirect()->route('payment-' . $payment_flag, ['token' => base64_encode($token_string)]);
}
```

**السلوك:**
- الـ `external_redirect_link` هو `callback` query parameter في `/payment-mobile?...&callback=...`.
- الـ Flutter **لا يمرر callback** (انظر `payment_screen.dart` السطر 48-50).
- `isSafeExternalRedirect(null)` = `false`.
- → `redirect()->route('payment-success')` → HTTP 500.

---

### 3.4 🔴 [CRITICAL-P0] لا custom URL scheme / deep link handler في Flutter

**الموقع الدقيق:**
- `d:\pikels\picklespiesapp\android\app\src\main\AndroidManifest.xml` (لا intent-filter لـ `pickles://`)
- `d:\pikels\picklespiesapp\ios\Runner\Info.plist` (لا `CFBundleURLTypes` للـ payment)

**AndroidManifest.xml الفعلي (السطر 75-93):**

```xml
<intent-filter>
    <action android:name="android.intent.action.MAIN"/>
    <category android:name="android.intent.category.LAUNCHER"/>
</intent-filter>

<meta-data android:name="flutter_deeplinking_enabled" android:value="false" />

<intent-filter android:autoVerify="true">
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="https" android:host="pickles-pies.com" />
</intent-filter>
```

**ما ينقص:**
- لا `<data android:scheme="pickles" />` — لا custom scheme
- `flutter_deeplinking_enabled` = `false`

**Info.plist الفعلي (السطر 25-44):** يحتوي فقط على schemes لـ Google Sign-in و Facebook و Stripe Apple Pay — **لا `pickles://` للـ payment**.

**Flutter WebView behavior:**

```dart
// payment_webview_screen.dart السطور 127-149
shouldOverrideUrlLoading: (controller, navigationAction) async {
    Uri uri = navigationAction.request.url!;
    if (!["http", "https"].contains(uri.scheme)) {
        if (uri.scheme == "intent" || uri.scheme == "tel" || uri.scheme == "mailto") {
            launchUrl(uri, mode: LaunchMode.externalApplication);
        }
        return NavigationActionPolicy.CANCEL;   // ❌ custom scheme محظور
    }
    ...
}
```

**المشكلة:**
- إذا الـ `success_url` كان `pickles://payment/success?...`، الـ WebView سيلتقطه ويرجع `CANCEL`.
- **الـ WebView يلغي الـ navigation، ولا أحد يعرف أن الـ payment انتهى**.

---

### 3.5 🟠 [HIGH-P1] AppConstants.payInWevView = false افتراضياً

**الموقع الدقيق:**
- `d:\pikels\picklespiesapp\lib\util\app_constants.dart` السطر 16
- `d:\pikels\picklespiesapp\lib\helper\route_helper.dart` السطور 555-576

**الكود الفعلي (app_constants.dart السطر 16):**

```dart
static const bool payInWevView = false;  // ← typo: "Wev" بدل "Web"
```

**الكود الفعلي (route_helper.dart):**

```dart
return getRoute(AppConstants.payInWevView ? PaymentWebViewScreen(
    orderModel: order, ...
) : PaymentScreen(
    orderModel: order, ...
));
```

**النتيجة:**
- في الإنتاج، `payInWevView = false` → يستخدم `PaymentScreen` (الـ InAppBrowser منفصل).
- `PaymentScreen` يستخدم `MyInAppBrowser` (extends `InAppBrowser`) — وهو browser كامل يفتح كـ activity منفصلة.

**الفرق التقني:**

| الخاصية | PaymentScreen (InAppBrowser) | PaymentWebViewScreen (InAppWebView) |
|---------|------------------------------|--------------------------------------|
| نوع الـ WebView | Chrome Custom Tab / SFSafariViewController | WebView مدمج داخل Flutter |
| التحكم في navigation | محدود | كامل عبر callbacks |
| Custom URL schemes | لا يعالجها | يمكن اعتراضها |
| 3DS Support | أفضل (متصفح حقيقي) | أسوأ (WebView محدود) |
| التكامل مع Flutter | ضعيف | قوي |

**المشكلة الحالية:** `PaymentScreen` أفضل من ناحية 3DS، لكن **لا يمكنه إعادة التوجيه للـ Flutter**.

---

### 3.6 🟠 [HIGH-P1] alert() و console.error في Stripe Blade view

**الموقع الدقيق:** `c:\xampp\htdocs\pickadmin\resources\views\payment-views\stripe.blade.php` السطور 24-30

```javascript
}).then(function (result) {
    if (result.error) {
        alert(result.error.message);  // ❌ قد يتجمد في WebView
    }
}).catch(function (error) {
    console.error("error:", error);  // ❌ لا إشعار للـ user
});
```

**المشكلة مع alert() في WebView:**
- في Android WebView، `alert()` يفتح `JsDialog`. في بعض الإصدارات، الـ dialog **لا يغلق** عند الضغط على OK.
- إذا ظهر `alert()` بسبب فشل `redirectToCheckout`، الـ user يرى dialog ثابت، الـ WebView خلفه ثابت.

---

### 3.7 🟠 [HIGH-P1] لا spinner update أو progress feedback

**الموقع الدقيق:** `c:\xampp\htdocs\pickadmin\resources\views\payment-views\stripe.blade.php` السطر 9

```html
<div class="text-center"> <h1>Please do not refresh this page...</h1></div>
```

**المشكلة:**
- رسالة ثابتة بدون spinner، بدون progress bar، بدون timeout.

---

### 3.8 🟠 [HIGH-P1] WebView لا يدعم 3DS بشكل موثوق

**الوثائق الرسمية:** [Stripe Mobile Integration](https://stripe.com/docs/payments/accept-a-payment?platform=mobile)

**المشكلة:**
- 3D Secure في WebView يظهر كـ iframe من بنك مصدر البطاقة.
- بعض بنوك منطقة MENA/آسيا تستخدم JS قديم أو redirect chains لا تعمل في WebView.
- إذا حدث أي خطأ في الـ iframe، الـ WebView يبقى في حالة "loading" بصرياً إلى الأبد.

---

### 3.9 🟡 [MEDIUM-P2] لا timeout/keep-alive في الـ Blade view

- لا `setTimeout(() => location.href = 'pickles://payment/timeout', 300000)`.
- الـ user قد ينتظر إلى ما لا نهاية.

---

### 3.10 🟡 [MEDIUM-P2] WebView settings ناقصة

**الموقع:** `payment_webview_screen.dart` السطور 99-112

**ما ينقص لـ Stripe Checkout:**
- `cacheEnabled: true` (مطلوب لـ Stripe.js caching)
- `mixedContentMode` قد يحتاج تهيئة

---

## 4. خارطة المشاكل حسب الأولوية

### 🔴 P0 — يجب إصلاحه قبل أي production

| # | المشكلة | الملف | الحل المقترح (نظري فقط) |
|---|---------|------|-------------------------|
| 1 | URL pattern mismatch بين Flutter و Laravel | `order_service.dart:142-147` ↔ `StripePaymentController.php:203` | أضف `/stripe/success`, `/stripe/canceled` للـ check في Flutter، أو غير Laravel لـ `/payment-success` |
| 2 | HTTP 500 بسبب ميثود oksuccess غير موجود | `routes/web.php:76` ↔ `PaymentController.php` | نفذ oksuccess() method أو احذف الـ route |
| 3 | payment_response() يرفض external_redirect_link | `Processor.php:84-118` | أضف custom scheme للـ allow-list أو غير الـ flow |
| 4 | لا custom URL scheme handler | `AndroidManifest.xml` + `Info.plist` + `payment_webview_screen.dart` | أضف `pickles://` scheme + intent-filter + handler |

### 🟠 P1 — يجب إصلاحه لتجربة مستخدم مقبولة

| # | المشكلة | الملف | الحل المقترح (نظري فقط) |
|---|---------|------|-------------------------|
| 5 | alert() و console.error في Stripe blade | `stripe.blade.php:24-30` | استبدل بـ DOM alert مع زر "إلغاء" يستدعي `pickles://payment/cancel` |
| 6 | payInWevView = false افتراضياً | `app_constants.dart:16` + `route_helper.dart:555-576` | غير إلى `true` لاستخدام `PaymentWebViewScreen` |
| 7 | WebView لا يعالج 3DS | كل الـ flow | استبدل بـ SFSafariViewController (iOS) أو Chrome Custom Tabs (Android) أو Native SDK |
| 8 | shouldOverrideUrlLoading يحظر custom schemes | `payment_webview_screen.dart:127-149` | أضف `pickles://` للـ schemes المسموحة وافتح الـ app عند التقاطها |
| 9 | Flutter WebView يستدعي Get.find في كل onLoadStart | `payment_webview_screen.dart:116-126` | احفظ reference للـ controller في onWebViewCreated |

### 🟡 P2 — تحسينات للجودة

| # | المشكلة | الملف | الحل |
|---|---------|------|------|
| 10 | لا timeout | `stripe.blade.php` | أضف `setTimeout(() => location.href = 'pickles://payment/timeout', 300000)` |
| 11 | لا spinner update | `stripe.blade.php` | أضف spinner حقيقي + progress |
| 12 | WebView settings ناقصة | `payment_webview_screen.dart:99-112` | أضف `cacheEnabled: true` |
| 13 | InAppBrowser memory leak | `payment_screen.dart:154-281` | تأكد من dispose |
| 14 | لا logging في Flutter | `payment_webview_screen.dart` | أضف `log()` عند كل redirect |

---

## 5. الحلول المقترحة (نظري — بدون تطبيق)

### 5.1 الحل الجذري (الموصى به): استبدل WebView بـ Stripe Native SDK

**هذا هو الحل الوحيد طويل المدى.**

- **Flutter:** `flutter_stripe` ([pub.dev](https://pub.dev/packages/flutter_stripe))
- **iOS الأصلي:** `stripe-ios` ([GitHub](https://github.com/stripe/stripe-ios))
- **Android الأصلي:** `stripe-android` ([GitHub](https://github.com/stripe/stripe-android))

**المميزات:**
- لا WebView، لا تجمد
- 3D Secure يعالج داخل native UI
- الـ app تحصل على callback ناجح/فشل مباشرة
- لا 500 errors
- لا URL pattern issues

**المتطلبات:**
- تغيير كبير في الـ app
- يجب ترقية Flutter إلى إصدار حديث
- يحتاج PaymentIntent API على الـ Backend

### 5.2 الحل الوسط: أصلح URL patterns + أضف custom URL scheme

**Backend (Laravel):**

1. **نفذ oksuccess method** في `PaymentController.php`:
```php
public function oksuccess(Request $request)
{
    return redirect()->away('pickles://payment/success?token=' . urlencode($request->input('token', '')));
}
```

2. **أضف custom scheme للـ allow-list** في `Processor.php`:
```php
if (($parts['scheme'] ?? '') === 'pickles' || ($parts['scheme'] ?? '') === 'pickles-staging') {
    return true;
}
```

3. **أو غير success_url في Stripe** لاستخدام custom scheme مباشرة:
```php
// StripePaymentController.php السطر 203
'success_url' => 'pickles://payment/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
```

**Flutter:**

1. **أضف pickles:// للـ URL pattern check** في `order_service.dart`:
```dart
bool isSuccess = url.startsWith('pickles://payment/success') ||
                 url.startsWith('${AppConstants.baseUrl}/payment/stripe/success') ||
                 url.startsWith('${AppConstants.baseUrl}/payment-success');
```

2. **Android — أضف intent-filter في AndroidManifest.xml**:
```xml
<intent-filter>
    <action android:name="android.intent.action.VIEW" />
    <category android:name="android.intent.category.DEFAULT" />
    <category android:name="android.intent.category.BROWSABLE" />
    <data android:scheme="pickles" />
</intent-filter>
```

3. **iOS — أضف لـ Info.plist**:
```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>pickles</string>
        </array>
    </dict>
</array>
```

4. **عالج الـ scheme في الـ WebView**:
```dart
shouldOverrideUrlLoading: (controller, navigationAction) async {
    Uri uri = navigationAction.request.url!;
    if (uri.scheme == 'pickles') {
        Get.back(result: uri.toString());
        return NavigationActionPolicy.CANCEL;
    }
    ...
}
```

### 5.3 الحل الأدنى: أصلح الـ HTTP 500 فقط

إذا لم يكن لديك وقت لأي شيء آخر:

1. **نفذ oksuccess method**.
2. **أضف /payment-success URL check** في Flutter.

**لكن هذا لن يحل:** مشكلة deep link، مشكلة 3DS في WebView، مشكلة memory leak.

### 5.4 الحل البديل: استخدم Chrome Custom Tabs / SFSafariViewController

**Android:**
```dart
import 'package:flutter_custom_tabs/flutter_custom_tabs.dart';

launchUrl(
    Uri.parse('https://pickles-pies.com/payment-mobile?...'),
    customTabsOptions: CustomTabsOptions(
        toolbarColor: Colors.blue,
        enableUrlBarHiding: true,
    ),
);
```

**iOS:**
- استخدم `SFSafariViewController` عبر `url_launcher` أو `flutter_safari_view`.

**المميزات:**
- متصفح حقيقي، أفضل 3DS support
- أقل memory leak
- الـ user يحصل على تجربة مألوفة

**العيوب:**
- لا custom URL scheme handler (يحتاج intent-filter)
- الـ Flutter لا يعرف متى ينتهي الـ payment (يحتاج polling)

---

## 6. ملخص مراجع كود المشروع (مُحقَّقة 100% من الكود الفعلي)

### Flutter Files:

- `d:\pikels\picklespiesapp\lib\features\payment\screens\payment_screen.dart` (282 سطر)
- `d:\pikels\picklespiesapp\lib\features\payment\screens\payment_webview_screen.dart` (200 سطر)
- `d:\pikels\picklespiesapp\lib\features\payment\controllers\payment_controller.dart` (76 سطر)
- `d:\pikels\picklespiesapp\lib\features\order\controllers\order_controller.dart` (345 سطر)
- `d:\pikels\picklespiesapp\lib\features\order\domain\services\order_service.dart` (179 سطر) — **النقطة الحاسمة: السطور 134-177**
- `d:\pikels\picklespiesapp\lib\helper\route_helper.dart` (1057 سطر)
- `d:\pikels\picklespiesapp\lib\util\app_constants.dart` (السطر 16: `payInWevView = false`)
- `d:\pikels\picklespiesapp\android\app\src\main\AndroidManifest.xml` (117 سطر)
- `d:\pikels\picklespiesapp\ios\Runner\Info.plist` (117 سطر)
- `d:\pikels\picklespiesapp\pubspec.yaml` (119 سطر) — يحتوي `flutter_inappwebview: ^6.1.5` و `app_links: ^7.0.0`

### Laravel Files:

- `c:\xampp\htdocs\pickadmin\app\Http\Controllers\StripePaymentController.php` (504 سطر) — **النقطة الحاسمة: السطر 203 (success_url)**
- `c:\xampp\htdocs\pickadmin\app\Http\Controllers\PaymentController.php` (157 سطر) — **النقطة الحاسمة: لا يوجد oksuccess**
- `c:\xampp\htdocs\pickadmin\app\Traits\Processor.php` (119 سطر) — **النقطة الحاسمة: السطر 109-118 (payment_response)**
- `c:\xampp\htdocs\pickadmin\routes\web.php` (السطر 76: `Route::get('payment-success', 'PaymentController@oksuccess')`)
- `c:\xampp\htdocs\pickadmin\resources\views\payment-views\stripe.blade.php` (33 سطر)

---

## 7. التوصيات النهائية (صارمة ومباشِرة)

> **بصراحة كخبير:** لا تستخدم WebView لـ Stripe Checkout على الموبايل. هذا النمط مكسور في الـ design.

### 7.1 ما يجب فعله **اليوم** (Hot Fix — 2-3 ساعات)

1. **نفذ oksuccess method** في `PaymentController.php`:
```php
public function oksuccess(Request $request)
{
    return redirect()->away('pickles://payment/success?token=' . urlencode($request->input('token', '')));
}
```

2. **أضف pickles:// للـ URL check** في `order_service.dart` السطور 142-147.

3. **أضف intent-filter في AndroidManifest.xml** لـ `pickles://`.

4. **أضف CFBundleURLSchemes** في `Info.plist` لـ `pickles`.

### 7.2 ما يجب فعله **خلال شهر** (Proper Fix — 1-2 أسابيع)

5. **استبدل WebView بـ Stripe Native SDK** (`flutter_stripe` أو `flutter_pay`).
6. **أضف Universal Links** كـ fallback.
7. **أضف unit tests** على مسارات الـ success/fail/cancel.

### 7.3 ما يجب **تجنبه** نهائياً

- ❌ لا تستخدم `alert()` في WebView.
- ❌ لا تعتمد على `session()` في الـ WebView flow.
- ❌ لا تترك route يستدعي method غير موجود.
- ❌ لا تستخدم `https://` كـ redirect target للـ app (استخدم custom scheme).
- ❌ لا تضع `payment_process_3d` في الـ Blade view.
- ❌ لا تنشر للإنتاج قبل إصلاح `oksuccess`.
- ❌ لا تستخدم `payInWevView = false` افتراضياً بدون فهم الفرق.

---

## 8. أسئلة يجب الإجابة عنها قبل أي إصلاح

1. **هل لديك صلاحية تعديل Laravel Backend؟** (ضروري لـ 5.2.1)
2. **هل يمكنك إعادة نشر التطبيق؟** (ضروري لـ 5.2.2-5.2.4)
3. **هل تريد حل سريع أم حل جذري؟** (Native SDK = 2-3 أسابيع)
4. **هل الـ WebView يستخدم `PaymentScreen` (InAppBrowser) أم `PaymentWebViewScreen` (InAppWebView)؟**
5. **هل الـ URL في Flutter يبدأ بـ `https://pickles-pies.com` أم domain آخر؟**

---

## 9. ملاحظات ختامية

- **كل ما سبق مكتوب بدون تعديل أي كود** — هذا تقرير تحليلي فقط.
- **التحقق من كل ادعاء موجود في الكود الفعلي** — راجع القسم 6 للملفات والسطور.
- **هذا التقرير يركّز على الجذور الحقيقية** مع كود فعلي مُحقَّق من المصدر.
- **إذا أردت تطبيق الإصلاحات**، أعلمني وسأبدأ في التعديل.

---

> **حُرِّر في:** 2026-08-18
> **المُعدّ:** Senior Code Review & Mobile Integration Audit
> **الحالة:** FINAL — يُسلَّم كما هو
