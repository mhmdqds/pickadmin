@extends('layouts.admin.print')

@section('title','')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style type="text/css">
    /* إعدادات عامة */
    html, body {
        width: 100% !important;
        overflow-x: hidden !important;
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* إعدادات الطباعة الحرارية */
    .print--invoice,
    .print--invoice * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        font-family: 'Arial Black', 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
        color: #000 !important;
        text-shadow: none !important;
        filter: none !important;
        opacity: 1 !important;
        text-rendering: geometricPrecision !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        letter-spacing: 0.02em !important;
    }

    /* حاوية الفاتورة - عرض الطابعة الحرارية 80mm */
    .print--invoice {
        max-width: 280px !important;
        width: 100% !important;
        margin: 0 auto !important;
        padding: 8px !important;
        box-sizing: border-box !important;
    }

    /* عناوين */
    .print--invoice h2.store-name {
        font-size: 20px !important;
        font-weight: 900 !important;
        line-height: 1.2 !important;
        margin: 0 0 8px 0 !important;
    }

    .print--invoice h5 {
        font-size: 15px !important;
        font-weight: 900 !important;
        line-height: 1.3 !important;
        margin: 0 0 4px 0 !important;
    }

    /* جدول الفاتورة */
    .print--invoice .invoice--table th,
    .print--invoice .invoice--table td {
        font-size: 14px !important;
        font-weight: 700 !important;
        padding: 6px 4px !important;
        border: none !important;
    }

    /* معلومات الدفع */
    .print--invoice .checkout--info dt,
    .print--invoice .checkout--info dd {
        font-size: 14px !important;
        font-weight: 700 !important;
        margin-bottom: 4px !important;
    }

    .print--invoice .checkout--info dt.total,
    .print--invoice .checkout--info dd.total {
        font-size: 16px !important;
        font-weight: 900 !important;
    }

    /* الفواصل والعناوين العلوية */
    .print--invoice .top-info .text-uppercase,
    .print--invoice .receipt-divider {
        font-size: 14px !important;
        font-weight: 900 !important;
        letter-spacing: 2px !important;
        border-top: 3px solid #000 !important;
        border-bottom: 3px solid #000 !important;
        padding: 6px 0 !important;
        margin: 8px 0 !important;
        text-align: center !important;
    }

    /* حقوق النشر */
    .print--invoice .copyright {
        font-size: 11px !important;
        font-weight: 700 !important;
        margin-top: 10px !important;
        text-align: center !important;
    }

    /* ملاحظات المنتج */
    .print--invoice .note-info {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #333 !important;
        font-style: italic !important;
        margin: 2px 0 !important;
        padding: 2px 0 !important;
    }

    /* تفاصيل الطلب */
    .print--invoice .order-info-details h5 {
        font-size: 13px !important;
        font-weight: 900 !important;
        margin-bottom: 4px !important;
    }

    .print--invoice .order-info-id h5 {
        font-size: 15px !important;
        font-weight: 900 !important;
    }

    /* الإضافات */
    .print--invoice .addons div {
        font-size: 12px !important;
        font-weight: 700 !important;
    }

    /* المسافات */
    .print--invoice .row.mt-3 {
        margin-top: 8px !important;
    }

    /* Flexbox */
    .print--invoice .d-flex {
        display: flex !important;
    }

    .print--invoice .justify-content-center {
        justify-content: center !important;
    }

    .print--invoice .text-center {
        text-align: center !important;
    }
</style>

<style type="text/css" media="print">
    @page {
        margin: 0;
        size: 80mm auto;
    }

    html, body {
        width: 80mm !important;
        height: auto !important;
        overflow: visible !important;
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .print--invoice {
        width: 80mm !important;
        max-width: 80mm !important;
        padding: 4mm !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* منع تقسيم العناصر عند الطباعة */
    .print--invoice,
    .print--invoice *,
    table, thead, tbody, tr, td, th {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* إخفاء العناصر غير الضرورية */
    .invoice-logo,
    .top-info img,
    .non-printable {
        display: none !important;
    }
</style>
@endpush

@section('content')
    @include('admin-views.order.partials._invoice')
@endsection

@push('script_2')
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 800);
    });
</script>
@endpush
