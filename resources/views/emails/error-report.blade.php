<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc2626;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 5px;
            border-left: 4px solid #dc2626;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
        }
        .error-message {
            background-color: #fee2e2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .stack-trace {
            background-color: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table th,
        .info-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 30%;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-error {
            background-color: #dc2626;
            color: white;
        }
        .badge-env {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">⚠️ Error Report</h1>
        <p style="margin: 10px 0 0 0;">
            <span class="badge badge-env">{{ config('app.env') }}</span>
            <span class="badge badge-error">{{ get_class($exception) }}</span>
        </p>
    </div>

    <div class="content">
        <!-- Error Information -->
        <div class="section">
            <div class="section-title">📋 Error Information</div>
            <table class="info-table">
                <tr>
                    <th>Exception Type</th>
                    <td>{{ get_class($exception) }}</td>
                </tr>
                <tr>
                    <th>Error Message</th>
                    <td><strong>{{ $exception->getMessage() }}</strong></td>
                </tr>
                <tr>
                    <th>File</th>
                    <td>{{ $exception->getFile() }}</td>
                </tr>
                <tr>
                    <th>Line</th>
                    <td>{{ $exception->getLine() }}</td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td>{{ now()->format('Y-m-d H:i:s') }}</td>
                </tr>
            </table>
        </div>

        <!-- Application Context -->
        <div class="section">
            <div class="section-title">🔧 Application Context</div>
            <table class="info-table">
                <tr>
                    <th>Environment</th>
                    <td>{{ config('app.env') }}</td>
                </tr>
                <tr>
                    <th>Application Name</th>
                    <td>{{ config('app.name') }}</td>
                </tr>
                <tr>
                    <th>URL</th>
                    <td>{{ $context['url'] ?? config('app.url') }}</td>
                </tr>
                @if(isset($context['method']))
                <tr>
                    <th>HTTP Method</th>
                    <td>{{ $context['method'] }}</td>
                </tr>
                @endif
                @if(isset($context['user_id']))
                <tr>
                    <th>User ID</th>
                    <td>{{ $context['user_id'] }}</td>
                </tr>
                @endif
                @if(isset($context['tenant']))
                <tr>
                    <th>Tenant</th>
                    <td>{{ $context['tenant'] }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Stack Trace -->
        <div class="section">
            <div class="section-title">📚 Stack Trace</div>
            <div class="stack-trace">{{ $exception->getTraceAsString() }}</div>
        </div>

        @if(isset($context['request_data']) && !empty($context['request_data']))
        <!-- Request Data -->
        <div class="section">
            <div class="section-title">📦 Request Data</div>
            <pre class="error-message">{{ json_encode($context['request_data'], JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif
    </div>

    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; border-radius: 5px; text-align: center; font-size: 12px; color: #6b7280;">
        <p style="margin: 0;">This is an automated error report from {{ config('app.name') }}</p>
        <p style="margin: 5px 0 0 0;">Please investigate and resolve this issue as soon as possible.</p>
    </div>
</body>
</html>
