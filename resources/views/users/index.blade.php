<!-- resources/views/users/index.blade.php -->

@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Users Management</h5>
            <div>
                <button class="btn btn-success me-2" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="btn btn-danger me-2" onclick="exportPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form id="searchForm">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search..." name="search" value="{{ $search }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
           
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th class="sortable" onclick="sortTable('id')">ID 
                                @if($sortField == 'id') <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i> @endif
                            </th>
                            <th class="sortable" onclick="sortTable('name')">Name 
                                @if($sortField == 'name') <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i> @endif
                            </th>
                            <th class="sortable" onclick="sortTable('email')">Email 
                                @if($sortField == 'email') <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i> @endif
                            </th>
                            <th class="sortable" onclick="sortTable('phone')">Phone 
                                @if($sortField == 'phone') <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i> @endif
                            </th>
                            <th>Profile Pic</th>
                            <th>Resume</th>
                            <th class="sortable" onclick="sortTable('created_at')">Created At 
                                @if($sortField == 'created_at') <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i> @endif
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
 
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>
                                    @if($user->profile_pic)
                                        <img src="{{ asset('storage/' . $user->profile_pic) }}" alt="Profile Pic" width="50">
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if($user->resume)
                                        <a href="{{ asset('storage/' . $user->resume) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ Carbon\Carbon::parse($user->created_at)->format('Y-m-d H:i:s') }} </td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-btn" data-id="{{ $user->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="{{ $user->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $users->appends([
                    'search' => $search,
                    'sort_field' => $sortField,
                    'sort_direction' => $sortDirection
                ])->links() }}
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // File preview for add form
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePicPreview').innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" width="100">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('resume').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('resumePreview').innerHTML = `
                    <span class="badge bg-primary">${file.name}</span>
                `;
            }
        });

        // File preview for edit form
        document.getElementById('edit_profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('editProfilePicPreview').innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" width="100">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('edit_resume').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('editResumePreview').innerHTML = `
                    <span class="badge bg-primary">${file.name}</span>
                `;
            }
        });

        // Add User
        $('#addUserForm').submit(function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            
            $.ajax({
                url: "{{ route('users.store') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#addUserModal').modal('hide');
                    $('#addUserForm')[0].reset();
                    $('#profilePicPreview').empty();
                    $('#resumePreview').empty();
                    showAlert('success', response.success);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    for (var key in errors) {
                        errorMessages.push(errors[key][0]);
                    }
                    showAlert('danger', errorMessages.join('<br>'));
                }
            });
        });

        // Edit User
        $('.edit-btn').click(function() {
            var userId = $(this).data('id');
            
            $.ajax({
                url: `/users/${userId}/edit`,
                type: 'GET',
                success: function(response) {
                    $('#edit_id').val(response.id);
                    $('#edit_name').val(response.name);
                    $('#edit_email').val(response.email);
                    $('#edit_phone').val(response.phone);
                    
                    // Clear previous previews
                    $('#editProfilePicPreview').empty();
                    $('#editResumePreview').empty();
                    
                    // Set current file previews if they exist
                    if (response.profile_pic) {
                        $('#editProfilePicPreview').html(`
                            <img src="/storage/${response.profile_pic}" class="img-thumbnail" width="100">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_profile_pic" name="remove_profile_pic">
                                <label class="form-check-label" for="remove_profile_pic">
                                    Remove profile picture
                                </label>
                            </div>
                        `);
                    }
                    
                    if (response.resume) {
                        $('#editResumePreview').html(`
                            <a href="/storage/${response.resume}" target="_blank" class="btn btn-sm btn-info mb-2">
                                <i class="fas fa-download"></i> Current Resume
                            </a>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_resume" name="remove_resume">
                                <label class="form-check-label" for="remove_resume">
                                    Remove resume
                                </label>
                            </div>
                        `);
                    }
                    
                    $('#editUserModal').modal('show');
                },
                error: function(xhr) {
                    showAlert('danger', 'Error fetching user data.');
                }
            });
        });

        // Update User
        $('#editUserForm').submit(function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var userId = $('#edit_id').val();
            
            $.ajax({
                url: `/users/${userId}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-HTTP-Method-Override': 'PUT'
                },
                success: function(response) {
                    $('#editUserModal').modal('hide');
                    showAlert('success', response.success);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    for (var key in errors) {
                        errorMessages.push(errors[key][0]);
                    }
                    showAlert('danger', errorMessages.join('<br>'));
                }
            });
        });

        // Delete User
        $('.delete-btn').click(function() {
            if (confirm('Are you sure you want to delete this user?')) {
                var userId = $(this).data('id');
                
                $.ajax({
                    url: `/users/${userId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        showAlert('success', response.success);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        showAlert('danger', 'Error deleting user.');
                    }
                });
            }
        });

        // Search Form
        $('#searchForm').submit(function(e) {
            e.preventDefault();
            var search = $('input[name="search"]').val();
            var url = new URL(window.location.href);
            url.searchParams.set('search', search);
            window.location.href = url.toString();
        });

        // Sort Table
        function sortTable(field) {
            var url = new URL(window.location.href);
            var sortDirection = 'asc';
            
            if (url.searchParams.get('sort_field') === field) {
                sortDirection = url.searchParams.get('sort_direction') === 'asc' ? 'desc' : 'asc';
            }
            
            url.searchParams.set('sort_field', field);
            url.searchParams.set('sort_direction', sortDirection);
            window.location.href = url.toString();
        }

        // Export Functions
        function exportCSV() {
            window.location.href = "{{ route('users.export.csv') }}";
        }

        function exportPDF() {
            window.location.href = "{{ route('users.export.pdf') }}";
        }

        // Show Alert
        function showAlert(type, message) {
            var alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('.container').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
    </script>
@endsection