@extends('layouts.admin.master')
@section('content')
    <!-- Content Wrapper. Contains page content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="card card-dark mt-5">
                        <div class="card-header">
                            <h3 class="card-title">Transaction Detail</h3>
                        </div>
                        <div class="card-body">
                            <table class="table projects">
                                <thead>
                                    <tr>
                                        <th>No Transaction</th>
                                        <th>Account Number</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalKredit = 0;
                                    @endphp
                                    @foreach ($transactionDetails as $detail)
                                        <tr>
                                            <td>{{ $transaction->no_transaction }}</td>
                                            <td>{{ $detail['account_number'] }} - {{ $detail['account_name'] }}</td>
                                            <td>{{ $detail['debit'] > 0 ? 'Rp ' . number_format($detail['debit'], 0, ',', '.') : '0' }}
                                            </td>
                                            <td>{{ $detail['credit'] > 0 ? 'Rp ' . number_format($detail['credit'], 0, ',', '.') : '0' }}
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($detail['date'])->format('j F Y') }}</td>
                                            <td>{{ $detail['description'] }}</td>
                                            <td>{{ \Carbon\Carbon::parse($detail['created_at'])->format('j F Y') }}</td>
                                        </tr>
                                        @php
                                            $totalDebit += $detail['debit'];
                                            $totalKredit += $detail['credit'];
                                        @endphp
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td><strong>{{ $totalDebit > 0 ? 'Rp ' . number_format($totalDebit, 0, ',', '.') : '0' }}</strong>
                                        </td>
                                        <td><strong>{{ $totalKredit > 0 ? 'Rp ' . number_format($totalKredit, 0, ',', '.') : '0' }}</strong>
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <a href="{{ route('journal.detail.pdf', ['id' => $transaction->id, 'type' => $type]) }}"
                                target="_blank" class="btn btn-warning btn-sm mt-2" id="print-pdf">
                                <i class="fa-solid fa-file-pdf fa-bounce"
                                    style="color: #000000; margin-right:2px;"></i>Print PDF
                            </a>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection