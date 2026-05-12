<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="light" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Admin - @yield('title', 'Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('rizz/default/assets/images/favicon.ico') }}">

    @stack('css')
    <!-- App css -->
    <link href="{{ asset('rizz/default/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('rizz/default/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('rizz/default/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

</head>

<body>
    <!-- Top Bar Start -->
    <div class="topbar d-print-none">
        <div class="container-xxl">
            <nav class="topbar-custom d-flex justify-content-between" id="topbar-custom">    
                <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">                        
                    <li>
                        <button class="nav-link mobile-menu-btn nav-icon" id="togglemenu">
                            <i class="iconoir-menu-scale"></i>
                        </button>
                    </li> 
                </ul>
                <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">
                    <li class="dropdown topbar-item">
                        @php
                            $currentAdmin = auth()->guard('admin')->user();
                        @endphp
                        <a class="nav-link dropdown-toggle arrow-none px-0" data-bs-toggle="dropdown" href="#" role="button"
                            aria-haspopup="false" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <img src="{{ $currentAdmin->avatar ?? asset('default_avatar.jpg') }}" alt="" class="thumb-lg rounded-circle">
                                <div class="ms-2 text-start d-none d-lg-block">
                                    <h6 class="my-0 fw-semibold text-dark fs-13">{{ $currentAdmin->name ?? 'Admin' }}</h6>
                                    <small class="text-muted mb-0">Quản trị viên</small>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end py-0">
                            <div class="d-flex align-items-center dropdown-item py-2 bg-secondary-subtle">
                                <div class="flex-shrink-0">
                                    <img src="{{ $currentAdmin->avatar ?? asset('default_avatar.jpg') }}" alt="" class="thumb-md rounded-circle">
                                </div>
                                <div class="flex-grow-1 ms-2 text-truncate align-self-center">
                                    <h6 class="my-0 fw-medium text-dark fs-13">{{ $currentAdmin->name ?? 'Admin' }}</h6>
                                    <small class="text-muted mb-0">Quản trị viên</small>
                                </div>
                            </div>
                            <div class="dropdown-divider mt-0"></div>
                            <small class="text-muted px-2 pb-1 d-block">Tài khoản</small>
                            <a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="las la-user fs-18 me-1 align-text-bottom"></i> Hồ sơ</a>
                            <a class="dropdown-item" href="{{ route('admin.password.change') }}"><i class="las la-key fs-18 me-1 align-text-bottom"></i> Đổi mật khẩu</a>
                            <div class="dropdown-divider mb-0"></div>
                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="las la-power-off fs-18 me-1 align-text-bottom"></i> Đăng xuất
                            </a>
                            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <!-- Top Bar End -->

    <!-- leftbar-tab-menu -->
    <div class="startbar d-print-none">
        <!--start brand-->
        <div class="brand">
            <a href="{{ route('admin.dashboard') }}" class="logo">
                <span>
                    <img src="{{ asset('rizz/default/assets/images/logo-sm.png') }}" alt="logo-small" class="logo-sm">
                </span>
                <span class="">
                    <img src="{{ asset('rizz/default/assets/images/logo-light.png') }}" alt="logo-large" class="logo-lg logo-light">
                    <img src="{{ asset('rizz/default/assets/images/logo-dark.png') }}" alt="logo-large" class="logo-lg logo-dark">
                </span>
            </a>
        </div>
        <!--end brand-->
        <!--start startbar-menu-->
        <div class="startbar-menu" >
            <div class="startbar-collapse" id="startbarCollapse" data-simplebar>
                <div class="d-flex align-items-start flex-column w-100">
                    <!-- Navigation -->
                    <ul class="navbar-nav mb-auto w-100">
                        <li class="menu-label pt-0 mt-0">
                            <span>Menu chính</span>
                        </li>
                        @if($currentAdmin->hasPermission('users.view'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.users.index') }}">
                                <i class="iconoir-user menu-icon"></i>
                                <span>Quản lý người dùng</span>
                            </a>
                        </li>
                        @endif

                        @if($currentAdmin->hasPermission('admins.view'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.admins.index') }}">
                                <i class="iconoir-user-badge-check menu-icon"></i>
                                <span>Quản lý Admin</span>
                            </a>
                        </li>
                        @endif

                        @if($currentAdmin->hasPermission('roles.view'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.roles.index') }}">
                                <i class="iconoir-privacy-policy menu-icon"></i>
                                <span>Quản lý Vai trò</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>    
    </div>
    <div class="startbar-overlay d-print-none"></div>
    <!-- end leftbar-tab-menu-->


    <div class="page-wrapper">
        <!-- Page Content-->
        <div class="page-content">
            <div class="container-xxl">
                @yield('content')
            </div><!-- container -->

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

            <!--Start Footer-->
            <footer class="footer text-center text-sm-start d-print-none">
                <div class="container-xxl">
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-0 rounded-bottom-0">
                                <div class="card-body">
                                    <p class="text-muted mb-0">
                                        © <script> document.write(new Date().getFullYear()) </script> Chat App Admin. All rights reserved.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!--end footer-->
        </div>
        <!-- end page content -->
    </div>
    <!-- end page-wrapper -->

    <!-- Javascript  -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('rizz/default/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('rizz/default/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('rizz/default/assets/js/app.js') }}"></script>
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

        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-container');
            if (!container) return;

            const bgClass = type === 'success' ? 'bg-primary' : 'bg-danger';
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            
            const toastHtml = `
                <div class="toast show align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="${icon} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-auto me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            const div = document.createElement('div');
            div.innerHTML = toastHtml.trim();
            const toastElement = div.firstChild;
            container.appendChild(toastElement);
            
            setTimeout(() => {
                const toast = new bootstrap.Toast(toastElement);
                toast.hide();
                setTimeout(() => toastElement.remove(), 700);
            }, 4000);
        }
    </script>
    @stack('js')
</body>
</html>
