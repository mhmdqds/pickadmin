 <div class="custom-offcanvas-header bg--secondary d-flex justify-content-between align-items-center px-3 py-3">
            <h3 class="mb-0">{{ translate('Employee Role ') }}</h3>
                <button type="button"
                    class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary text-dark offcanvas-close fz-15px p-0"
                    aria-label="Close">&times;</button>
        </div>
        {{-- @dump($role) --}}
        <div class="custom-offcanvas-body p-20">
            <div class="bg--secondary rounded p-20 mb-20 w-100">
               <div class="fs-14">{{ translate('Role Name :') }} <strong class="text-dark">{{ $role->name }}</strong></div>
            </div>

            @php
                $permissions = json_decode($role->modules, true)??[];
            @endphp
            <h5 class="fs-16 mb-3">
                 {{ translate('Permitted Management') }} <span class="badge badge-soft-dark ml-2" id="itemCount">{{ count($permissions) }}</span>
            </h5>


            @php
            $roles=['dashboard','profile'];
             $selected_roles = array_intersect($roles, $permissions);
            @endphp


            @if (count($permissions) > 0)
                <div class="mb-20">
                    {{-- <h5 class="fs-16 mb-10px fw-medium text-dark">
                        {{ translate('General') }}
                    </h5> --}}
                    <div class="bg--secondary rounded p-20 mb-20 w-100">
                        {{-- <p class="fs-14 mb-10px"> {{ translate('Role Management') }}</p> --}}
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($permissions as $item)
                                <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                    {{ translate($item) }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif



             {{-- @php
                $roles=['cashback','vehicle_category','deliveryman','customer_management','customer_wallet','customer_loyalty_point','subscription','contact_messages','employee_role','employee'];
                $selected_roles = array_intersect($roles, $permissions);
            @endphp

            @if (count($selected_roles) > 0)

            <div class="mb-20">
                <h5 class="fs-16 mb-10px fw-medium text-dark">
                    {{ translate('User Management') }}
                </h5>
                <div class="bg--secondary rounded p-20 mb-20 w-100">
                    @php
                        $roles=['cashback'];
                        $selected_roles = array_intersect($roles, $permissions);
                    @endphp

                    @if (count($selected_roles) > 0)
                        <div class="boxs mb-20">
                            <p class="fs-14 mb-10px">{{ translate('Promotion Management') }}</p>
                            <div class="d-flex flex-wrap gap-2">

                                @foreach ($selected_roles as $item)
                                    <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                        {{ translate($item) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif


                    @php
                        $roles=['vehicle_category','deliveryman'];
                        $selected_roles = array_intersect($roles, $permissions);
                    @endphp

                    @if (count($selected_roles) > 0)
                    <div class="boxs mb-20">
                        <p class="fs-14 mb-10px">{{ translate('Delivery Management') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                             @foreach ($selected_roles as $item)
                                    <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                        {{ translate($item) }}
                                    </div>
                            @endforeach
                        </div>
                    </div>
                       @endif

                    <div class="boxs mb-20">
                        <p class="fs-14 mb-10px">Customer Management</p>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Customers
                            </div>
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Customer Wallet
                            </div>
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Customer Loyalty Point
                            </div>
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Subscribed Mail List
                            </div>
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Contact Messages
                            </div>
                        </div>
                    </div>

                    <div class="boxs mb-20">
                        <p class="fs-14 mb-10px">Employee Management</p>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Employee Role
                            </div>
                            <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                                Employees
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif --}}


            {{-- <div class="mb-20">
                <h5 class="fs-16 mb-10px fw-medium text-dark">
                     {{ translate('Transaction & Report') }}
                </h5>
                <div class="bg--secondary rounded p-20 mb-20 w-100">
                    <p class="fs-14 mb-10px"> {{ translate('Business Management') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Withdraws') }}
                        </div>
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Disbursement') }}
                        </div>
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Collect Cash') }}
                        </div>
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Deliveryman Payments') }}
                        </div>
                    </div>
                </div>
                <div class="bg--secondary rounded p-20 mb-20 w-100">
                    <p class="fs-14 mb-10px"> {{ translate('Delivery Management') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Vehicle Category') }}
                        </div>
                        <div class="d-inline bg-white rounded-pill py-1 px-10px text-center fs-14 text-dark">
                             {{ translate('Deliveryman Manage') }}
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>
        <div class="offcanvas-footer p-3 d-flex align-items-center justify-content-center gap-3">
            <button type="reset" class="btn w-100 offcanvas-close btn--reset">{{ translate('messages.Cancel') }}</button>
            <a type="button" href="{{route('admin.users.custom-role.edit',[$role['id']])}}"  class="btn w-100 btn--primary">{{ translate('messages.Edit Details') }}</a>
        </div>

