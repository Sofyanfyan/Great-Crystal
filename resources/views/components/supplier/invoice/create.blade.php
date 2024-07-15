@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row d-flex justify-content-center">
                <!-- left column -->
                <div class="col-md-12">
                    <!-- general form elements -->
                    <div>
                        <form id="transferForm" method="POST" action="{{ route('invoice-supplier.store') }}"
                            onsubmit="submitForm()">
                            @csrf
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Create Invoice Supplier</h3>
                                </div>
                                <!-- /.card-header -->
                                <!-- form start -->
                                <div class="card-body">
                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="no_invoice">No. Invoice<span style="color: red">*</span> :</label>
                                            <div class="input-group">

                                                <input name="no_invoice" type="text" class="form-control" id="no_invoice"
                                                    placeholder="Enter No Invoice" autocomplete="off"
                                                    value="{{ old('no_invoice') }}">
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('no_invoice') }}</p>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label for="supplier_id">Supplier Name<span style="color: red">*</span>
                                                :</label>
                                            <select name="supplier_id" id="supplier_id" class="form-control select2">
                                                @foreach ($supplierDatas as $supplierData)
                                                    <option value="{{ $supplierData->id }}">{{ $supplierData->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="amount">Amount<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="amount" type="text" class="form-control" id="amount"
                                                    placeholder="Enter amount" autocomplete="off"
                                                    value="{{ old('amount') ? number_format(old('amount'), 0, ',', '.') : '' }}">
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('amount') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label for="date">Date<span style="color: red">*</span> :</label>
                                            <input type="date" name="date" class="form-control"
                                                data-inputmask-inputformat="dd/mm/yyyy" data-mask>

                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('date') }}</p>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="nota">Nota<span style="color: red">*</span> :</label>
                                            <div class="input-group">

                                                <input name="nota" type="text" class="form-control" id="nota"
                                                    placeholder="Enter Nota" autocomplete="off" value="{{ old('nota') }}">
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('nota') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label for="deadline_invoice">Deadline Invoice<span style="color: red">*</span>
                                                :</label>
                                            <input type="date" name="deadline_invoice" class="form-control"
                                                data-inputmask-inputformat="dd/mm/yyyy" data-mask>

                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('deadline_invoice') }}</p>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="pph">PPH<span style="color: red">*</span> :</label>
                                            <select name="pph" class="form-control" id="pph">
                                                <option value="">-- Select PPH --</option>
                                                @php
                                                    $pph_values = [21, 22, 23, 24, 25, 26, 29];
                                                @endphp
                                                @foreach ($pph_values as $pph)
                                                    <option value="{{ $pph }}">PPH {{ $pph }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6" id="pph_percentage_container" style="display: none;">
                                            <label for="pph_percentage">PPN Status<span style="color: red">*</span>
                                                :</label>
                                            <select name="pph_percentage" class="form-control" id="pph_percentage">
                                                <option value="2%">2%</option>
                                                <option value="15%">15%</option>
                                                {{-- pph 22 --}}
                                                <option value="2,5%">2,5%</option>
                                                <option value="7,5%">7,5%</option>
                                                {{-- pph 26 --}}
                                                <option value="7,5%">20%</option>

                                            </select>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('pph_percentage') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <label for="description">Description :</label>
                                            <textarea autocomplete="off" name="description" class="form-control" id="description" cols="30"
                                                rows="10" placeholder="Enter description">{{ old('description') }}</textarea>
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
            document.getElementById("transferForm").submit();
        }


        document.addEventListener('DOMContentLoaded', function() {
            let pphSelect = document.getElementById('pph');
            let ppnStatusContainer = document.getElementById('pph_percentage_container');
            let ppnStatusSelect = document.getElementById('pph_percentage');

            pphSelect.addEventListener('change', function() {
                if (pphSelect.value !== '') {
                    ppnStatusContainer.style.display = 'block';
                } else {
                    ppnStatusContainer.style.display = 'none';
                }

                let options = ['2%', '15%', '2,5%', '7,5%', '20%'];
                ppnStatusSelect.innerHTML = '';
                options.forEach(function(option) {
                    let opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    ppnStatusSelect.appendChild(opt);
                });
            });

            $('.select2').select2();
        });
    </script>
@endsection
