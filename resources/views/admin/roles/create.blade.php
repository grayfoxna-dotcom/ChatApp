@extends('layouts.admin')

@section('title', 'Thêm vai trò mới')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">                      
                        <h4 class="card-title">Thêm vai trò mới</h4>                      
                    </div><!--end col-->
                    <div class="col-auto"> 
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Quay lại</a>           
                    </div><!--end col-->
                </div>  <!--end row-->                                  
            </div><!--end card-header-->
            <div class="card-body pt-0">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="mb-3">
                                <label for="display_name" class="form-label">Tên vai trò (Hiển thị)</label>
                                <input type="text" class="form-control @error('display_name') is-invalid @enderror" id="display_name" name="display_name" placeholder="Ví dụ: Quản lý kỹ thuật" value="{{ old('display_name') }}" required>
                                @error('display_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Mã vai trò (Name - viết liền không dấu)</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Ví dụ: manager_tech" value="{{ old('name') }}" required>
                                <small class="text-muted">Dùng để kiểm tra quyền trong code (ví dụ: super_admin, editor...)</small>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <h5 class="mt-3 mb-3 border-bottom pb-2">Thiết lập Quyền hạn</h5>
                            <div class="row">
                                @foreach($permissions as $group => $perms)
                                <div class="col-md-4 mb-4">
                                    <div class="card shadow-none border h-100">
                                        <div class="card-header bg-light">
                                            <h6 class="m-0">{{ $group }}</h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($perms as $perm)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}">
                                                <label class="form-check-label" for="perm_{{ $perm->id }}">
                                                    {{ $perm->display_name }}
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @error('permissions') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4">Lưu vai trò</button>
                        </div>
                    </div>
                </form>
            </div><!--end card-body-->
        </div><!--end card-->
    </div> <!--end col-->                               
</div><!--end row-->
@endsection
