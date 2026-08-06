@extends('layouts.system')

@section('title', 'خصائص الباقة')
@section('page-title', 'خصائص الباقة')

@section('content')

<div class="container-fluid p-0">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                {{ $plan->name }}
            </h4>

            <div class="text-muted">
                تحديد الوحدات والخصائص المتاحة في الباقة
            </div>
        </div>

        <a href="{{ route('system.plans.index') }}"
           class="btn btn-light border">

            رجوع للباقات

        </a>

    </div>


    <form id="featuresForm">

        <div class="card border-0 shadow-sm rounded-4 mb-4">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <strong>خصائص الباقة</strong>

                        <div class="text-muted small mt-1">
                            اختر الخصائص التي يستطيع مشتركو هذه الباقة استخدامها.
                        </div>
                    </div>

                    <div class="d-flex gap-2">

                        <button type="button"
                                id="selectAll"
                                class="btn btn-sm btn-light border">

                            تحديد الكل

                        </button>

                        <button type="button"
                                id="clearAll"
                                class="btn btn-sm btn-light border">

                            إلغاء الكل

                        </button>

                    </div>

                </div>

            </div>

        </div>


        <div class="row g-4">

            @php
                $moduleNames = [
                    'hr' => 'الموارد البشرية',
                    'organization' => 'الهيكل التنظيمي',
                    'attendance' => 'الحضور والانصراف',
                    'leave' => 'الإجازات',
                    'payroll' => 'الرواتب',
                    'recruitment' => 'التوظيف',
                    'performance' => 'الأداء',
                    'training' => 'التدريب',
                    'self_service' => 'الخدمة الذاتية',
                    'workflow' => 'الموافقات',
                    'reports' => 'التقارير',
                    'integration' => 'التكامل والـ API',
                    'audit' => 'التدقيق',
                ];
            @endphp


            @foreach($features as $module => $moduleFeatures)

                <div class="col-lg-6">

                    <div class="card border-0 shadow-sm rounded-4 h-100">

                        <div class="card-header bg-white py-3">

                            <div class="d-flex justify-content-between">

                                <strong>
                                    {{ $moduleNames[$module] ?? $module }}
                                </strong>

                                <button type="button"
                                        class="btn btn-sm btn-link module-toggle"
                                        data-module="{{ $module }}">

                                    تحديد المجموعة

                                </button>

                            </div>

                        </div>


                        <div class="card-body">

                            @foreach($moduleFeatures as $feature)

                                <div class="form-check mb-3">

                                    <input class="form-check-input feature-checkbox module-{{ $module }}"
                                           type="checkbox"
                                           name="features[]"
                                           value="{{ $feature->id }}"
                                           id="feature_{{ $feature->id }}"
                                           @checked(
                                               $selectedFeatures->contains(
                                                   $feature->id
                                               )
                                           )>

                                    <label class="form-check-label"
                                           for="feature_{{ $feature->id }}">

                                        <strong>
                                            {{ $feature->name }}
                                        </strong>

                                        <div class="text-muted small"
                                             dir="ltr">

                                            {{ $feature->code }}

                                        </div>

                                    </label>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endforeach

        </div>


        <div class="card border-0 shadow-sm rounded-4 mt-4">

            <div class="card-body d-flex justify-content-end">

                <button type="submit"
                        class="btn btn-primary px-5"
                        id="btnSaveFeatures">

                    حفظ خصائص الباقة

                </button>

            </div>

        </div>

    </form>

</div>

@endsection


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const $ = window.jQuery;
    const Swal = window.Swal;


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content'),

            'Accept': 'application/json'
        }
    });


    $('#selectAll').on('click', function () {

        $('.feature-checkbox')
            .prop('checked', true);

    });


    $('#clearAll').on('click', function () {

        $('.feature-checkbox')
            .prop('checked', false);

    });


    $('.module-toggle').on('click', function () {

        const module = $(this).data('module');

        const checkboxes =
            $('.module-' + module);

        const allChecked =
            checkboxes.length ===
            checkboxes.filter(':checked').length;

        checkboxes.prop(
            'checked',
            !allChecked
        );

    });


    $('#featuresForm').on('submit', function (event) {

        event.preventDefault();

        const button =
            $('#btnSaveFeatures');

        button
            .prop('disabled', true)
            .text('جاري الحفظ...');


        $.ajax({

            url: @json(
                route(
                    'system.plans.features.update',
                    $plan
                )
            ),

            type: 'PUT',

            data: $(this).serialize(),

            success: function (response) {

                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: response.message,
                    timer: 1600,
                    showConfirmButton: false
                });

            },

            error: function (xhr) {

                Swal.fire({
                    icon: 'error',
                    title: 'تعذر الحفظ',
                    text:
                        xhr.responseJSON?.message
                        ?? 'حدث خطأ غير متوقع.'
                });

            },

            complete: function () {

                button
                    .prop('disabled', false)
                    .text('حفظ خصائص الباقة');

            }

        });

    });

});

</script>

@endpush