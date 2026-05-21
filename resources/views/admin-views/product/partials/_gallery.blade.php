<div class="row g-3">
    @foreach ($items as $key => $item)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2 justify-content-between h-100">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex gap-10px">
                                    <div class="border min-w-70 min-h-70 w-100px h-100px rounded overflow-hidden">
                                        <img class="onerror-image w-100 h-100 object-cover"
                                            src="{{ $item['image_full_url'] ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            alt="Image Description">
                                    </div>
                                    <div class="cont py-lg-3 py-2">
                                        <h4 class="m-0 fs-16 fw-semibold title-clr line--limit-2">
                                            {{ $item?->getRawOriginal('name') }} </h4>
                                        <div class="mt-10px d-flex gap-10px flex-wrap">

                                            @if ($item->organic == 1)
                                                <div
                                                    class="badge badge-success font-weight-normal px-2 fs-10 rounded-pill">
                                                    {{ translate('messages.Organic') }}</div>
                                            @endif
                                            @if ($item->is_halal == 1)
                                                <div
                                                    class="badge badge-warning font-weight-normal px-2 fs-10 rounded-pill text-white">
                                                    {{ translate('messages.Halal') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>


                            @php
                                $has_variation = ($item->module->module_type == 'food' && count(json_decode($item->food_variations, true)??[]) > 0  ) || ($item->module->module_type != 'food' &&  count(json_decode($item->variations, true)??[]) > 0 )
                            @endphp
                            <div class="col-xxl-{{ $has_variation ? '6':'12' }}">
                                <div class="bg--secondary rounded p-10px h-100">
                                    <h6 class="mb-0 text-capitalize fs-14 fw-semibold">
                                        {{ translate('General_Information') }}</h6>
                                    <div class="product-gallery-info mt-10px">
                                        <span class="d-flex mb-2 gap-1 fs-12">
                                            <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                                {{ translate('messages.Category') }}</div>
                                            <span class="title-clr">:</span>
                                            <strong
                                                class="fw-medium title-clr">{{ Str::limit(
                                                    ($item?->category?->parent ? $item?->category?->parent?->name : $item?->category?->name) ??
                                                        translate('messages.uncategorize'),
                                                    20,
                                                    '...',
                                                ) }}</strong>
                                        </span>
                                        <span class="d-flex mb-2 gap-1 fs-12">
                                            <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                                {{ translate('messages.Sub_Category') }}</div>
                                            <span class="title-clr">:</span>
                                            <strong
                                                class="fw-medium title-clr">{{ Str::limit($item?->category?->name ?? translate('messages.uncategorize'), 20, '...') }}</strong>
                                        </span>
                                        @if ($item->module->module_type == 'grocery')
                                            <span class="d-flex mb-2 gap-1 fs-12">
                                                <div
                                                    class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                                    {{ translate('messages.Is_Organic') }}</div>
                                                <span class="title-clr">:</span>
                                                <strong class="fw-medium title-clr">
                                                    {{ $item->organic == 1 ? translate('messages.yes') : translate('messages.no') }}</strong>
                                            </span>
                                        @endif
                                        @if ($item->module->module_type == 'food')
                                            <span class="d-flex mb-2 gap-1 fs-12">
                                                <div
                                                    class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                                    {{ translate('messages.Item_type') }} </div>
                                                <span class="title-clr">:</span>
                                                <strong class="fw-medium title-clr">
                                                    {{ $item->veg == 1 ? translate('messages.veg') : translate('messages.non_veg') }}</strong>
                                            </span>
                                        @else
                                            @if ($item?->unit)
                                                <span class="d-flex mb-2 gap-1 fs-12">
                                                    <div
                                                        class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                                        {{ translate('messages.Unit') }} </div>
                                                    <span class="title-clr">:</span>
                                                    <strong class="fw-medium title-clr">
                                                        {{ $item?->unit?->unit }}</strong>
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>



                            @if ($has_variation)


                                <div class="col-xxl-6">
                                    <div class="bg--secondary rounded p-10px h-100">
                                        <h6 class="mb-0 text-capitalize fs-14 fw-semibold">
                                            {{ translate('Available_Variations') }}</h6>
                                        <div class="product-gallery-info mt-10px">
                                            <div class="d-flex flex-wrap gap-10px">

                                                @if ($item->module->module_type == 'food')
                                                    @php
                                                        $variations = json_decode($item->food_variations, true) ?? [];

                                                        $total = array_sum(
                                                            array_map(function ($item) {
                                                                return isset($item['values']) &&
                                                                    is_array($item['values'])
                                                                    ? count($item['values'])
                                                                    : 0;
                                                            }, $variations),
                                                        );
                                                        $count = 0;
                                                    @endphp
                                                    @foreach ($variations as $key => $variation)
                                                        @if (isset($variation['values']))
                                                            @foreach ($variation['values'] as $value)
                                                                <span
                                                                    class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                                                    {{ $variation['name'] }} - {{ $value['label'] }}
                                                                </span>


                                                                @php $count++; @endphp
                                                            @endforeach
                                                            @if ($count == 4 && $total > 5)
                                                                <span
                                                                    class="bg--EDEDED rounded-pill fw-medium py-1 px-2 fs-12 title-clr">
                                                                    {{ $total - 5 }}+
                                                                </span>
                                                                @break
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                @else
                                                    @if ($item->variations && is_array(json_decode($item['variations'], true)))
                                                        @foreach (json_decode($item['variations'], true) as $key => $variation)
                                                            <span
                                                                class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                                                {{ $variation['type'] }}
                                                            </span>

                                                            @if ($key == 4 && count(json_decode($item['variations'], true)) > 5)
                                                                <span
                                                                    class="bg--EDEDED rounded-pill fw-medium py-1 px-2 fs-12 title-clr">
                                                                    {{ count(json_decode($item['variations'], true)) - 5 }}+
                                                                </span>
                                                                @break
                                                            @endif
                                                        @endforeach
                                                    @endif


                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif




                            @if (count($item->tags ?? []) > 0)
                                <div class="col-12">
                                    <div class="bg--secondary rounded p-10px">
                                        <div class="d-flex gap-10px align-items-center flex-wrap">
                                            <h6 class="mb-0 text-capitalize fs-14 fw-semibold">{{ translate('Tags') }}
                                            </h6>


                                            @foreach ($item->tags as $key => $c)
                                                @if ($key < 5)
                                                    <span class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                                        {{ $c->tag }}
                                                    </span>
                                                @endif

                                                @if ($key == 4 && count($item->tags) > 5)
                                                    <span
                                                        class="bg--EDEDED rounded-pill fw-medium py-1 px-2 fs-12 title-clr">
                                                        {{ count($item->tags) - 5 }}+
                                                    </span>
                                                    @break
                                                @endif
                                            @endforeach

                                        </div>
                                    </div>
                                </div>

                            @endif



                        </div>
                        <div class="mt-2">
                            <div class="d-flex gap-3 flex-wrap justify-content-end">
                                <a href="#0" class="btn btn--sm btn-outline-primary offcanvas-trigger data-info-show"
                                data-url="{{ Auth::guard('admin')->check() ? route('admin.item.item-view', ['id' => $item->id]):  route('vendor.item.item-view', ['id' => $item->id])}}"
                                    data-target="#offcanvas_common_condition">
                                    {{ translate('messages.View Details') }}
                                </a>
                                <a target="_blank"
                                    href="{{ Auth::guard('admin')->check() ? route('admin.item.edit', ['id' => $item->id, 'product_gellary' => true]):route('vendor.item.edit', ['id' => $item->id, 'product_gellary' => true]) }}"
                                    class="btn btn--sm btn-primary">
                                    {{ translate('messages.use_this_product_info') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>



<div id="offcanvas_common_condition" class="custom-offcanvas d-flex flex-column justify-content-between">

 <div id="data-view" class="h-100">
        </div>


</div>
<div id="offcanvasOverlay" class="offcanvas-overlay"></div>





