<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COCONUT Export Notification</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding: 20px;
            background-color: #6d4c41;
            color: white;
        }
        .logo {
            max-width: 200px;
            margin: 0 auto;
            display: block;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .message {
            margin-bottom: 25px;
        }
        .section {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section-title {
            font-weight: bold;
            font-size: 16px;
            color: #6d4c41;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            flex: 0 0 180px;
            font-weight: bold;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .error-details {
            background-color: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 12px;
            overflow-x: auto;
        }
        .file-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        .file-table th {
            background-color: #f5f5f5;
            text-align: left;
            padding: 8px;
            border-bottom: 2px solid #ddd;
        }
        .file-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .s3-path {
            font-family: monospace;
            background-color: #f5f5f5;
            padding: 5px 8px;
            border-radius: 4px;
            margin-bottom: 5px;
            display: inline-block;
            font-size: 13px;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .success {
            color: #4caf50;
        }
        .error {
            color: #f44336;
        }
        .signature {
            margin-top: 30px;
        }
        @media only screen and (max-width: 600px) {
            .info-row {
                flex-direction: column;
            }
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('img/logo.png') }}" alt="COCONUT Database" class="logo">
            <h1>
                @if($status === 'success')
                    @if(isset($backup_stats['backup_type']) && $backup_stats['backup_type'] === 'Private Daily Backup')
                        COCONUT Daily Backup Complete
                    @else
                        COCONUT Monthly Export Complete
                    @endif
                @else
                    COCONUT Process Failed
                @endif
            </h1>
        </div>
        
        <div class="content">
            <div class="greeting">Hello!</div>
            
            <div class="message">
                @if($status === 'success')
                    @if(isset($backup_stats['backup_type']) && $backup_stats['backup_type'] === 'Private Daily Backup')
                        <p>A new daily backup of the COCONUT database was successfully created.</p>
                    @else
                        <p>The monthly COCONUT database export has been completed successfully. This includes both a private backup and public download files.</p>
                    @endif
                @else
                    <p>The COCONUT database process has encountered an error.</p>
                @endif
            </div>
            
            <!-- Basic Information Section -->
            <div class="section">
                <div class="section-title">Basic Information</div>
                
                <div class="info-row">
                    <div class="info-label">Application name:</div>
                    <div class="info-value">COCONUT Database</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Server:</div>
                    <div class="info-value">{{ $server }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Operation type:</div>
                    <div class="info-value">{{ $backup_stats['backup_type'] ?? 'Unknown' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Timestamp:</div>
                    <div class="info-value">{{ $timestamp }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="{{ $status === 'success' ? 'success' : 'error' }}">
                            {{ $status === 'success' ? 'Successful' : 'Failed' }}
                        </span>
                    </div>
                </div>
            </div>
            
            @if($status === 'success' && isset($backup_stats))
            <!-- Statistics Section -->
            <div class="section">
                <div class="section-title">Operation Statistics</div>
                
                @if(isset($backup_stats['total_molecules']))
                <div class="info-row">
                    <div class="info-label">Total molecules:</div>
                    <div class="info-value">{{ number_format($backup_stats['total_molecules']) }}</div>
                </div>
                @endif
                
                @if(isset($backup_stats['total_collections']))
                <div class="info-row">
                    <div class="info-label">Total collections:</div>
                    <div class="info-value">{{ number_format($backup_stats['total_collections']) }}</div>
                </div>
                @endif
                
                <div class="info-row">
                    <div class="info-label">Total files generated:</div>
                    <div class="info-value">{{ number_format($backup_stats['total_files'] ?? 0) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Total size:</div>
                    <div class="info-value">{{ $backup_stats['total_size_mb'] ?? 0 }} MB</div>
                </div>
            </div>
            
            <!-- Files Section -->
            @if(isset($backup_stats['file_details']) && count($backup_stats['file_details']) > 0)
            <div class="section">
                <div class="section-title">Generated Files</div>
                
                <table class="file-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backup_stats['file_details'] as $file)
                        <tr>
                            <td>{{ $file['name'] }}</td>
                            <td>{{ $file['type'] }}</td>
                            <td>{{ $file['size_mb'] }} MB</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            
            <!-- Storage Location Section -->
            @if(isset($backup_stats['s3_paths']) && count($backup_stats['s3_paths']) > 0)
            <div class="section">
                <div class="section-title">Storage Locations</div>
                
                @if(isset($backup_stats['backup_type']) && $backup_stats['backup_type'] === 'Private Daily Backup')
                    <div>Files have been backed up to:</div>
                @else
                    <div>Files have been uploaded to the following locations:</div>
                    <div style="margin-top: 5px; margin-bottom: 10px;">
                        <strong>Note:</strong> Public download files are available at paths starting with "prod/downloads/"
                    </div>
                @endif
                <div style="margin-top: 10px;">
                    @foreach($backup_stats['s3_paths'] as $path)
                    <div class="s3-path">{{ $path }}</div>
                    @endforeach
                </div>
            </div>
            @endif
            
            @else
                @if($status !== 'success')
                <!-- Error Section -->
                <div class="section">
                    <div class="section-title">Error Information</div>
                    
                    @if($error_message)
                    <div class="info-row">
                        <div class="info-label">Error message:</div>
                        <div class="info-value">{{ $error_message }}</div>
                    </div>
                    @endif
                    
                    @if($error_details)
                    <div class="info-row">
                        <div class="info-label">Error details:</div>
                        <div class="info-value">
                            <div class="error-details">{{ $error_details }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            @endif
            
            <div class="signature">
                <p>Regards,<br>COCONUT Export System</p>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} COCONUT Database. All rights reserved.</p>
            <p>This is an automated message from the COCONUT export system.</p>
        </div>
    </div>
</body>
</html>