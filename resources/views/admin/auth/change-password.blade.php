@extends('layouts.admin')

@section('title', 'Đổi mật khẩu')

@section('content')
<div class="row mt-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Cập nhật mật khẩu tài khoản</h4>
                <p class="text-muted mb-0">Bạn nên sử dụng mật khẩu mạnh để bảo vệ tài khoản của mình.</p>
            </div><!--end card-header-->
            <div class="card-body">
                <form action="{{ route('admin.password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" id="email" name="email" value="{{ auth()->guard('admin')->user()->email }}">

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                        <input type="text" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" for="password">Mật khẩu mới</label>
                        <input type="text" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Nhập mật khẩu mới" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Xác nhận mật khẩu mới</label>
                        <input type="text" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Xác nhận mật khẩu mới" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="otp">Mã xác thực (OTP)</label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" placeholder="Nhập mã 6 số" maxlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" id="btnSendOtp">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                <span class="btn-text">Gửi mã qua Email</span>
                            </button>
                        </div>
                        @error('otp')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="text-end">
                        <button class="btn btn-primary px-4" type="submit">Cập nhật mật khẩu <i class="fas fa-save ms-1"></i></button>
                    </div>
                </form>
            </div><!--end card-body-->
        </div><!--end card-->
    </div><!--end col-->
</div><!--end row-->
@endsection

@push('js')
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

        // Show loading
        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.classList.add('d-none');

        fetch("{{ route('admin.password.change.send') }}", {
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
                        btnText.innerText = 'Gửi mã qua Email';
                    }
                }, 1000);
            } else {
                btn.disabled = false;
                spinner.classList.add('d-none');
                btnText.classList.remove('d-none');
                btnText.innerText = 'Gửi mã qua Email';
                showToast(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'error');
            }
        })
        .catch(error => {
            spinner.classList.add('d-none');
            btnText.classList.remove('d-none');
            btn.disabled = false;
            btnText.innerText = 'Gửi mã qua Email';
            showToast('Lỗi kết nối hệ thống.', 'error');
        });
    });
</script>
@endpush
