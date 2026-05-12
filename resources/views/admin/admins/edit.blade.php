@extends('layouts.admin')

@section('title', 'Chỉnh sửa Admin')

@push('css')
<link href="{{ asset('rizz/default/assets/libs/cropperjs/cropper.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .img-container img {
        max-width: 100%;
    }
    #avatar-preview {
        cursor: pointer;
        transition: opacity 0.2s;
    }
    #avatar-preview:hover {
        opacity: 0.8;
    }
    .cropper-view-box,
    .cropper-face {
        border-radius: 50%;
    }
    #cropModal {
        z-index: 1200;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">                      
                        <h4 class="card-title">Chỉnh sửa Admin: {{ $admin->name }}</h4>                      
                    </div><!--end col-->
                    <div class="col-auto"> 
                        <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Quay lại</a>           
                    </div><!--end col-->
                </div>  <!--end row-->                                  
            </div><!--end card-header-->
            <div class="card-body pt-0">
                <form id="editAdminForm" action="{{ route('admin.admins.update', $admin->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-lg-8 border-end">
                            <div class="p-3">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Tên Admin</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $admin->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email (Không thể thay đổi)</label>
                                    <input type="email" class="form-control bg-light" id="email" name="email" value="{{ old('email', $admin->email) }}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Vai trò</label>
                                    <div class="d-flex flex-wrap gap-3 p-2 border rounded">
                                        @foreach($roles as $role)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, old('roles', $adminRoles)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="role_{{ $role->id }}">
                                                    {{ $role->display_name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('roles')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái tài khoản</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="0" {{ old('status', $admin->status) == '0' ? 'selected' : '' }}>Chưa duyệt (Pending)</option>
                                        <option value="1" {{ old('status', $admin->status) == '1' ? 'selected' : '' }}>Đã duyệt (Approved)</option>
                                        <option value="2" {{ old('status', $admin->status) == '2' ? 'selected' : '' }}>Khóa (Blocked)</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                                            <input type="text" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                                            <input type="text" class="form-control" id="password_confirmation" name="password_confirmation">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- end col -->
                        <div class="col-lg-4">
                            <div class="p-3">
                                <div class="mb-3 text-center">
                                    <label class="form-label d-block">Ảnh đại diện</label>
                                    <div class="position-relative d-inline-block mb-3">
                                        <img id="avatar-preview" src="{{ $admin->avatar }}" alt="avatar" class="img-thumbnail rounded-circle thumb-xl" style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                    <div class="input-group">
                                        <input type="file" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar" accept="image/*">
                                        <input type="hidden" name="cropped_avatar" id="cropped_avatar">
                                        <button class="btn btn-outline-danger" type="button" onclick="removeAvatar()"><i class="fas fa-trash-alt"></i></button>
                                        @error('avatar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted d-block mt-2">Định dạng: JPEG, PNG, JPG, GIF. Tối đa 2MB.</small>
                                </div>
                            </div>
                        </div><!-- end col -->
                    </div><!-- end row -->
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-primary px-4" onclick="showConfirmModal()">Cập nhật Admin</button>
                        </div>
                    </div>
                </form>
            </div><!--end card-body-->
        </div><!--end card-->
    </div> <!--end col-->                               
</div><!--end row-->

<!-- Image Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="cropModalLabel">Cắt ảnh đại diện</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="img-container">
                    <img id="image-to-crop" src="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm" id="cropButton">Cắt & Lưu</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title m-0 text-white" id="confirmModalLabel">Xác nhận cập nhật</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3">Bạn có chắc chắn muốn cập nhật thông tin cho Admin này không?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('editAdminForm').submit();">Xác nhận cập nhật</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('rizz/default/assets/libs/cropperjs/cropper.min.js') }}"></script>
<script>
    let cropper;
    let currentOriginalImage = null;
    const avatarInput = document.getElementById('avatar');
    const imageToCrop = document.getElementById('image-to-crop');
    const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
    const croppedAvatarInput = document.getElementById('cropped_avatar');
    const avatarPreview = document.getElementById('avatar-preview');
    const removeAvatarInput = document.getElementById('remove_avatar');

    avatarInput.addEventListener('change', function (e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = function (event) {
                currentOriginalImage = event.target.result;
                // Nếu là ảnh GIF, không cần crop
                if (file.type === 'image/gif') {
                    avatarPreview.src = currentOriginalImage;
                    croppedAvatarInput.value = "";
                    removeAvatarInput.value = "0";
                } else {
                    openCropper(currentOriginalImage);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    avatarPreview.addEventListener('click', function() {
        const currentSrc = avatarPreview.src;
        if (!currentSrc.includes('default_avatar.jpg')) {
            if (currentSrc.startsWith('data:image/gif') || currentSrc.toLowerCase().endsWith('.gif')) {
                avatarInput.click();
            } else {
                openCropper(currentSrc);
            }
        } else {
            avatarInput.click();
        }
    });

    function openCropper(src) {
        imageToCrop.src = src;
        cropModal.show();
    }

    document.getElementById('cropModal').addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
        });
    });

    document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // Nếu hủy mà có ảnh gốc mới chọn, hiển thị nó
        if (!croppedAvatarInput.value && currentOriginalImage) {
            avatarPreview.src = currentOriginalImage;
            removeAvatarInput.value = "0";
        }
    });

    document.getElementById('cropButton').addEventListener('click', function () {
        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });
        const base64Image = canvas.toDataURL('image/jpeg');
        croppedAvatarInput.value = base64Image;
        avatarPreview.src = base64Image;
        removeAvatarInput.value = "0";
        cropModal.hide();
    });

    function removeAvatar() {
        avatarInput.value = "";
        croppedAvatarInput.value = "";
        avatarPreview.src = "{{ asset('default_avatar.jpg') }}";
        removeAvatarInput.value = "1";
    }

    function showConfirmModal() {
        const form = document.getElementById('editAdminForm');
        if (form.checkValidity()) {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        } else {
            form.reportValidity();
        }
    }
</script>
@endpush
