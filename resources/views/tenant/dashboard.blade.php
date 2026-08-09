@extends('layouts.tenant')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')

<div class="row g-4">

    <div class="col-12">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>

                        <h4 class="mb-2">

                            مرحبًا،
                            {{ auth()->user()->name }}

                        </h4>

                        <div class="text-muted">

                            {{ auth()->user()->tenant->name }}

                        </div>

                    </div>


                    <span class="badge bg-success-subtle text-success px-3 py-2">

                        النظام متاح

                    </span>

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    الشركة
                </div>

                <h6 class="mb-0">

                    {{ auth()->user()->tenant->name }}

                </h6>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    المنطقة الزمنية
                </div>

                <h6 class="mb-0" dir="ltr">

                    {{ auth()->user()->tenant->timezone }}

                </h6>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="card border-0 shadow-sm rounded-4 h-100">

            <div class="card-body p-4">

                <div class="text-muted small mb-2">
                    العملة
                </div>

                <h5 class="mb-0">

                    {{ auth()->user()->tenant->currency_code }}

                </h5>

            </div>

        </div>

    </div>

</div>

@endsection