@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-dark">
                            <div class="card-header">
                                <h3 class="card-title">Upload Proof of Payment</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <form id="uploadForm{{ $invoice->id }}"
                                    action="{{ route('invoice-supplier.upload-proof', $invoice->id) }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="no_invoice"># No. Invoice <span style="color: red">*</span>
                                                    :</label>
                                                <input name="no_invoice" type="text" class="form-control" id="no_invoice"
                                                    value="{{ $invoice->no_invoice }}" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="supplier_name">Supplier Name <span style="color: red">*</span>
                                                    :</label>
                                                <input name="supplier_name" type="text" class="form-control"
                                                    id="supplier_name" value="{{ $invoice->supplier->name }}" readonly>
                                                {{-- @if ($errors->has('supplier_name'))
                                                    <span class="text-danger">{{ $errors->first('supplier_name') }}</span>
                                                @endif --}}
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nota">Nota <span style="color: red">*</span> :</label>
                                                <input name="nota" type="text" class="form-control" id="nota"
                                                    value="{{ $invoice->nota }}" readonly>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="description">Description <span style="color: red">*</span>
                                                    :</label>
                                                <textarea name="description" class="form-control" id="description" readonly>{{ $invoice->description }}</textarea>
                                                {{-- @if ($errors->has('description'))
                                                    <span class="text-danger">{{ $errors->first('description') }}</span>
                                                @endif --}}
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="payment_method">Payment Method <span style="color: red">*</span>
                                                    :</label>
                                                <select name="payment_method" class="form-control" id="payment_method">
                                                    <option value="Cash"
                                                        {{ $invoice->payment_method == 'Cash' ? 'selected' : '' }}>Cash
                                                    </option>
                                                    <option value="Bank"
                                                        {{ $invoice->payment_method == 'Bank' ? 'selected' : '' }}>Bank
                                                    </option>

                                                </select>
                                                @if ($errors->any())
                                                    <p style="color: red">
                                                        {{ $errors->first('payment_method') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="payment_status">Payment Status <span style="color: red">*</span>
                                                    :</label>
                                                <select name="payment_status" class="form-control" id="payment_status">
                                                    <option value="Not Yet"
                                                        {{ $invoice->payment_status == 'Not Yet' ? 'selected' : '' }}>
                                                        Not Yet</option>
                                                    <option value="Paid"
                                                        {{ $invoice->payment_status == 'Paid' ? 'selected' : '' }}>
                                                        Paid</option>
                                                </select>
                                                @if ($errors->any())
                                                    <p style="color: red">
                                                        {{ $errors->first('payment_status') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="transfer_account_id">Pay From <span
                                                        style="color: red">*</span>:</label>
                                                <select name="transfer_account_id" id="transfer_account_id"
                                                    class="form-control select2">
                                                    @foreach ($accountNumbers as $accountNumber)
                                                        <option value="{{ $accountNumber->id }}">
                                                            {{ $accountNumber->account_no }} -
                                                            {{ $accountNumber->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn text-primary" data-toggle="modal"
                                                        data-target="#addAccountModal">
                                                        + Add Account
                                                    </button>
                                                </div>
                                                @if ($errors->any())
                                                    <p style="color: red">{{ $errors->first('transfer_account_id') }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-md-12 mt-3">
                                            <label for="upload_image">Upload Image<span style="color: red">*</span>
                                                :</label>
                                            <div class="image-upload-wrap" id="image-upload-wrap{{ $invoice->id }}">
                                                {{-- <input type="file" name="image_path" class="file-upload-input"
                                                    onchange="readURL(this, '{{ $invoice->id }}');" accept="image/*"> --}}

                                                <input type="file" name="image_path" class="file-upload-input"
                                                    onchange="readURL(this, '{{ $invoice->id }}');" accept="image/*">

                                                <div class="drag-text">
                                                    <h3>Drag and drop a file or select add
                                                        Image</h3>
                                                </div>
                                            </div>
                                            <div class="file-upload-content" id="file-upload-content{{ $invoice->id }}"
                                                style="display:none;">
                                                <div class="image-file-name" id="image-file-name{{ $invoice->id }}"
                                                    style="text-align: center; margin-top: 10px;">
                                                </div>
                                                <img class="file-upload-image" id="file-upload-image{{ $invoice->id }}"
                                                    src="#" alt="your image"
                                                    style="max-width: 100%; max-height: 100%;" />
                                                <div class="image-title-wrap"
                                                    style="display: flex; justify-content: space-between; align-items: center;">
                                                    <button type="button"
                                                        onclick="removeUpload(this, '{{ $invoice->id }}')"
                                                        class="remove-image" style="margin-right: 10px">
                                                        <i class="fa-solid fa-trash fa-2xl"
                                                            style="margin-bottom: 1em;"></i>
                                                        <br> Remove
                                                        <span class="image-title">Image</span>
                                                    </button>
                                                    <button type="submit" role="button" class="upload-image">
                                                        <i class="fa-solid fa-cloud-arrow-up fa-2xl fa-bounce"
                                                            style="margin-bottom: 1em;"></i>
                                                        <br> Upload Proof
                                                        <span class="image-title">Image</span>
                                                    </button>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog"
                                    aria-labelledby="addAccountModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addAccountModalLabel">Add Account</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <form
                                                    action="{{ route('invoice-supplier.account.store', ['invoice_id' => $invoice->id]) }}"
                                                    id="addAccountForm" method="POST">

                                                    @csrf
                                                    <div class="form-group">
                                                        <label for="account_no">Account Number<span
                                                                style="color: red">*</span> :</label>
                                                        <input type="text" class="form-control" id="account_no"
                                                            name="account_no" placeholder="Enter Account Number" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="name">Account Name<span
                                                                style="color: red">*</span> :</label>
                                                        <input type="text" class="form-control" id="name"
                                                            name="name" placeholder="Enter Account Name" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Category<span style="color: red">*</span> :</label>
                                                        <div class="input-group">
                                                            <select name="account_category_id"
                                                                class="form-control select2" id="account_category_id"
                                                                style="width: 100%">
                                                                @foreach ($accountCategory as $category)
                                                                    <option value="{{ $category->id }}">
                                                                        {{ $category->category_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @error('account_category_id')
                                                            <p style="color: red;">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="description">Description :</label>
                                                        <textarea class="form-control" id="description" name="description" placeholder="Enter Description"></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
    </section>

    <script src="{{ asset('template') }}/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/projects.js') }}" defer></script>
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


        });
    </script>

    <script>
        // Fungsi untuk menghapus pemisah ribuan sebelum formulir disubmit
        function submitForm() {
            // Hapus pemisah ribuan dari input amount
            let amountInput = document.getElementById("amount");
            let value = amountInput.value.replace(/\./g, '');
            amountInput.value = value;

            // Submit formulir
            document.getElementById("uploadForm{{ $invoice->id }}").submit();
        }

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
@endsection
