@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <h2 class="text-center display-4 mb-5">Journal Search</h2>
            <div class="m-1">
                <form action="{{ route('journal.index') }}" method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="type">Type Transaction</label>
                            <select name="type" class="form-control">
                                <option value="">-- All Data --</option>
                                <option value="transaction_transfer"
                                    {{ $form->type === 'transaction_transfer' ? 'selected' : '' }}>Transaction Transfer
                                </option>
                                <option value="transaction_receive"
                                    {{ $form->type === 'transaction_receive' ? 'selected' : '' }}>Transaction Receive
                                </option>
                                <option value="transaction_send" {{ $form->type === 'transaction_send' ? 'selected' : '' }}>
                                    Transaction Send</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Sort By </label>
                            <select name="sort" class="form-control select2" id="sort-select">
                                {{-- <option value="" selected disabled>-- Select Sort --</option> --}}
                                <option value="date"
                                    {{ $form->sort === 'date' && $form->order === 'asc' ? 'selected' : '' }}
                                    data-order="asc">Date (Oldest First)</option>
                                <option value="date"
                                    {{ $form->sort === 'date' && $form->order === 'desc' ? 'selected' : '' }}
                                    data-order="desc">Date (Newest First)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date">Start Date <span
                                    style="font-size: 14px; color:black">(Transaction)</span></label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ $form->start_date ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date">End Date <span
                                    style="font-size: 14px; color:black">(Transaction)</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ $form->end_date ?? '' }}">
                        </div>
                        <div class="col-md-12 mt-3">
                            <label for="search">Search Data</label>
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
                    <input type="hidden" name="order" id="order" value="{{ $form->order ?? 'desc' }}">
                </form>
            </div>

            <div class="btn p-1 d-inline-block mt-3 mb-3">
                <form action="{{ route('journal.detail.selected') }}" method="GET">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $form->start_date }}">
                    <input type="hidden" name="end_date" value="{{ $form->end_date }}">
                    <input type="hidden" name="type" value="{{ $form->type }}">
                    <input type="hidden" name="search" value="{{ $form->search }}">
                    <input type="hidden" name="sort" value="{{ $form->sort }}">
                    <input type="hidden" name="order" value="{{ $form->order }}">
                    <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-filter fa-bounce"
                            style="margin-right: 4px"></i>View Filter
                    </button>
                </form>
            </div>

            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-file-import fa-bounce" style="margin-right: 4px"></i>Import Data
            </button>
        </div>

        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card card-dark mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Report Journal</h3>
                    </div>
                    <div class="card-body p-0">


                        <table class="table table-striped projects">
                            <thead>
                                <tr class="">
                                    <th>#</th>
                                    <th>No Transaction</th>
                                    <th>Transfer Account</th>
                                    <th>Deposit Account</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Created At</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allData as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->no_transaction }}</td>
                                        <td>{{ $item->transfer_account_no }} - {{ $item->transfer_account_name }}
                                        </td>
                                        <td>{{ $item->transfer_account_no }} - {{ $item->deposit_account_name }}
                                        </td>
                                        <td>Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->date)->format('j F Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('j F Y') }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="{{ route('journal.detail', ['id' => $item->id, 'type' => $item->type]) }}"
                                                    class="btn btn-primary btn-sm"><i class="fas fa-folder"></i>
                                                    View</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>



                        <div class="d-flex justify-content-between mt-4 px-3">
                            <div class="mb-3">
                                Showing {{ $allData->firstItem() }} to {{ $allData->lastItem() }} of
                                {{ $allData->total() }} results
                            </div>
                            <div>
                                {{ $allData->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    {{-- Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('journal.import') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="file-upload">
                            <button class="download-template-btn" type="button" id="download-template">
                                Download Template
                            </button>

                            <div class="image-upload-wrap">
                                <input type="file" name="import_transaction" class="file-upload-input"
                                    onchange="readURL(this);"
                                    accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">

                                <div class="drag-text">
                                    <h3>Drag and drop a file or select add Excel</h3>
                                </div>
                            </div>

                            <div class="file-upload-content">
                                <h4 class="file-upload-image"></h4>
                                <div class="image-title-wrap"
                                    style="display: flex; justify-content: space-between; align-items: center;">
                                    <button type="button" onclick="removeUpload()" class="remove-image"
                                        style="margin-right: 10px"><i class="fa-solid fa-trash fa-2xl"
                                            style="margin-bottom: 1em;"></i> <br> Remove
                                        <span class="image-title">Excel</span></button>
                                    <button type="submit" role="button" class="upload-image"><i
                                            class="fa-solid fa-cloud-arrow-up fa-2xl fa-bounce"
                                            style="margin-bottom: 1em;"></i> <br> Post <span
                                            class="image-title">Excel</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include jQuery and SweetAlert library -->
    <script src="{{ asset('template') }}/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/projects.js') }}" defer></script>

    <script>
        $("body").on("click", "#download-template", function(event) {
            event.preventDefault();
            console.log("terklik");
            window.location.href = 'journal/journal/templates/import';
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $(".image-upload-wrap").hide();
                    $(".file-upload-image").html(input.files[0].name);
                    $(".file-upload-content").show();
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                removeUpload();
            }
        }

        function removeUpload() {
            $(".file-upload-input").replaceWith($(".file-upload-input").clone());
            $(".file-upload-content").hide();
            $(".image-upload-wrap").show();

            $(".file-upload-wrap").bind("dragover", function() {
                $(".image-upload-wrap").addClass("image-dropping");
            });

            $("image-upload-wrap").bind("dragleave", function() {
                $(".image-upload-wrap").removeClass("image-dropping");
            });
        }
    </script>

    @php
        $code = null;
        $msg = null;
        $session = session('import_status');
        if ($session) {
            $code = $session['code'];
            $msg = $session['msg'];
        }
    @endphp


    @if (session('import_status'))
        <script>
            const code = "{{ $code }}";
            const msg = "{{ $msg }}";

            if (code > 200) {
                Swal.fire({
                    icon: "error",
                    title: "Validation errors",
                    text: msg,
                    footer: '<a href="#">Why do I have this issue?</a>'
                });
                console.log('errors ' + msg);

            }
        </script>
    @elseif (session('success'))
        <script>
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
        </script>
    @endif


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('sort-select');
            const orderInput = document.getElementById('order');

            sortSelect.addEventListener('change', function() {
                const selectedOption = sortSelect.options[sortSelect.selectedIndex];
                orderInput.value = selectedOption.getAttribute('data-order');
            });
        });
    </script>
@endsection