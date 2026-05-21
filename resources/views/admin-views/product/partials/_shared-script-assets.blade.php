<script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
<script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>

@isset($viewPageScript)
    <script src="{{ asset($viewPageScript) }}"></script>
@endisset

<script src="{{ asset('public/assets/admin/js/AI/products/product-title-autofill.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/AI/products/product-description-autofill.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/AI/products/general-setup-autofill.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/AI/products/product-others-autofill.js') }}"></script>
@if ($moduleType == 'food')
    <script src="{{ asset('public/assets/admin/js/AI/products/variation-setup-auto-fill.js') }}"></script>
@else
    <script src="{{ asset('public/assets/admin/js/AI/products/other-variation-setup-auto-fill.js') }}"></script>
@endif
<script src="{{ asset('public/assets/admin/js/AI/products/seo-section-autofill.js') }}"></script>
<script src="{{ asset('public/assets/admin/js/AI/products/ai-sidebar.js') }}"></script>
<script src="{{ asset('/public/assets/admin/js/AI/products/compressor/image-compressor.js') }}"></script>
<script src="{{ asset('/public/assets/admin/js/AI/products/compressor/compressor.min.js') }}"></script>
