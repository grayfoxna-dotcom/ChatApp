@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12 col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">                      
                        <h4 class="card-title">Tổng quan hệ thống</h4>                      
                    </div><!--end col-->
                </div>  <!--end row-->                                  
            </div><!--end card-header-->
            <div class="card-body pt-0">
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-none border mb-3 mb-lg-0">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <p class="text-muted mb-1 fw-semibold">Người dùng</p>
                                        <h4 class="m-0 fw-bold">{{ \App\Models\User::count() }}</h4>
                                    </div>
                                    <div class="col-auto">
                                        <i class="iconoir-user fs-24 text-primary"></i>
                                    </div>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div><!--end col-->
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-none border mb-3 mb-lg-0">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <p class="text-muted mb-1 fw-semibold">Admin</p>
                                        <h4 class="m-0 fw-bold">{{ \App\Models\Admin::count() }}</h4>
                                    </div>
                                    <div class="col-auto">
                                        <i class="iconoir-user-badge-check fs-24 text-success"></i>
                                    </div>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div><!--end col-->
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-none border mb-3 mb-lg-0">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <p class="text-muted mb-1 fw-semibold">Vai trò</p>
                                        <h4 class="m-0 fw-bold">{{ \App\Models\Role::count() }}</h4>
                                    </div>
                                    <div class="col-auto">
                                        <i class="iconoir-privacy-policy fs-24 text-warning"></i>
                                    </div>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div><!--end col-->
                    <div class="col-md-6 col-lg-3">
                        <div class="card shadow-none border mb-3 mb-lg-0">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <p class="text-muted mb-1 fw-semibold">Trực tuyến</p>
                                        <h4 class="m-0 fw-bold">1</h4>
                                    </div>
                                    <div class="col-auto">
                                        <i class="iconoir-electronics-chip fs-24 text-info"></i>
                                    </div>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end card-body-->
        </div><!--end card-->
    </div> <!--end col-->                               
</div><!--end row-->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Chào mừng quay trở lại!</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Đây là bảng điều khiển quản trị của bạn. Bạn có thể quản lý người dùng, phân quyền và các cài đặt hệ thống tại đây.</p>
                <div class="alert alert-info border-0" role="alert">
                    <strong>Thông báo:</strong> Hệ thống phân quyền đã được kích hoạt thành công.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
