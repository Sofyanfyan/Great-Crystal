@extends('layouts.admin.master')
@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="row d-flex justify-content-center">
                <div class="col-md-12">
                    <div>
                        <form id="accountForm" method="POST" action="{{ route('balance.store') }}">
                            @csrf
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Create Balance</h3>
                                </div>
                                <div class="card-body" id="dynamic-form">
                                    <!-- Initial Form Row -->
                                    <div class="form-group row dynamic-row">
                                        <div class="col-md-3">
                                            <label>Account <span style="color: red">*</span> :</label>
                                            <select name="accountnumber_id[]" class="form-control select2">
                                                @foreach ($accountNumbers as $accountNumber)
                                                    <option value="{{ $accountNumber->id }}">
                                                        {{ $accountNumber->account_no }} -
                                                        {{ $accountNumber->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="debit">Debit<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="debit[]" type="text" class="form-control currency"
                                                    placeholder="Enter amount" autocomplete="off" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('debit') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-3">
                                            <label for="credit">Credit<span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input name="credit[]" type="text" class="form-control currency"
                                                    placeholder="Enter amount" autocomplete="off" required>
                                            </div>
                                            @if ($errors->any())
                                                <p style="color: red">{{ $errors->first('credit') }}</p>
                                            @endif
                                        </div>

                                        <div class="col-md-3">
                                            <label for="month">Post Month <span style="color: red">*</span> :</label>
                                            <div class="input-group">
                                                <input type="month" name="month[]" class="form-control"
                                                    style="margin-right: 12px" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-remove-row">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-md-6" style="margin-left: 20px">
                                            <button type="button" class="btn btn-primary btn-sm btn-add-row"
                                                style="margin-right: 7px">+ Add
                                                Row</button>
                                            <button type="submit" class="btn btn-success btn-sm">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        //     const dynamicForm = document.getElementById('dynamic-form');
        //     const addButton = document.querySelector('.btn-add-row');

        //     addButton.addEventListener('click', function() {
        //         addNewRow();
        //         initializeSelect2(); // Initialize select2 after adding new row
        //         initializeAutoNumeric(); // Initialize autoNumeric for new rows
        //     });

        //     dynamicForm.addEventListener('click', function(e) {
        //         if (e.target && (e.target.classList.contains('btn-remove-row') || e.target.closest(
        //                 '.btn-remove-row'))) {
        //             e.target.closest('.dynamic-row').remove();
        //         }
        //     });

        //     function addNewRow() {
        //         const newRow = `
    //             <div class="form-group row dynamic-row">
    //                 <div class="col-md-3">
    //                     <label>Account <span style="color: red">*</span> :</label>
    //                     <select name="accountnumber_id[]" class="form-control select2">
    //                         @foreach ($accountNumbers as $accountNumber)
    //                             <option value="{{ $accountNumber->id }}">
    //                                 {{ $accountNumber->account_no }} -
    //                                 {{ $accountNumber->name }}</option>
    //                         @endforeach
    //                     </select>
    //                 </div>

    //                 <div class="col-md-3">
    //                     <label for="debit">Debit<span style="color: red">*</span> :</label>
    //                     <div class="input-group">
    //                         <div class="input-group-prepend">
    //                             <span class="input-group-text">Rp.</span>
    //                         </div>
    //                         <input name="debit[]" type="text" class="form-control currency" placeholder="Enter amount" autocomplete="off" required>
    //                     </div>
    //                 </div>
    //                 <div class="col-md-3">
    //                     <label for="credit">Credit<span style="color: red">*</span> :</label>
    //                     <div class="input-group">
    //                         <div class="input-group-prepend">
    //                             <span class="input-group-text">Rp.</span>
    //                         </div>
    //                         <input name="credit[]" type="text" class="form-control currency" placeholder="Enter amount" autocomplete="off" required>
    //                     </div>
    //                 </div>

    //                 <div class="col-md-3">
    //                     <label for="month">Post Month <span style="color: red">*</span> :</label>
    //                     <div class="input-group">
    //                         <input type="month" name="month[]" class="form-control" style="margin-right: 12px" required>
    //                             <div class="input-group-append">
    //                                 <button type="button" class="btn btn-remove-row">
    //                                     <i class="fa fa-trash"></i>
    //                                 </button>
    //                             </div>
    //                     </div>
    //                 </div>
    //             </div>
    //         `;

        //         dynamicForm.insertAdjacentHTML('beforeend', newRow);
        //     }

        //     function initializeSelect2() {
        //         $('.select2').select2(); // Initialize select2 for new rows
        //     }

        //     function initializeAutoNumeric() {
        //         AutoNumeric.multiple('.currency', {
        //             currencySymbol: 'Rp.',
        //             decimalCharacter: ',',
        //             digitGroupSeparator: '.',
        //             decimalPlaces: 0,
        //         });
        //     }

        //     // Initialize select2 and autoNumeric for existing rows on page load
        //     initializeSelect2();
        //     initializeAutoNumeric();

        //     // Remove thousand separators before form submit
        //     document.getElementById('accountForm').addEventListener('submit', function() {
        //         const currencyInputs = document.querySelectorAll('.currency');
        //         currencyInputs.forEach(input => {
        //             input.value = input.value.replace(/\./g, ''); // Remove all thousand separators
        //         });
        //     });
        // });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize select2 and autoNumeric for existing rows on page load
            initializeSelect2();
            initializeAutoNumeric();

            const dynamicForm = document.getElementById('dynamic-form');
            const addButton = document.querySelector('.btn-add-row');

            addButton.addEventListener('click', function() {
                addNewRow();
                initializeSelect2(); // Initialize select2 after adding new row
                initializeAutoNumeric(); // Initialize autoNumeric for new rows
            });

            dynamicForm.addEventListener('click', function(e) {
                if (e.target && (e.target.classList.contains('btn-remove-row') || e.target.closest(
                        '.btn-remove-row'))) {
                    e.target.closest('.dynamic-row').remove();
                }
            });

            function addNewRow() {
                const newRow = `
            <div class="form-group row dynamic-row">
                <div class="col-md-3">
                    <label>Account <span style="color: red">*</span> :</label>
                    <select name="accountnumber_id[]" class="form-control select2">
                        @foreach ($accountNumbers as $accountNumber)
                            <option value="{{ $accountNumber->id }}">
                                {{ $accountNumber->account_no }} - {{ $accountNumber->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="debit">Debit<span style="color: red">*</span> :</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp.</span>
                        </div>
                        <input name="debit[]" type="text" class="form-control currency" placeholder="Enter amount" autocomplete="off" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="credit">Credit<span style="color: red">*</span> :</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Rp.</span>
                        </div>
                        <input name="credit[]" type="text" class="form-control currency" placeholder="Enter amount" autocomplete="off" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="month">Post Month <span style="color: red">*</span> :</label>
                    <div class="input-group">
                        <input type="month" name="month[]" class="form-control" style="margin-right: 12px" required>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-remove-row">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                    </div>
                </div>
            </div>
        `;
                dynamicForm.insertAdjacentHTML('beforeend', newRow);
            }

            function initializeSelect2() {
                $('.select2').select2(); // Initialize select2 for new rows
            }

            function initializeAutoNumeric() {
                AutoNumeric.multiple('.currency', {
                    currencySymbol: 'Rp.',
                    decimalCharacter: ',',
                    digitGroupSeparator: '.',
                    decimalPlaces: 0,
                });
            }

            // Remove thousand separators before form submit
            document.getElementById('accountForm').addEventListener('submit', function() {
                const currencyInputs = document.querySelectorAll('.currency');
                currencyInputs.forEach(input => {
                    input.value = input.value.replace(/\./g, ''); // Remove all thousand separators
                });
            });
        });
    </script>
@endsection