@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row d-flex justify-content-center">
                <!-- left column -->
                <div class="col-md-6">
                    <!-- general form elements -->
                    <div>
                        <form id="accountForm" method="POST" action="{{ route('account.update', $accountNumbers->id) }}"
                            onsubmit="submitForm()">
                            @csrf
                            @method('PUT') <!-- Tambahkan method PUT untuk mengindikasikan method HTTP yang digunakan -->
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Edit Account Number</h3>
                                </div>
                                <!-- /.card-header -->
                                <!-- form start -->
                                <div class="card-body">

                                    <div class="form-group row">
                                        <div class="col-md-6">
                                            <label for="name">Name<span style="color: red">*</span> :</label>
                                            <input name="name" type="text" class="form-control" id="name"
                                                placeholder="Enter Name" value="{{ $accountNumbers->name }}"
                                                autocomplete="off" required>

                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('name') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <label for="account_no">Account Number<span style="color: red">*</span>
                                                :</label>
                                            <input name="account_no" type="text" class="form-control" id="account_no"
                                                placeholder="Enter Account Number" value="{{ $accountNumbers->account_no }}"
                                                autocomplete="off" required>

                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('account_no') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6 mt-3">
                                            <label for="amount">Amount<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="amount" type="text" class="form-control" id="amount"
                                                    placeholder="Enter amount" autocomplete="off"
                                                    value="{{ $accountNumbers->amount }}" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('amount') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-6 mt-3">
                                            <label>Category : <span style="color: red"></span></label>
                                            <select name="account_category_id" class="form-control">
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ $category->id == $accountNumbers->account_category_id ? 'selected' : '' }}>
                                                        {{ $category->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-6 mt-3">
                                            <label for="beginning_balance">Beginning Balance<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="beginning_balance" type="text" class="form-control" id="beginning_balance"
                                                    placeholder="Enter Beginning Balance" autocomplete="off"
                                                    value="{{ $accountNumbers->beginning_balance }}" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('beginning_balance') }}</p>
                                            @endif
                                        </div>
                                        
                                        <div class="col-md-6 mt-3">
                                            <label for="ending_balance">Ending Balance<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="ending_balance" type="text" class="form-control" id="ending_balance"
                                                    placeholder="Enter Ending Balance" autocomplete="off"
                                                    value="{{ $accountNumbers->ending_balance }}" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('ending_balance') }}</p>
                                            @endif
                                        </div>

                                        
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <label for="description">Description :</label>
                                            <textarea autocomplete="off" name="description" class="form-control" id="description" cols="30" rows="10"
                                                placeholder="Enter description">{{ $accountNumbers->description }}</textarea>
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
            document.getElementById("accountForm").submit();
        }
    </script>
@endsection