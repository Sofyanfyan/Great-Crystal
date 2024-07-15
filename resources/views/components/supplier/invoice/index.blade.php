@extends('layouts.admin.master')
@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="container-fluid">
        <h2 class="text-center display-4 mb-3">Invoice Supplier Search</h2>
        <form action="{{ route('invoice-supplier.index') }}" method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <label for="status">Payment Status</label>
                    <select name="status" class="form-control" id="status-select">
                        <option value="">-- All Status --</option>
                        <option value="Paid" {{ $form->status === 'Paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Not Yet" {{ $form->status === 'Not Yet' ? 'selected' : '' }}>Not Yet</option>
                    </select>
                    <input type="hidden" name="order" id="sort-order" value="{{ $form->order }}">
                </div>

                <div class="col-md-3">
                    <label for="sort">Sort By</label>
                    <select name="sort" class="form-control" id="sort-select">
                        <option value="">-- All Data --</option>
                        <option value="oldest" {{ $form->sort === 'oldest' ? 'selected' : '' }}>Date (Oldest First)</option>
                        <option value="newest" {{ $form->sort === 'newest' ? 'selected' : '' }}>Date (Newest First)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $form->date ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label for="date">Search Data</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                            value="{{ $form->search ?? '' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Conditional rendering based on data availability -->
        @if (sizeof($data) == 0 && ($form->type || $form->sort || $form->order || $form->status || $form->search))
            <!-- Display message when no data found based on search criteria -->
            <div class="row h-100 my-5">
                <div class="col-sm-12 my-auto text-center">
                    <h3>Not found on your search criteria!</h3>
                </div>
            </div>
        @elseif (sizeof($data) == 0)
            <!-- Display message when no transfer data found -->
            <div class="row h-100 my-5">
                <div class="col-sm-12 my-auto text-center">
                    <h3>Click the button below to create Invoice Supplier!</h3>
                    <div class="btn-group">
                        <a type="button" href="{{ route('create-invoice-supplier.create') }}"
                            class="btn btn-success btn-sm mt-3">
                            <i class="fa-solid fa-plus"></i> Create Invoice
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="btn-group">
                <a type="button" href="{{ route('create-invoice-supplier.create') }}" class="btn btn-success btn-sm mt-3">
                    <i class="fa-solid fa-plus"></i> Create Invoice
                </a>
            </div>
            <!-- Display Cash or Bank data in a table -->
            <div class="card card-dark mt-4">
                <div class="card-header">
                    <h3 class="card-title">Invoice Supplier List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>No. Invoice</th>
                                <th>Supplier Name</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Deadline Invoice</th>
                                <th>Paid Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loop through transfer data -->
                            @foreach ($data as $item)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->no_invoice }} </td>
                                    <td>{{ $item->supplier->name }} </td>
                                    <td>Rp. {{ number_format($item->amount, 0, ',', '.') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->date)->format('j F Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->deadline_invoice)->format('j F Y') }}</td>
                                    <td>{{ $item->payment_status }} </td>
                                    <td class="text-center">
                                        <a href="{{ route('invoice-supplier.upload-proof-view', $item->id) }}"
                                            class="btn btn-sm btn-warning"><i class="fas fa-upload"
                                                style="margin-right: 4px"></i>Upload</a>

                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                            data-target="#importModal{{ $item->id }}" style="">
                                            <i class="fas fa-eye" style="margin-right: 4px"></i>Review
                                        </button>

                                        <button type="button" class="btn btn-sm delete-btn btn-danger"
                                            data-id="{{ $item->id }}">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </td>

                                    <td class="project-actions">
                                        <div class="modal fade" id="importModal{{ $item->id }}" tabindex="-1"
                                            role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document"
                                                style="max-width: 60%; margin: 1.75rem auto;">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title" id="importModalLabel">Proof of
                                                            Payment</h4>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <form id="uploadForm{{ $item->id }}"
                                                        action="{{ route('invoice-supplier.upload-proof', $item->id) }}"
                                                        method="post" enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="modal-body" style="width: 100%; height: auto;">
                                                            <div class="file-upload"
                                                                style=" display: flex; justify-content: center; align-items: center;">
                                                                <div class="form-group row">
                                                                    <div class="col-md-6">
                                                                        <label for="no_invoice"># No. Invoice :</label>
                                                                        <div class="input-group">
                                                                            <input name="no_invoice" type="text"
                                                                                class="form-control" id="no_invoice"
                                                                                value="{{ $item->no_invoice }}" readonly>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label for="name">Supplier
                                                                            Name<span></span> :</label>
                                                                        <div class="input-group">
                                                                            <input name="supplier_name" type="text"
                                                                                class="form-control" id="supplier_name"
                                                                                value="{{ $item->supplier->name }}"
                                                                                readonly>
                                                                        </div>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('name') }}</p>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-6 mt-3">
                                                                        <label for="nota">Nota<span></span> :</label>
                                                                        <div class="input-group">
                                                                            <input name="nota" type="text"
                                                                                class="form-control" id="nota"
                                                                                value="{{ $item->nota }}" readonly>
                                                                        </div>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('nota') }}</p>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-6 mt-3">
                                                                        <label for="description">Description :</label>
                                                                        <textarea autocomplete="off" name="description" class="form-control" id="description"
                                                                            placeholder="Enter description" readonly>{{ $item->description }}</textarea>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('description') }}</p>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-6 mt-3">
                                                                        <label for="payment_method">Payment Method
                                                                            :</label>
                                                                        <select name="payment_method" class="form-control"
                                                                            id="payment_method" disabled>
                                                                            <option value="Cash"
                                                                                {{ $item->payment_method == 'Cash' ? 'selected' : '' }}>
                                                                                Kas</option>
                                                                            <option value="Bank"
                                                                                {{ $item->payment_method == 'Bank' ? 'selected' : '' }}>
                                                                                Bank</option>
                                                                        </select>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('payment_method') }}
                                                                            </p>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-6 mt-3">
                                                                        <label for="payment_status">Payment Status
                                                                            :</label>
                                                                        <select name="payment_status" class="form-control"
                                                                            id="payment_status" disabled>
                                                                            <option value="Not Yet"
                                                                                {{ $item->payment_status == 'Not Yet' ? 'selected' : '' }}>
                                                                                Not Yet</option>
                                                                            <option value="Paid"
                                                                                {{ $item->payment_status == 'Paid' ? 'selected' : '' }}>
                                                                                Paid</option>
                                                                        </select>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('payment_status') }}
                                                                            </p>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-6 mt-3">
                                                                        <label for="transfer_account_id">Pay
                                                                            From :</label>
                                                                        <select name="transfer_account_id"
                                                                            id="transfer_account_id"
                                                                            class="form-control" disabled>
                                                                            @if ($item->transferAccount)
                                                                                <option
                                                                                    value="{{ $item->transferAccount->id }}">
                                                                                    {{ $item->transferAccount->account_no }}
                                                                                    - {{ $item->transferAccount->name }}
                                                                                </option>
                                                                            @endif
                                                                        </select>
                                                                        @if ($errors->any())
                                                                            <p style="color: red">
                                                                                {{ $errors->first('transfer_account_id') }}
                                                                            </p>
                                                                        @endif
                                                                    </div>                                                                    

                                                                    <div class="col-md-12 mt-3">
                                                                        <label for="upload_image">Upload Image :</label>

                                                                        @if ($item->image_path)
                                                                            <div class="image-container text-center">
                                                                                <img src="{{ asset('uploads/' . $item->image_path) }}"
                                                                                    alt="Proof of Payment"
                                                                                    class="img-thumbnail"
                                                                                    style="max-width: 100%;"
                                                                                    loading="lazy">
                                                                            </div>
                                                                        @else
                                                                            <div class="text-center">
                                                                                <p>No image uploaded.</p>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <!-- End Loop through transfer data -->
                        </tbody>
                    </table>
                    <!-- Pagination with adjusted layout -->
                    <div class="d-flex justify-content-between mt-4 px-3">
                        <div class="mb-3">
                            Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of
                            {{ $data->total() }} results
                        </div>
                        <div>
                            {{ $data->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Include jQuery and SweetAlert library -->
    <script src="{{ asset('template') }}/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/projects.js') }}" defer></script>


    <script>
        function readURL(input, id) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('file-upload-content' + id).style.display = 'block';
                    document.getElementById('image-upload-wrap' + id).style.display = 'none';
                    document.getElementById('file-upload-image' + id).src = e.target.result;
                    document.getElementById('image-file-name' + id).innerHTML = input.files[0].name;
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                removeUpload(input, id);
            }
        }

        function removeUpload(input, id) {
            document.getElementById('file-upload-content' + id).style.display = 'none';
            document.getElementById('image-upload-wrap' + id).style.display = 'block';
            input.value = '';
        }
    </script>

    <script>
        $(document).ready(function() {
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '{{ session('success') }}'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}'
                });
            @endif

            $('.delete-btn').click(function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mengirim request DELETE menggunakan Ajax
                        $.ajax({
                            url: '{{ route('invoice-supplier.destroy', ['id' => ':id']) }}'
                                .replace(':id', id),
                            type: 'DELETE',
                            data: {
                                "_token": "{{ csrf_token() }}",
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location
                                        .reload(); // Refresh halaman setelah menghapus
                                });
                            },
                            error: function(response) {
                                Swal.fire(
                                    'Failed!',
                                    response.responseJSON.error ? response
                                    .responseJSON.error :
                                    'There was an error deleting the invoice supplier.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script>
        document.getElementById('sort-select').addEventListener('change', function() {
            let order = this.options[this.selectedIndex].getAttribute('data-order');
            document.getElementById('sort-order').value = order;
            this.form.submit();
        });
    </script>

    <script>
        document.getElementById('status-select').addEventListener('change', function() {
            this.form.submit();
        });

        document.getElementById('sort-select').addEventListener('change', function() {
            let order = this.options[this.selectedIndex].getAttribute('data-order');
            document.getElementById('sort-order').value = order;
            this.form.submit();
        });
    </script>


    <script>
        $('#yourModal').on('shown.bs.modal', function() {
            $('.select2').select2({
                width: '100%' // Sesuaikan lebar sesuai kebutuhan Anda
            });
        });
    </script>


@endsection
