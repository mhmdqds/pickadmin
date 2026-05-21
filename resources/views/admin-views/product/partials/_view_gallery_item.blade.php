<div>
        <div class="custom-offcanvas-header bg--secondary d-flex justify-content-between align-items-center px-3 py-3">
            <h3 class="mb-0">{{ translate('Product Details') }}</h3>
            <button type="button"
                class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary text-dark offcanvas-close fz-15px p-0"
                aria-label="Close">
                &times;
            </button>
        </div>
        <div class="custom-offcanvas-body p-20 pb-5">
            <div class="d-flex flex-column gap-20px">
                <div>
                    <div class="d-flex gap-10px">
                        <div class="border minmax-xl-130px rounded overflow-hidden">
                            <img class="onerror-image w-100 h-100 object-cover"
                                src="{{ $item['image_full_url'] ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                alt="Image Description">
                        </div>
                        <div class="cont overflow-hidden py-0">
                            <h4 class="m-0 fs-16 fw-semibold title-clr line--limit-2" data-toggle="tooltip"
                                data-placement="bottom"
                                data-original-title="{{ $item?->getRawOriginal('name') }}">
                                {{ $item?->getRawOriginal('name') }} </h4>
                            <div class="mt-10px d-flex gap-10px flex-wrap">
                                @if ($item->organic == 1)
                                <div class="badge badge-success font-weight-normal px-2 fs-10 rounded-pill">
                                    {{ translate('messages.Organic') }}</div>

                                @endif
                                @if ($item->is_halal == 1)
                                <div class="badge badge-warning font-weight-normal px-2 fs-10 rounded-pill text-white">
                                    {{ translate('messages.Halal') }}</div>
                                @endif
                            </div>
                            <div class="mt-10px">
                                <div class="tabs-slide-wrap tabs-slide-wrap-pdetails position-relative">
                                    <div class="tabs-inner d-flex align-items-center gap-xxl-20 gap-2">



                                        @foreach($item->images as $key => $img)
                                            @php
                                            $photo = is_array($img) ? $img : ['img' => $img, 'storage' => 'public'];
                                            @endphp

                                            <div class="tabs-slide_items">
                                                <div class="product-d-thumb aspect-ratio-1 overflow-hidden rounded border">
                                                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('product', $photo['img'] ?? '', $photo['storage']) }}"
                                                        alt="img" class="w-100 h-100 object-cover">
                                                </div>
                                            </div>
                                            @endforeach

                                    </div>
                                    <div class="arrow-area">
                                        <div class="button-prev align-items-center">
                                            <button type="button"
                                                class="btn btn-click-prev mr-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                <i class="tio-chevron-left fs-24"></i>
                                            </button>
                                        </div>
                                        <div class="button-next align-items-center">
                                            <button type="button"
                                                class="btn btn-click-next ml-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                <i class="tio-chevron-right fs-24"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-20 bg--secondary p-10px rounded">
                        <P class="m-0 fs-12 see-more_pragraph" data-character="280">
                            {{ $item?->getRawOriginal('description') }}
                            <span class="text-info see__moreBtn d-none text-underline cursor-pointer">{{ translate('See more') }}</span>
                        </P>
                    </div>
                </div>
                <div class="bg--secondary rounded p-10px h-100">
                    <h6 class="mb-0 text-capitalize fs-14 fw-semibold">{{ translate('General_Information') }}</h6>
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
                                <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                    {{ translate('messages.Is_Organic') }}</div>
                                <span class="title-clr">:</span>
                                <strong class="fw-medium title-clr">
                                    {{ $item->organic == 1 ? translate('messages.yes') : translate('messages.no') }}</strong>
                            </span>
                        @endif
                        @if ($item->module->module_type == 'food')
                            <span class="d-flex mb-2 gap-1 fs-12">
                                <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                    {{ translate('messages.Item_type') }} </div>
                                <span class="title-clr">:</span>
                                <strong class="fw-medium title-clr">
                                    {{ $item->veg == 1 ? translate('messages.veg') : translate('messages.non_veg') }}</strong>
                            </span>
                        @else
                            @if ($item?->unit)
                                <span class="d-flex mb-2 gap-1 fs-12">
                                    <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                        {{ translate('messages.Unit') }} </div>
                                    <span class="title-clr">:</span>
                                    <strong class="fw-medium title-clr"> {{ $item?->unit?->unit }}</strong>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="bg--secondary rounded p-10px h-100">
                    <div>
                        <h6 class="mb-1 text-capitalize fs-14 fw-semibold">{{ translate('Price Information') }}</h6>

                                <span class="d-flex mb-2 gap-1 fs-12">
                                    <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                        {{ translate('Price') }} </div>
                                    <span class="title-clr">:</span>
                                    <strong class="fw-medium title-clr"> {{\App\CentralLogics\Helpers::format_currency($item?->price)   }}</strong>
                                </span>
                                <span class="d-flex mb-2 gap-1 fs-12">
                                    <div class="w-100px max-w-100px text-nowrap line--limit-1 text-title fs-12">
                                        {{ translate('Discount') }} </div>
                                    <span class="title-clr">:</span>
                                    <strong class="fw-medium title-clr"> {{$item->discount_type == 'percent' ? $item->discount . ' %' : \App\CentralLogics\Helpers::format_currency($item->discount) }}</strong>
                                </span>
                    </div>
                </div>

                 @php
                    $has_variation = ($item->module->module_type == 'food' && count(json_decode($item->food_variations, true)??[]) > 0  ) || ($item->module->module_type != 'food' &&  count(json_decode($item->variations, true)??[]) > 0 )
                @endphp

                    @if ($has_variation)
                    <div class="bg--secondary rounded p-10px h-100">
                        <h6 class="mb-0 text-capitalize fs-14 fw-semibold">{{ translate('Available_Variations') }}</h6>
                        <div class="product-gallery-info mt-10px">
                            <div class="d-flex flex-wrap gap-10px">

                                    @if ($item->module->module_type == 'food')
                                                @foreach (json_decode($item->food_variations, true) as $key => $variation)
                                                    @if (isset($variation['values']))
                                                        @foreach ($variation['values'] as $value)
                                                         <span class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                                            {{ $variation['name'] }} - {{ $value['label'] }} </span>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                    @else
                                                @if ($item->variations && is_array(json_decode($item['variations'], true)))
                                                    @foreach (json_decode($item['variations'], true) as $key => $variation)
                                                        <span class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                                            {{ $variation['type'] }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                    @endif
                            </div>
                        </div>
                    </div>
                    @endif


                    @if (count($item->tags ?? []) > 0)

                    <div class="bg--secondary rounded p-10px">
                        <h6 class="mb-0 text-capitalize fs-14 fw-semibold">{{ translate('Tags') }}</h6>
                        <div class="d-flex gap-10px align-items-center flex-wrap mt-10px">

                            @foreach ($item->tags as $key => $c)
                            <span class="bg-white rounded-pill py-1 px-2 fs-12 title-clr">
                                {{ $c->tag }}
                            </span>

                            @endforeach

                        </div>
                    </div>
                    @endif


            </div>
        </div>
        <div class="offcanvas-footer p-3 d-flex align-items-center justify-content-center gap-3">
            <button type="reset" class="btn w-100 btn--reset offcanvas-close">{{ translate('messages.Cancel') }}</button>

            <a target="_blank"  href="{{ Auth::guard('admin')->check() ? route('admin.item.edit', ['id' => $item->id, 'product_gellary' => true]) :route('vendor.item.edit', ['id' => $item->id, 'product_gellary' => true]) }}"
                 class="btn w-100 btn--primary offcanvas-close">  {{ translate('messages.use_this_product_info') }} </a>
        </div>
    </div>
