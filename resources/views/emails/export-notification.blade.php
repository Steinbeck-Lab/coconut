<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: {{ $status === 'success' ? '#4CAF50' : '#F44336' }}; color: white; padding: 10px; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
        pre { background-color: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>COCONUT Export {{ $status === 'success' ? 'Complete' : 'Failed' }}</h2>
        </div>
        <div class="content">
            @if($status === 'success')
                <h3>Export Process Completed Successfully</h3>
                <p><strong>Server:</strong> {{ $server }}</p>
                <p><strong>Timestamp:</strong> {{ $timestamp }}</p>
                <p>The database export, conversion, and upload process has completed successfully.</p>
                <p>All required files have been generated and uploaded to S3.</p>
            @else
                <h3>Export Process Failed</h3>
                <p><strong>Server:</strong> {{ $server }}</p>
                <p><strong>Timestamp:</strong> {{ $timestamp }}</p>
                <p>The database export process encountered an error:</p>
                <pre>{{ $error_message }}</pre>
                @if(isset($error_details) && $error_details)
                    <p><strong>Detailed Error:</strong></p>
                    <pre>{{ $error_details }}</pre>
                @endif
            @endif
        </div>
        <div class="footer">
            <p>This is an automated message from the COCONUT export system.</p>
        </div>
    </div>
</body>
</html>