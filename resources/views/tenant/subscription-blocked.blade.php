<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>الاشتراك | رؤية يوم</title>

    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js'
    ])

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm">

                <div class="card-body text-center p-5">

                    <h3 class="mb-3">
                        الاشتراك غير متاح
                    </h3>

                    <p class="text-muted">
                        {{ $message }}
                    </p>

                    <div class="mb-4">
                        {{ $tenant->name }}
                    </div>


                    <form method="POST"
                          action="{{ route('app.logout') }}">

                        @csrf

                        <button class="btn btn-outline-danger">
                            تسجيل الخروج
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>