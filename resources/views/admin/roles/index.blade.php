@extends('layouts.admin')

@section('title', 'Quản lý vai trò')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">                      
                        <h4 class="card-title">Danh sách Vai trò</h4>                      
                    </div><!--end col-->
                    <div class="col-auto"> 
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Thêm vai trò</a>           
                    </div><!--end col-->
                </div>  <!--end row-->                                  
            </div><!--end card-header-->
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Tên Vai trò</th>
                                <th>Mã (Name)</th>
                                <th>Mô tả</th>
                                <th>Số Admin</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td><span class="fw-semibold">{{ $role->display_name }}</span></td>
                                <td><code>{{ $role->name }}</code></td>
                                <td>{{ $role->description }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $role->admins_count }} Admin</span></td>
                                <td class="text-end">
                                    @if($role->name !== 'super_admin')
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary"><i class="las la-pen"></i></a>
                                        
                                        <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="d-inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa vai trò này?')"><i class="las la-trash"></i></button>
                                        </form>
                                    @else
                                    <span class="badge bg-secondary">Hệ thống</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div><!--end card-body-->
        </div><!--end card-->
    </div> <!--end col-->                               
</div><!--end row-->
@endsection
