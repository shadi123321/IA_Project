<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>

<h3>Daily Complaints Monitoring Report</h3>
<p>Date: {{ $date }}</p>

<table>
    <thead>
        <tr>
            <th>Reference</th>
            <th>Citizen</th>
            <th>Status</th>
            <th>Note</th>
            <th>Handled By</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
            <tr>
                <td>{{ $row->reference_number }}</td>
                <td>{{ $row->citizen_name }}</td>
                <td>{{ $row->status }}</td>
                <td>{{ $row->note }}</td>
                <td>{{ $row->handled_by_employee ?? '-' }}</td>
                <td>{{ $row->changed_at }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
