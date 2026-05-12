<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Admin Login - ChatApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('rizz/default/assets/images/favicon.ico') }}">

    <!-- App css -->
    <link href="{{ asset('rizz/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('rizz/default/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('rizz/default/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
</head>

<body>
<div class="container-xxl">
    <div class="row vh-100 d-flex justify-content-center">
        <div class="col-12 align-self-center">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 mx-auto">
                        <div class="card">
                            <div class="card-body p-0 bg-black auth-header-box rounded-top">
                                <div class="text-center p-3">
                                    <a href="#" class="logo logo-admin">
                                        <img src="{{ asset('rizz/default/assets/images/logo-sm.png') }}" height="50" alt="logo" class="auth-logo">
                                    </a>
                                    <h4 class="mt-3 mb-1 fw-semibold text-white fs-18">Đăng nhập Quản trị</h4>   
                                    <p class="text-muted fw-medium mb-0">Hệ thống quản trị Chat App</p>  
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <!-- Toast container sẽ hiển thị ở cuối body -->

                                <form class="my-4" action="{{ route('admin.login') }}" method="POST">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label class="form-label" for="login">Email hoặc Tên đăng nhập</label>
                                        <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" placeholder="Nhập email hoặc tên đăng nhập" required>
                                        @error('login')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="userpassword">Mật khẩu</label>                                            
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="userpassword" placeholder="Nhập mật khẩu" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group row mt-3">
                                        <div class="col-sm-6">
                                            <div class="form-check form-switch form-switch-success">
                                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                                <label class="form-check-label" for="remember">Ghi nhớ tôi</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <a href="{{ route('admin.password.forgot') }}" class="text-muted font-13"><i class="dripicons-lock"></i> Quên mật khẩu?</a>                                    
                                        </div>
                                    </div>

                                    <div class="form-group mb-0 row">
                                        <div class="col-12">
                                            <div class="d-grid mt-3">
                                                <button class="btn btn-primary" type="submit">Đăng nhập <i class="fas fa-sign-in-alt ms-1"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <div class="text-center mb-2">
                                    <p class="text-muted">Chưa có tài khoản? <a href="{{ route('admin.register') }}" class="text-primary ms-2">Đăng ký ngay</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('rizz/default/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tự động ẩn Toast sau 5 giây
        var toastElList = [].slice.call(document.querySelectorAll('.toast.show'))
        var toastList = toastElList.map(function (toastEl) {
            setTimeout(function() {
                var toast = bootstrap.Toast.getInstance(toastEl);
                if (!toast) {
                    toast = new bootstrap.Toast(toastEl);
                }
                toast.hide();
            }, 5000);
        });
    });
</script>

<!-- Global Toasts -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    @if (session('success'))
        <div class="toast show align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="toast show align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endif
</div>
</body>
</html>
