<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Khôi phục mật khẩu OTP - ChatApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
                    <div class="col-lg-5 col-xl-4 mx-auto">
                        <div class="card">
                            <div class="card-body p-0 bg-black auth-header-box rounded-top">
                                <div class="text-center p-3">
                                    <a href="#" class="logo logo-admin">
                                        <img src="{{ asset('rizz/default/assets/images/logo-sm.png') }}" height="50" alt="logo" class="auth-logo">
                                    </a>
                                    <h4 class="mt-3 mb-1 fw-semibold text-white fs-18">Khôi phục bằng OTP</h4>   
                                    <p class="text-muted fw-medium mb-0">Nhập thông tin để đặt lại mật khẩu</p>  
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <!-- Thông báo Toast sẽ hiển thị ở cuối body -->

                                <form class="my-4" action="{{ route('admin.password.forgot.reset') }}" method="POST">
                                    @csrf
                                    <!-- Phần nhập Email -->
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="email">Email quản trị</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', session('otp_email')) }}" placeholder="Nhập email" required>
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Mật khẩu mới -->
                                    <div class="form-group mb-2">
                                        <label class="form-label" for="password">Mật khẩu mới</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Tối thiểu 8 ký tự" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Xác nhận mật khẩu -->
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                                        <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Nhập lại mật khẩu mới" required>
                                    </div>

                                    <!-- Ô nhập mã OTP và Gửi mã -->
                                    <div class="form-group mb-3">
                                        <label class="form-label" for="otp">Mã xác thực (OTP)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" placeholder="Nhập mã 6 số" maxlength="6" required>
                                            <button class="btn btn-secondary" type="button" id="btnSendOtp">
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                                <span class="btn-text">Gửi mã</span>
                                            </button>
                                        </div>
                                        @error('otp')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-0 row">
                                        <div class="col-12">
                                            <div class="d-grid mt-3">
                                                <button class="btn btn-primary" type="submit">Đặt lại mật khẩu <i class="fas fa-sync-alt ms-1"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Form ẩn để gửi yêu cầu OTP -->
                                <form id="formSendOtp" action="{{ route('admin.password.forgot.send') }}" method="POST" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="email" id="hiddenEmail">
                                </form>

                                <div class="text-center mb-2">
                                    <p class="text-muted">Quay lại trang <a href="{{ route('admin.login') }}" class="text-primary ms-2">Đăng nhập</a></p>
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
    function showToast(message, type = 'success') {
        const container = document.querySelector('.toast-container');
        const bgClass = type === 'success' ? 'bg-primary' : 'bg-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const toastHtml = `
            <div class="toast show align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${iconClass} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        const div = document.createElement('div');
        div.innerHTML = toastHtml.trim();
        const toastEl = div.firstChild;
        container.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl);
        setTimeout(() => toast.hide(), 5000);
    }

    document.getElementById('btnSendOtp').addEventListener('click', function() {
        const btn = this;
        const spinner = btn.querySelector('.spinner-border');
        const btnText = btn.querySelector('.btn-text');
        const email = document.getElementById('email').value;

        if (!email) {
            showToast('Vui lòng nhập email trước khi gửi mã!', 'error');
            return;
        }

        // Show loading
        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.classList.add('d-none');

        fetch("{{ route('admin.password.forgot.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            spinner.classList.add('d-none');
            btnText.classList.remove('d-none');
            if (data.success) {
                showToast(data.success, 'success');
                // Start countdown
                let seconds = 60;
                btnText.innerText = `Gửi lại (${seconds}s)`;
                const interval = setInterval(() => {
                    seconds--;
                    btnText.innerText = `Gửi lại (${seconds}s)`;
                    if (seconds <= 0) {
                        clearInterval(interval);
                        btn.disabled = false;
                        btnText.innerText = 'Gửi mã';
                    }
                }, 1000);
            } else {
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.classList.remove('d-none');
                btnText.innerText = 'Gửi mã';
                showToast(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
            }
        })
        .catch(error => {
            spinner.classList.add('d-none');
            btnText.classList.remove('d-none');
            btn.disabled = false;
            btnText.innerText = 'Gửi mã';
            showToast('Lỗi kết nối hệ thống.', 'error');
        });
    });

    // Toast auto-hide logic
    document.addEventListener('DOMContentLoaded', function () {
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
                    {!! session('success') !!}
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
