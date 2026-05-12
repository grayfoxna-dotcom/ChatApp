@extends('layouts.admin')

@section('title', 'Quản lý người dùng')

@push('css')
<link href="{{ asset('rizz/default/assets/libs/simple-datatables/style.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">                      
                        <h4 class="card-title">Danh sách người dùng</h4>                      
                    </div><!--end col-->
                    <div class="col-auto"> 
                        <a href="{{ route('admin.users.create') }}" class="btn bg-primary-subtle text-primary"><i class="fas fa-plus me-1"></i> Thêm người dùng</a>           
                    </div><!--end col-->
                </div>  <!--end row-->                                  
            </div><!--end card-header-->
            <div class="card-body pt-0">
                <!-- Filter Form -->
                <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tìm kiếm</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tên hoặc email..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Trạng thái</label>
                        <select name="isActive" class="form-select form-select-sm">
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('isActive') == '1' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="0" {{ request('isActive') == '0' ? 'selected' : '' }}>Bị khóa</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Xác thực</label>
                        <select name="verified" class="form-select form-select-sm">
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Đã xác thực</option>
                            <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>Chưa xác thực</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Lọc</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-sm"><i class="fas fa-undo me-1"></i> Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table mb-0" id="datatable_1">
                        <thead class="table-light">
                          <tr>
                            <th>Người dùng</th>
                            <th>Email</th>
                            <th>Xác thực</th>
                            <th>Trạng thái</th>
                            <th>Ngày tham gia</th>
                            <th class="text-end">Hành động</th>
                          </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $user->avatar }}" alt="" class="thumb-md rounded-circle me-2">
                                        <div class="flex-grow-1 text-truncate">
                                            <h6 class="m-0 text-dark">{{ $user->name }}</h6>
                                            <p class="text-muted mb-0">ID: {{ $user->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->email_verified_at)
                                        <span class="text-success"><i class="fas fa-check-double me-1"></i> Đã xác thực</span>
                                    @else
                                        <span class="text-muted"><i class="fas fa-exclamation-triangle me-1"></i> Chưa xác thực</span>
                                    @endif
                                </td>
                                <td>
                                    <select class="form-select form-select-sm active-select" data-id="{{ $user->id }}" style="width: 120px;">
                                        <option value="1" {{ $user->isActive == 1 ? 'selected' : '' }}>Hoạt động</option>
                                        <option value="0" {{ $user->isActive == 0 ? 'selected' : '' }}>Bị khóa</option>
                                    </select>
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">                                                       
                                    <a href="{{ route('admin.users.edit', $user->id) }}"><i class="las la-pen text-secondary fs-18"></i></a>
                                    <a href="javascript:void(0);" onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')"><i class="las la-trash-alt text-secondary fs-18"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            </div><!--end card-body-->
        </div><!--end card-->
    </div> <!--end col-->                               
</div><!--end row-->

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h6 class="modal-title m-0 text-white" id="deleteUserModalLabel">Xác nhận xóa người dùng</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mt-3">Bạn có chắc chắn muốn xóa người dùng <strong id="deleteUserName"></strong> không?</p>
                <p class="text-muted small mb-0">Dữ liệu người dùng này sẽ bị xóa vĩnh viễn!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy bỏ</button>
                <form id="deleteUserForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Đồng ý xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('rizz/default/assets/libs/simple-datatables/umd/simple-datatables.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const dataTable = new simpleDatatables.DataTable("#datatable_1", {
            searchable: false,
            labels: {
                placeholder: "Tìm kiếm...",
                perPage: "bản ghi mỗi trang",
                noRows: "Không tìm thấy dữ liệu",
                info: "Hiển thị từ {start} đến {end} trong tổng số {rows} bản ghi",
            },
            fixedHeight: false,
        });
    });

    function confirmDelete(id, name) {
        const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteUserForm').action = `/admin/users/${id}`;
        modal.show();
    }

    $(document).on('change', '.active-select', function() {
        const userId = $(this).data('id');
        const isActive = $(this).val();
        
        $.ajax({
            url: `/admin/users/${userId}/update-active`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                isActive: isActive
            },
            success: function(response) {
                if(response.success) {
                    showToast('Cập nhật trạng thái thành công!', 'success');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại!', 'error');
            }
        });
    });
</script>
@endpush
