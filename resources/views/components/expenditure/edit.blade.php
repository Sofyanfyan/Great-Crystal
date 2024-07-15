@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row d-flex justify-content-center">
                <!-- left column -->
                <div class="col-md-6">
                    <!-- general form elements -->
                    <div>
                        <form id="expenditureForm" method="POST" action="{{ route('expenditure.update', $expenditure->id) }}"
                            onsubmit="submitForm()">
                            @csrf
                            @method('PUT') <!-- Tambahkan method PUT untuk mengindikasikan method HTTP yang digunakan -->
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Edit Expenditure</h3>
                                </div>
                                <!-- /.card-header -->
                                <!-- form start -->
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Date Expenditure<span style="color: red">*</span></label>
                                            <!-- Tampilkan tanggal pengeluaran yang ada di dalam variabel $expenditure -->
                                            <div class="input-group date" id="reservationdate" data-target-input="nearest">
                                                <input name="spent_at" type="text" class="form-control "
                                                    placeholder="{{ $expenditure->spent_at }}"
                                                    data-target="#reservationdate" data-inputmask-alias="datetime"
                                                    data-inputmask-inputformat="dd/mm/yyyy" data-mask required />
                                                <div class="input-group-append" data-target="#reservationdate"
                                                    data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('date_birth') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label>Type : <span style="color: red"></span></label>
                                            <select name="type" class="form-control">
                                                <option value="inside the school"
                                                    {{ $expenditure->type == 'inside the school' ? 'selected' : '' }}>
                                                    inside the school</option>
                                                <option value="out of school"
                                                    {{ $expenditure->type == 'out of school' ? 'selected' : '' }}>out of
                                                    school
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="amount_spent">Amount<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="amount_spent" type="text" class="form-control"
                                                    id="amount" placeholder="Enter amount" autocomplete="off"
                                                    value="{{ $expenditure->amount_spent }}" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('amount_spent') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <label for="description">Description :</label>
                                            <textarea autocomplete="off" name="description" class="form-control" id="description" cols="30" rows="10"
                                                placeholder="Enter description">{{ $expenditure->description }}</textarea>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('description') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row d-flex justify-content-center">
                                <input role="button" type="submit" class="btn btn-success center col-12 mt-3">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function removeThousandSeparator(input) {
            // Remove thousand separator (.)
            let value = input.value.replace(/\./g, '');

            // Update input value
            input.value = value;
        }

        // Fungsi untuk menghapus pemisah ribuan sebelum formulir disubmit
        function submitForm() {
            // Hapus pemisah ribuan dari input amount_spent
            let amountInput = document.getElementById("amount");
            removeThousandSeparator(amountInput);

            // Submit formulir
            document.getElementById("expenditureForm").submit();
        }
    </script>
@endsection