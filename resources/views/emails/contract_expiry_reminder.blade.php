<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Expiry Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 20px 0;
        }
        .alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .alert.urgent {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 15px 0;
            border-radius: 3px;
        }
        .details {
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            text-align: right;
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Contract Expiry Reminder</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $company->name }}</strong>,</p>

            <div class="alert {{ $daysRemaining <= 7 ? 'urgent' : '' }}">
                <strong>⏰ Important Notice:</strong> Your contract will expire in <strong>{{ $daysRemaining }} day(s)</strong>.
            </div>

            <p>This is a friendly reminder that the following contract is approaching its expiration date:</p>

            <div class="info-box">
                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">Contract Name:</span>
                        <span class="detail-value">{{ $contract->name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contract Type:</span>
                        <span class="detail-value">{{ ucfirst($contract->type) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Start Date:</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Expiration Date:</span>
                        <span class="detail-value"><strong>{{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</strong></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Current Status:</span>
                        <span class="detail-value">{{ ucfirst($contract->status) }}</span>
                    </div>
                </div>
            </div>

            <p><strong>Recommended Actions:</strong></p>
            <ul>
                <li>Review the contract terms and conditions</li>
                <li>Prepare for renewal or termination as needed</li>
                <li>Reach out to your account manager if you need to discuss renewal options</li>
            </ul>

            <p>If you have any questions or need to take action on this contract, please contact us as soon as possible.</p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} All Rights Reserved. Contact us for more information.</p>
        </div>
    </div>
</body>
</html>
