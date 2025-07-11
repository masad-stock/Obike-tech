<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rental Agreement #{{ $agreement->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 22px;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 16px;
            margin-bottom: 15px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
        .signatures {
            margin-top: 50px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            display: inline-block;
            margin: 0 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>OBIKE TECH SYSTEMS</h1>
        <h2>EQUIPMENT RENTAL AGREEMENT</h2>
        <p>Agreement #: {{ $agreement->id }} | Date: {{ $agreement->created_at->format('F d, Y') }}</p>
    </div>
    
    <div class="section">
        <div class="section-title">CUSTOMER INFORMATION</div>
        <table>
            <tr>
                <th width="25%">Name:</th>
                <td>{{ $agreement->customer->name }}</td>
                <th width="25%">Phone:</th>
                <td>{{ $agreement->customer->phone }}</td>
            </tr>
            <tr>
                <th>Email:</th>
                <td>{{ $agreement->customer->email }}</td>
                <th>ID/License:</th>
                <td>{{ $agreement->customer->id_number }}</td>
            </tr>
            <tr>
                <th>Address:</th>
                <td colspan="3">{{ $agreement->customer->address }}</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">RENTAL DETAILS</div>
        <table>
            <tr>
                <th width="25%">Start Date:</th>
                <td>{{ $agreement->start_date->format('F d, Y') }}</td>
                <th width="25%">End Date:</th>
                <td>{{ $agreement->end_date->format('F d, Y') }}</td>
            </tr>
            <tr>
                <th>Duration:</th>
                <td>{{ $agreement->start_date->diffInDays($agreement->end_date) }} days</td>
                <th>Status:</th>
                <td>{{ ucfirst($agreement->status) }}</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">EQUIPMENT RENTED</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Serial/ID</th>
                    <th>Daily Rate</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agreement->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->rentalItem->name }}</td>
                    <td>{{ $item->rentalItem->serial_number }}</td>
                    <td>${{ number_format($item->daily_rate, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->daily_rate * $item->quantity * $agreement->start_date->diffInDays($agreement->end_date), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" style="text-align: right">Total Rental Amount:</th>
                    <th>${{ number_format($agreement->total_amount, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="5" style="text-align: right">Deposit Amount:</th>
                    <th>${{ number_format($agreement->deposit_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">TERMS AND CONDITIONS</div>
        <ol>
            <li>The Renter agrees to pay for any damage to or loss of the equipment regardless of cause.</li>
            <li>The Renter agrees not to use the equipment in a manner contrary to its intended use.</li>
            <li>Late returns will be charged at 150% of the daily rate for each day past the return date.</li>
            <li>The deposit will be refunded upon return of all equipment in good condition.</li>
            <li>Cancellation within 24 hours of the rental start time will result in a 50% charge of the total rental amount.</li>
        </ol>
    </div>
    
    <div class="signatures">
        <div class="signature-line">
            <p>Customer Signature</p>
        </div>
        <div class="signature-line">
            <p>Company Representative</p>
            <p>{{ $agreement->createdBy->name }}</p>
        </div>
    </div>
    
    <div class="footer">
        <p>Obike Tech Systems | 123 Main Street, City, Country | Phone: (123) 456-7890 | Email: info@obiketech.com</p>
        <p>This document was generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>