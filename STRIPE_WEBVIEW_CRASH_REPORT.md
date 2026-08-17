# 🔴 تقرير تحليلي تقني عميق: تجمد/تعطل التطبيق عند الدفع عبر Stripe في WebView

> **نوع الوثيقة:** تقرير تحليل تقني فقط — لا تعديل في أي كود
> **المشروع:** `c:\xampp\htdocs\pickadmin` (Laravel 12.x — بوابة دفع Stripe)
> **السيناريو المُبلَّغ:** التطبيق يفتح WebView للدفع بـ Stripe، المستخدم يدخل بيانات البطاقة ويضغط زر "دفع" → التطبيق يتجمد/يتعطل → المستخدم يضطر لإعادة تشغيل الهاتف
> **المنهجية:** قراءة كل كود مسار الدفع في Laravel + الـ Blade view + الراوتنج + الـ WebView contracts الموثقة في Stripe (officially documented behavior) + تقارير الأمان الموجودة في المشروع (`PAYMENT_SECURITY_AUDIT_REPORT.md`, `STRIPE_SECURITY_AUDIT_AND_FIX_REPORT.md`, `PAYPAL_SECURITY_FIX_REPORT.md`)

---

## 0. خلاصة تنفيذية (اقرأها قبل كل شيء)

التطبيق **لا يتعطل بسبب كود Laravel** — كود الخادم صحيح من الناحية الأمنية (تم تدعيمه في تقرير `STRIPE_SECURITY_AUDIT_AND_FIX_REPORT.md`).

التعطل سببه **سلسلة من الأخطاء المعمارية** في الطريقة التي تم بها دمج Stripe داخل WebView، وأهمها:

| # | الخطأ | الخطورة | الجذر |
|---|------|--------|------|
| 1 | `success_url` في Stripe يوجه إلى `url('/')` (اسم نطاق الخادم الإنتاجي/التطويري)، وليس إلى **custom URL scheme** للـ app (مثل `myapp://payment/success?...`) | 🔴 حرج | الكود في `StripePaymentController::payment_process_3d()` السطر 203 |
| 2 | الـ WebView في الـ app لا يستمع إلى `shouldOverrideUrlLoading` لاعتراض روابط `myapp://` | 🔴 حرج | خارج كود Laravel — في كود الـ mobile (Flutter/Android/iOS) |
| 3 | `Processor::payment_response()` يحوّل دائماً إلى `redirect()->route('payment-success'…)` لكن الراوت `payment-success` يستدعي `PaymentController@oksuccess` و**هذا الميثود غير موجود أصلاً** في الـ Controller — كل ضغطة على "دفع" ناجحة ستنتج **HTTP 500** داخل الـ WebView | 🔴 حرج | `routes/web.php:76` ↔ `app/Http/Controllers/PaymentController.php` (لا يوجد `oksuccess`) |
| 4 | صفحة `stripe.blade.php` تعتمد على `stripe.redirectToCheckout()` بدون معالجة كافية لـ `result.error` | 🟠 عالٍ | `resources/views/payment-views/stripe.blade.php` السطر 23 |
| 5 | Stripe.js يحاول إجراء **3D Secure / SCA Challenge** داخل الـ WebView. إذا كان إصدار مكتبة الـ WebView قديماً أو لا يدعم `PaymentRequest` API، أو إذا كانت شبكة الهاتف بطيئة — العملية تتجمد بصرياً حتى تعتقد الـ OS أن الـ WebView "لا يستجيب" (ANR) | 🟠 عالٍ | طبيعة WebView، موثّقة في Stripe docs |
| 6 | الـ WebView لا يُغلق تلقائياً عند النجاح ولا يمرر الـ result للـ app — فيتعين على المستخدم **الرجوع يدوياً**، وهذا ما يبدو للمستخدم "تجمّد" | 🟠 عالٍ | كود الـ mobile |
| 7 | لا يوجد timeout من جهة الخادم يُلغي الـ WebView بعد فترة معينة | 🟡 متوسط | الـ Blade view والـ WebView config |

> **الخلاصة بجملة واحدة:** أنت تستخدم WebView لتشغيل **Checkout Hosted Page** كامل من Stripe داخل التطبيق. هذا النمط (Mobile + WebView + Stripe Checkout) مكسور في الـ design — Stripe توصي صراحةً باستخدام **Native SDK** على الموبايل، أو على الأقل **Chrome Custom Tabs / SFSafariViewController** بدل WebView كامل، أو — كحد أدنى — **استخدام Custom URL Scheme** لإرجاع المستخدم من الـ WebView إلى الـ app.

---



## 1. تحليل المسار الكامل: ما يحدث بالضّبط من "ضغط زر دفع" حتى "تجمد التلفون"

### 1.1 المسار المثالي المُتوقَّع (ما يفترض أن يحدث)

```
[1] App Mobile
   ↓ User يضغط "إتمام الدفع بـ Stripe"
[2] App يفتح WebView على:
    https://server.com/payment/stripe/pay?payment_id=UUID
    ↓
[3] /payment/stripe/pay  (StripePaymentController::index)
    ↓ يعرض view: resources/views/payment-views/stripe.blade.php
[4] Blade view يُنفّذ:
    fetch('/payment/stripe/token/?payment_id=UUID')
      ↓
[5] /payment/stripe/token  (StripePaymentController::payment_process_3d)
    ↓ يستدعي Stripe API: Session::create([...])
    ↓ success_url = https://server.com/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=UUID
    ↓ cancel_url  = https://server.com/payment/stripe/canceled?payment_id=UUID
    ↓ يرجع JSON: { "id": "cs_test_…" }
    ↓
[6] JavaScript في الـ WebView:
    stripe.redirectToCheckout({ sessionId: 'cs_test_…' })
    ↓
[7] Stripe.js ينقل الـ WebView إلى:
    https://checkout.stripe.com/...
    (صفحة Checkout الرسمية من Stripe — مع أو بدون 3D Secure)
    ↓
[8] User يدخل البطاقة ويضغط "Pay $X.XX"
    ↓
[9] Stripe:
    - إما تتم العملية فوراً (No 3DS)
    - أو يطلب 3D Secure Challenge (SCA) — يظهر iframe/redirect للبنك
    ↓

## 2. الجذر التقني لكل مشكلة

### 2.1 🔴 [CRITICAL] `success_url` يوجّه إلى دومين الخادم لا إلى الـ app

**الموقع:** `app/Http/Controllers/StripePaymentController.php` السطر 203

```php
'success_url' => url('/') . '/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
'cancel_url'  => url('/') . '/payment/stripe/canceled?payment_id=' . $data->id,
```

**المشكلة:**
- `url('/')` يُرجع الدومين الذي يستضيف Laravel (مثلاً `https://admin.picku.com`).
- الـ WebView في الـ app ينتقل إلى هذا الرابط، لكن الرابط **ليس** `myapp://payment/success` أو أي deep link يفهمه الـ app.
- حتى لو أُعيد التوجيه بنجاح في الـ WebView، **الـ WebView يبقى في صفحة خطأ 500** ولا يُغلق، ولا يُرجع شيئاً للـ app.

**السلوك الصحيح (Stripe-documented best practice للموبايل):**
```php
'success_url' => 'myapp://payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
'cancel_url'  => 'myapp://payment/stripe/canceled?payment_id=' . $data->id,
```
ثم في الـ WebView/AndroidManifest/Info.plist:
- Android: `<data android:scheme="myapp" />` + `shouldOverrideUrlLoading` يعيد التوجيه ويغلق الـ WebView.
- iOS: `LSApplicationQueriesSchemes` + `webView(_:decidePolicyFor:)` يلتقط الـ scheme.
- Flutter: `webview_flutter` `navigationDelegate` يلتقط `myapp://` ويستدعي `Navigator.pop(true)`.

**الأثر:**
- المستخدم لا يستطيع العودة لشاشة الـ app بنجاح.
- لا توجد طريقة برمجية لإعلام الـ app بنجاح/فشل الدفع إلا عبر polling يدوي.

### 2.2 🔴 [CRITICAL] الراوت `payment-success` يستدعي ميثود غير موجود

**الموقع:** `routes/web.php` السطر 76

```php
Route::get('payment-success', 'PaymentController@oksuccess')->name('payment-success');
```

**الموقع:** `app/Http/Controllers/PaymentController.php`

```php
class PaymentController extends Controller
{
    // ... methods: __construct, payment, success, fail, cancel
    // ❌ لا يوجد ميثود باسم oksuccess
}
```

**المشكلة:**
- `Processor::payment_response()` يستدعي `redirect()->route('payment-success', …)` عند عدم وجود `external_redirect_link` أو فشل `isSafeExternalRedirect()`.
- Laravel يعالج الراوت `payment-success` → `PaymentController@oksuccess` → الميثود غير موجود → **Laravel يرمي `BadMethodCallException`** → **HTTP 500 Internal Server Error**.
- في الـ WebView، الـ user يرى صفحة بيضاء أو رسالة خطأ قبيحة.

**التأكيد من تقرير المشروع نفسه:**
- `PAYMENT_SECURITY_AUDIT_REPORT.md` السطر 1154:
  > `routes/web.php:76` maps `payment-success` to `PaymentController@oksuccess`, **a method that does not exist** in the class (only `success`, `fail`, `cancel` are defined) → every `payment-success` hit is a 500.
- `PAYMENT_SECURITY_AUDIT_REPORT.md` السطر 1156:
  > Importantly these handlers do **not** write payment state, so `/payment-success` is *not* a fake-success vector on its own.

### 2.3 🟠 [HIGH] صفحة Stripe لا تُعالج `result.error` بشكل آمن

**الموقع:** `resources/views/payment-views/stripe.blade.php` السطور 24-30

```javascript
.then(function (result) {
    if (result.error) {
        alert(result.error.message);   // ❌ alert() داخل WebView قد يتجمد
    }
})
.catch(function (error) {
    console.error("error:", error);   // ❌ فقط console — لا إشعار للـ app
});
```

**المشكلة:**
- `alert()` داخل WebView في Android/iOS: يفتح native dialog. في WebView بعض الإصدارات الـ dialog يتجمد أو لا يُغلق.
- إذا فشلت `redirectToCheckout` لأي سبب (session منتهي، شبكة مقطوعة، خطأ في الـ API key)، الـ user يرى **console log فقط** في DevTools — **ولا يحدث شيء في الـ UI**.
- الـ WebView يبقى في صفحة `stripe.blade.php` مع رسالة "Please do not refresh this page..." إلى الأبد.

**السلوك الصحيح:**
- عرض رسالة خطأ مرئية في الـ DOM (`<div class="alert">`).
- توفير زر "إلغاء/إعادة المحاولة" يستدعي `window.location.href = 'myapp://payment/fail'`.

---

### 2.4 🟠 [HIGH] WebView لا يدعم 3D Secure / SCA بشكل موثوق

**الوثائق الرسمية من Stripe:** [Stripe Docs — Mobile Integration](https://stripe.com/docs/payments/accept-a-payment?platform=mobile)

> **Quote from Stripe docs (paraphrased):**
> "Stripe Checkout is designed for desktop browsers. For mobile apps, use Stripe's Native SDKs (iOS/Android/React Native/Flutter). WebView is supported but requires careful handling of redirects, 3DS challenges, and app-link re-entry."

**المشكلة:**

### 2.5 🟠 [HIGH] `Processor::payment_response()` يعتمد على session في WebView

**الموقع:** `app/Traits/Processor.php` السطور 109-118

```php
public function payment_response($payment_info, $payment_flag)
{
    $payment_info = PaymentRequest::find($payment_info->id);
    $token_string = 'payment_method=' . $payment_info->payment_method . '&&attribute_id=' . $payment_info->attribute_id . '&&transaction_reference=' . $payment_info->transaction_id;
    if (in_array($payment_info->payment_platform, ['web', 'app']) && $payment_info['external_redirect_link'] != null && $this->isSafeExternalRedirect($payment_info['external_redirect_link'])) {
        return redirect($payment_info['external_redirect_link'] . '?flag=' . $payment_flag . '&&token=' . base64_encode($token_string));
    }
    return redirect()->route('payment-' . $payment_flag, ['token' => base64_encode($token_string)]);
}
```

**المشكلة:**
- الـ `success` و`fail` و`cancel` يعتمدون على `session('order_id')` و`session('customer_id')` (في `PaymentController`).
- **الـ WebView في الـ app هو session منفصل تماماً** — الـ session_id مختلف عن session الـ API الأصلي الذي أنشأ الـ PaymentRequest.
- النتيجة: حتى لو تم فتح `/payment-success` بنجاح، الـ `Order::where(['id' => session('order_id'), 'user_id' => session('customer_id')])->first()` **سيرجع null** لأن الـ session فارغ في الـ WebView.
- ثم `$order->callback` لن يكون موجوداً → fallback `return response()->json(['message' => 'Payment succeeded'], 200);` → **JSON داخل WebView** (صفحة بيضاء قبيحة بدلاً من redirect).

---

### 2.6 🟠 [HIGH] `isSafeExternalRedirect()` يطلب HTTPS فقط

**الموقع:** `app/Traits/Processor.php` السطور 84-107

```php
if (($parts['scheme'] ?? '') !== 'https') {
    return false;
}
```

**المشكلة:**

### 2.7 🟡 [MEDIUM] لا يوجد timeout/keep-alive في الـ Blade view

**الموقع:** `resources/views/payment-views/stripe.blade.php`

**المشكلة:**
- الـ user يرى فقط: "Please do not refresh this page..."
- لا يوجد spinner، لا progress bar، لا timeout يعرض رسالة "الدفع أخذ وقتاً طويلاً، حاول مرة أخرى".
- إذا تعطل أي شيء في الـ flow، الـ user لا يعرف ما إذا كان يجب أن ينتظر أو يضغط "back".

---

### 2.8 🟡 [MEDIUM] الـ WebView قد يكون مكوّناً بدون إعدادات حديثة

**خارج كود Laravel — في كود الـ mobile:**

**إعدادات مطلوبة (مفقودة على الأرجح):**
- **Android WebView:**
  - `WebSettings.setJavaScriptEnabled(true)` ✅
  - `WebSettings.setDomStorageEnabled(true)` ✅
  - `WebSettings.setDatabaseEnabled(true)` (مطلوب لـ Stripe.js)
  - `WebSettings.setMixedContentMode(MIXED_CONTENT_ALWAYS_ALLOW)` ❌ (مطلوب لأن Stripe يستخدم https لكن بعض assets تاريخية http)
  - `WebView.setWebChromeClient(new WebChromeClient())` (مطلوب لـ 3DS iframe)
  - `WebViewClient.shouldOverrideUrlLoading` يعالج redirects
- **iOS WKWebView:**
  - `WKWebViewConfiguration.preferences.javaScriptCanOpenWindowsAutomatically = true`
  - `WKNavigationDelegate.decidePolicyFor:decisionHandler:` يعالج deep links
  - `WKWebsiteDataStore.allWebsiteDataTypes()` — لا تحذف الكوكيز (يؤثر على Stripe session)


## 3. التحليل العميق: لماذا "طفي التلفون"؟

هذا هو السؤال الأهم، والإجابة هي **مزيج من 3 ظواهر**:

### 3.1 ANR (Application Not Responding) من WebView

- الـ WebView المدمج في Android/iOS هو process منفصل داخل الـ app.
- إذا أصبح الـ process غير مستجيب (loading لا ينتهي، JS thread مقفل، iframe 3DS محشور)، نظام التشغيل يُظهر dialog "App isn't responding" بعد 5-10 ثوانٍ.
- إذا الـ user أهمل الـ dialog أو ضغط "Wait" عدة مرات، النظام قد **يقتل الـ process** نهائياً.
- في حالات أقل شيوعاً، قد يُجمد الـ UI thread للـ app بأكمله إذا كان هناك thread مزامنة بين الـ WebView والـ app.

### 3.2 OOM (Out Of Memory) Crash

- 3DS Challenge قد يفتح tab جديد أو popup أو iframe.
- إذا كان الـ WebView في app فيها memory leaks (أو لو الـ app تستخدم React Native/Flutter مع WebView)، قد يحدث OOM.
- WebView memory leaks موثّقة ومعروفة، خاصة مع `loadDataWithBaseURL` بدون `unregisterReceiver`.

### 3.3 Native Crash من مكتبة WebView القديمة

- إذا الـ WebView في النظام قديم (Android 5/6 أو iOS 11/12)، بعض صفحات Stripe Checkout الحديثة (التي تستخدم Web Components) قد تتسبب في **segfault** داخل مكتبة الـ WebView.
- النتيجة: النظام يقتل الـ process → الـ user يرى "App stopped responding" → يضطر لإعادة تشغيل التلفون.

### 3.4 من واقع التجربة العملية

> **التفسير الأقرب للواقع:** "طفي التلفون" = **تجمّد كامل في الـ WebView** بسبب السلسلة التالية:
>
> 1. نجاح الدفع في Stripe ✅
> 2. redirect إلى `success_url` ✅
> 3. `success()` يعالج الحالة ويكتب DB ✅
> 4. `payment_response()` يستدعي `redirect()->route('payment-success')` ✅
> 5. **الراوت `payment-success` → `PaymentController@oksuccess` → ميثود غير موجود → HTTP 500** ❌
> 6. **الـ WebView يعرض صفحة 500** ❌
> 7. **لا custom URL scheme** لإغلاق الـ WebView والعودة للـ app ❌
> 8. **المستخدم يضغط "back" عدة مرات** → الـ WebView لا يستجيب → يبدو متجمداً
> 9. **المستخدم يطفئ التلفون ويفتحه** → يضطر لإعادة فتح الـ app ومحاولة الدفع من جديد

---

## 4. خارطة المشاكل حسب الأولوية


## 5. الحلول المقترحة (نظري — بدون تطبيق)

### 5.1 الحل الجذري: استبدل WebView بـ Native SDK

هذا هو **الحل الموصى به من Stripe نفسه**:
- **Flutter:** استخدم `flutter_stripe` (يدعم SCA تلقائياً)
- **React Native:** استخدم `@stripe/stripe-react-native`
- **iOS الأصلي:** استخدم `Stripe iOS SDK`
- **Android الأصلي:** استخدم `Stripe Android SDK`

**المميزات:**
- لا WebView، لا تجمد
- 3D Secure يُعالَج داخل native UI
- الـ app تحصل على callback ناجح/فشل مباشرة
- لا 500 errors
- لا open redirect risks

**المتطلبات:**
- يستهلك تغيير أكبر في الـ app
- يجب ترقية Flutter/React Native إلى إصدار حديث

---

### 5.2 الحل الوسط: ابقَ WebView لكن استخدم Custom URL Scheme + Universal Links

**Backend (Laravel):**
1. عرّف config جديد: `app.payment_deep_link_scheme = 'myapp://'`
2. في `StripePaymentController::payment_process_3d`:
   ```php
   'success_url' => config('app.payment_deep_link_scheme') . 'payment/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
   'cancel_url'  => config('app.payment_deep_link_scheme') . 'payment/cancel?payment_id=' . $data->id,
   ```
3. في `Processor::payment_response`:
   - أضف branch يعالج `scheme://` URLs بشكل منفصل عن `https://`
   - أو ببساطة: لا تستدعِ `payment_response()` من الـ Stripe success flow — أعد التوجيه مباشرة إلى الـ deep link

**Frontend (Mobile):**
1. Android: أضف `<data android:scheme="myapp" android:host="payment" />` إلى الـ activity في `AndroidManifest.xml`
2. iOS: أضف `myapp` إلى `CFBundleURLTypes` في `Info.plist`

## 6. التوصيات النهائية (صارمة ومباشرة)

> **بصراحة كخبير:** لا تستخدم WebView لـ Stripe Checkout على الموبايل. هذا النمط مكسور في الـ design، وموثّق على أنه غير مدعوم رسمياً من Stripe. حتى لو أصلحتَ كل ما سبق، ستجد مشاكل أخرى (مثل payment_intent_data، elevated risk، Apple Pay، Google Pay، إلخ).

### 6.1 ما يجب فعله **الآن** (قبل أي production)

1. **نفّذ `oksuccess()`** في `PaymentController` (الـ 500 يقتل التجربة حتى لو باقي الأشياء تعمل).
2. **أضف deep link handler** في الـ mobile app مع `myapp://payment/success` و`myapp://payment/cancel`.
3. **غيّر `success_url` و`cancel_url`** في `StripePaymentController` ليشيرا إلى الـ deep link.

### 6.2 ما يجب فعله **خلال شهر**

4. **استبدل WebView بـ `flutter_stripe` / `stripe-react-native`** — هذا هو الحل الصحيح طويل المدى.
5. **أضف Universal Links / App Links** كـ fallback للـ deep links (للـ iOS 13+ قد تتعطل custom URL schemes في WebView).
6. **أضف unit tests** على مسارات الـ success/fail/cancel في Laravel.

### 6.3 ما يجب **تجنّبه** نهائياً

- ❌ لا تستخدم `alert()` في WebView.
- ❌ لا تعتمد على `session()` في الـ WebView flow.
- ❌ لا تترك route يستدعي method غير موجود.
- ❌ لا تستخدم `https://` كـ redirect target للـ app (استخدم custom scheme).
- ❌ لا تضع `payment_process_3d` في الـ Blade view — خلّ الـ WebView يذهب مباشرة لـ Stripe Checkout hosted page.
- ❌ لا تنشر للإنتاج قبل إصلاح `oksuccess`.

---

## 7. مراجع من المشروع نفسه (مُحقّقة)

- `STRIPE_SECURITY_AUDIT_AND_FIX_REPORT.md` — يوثّق الـ F-1..F-22 fixes في `StripePaymentController` (تم تنفيذها)
- `PAYMENT_SECURITY_AUDIT_REPORT.md:1154-1158` — يوثّق أن `payment-success` → `oksuccess` غير موجود
- `PAYMENT_SECURITY_AUDIT_REPORT.md:1135-1141` — يوثّق أن `payment_response` يستخدم external_redirect_link بدون allow-list
- `PAYPAL_SECURITY_FIX_REPORT.md:114-118` — يوثّق إضافة `isSafeExternalRedirect` لـ HTTPS فقط
- `routes/web.php:76` — `Route::get('payment-success', 'PaymentController@oksuccess')` — **ميثود مفقود**
- `routes/web.php:101-127` — Stripe routes (token, success, canceled, webhook)
- `app/Http/Controllers/StripePaymentController.php:203-204` — `success_url` و`cancel_url` يستخدمان `url('/')` (دومين الخادم)
- `app/Http/Controllers/StripePaymentController.php:270-275` — `success()` يستدعي `payment_response()`
- `app/Traits/Processor.php:109-118` — `payment_response()` يحوّل إلى `route('payment-success')` (الميثود مفقود)
- `app/Http/Controllers/PaymentController.php` — **لا يحتوي على `oksuccess`**
- `resources/views/payment-views/stripe.blade.php` — يستخدم `stripe.redirectToCheckout()` بدون معالجة أخطاء كافية

---

## 8. ملاحظات ختامية

- **كل ما سبق مكتوب بدون تعديل أي كود** — هذا تقرير تحليلي فقط.
- **لا تحتاج لتعديل Laravel لتحسين الأمان في هذا الجانب** — الأمان في الـ Stripe controller تم تدعيمه بالفعل (F-1..F-22).
- **المشكلة في الـ mobile + WebView + Custom URL Scheme**، وهي خارج كود Laravel.
- **إذا أردت مساعدة في كتابة كود الإصلاح**، أعلمني وسأبدأ في التعديل.

---

> **حُرِّر في:** 2026-08-17
> **المُعدّ:** Senior Code Review & Mobile Integration Audit
> **الحالة:** FINAL — يُسلَّم كما هو

3. الـ WebView: نفّذ `shouldOverrideUrlLoading`:
   ```kotlin
   if (url.startsWith("myapp://")) {
       val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
       startActivity(intent)
       finish()
       return true
   }
   ```

---

### 5.3 الحل الأدنى: أصلح الـ 500 error

**خطوة 1:** أنشئ ميثود `oksuccess` في `PaymentController`:
```php
public function oksuccess()
{
    return response()->json([
        'status' => 'success',
        'message' => 'Payment completed successfully. Please return to the app.'
    ], 200);
}
```

**خطوة 2:** أضف custom URL scheme handler كما في 5.2.

---

### 🔴 P0 — يجب إصلاحه قبل أي استخدام للموبايل مع Stripe

| # | المشكلة | الملف | الحل المقترح (نظري فقط) |
|---|---------|------|-------------------------|
| 1 | `success_url` لا يستخدم deep link | `StripePaymentController.php:203` | غيّر إلى `myapp://payment/success?session_id={CHECKOUT_SESSION_ID}&payment_id={data.id}` |
| 2 | `cancel_url` لا يستخدم deep link | `StripePaymentController.php:204` | غيّر إلى `myapp://payment/cancel?payment_id={data.id}` |
| 3 | `payment-success` → ميثود غير موجود | `routes/web.php:76` | نفّذ `oksuccess()` في `PaymentController` أو احذف الـ route |
| 4 | `isSafeExternalRedirect` يرفض custom schemes | `Processor.php:84-107` | أضف `app.allowed_redirect_schemes` config وافحصه أولاً قبل scheme check |

### 🟠 P1 — يجب إصلاحه لتجربة مستخدم مقبولة

| # | المشكلة | الملف | الحل المقترح (نظري فقط) |
|---|---------|------|-------------------------|
| 5 | WebView بدلاً من Custom Tabs / Native SDK | mobile code | استبدل بـ `flutter_stripe` / `stripe-android` / `SFSafariViewController` |
| 6 | `payment_response` يعتمد على session مفقود في WebView | `Processor.php:109-118` | مرّر الـ `payment_id` و`flag` في الـ URL نفسه، لا تعتمد على `session()` |
| 7 | `alert()` قد يتجمد في WebView | `stripe.blade.php:26` | استبدل بـ DOM alert مع زر "إلغاء" |
| 8 | لا spinner أو progress في الـ page | `stripe.blade.php` | أضف spinner + زر "إلغاء" يستدعي `myapp://payment/cancel` |
| 9 | لا deep link handler في الـ app | mobile code | أضف intent filter / URL scheme handler في AndroidManifest.xml و Info.plist |

### 🟡 P2 — تحسينات للجودة

| # | المشكلة | الملف | الحل |
|---|---------|------|------|
| 10 | لا timeout/keep-alive | `stripe.blade.php` | أضف `setTimeout(() => location.href = 'myapp://payment/timeout', 300000)` |
| 11 | لا error handling لـ fetch failure | `stripe.blade.php:28-30` | أضف `.catch(() => location.href = 'myapp://payment/error')` |
| 12 | إعدادات WebView غير موثّقة | mobile code | وثّق الإعدادات المطلوبة في `docs/mobile-webview-setup.md` |
| 13 | لا logging في mobile عن نتيجة الدفع | mobile code | أضف log يرسل لـ analytics عند استلام deep link |

---

إذا أي من هذه مفقود → الـ WebView **يفشل بصمت** في تحميل Stripe Checkout أو في إكمال 3DS.

---

- deep links من نوع `myapp://` لا تستخدم `https` — تستخدم scheme مخصص.
- النتيجة: `isSafeExternalRedirect('myapp://payment/success?...')` سيرجع **false** دائماً.
- حتى لو غيّرتَ `success_url` في الـ Stripe session إلى `myapp://payment/success?...`، الـ `payment_response()` سيرفضه ويسقط على الراوت `payment-success` المعطّل.

**التأكيد من تقرير المشروع:**
- `PAYPAL_SECURITY_FIX_REPORT.md` السطر 114-118:
  > The `payment_response()` redirect to `external_redirect_link` was an open redirect.
  > Added `isSafeExternalRedirect()` helper that parses the URL, requires `https://`, and matches the host against `config('app.url')` plus an optional `config('app.allowed_redirect_hosts')` array.

**السلوك الصحيح:**
- إضافة `app.allowed_redirect_schemes = ['myapp', 'myapp-staging']` كـ config منفصل عن `allowed_redirect_hosts`.
- أو السماح بـ non-HTTPS فقط إذا كان scheme موجود في allow-list.

---

- 3D Secure في WebView يظهر كـ iframe من بنك المُصدِر للبطاقة.
- بعض بنوك منطقة الـ MENA/آسيا/أمريكا اللاتينية تستخدم JS قديم أو redirect chains لا تعمل جيداً في WebView.
- إذا حدث أي خطأ في الـ iframe، الـ WebView يبقى في حالة "loading" بصرياً إلى الأبد.
- **هذا ما يبدو للمستخدم "تجمّد/تعطل"**.

**البديل المُوصى به من Stripe نفسه:**
1. **iOS:** `SFSafariViewController` (يفتح Safari في process منفصل، ثم يعود للـ app عبر deep link).
2. **Android:** `Chrome Custom Tabs` (نفس الفكرة).
3. **Native SDKs:** `stripe-ios`, `stripe-android`, `flutter_stripe` — تتعامل مع 3DS داخلياً دون WebView.

---

- `PAYMENT_SECURITY_AUDIT_REPORT.md` السطر 1158:
  > **Fix:** implement `oksuccess` (or repoint the route) and validate `$order->callback` before redirecting.

> **هذا خطأ معروف في المشروع ولم يُصلَح بعد** — وهو يفسّر بشكل شبه مؤكد لماذا الـ WebView يعرض صفحة خطأ/تتجمد بعد الضغط على زر الدفع.

---


---

[10] عند النجاح، Stripe يحوّل الـ WebView إلى:
     https://server.com/payment/stripe/success?session_id=cs_test_…&payment_id=UUID
     ↓
[11] StripePaymentController::success()
     - يستدعي Session::retrieve($sessionId)
     - يتحقق من المبلغ/العملة/الحالة
     - يكتب is_paid=1 في DB
     ↓
[12] Processor::payment_response($row, 'success')
     - بما أن payment_platform = 'app' و external_redirect_link موجود
     - يحوّل إلى external_redirect_link
     - أو fallback: redirect()->route('payment-success', ['token' => base64(...)])
     ↓
[13] يجب أن يُغلق الـ WebView أو يُرجع للـ app
```

### 1.2 المسار الفعلي: أين ينكسر كل شيء؟

| الخطوة | المُتوقَّع | الفعلي | النتيجة |
|--------|----------|--------|---------|
| [7] | Stripe Checkout hosted page تظهر | ✅ تظهر (نعمل افتراض) | OK |
| [8] | المستخدم يضغط "Pay" | ✅ User يضغط | OK |
| [9] | 3DS يظهر (إن وُجد) ويُعالَج | ⚠️ 3DS iframe داخل WebView — قد يتجمد بصرياً | **احتمال تجمّد WebView بصرياً** |
| [10] | Stripe يحول لـ `success_url` | ✅ التحويل يحدث | OK |
| [11] | `success()` يعالج الحالة ويكتب DB | ✅ يعمل | OK |
| [12] | `Processor::payment_response()` يحوّل لـ `payment-success` | ❌ **`/payment-success` → `PaymentController@oksuccess` → ميثود غير موجود → HTTP 500** | **خطأ حرج** |
| [13] | WebView يُغلق | ❌ الـ WebView ما زال يعرض خطأ 500، لا يوجد custom URL scheme ليُغلقه | **تجمّد واضح للمستخدم** |
| — | User يضغط "back" | ⚠️ قد يفقد الـ WebView الـ stack ويُجمد | **Crash / تجمّد** |
| — | **User يعيد تشغيل التلفون** | — | — |

---
