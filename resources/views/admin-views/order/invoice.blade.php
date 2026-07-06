@extends('layouts.admin.app')

@section('title','')


@push('css_or_js')
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style type="text/css" media="print">
        @page {
            size: auto;   /* auto is the initial value */
            margin: 0;  /* this affects the margin in the printer settings */
        }
        
        /* تحسين وضوح الخطوط للطابعات الحرارية */
        .print--invoice,
        .print--invoice * {
            font-family: 'Courier New', Courier, monospace !important;
            font-weight: 700 !important;
            color: #000000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* تكبير حجم الخط الأساسي */
        .print--invoice {
            font-size: 14px !important;
            line-height: 1.4 !important;
        }
        
        /* تكبير العناوين */
        .print--invoice h2.store-name {
            font-size: 18px !important;
            font-weight: 900 !important;
            letter-spacing: 1px !important;
        }
        
        .print--invoice h5 {
            font-size: 14px !important;
            font-weight: 700 !important;
        }
        
        /* تحسين جدول المنتجات */
        .print--invoice .invoice--table th,
        .print--invoice .invoice--table td {
            font-size: 13px !important;
            padding: 4px 2px !important;
            border: none !important;
        }
        
        /* تحسين صف الإجمالي */
        .print--invoice .checkout--info dt,
        .print--invoice .checkout--info dd {
            font-size: 13px !important;
            font-weight: 700 !important;
            margin-bottom: 2px !important;
        }
        
        .print--invoice .checkout--info dt.total,
        .print--invoice .checkout--info dd.total {
            font-size: 15px !important;
            font-weight: 900 !important;
        }
        
        /* إخفاء الصور غير الضرورية */
        .print--invoice .invoice-logo,
        .print--invoice .top-info img {
            display: none !important;
        }
        
        /* تحسين خط الفواصل */
        .print--invoice .top-info .text-uppercase {
            font-size: 13px !important;
            font-weight: 900 !important;
            letter-spacing: 2px !important;
            border-top: 2px dashed #000 !important;
            border-bottom: 2px dashed #000 !important;
            padding: 4px 0 !important;
            margin: 5px 0 !important;
        }
        
        /* تحسين تذييل الفاتورة */
        .print--invoice .copyright {
            font-size: 10px !important;
            margin-top: 10px !important;
        }
        
        /* ضبط عرض الفاتورة للورق الحراري */
        .print--invoice {
            max-width: 280px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 5px !important;
        }
        
        /* تحسين معلومات العميل */
        .print--invoice .order-info-details h5 {
            font-size: 12px !important;
            margin-bottom: 2px !important;
        }
        
        /* تحسين معلومات الطلب */
        .print--invoice .order-info-id h5 {
            font-size: 14px !important;
            font-weight: 900 !important;
        }
        
        /* إضافة مسافات مناسبة */
        .print--invoice .row.mt-3 {
            margin-top: 5px !important;
        }
        
        /* تحسين عرض المنتجات */
        .print--invoice .addons div {
            font-size: 11px !important;
            font-weight: 600 !important;
        }
    </style>
@endpush


@section('content')

@include('admin-views.order.partials._invoice')

@endsection

@push('script')
    <script>
        function printDiv(divName) {
            window.open('{{route("admin.order.print-invoice",["id" => $order->id])}}', '_blank');
        }

    </script>
@endpush
