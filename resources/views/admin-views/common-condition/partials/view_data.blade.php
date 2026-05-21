 <div>
     <div class="custom-offcanvas-header bg--secondary d-flex justify-content-between align-items-center px-3 py-3">
         <h3 class="mb-0">{{ translate('Common Conditions') }}</h3>
         <button type="button"
             class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary text-dark offcanvas-close fz-15px p-0"
             aria-label="Close">
             &times;
         </button>
     </div>
     <div class="custom-offcanvas-body p-20">
         <div class="bg--secondary rounded p-20 mb-20 w-100">
             <div class="d-flex align-items-center gap-2 justify-content-between mb-12px">
                 <h3 class="fs-18 mb-0 max-w-250 line--limit-2">
                     {{ $condition->name }}
                 </h3>
                 <div class="d-flex gap-10px">
                     <a class="btn action-btn border w-40px h-40px bg-white form-alert"
                         data-id="condition-{{ $condition['id'] }}" data-toggle="modal"
                         data-target="#confirmation-deletes-{{ $condition['id'] }}"
                         data-id="condition-{{ $condition['id'] }}"
                         data-message="{{ translate('messages.Want to delete this condition') }}" href="javascript:">
                         <i class="tio-delete-outlined text-danger"></i>
                     </a>

                     <div class="modal shedule-modal fade" id="confirmation-deletes-{{ $condition['id'] }}"
                         tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                         <div class="modal-dialog modal-dialog-centered">
                             <form action="{{ route('admin.common-condition.delete', [$condition['id']]) }}"
                                 method="post" id="condition-{{ $condition['id'] }}">
                                 @csrf @method('delete')
                                 <div class="modal-content pb-2 max-w-500">
                                     <div class="modal-header">
                                         <button type="button"
                                             class="close bg-modal-btn w-30px h-30 rounded-circle position-absolute right-0 top-0 m-2 z-2"
                                             data-dismiss="modal" aria-label="Close">
                                             <span aria-hidden="true">&times;</span>
                                         </button>
                                     </div>
                                     <div class="modal-body">
                                         <div class="text-center">
                                             <img src="{{ asset('public/assets/admin/img/delete.png') }}" alt="icon"
                                                 class="mb-20">
                                             <h3 class="mb-2 fs-18">
                                                 {{ translate('Want to delete this common condition?') }}</h3>


                                         </div>
                                     </div>
                                     <div class="modal-footer justify-content-center border-0 pt-0 mb-1 gap-2">
                                         <button type="submit"
                                             class="btn min-w-120px btn-danger min-h-45px">{{ translate('messages.Yes, Delete') }}</button>
                                         <button type="button" class="btn min-w-120px btn--reset min-h-45px"
                                             data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                                     </div>
                                 </div>
                             </form>
                         </div>
                     </div>


                     <div class="border px-10px py-1 bg-white h-40px rounded gap-10px d-flex align-items-center">
                         <div class="fs-14 lh-1 title-clr lh--1 d-sm-inline-block d-none">{{ translate('Status') }}
                         </div>

                         <label class="toggle-switch toggle-switch-sm mb-0" for="status_onoff">
                             <input type="checkbox" class="toggle-switch-input redirect-url"
                              data-url="{{route('admin.common-condition.status',[$condition['id'],$condition->status?0:1])}}"

                             {{$condition->status?'checked':''}} id="status_onoff">
                             <span class="toggle-switch-label mx-auto">
                                 <span class="toggle-switch-indicator"></span>
                             </span>
                         </label>
                     </div>
                 </div>
             </div>
             <div class="d-flex flex-wrap gap-xxl-20 gap-2">
                 <p class="mb-0 fs-12">{{ translate('Created Date :') }} <strong
                         class="text-dark">{{ \App\CentralLogics\Helpers::date_format($condition->created_at) }}</strong>
                 </p>
                 <div class="border d-xl-inline-block d-none lh--1 border-end"></div>
                 <p class="mb-0 fs-12">{{ translate('Last Modified Date :') }} <strong
                         class="text-dark">{{ \App\CentralLogics\Helpers::date_format($condition->updated_at) }}</strong>
                 </p>
             </div>
         </div>
         <h5 class="fs-16 mb-20">
             {{ translate('Product List') }} <span class="badge badge-soft-dark ml-2"
                 id="itemCount">{{ $items->count() }}</span>
         </h5>

         <div class="d-flex flex-column gap-10px">


             @forelse ($items as $item)
                 <a href="{{ route('admin.item.view', [$item['id']]) }}" class="d-flex gap-10px align-items-center">
                     <div class="w-60px min-w-60px h-60px rounded overflow-hidden border">
                         <img src="{{ $item->image_full_url }}" alt="public">
                     </div>
                     <div class="info">
                         <div class="fs-14 title-clr fw-semibold mb-1 line--limit-1">
                             {{ $item->name }}
                         </div>
                         <div class="fs-14 title-clr">
                             {{ \App\CentralLogics\Helpers::format_currency($item?->price) }}
                         </div>
                     </div>
                 </a>
                 <div class="border-bottom"></div>
             @empty


                 <div class="empty--data">
                     <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                     <h5>
                         {{ translate('no_data_found') }}
                     </h5>
                 </div>
             @endforelse


         </div>
     </div>
     <div class="offcanvas-footer p-3 d-flex align-items-center justify-content-center gap-3">
         <button type="reset"
             class="btn w-100 btn--reset offcanvas-close">{{ translate('messages.Cancel') }}</button>
         <a href="{{ route('admin.common-condition.edit', $condition->id) }}" type="button"
             class="btn w-100 btn--primary">{{ translate('messages.Edit') }}</a>
     </div>
 </div>
