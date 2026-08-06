<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}">

    <title>تسجيل الدخول | رؤية يوم</title>

    @vite([
        'resources/sass/app.scss',
        'resources/js/app.js'
    ])

    <style>
        body {
            min-height: 100vh;
            background:
                linear-gradient(
                    135deg,
                    #071633 0%,
                    #0d2452 100%
                );
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            border: 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .brand-mark {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
            border-radius: 16px;
            background: #2f6bff;
            color: white;
            font-size: 26px;
            font-weight: 700;
        }
    </style>

</head>

<body>

<div class="login-wrapper">

    <div class="card login-card shadow-lg">

        <div class="card-body p-4 p-md-5">

            <div class="text-center mb-4">

                <div class="brand-mark mb-3">
                    ر
                </div>

                <h3 class="fw-bold mb-1">
                    رؤية يوم
                </h3>

                <div class="text-muted">
                    إدارة النظام
                </div>

            </div>


            @if($errors->any())

                <div class="alert alert-danger">

                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('system.login.submit') }}">

                @csrf


                <div class="mb-3">

                    <label class="form-label">
                        البريد الإلكتروني
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control form-control-lg"
                        value="{{ old('email') }}"
                        dir="ltr"
                        autocomplete="email"
                        autofocus
                        required>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        كلمة المرور
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control form-control-lg"
                        dir="ltr"
                        autocomplete="current-password"
                        required>

                </div>


                <div class="form-check mb-4">

                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        id="remember"
                        class="form-check-input">

                    <label
                        for="remember"
                        class="form-check-label">
                        تذكرني
                    </label>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary btn-lg w-100">
                    تسجيل الدخول
                </button>

            </form>

        </div>

    </div>

</div>

</body>

</html>