<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Detail Journal</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
        }

        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
        }

        .header .title {
            font-size: 24px;
            font-weight: bold;
            color: rgb(255, 115, 0);
            margin-top: 40px;
        }

        .header .texttype {
            font-size: 18px;
            margin-top: 20px;
            text-align: left;
        }

        .address {
            text-align: right;
            margin-bottom: 30px;
            margin-top: 40px;
            color: grey;
        }

        .transaction-details {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .transaction-details th,
        .transaction-details td {
            padding: 5px;
            border: 1px solid black;
        }

        .transaction-details th {
            text-align: left;
            border-right: 1px solid black;
            /* Tambahkan border right */
        }

        .transaction-details .total {
            font-weight: bold;
        }

        .header-table {
            background-color: rgb(255, 115, 0);
            color: white;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">GREAT CRYSTAL SCHOOL AND COURSE CENTER</div>
        <div class="texttype">Transaction Detail</div>
    </div>

    <table class="transaction-details">
        <thead class="header-table">
            <tr>
                <th>No Transaction</th>
                <th>Account Number</th>
                <th>Account Name</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Date</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDebit = 0;
                $totalKredit = 0;
            @endphp
            @foreach ($transactionDetails as $detail)
                <tr>
                    <td>{{ $detail['no_transaction'] }}</td>
                    <td>{{ $detail['account_number'] }}</td>
                    <td>{{ $detail['account_name'] }}</td>
                    <td>{{ $detail['debit'] > 0 ? 'Rp ' . number_format($detail['debit'], 0, ',', '.') : '0' }}</td>
                    <td>{{ $detail['credit'] > 0 ? 'Rp ' . number_format($detail['credit'], 0, ',', '.') : '0' }}</td>
                    <td>{{ \Carbon\Carbon::parse($detail['date'])->format('j F Y') }}</td>
                    <td>{{ $detail['description'] }}</td>
                </tr>
                @php
                    $totalDebit += $detail['debit'];
                    $totalKredit += $detail['credit'];
                @endphp
            @endforeach
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>{{ 'Rp ' . number_format($totalDebit, 0, ',', '.') }}</strong></td>
                <td><strong>{{ 'Rp ' . number_format($totalKredit, 0, ',', '.') }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="address">
        Great Crystal School, {{ date('l, d F Y') }}, Jl. Raya Darmo Permai III, Surabaya, Indonesia
    </div>

</body>

</html>