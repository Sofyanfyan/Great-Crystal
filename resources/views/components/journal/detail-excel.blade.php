<!DOCTYPE html>
<html>

<head>
    <title>Journal Details</title>
</head>

<body>
    <table>
        <thead>
            <tr>
                <th>No Trans.</th>
                <th>Account Number</th>
                <th>Account Name</th>
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
            @foreach ($transactionDetails as $details)
                @foreach ($details as $detail)
                    <tr>
                        <td>{{ $detail['no_transaction'] }}</td>
                        <td>{{ $detail['account_number'] }}</td>
                        <td>{{ $detail['account_name'] }}</td>
                        <td>{{ $detail['debit'] }}</td>
                        <td>{{ $detail['credit'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($detail['date'])->format('j F Y') }}</td>
                        <td>{{ $detail['description'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($detail['created_at'])->format('j F Y') }}</td>
                    </tr>
                    @php
                        $totalDebit += $detail['debit'];
                        $totalKredit += $detail['credit'];
                    @endphp
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>{{ 'Rp ' . number_format($totalDebit, 0, ',', '.') }}</strong></td>
                <td><strong>{{ 'Rp ' . number_format($totalKredit, 0, ',', '.') }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>

</html>