
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>COCONUT {{ $event->report->report_category }} request Assigned</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;">
	<div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
		<div style="text-align: center; padding: 20px; background-color: #6d4c41; color: white;">
			<img src="{{ asset('img/logo.png') }}" alt="COCONUT Database" style="max-width: 200px; margin: 0 auto; display: block;">
			<h1 style="color: white; margin: 10px 0 0 0; font-size: 24px;">{{ $event->report->report_category }} request Assigned</h1>
		</div>
		<div style="padding: 30px;">
			<div style="font-size: 20px; font-weight: bold; margin-bottom: 20px; color: #555;">Hello {{ $curator->name }}!</div>
			<div style="margin-bottom: 25px; color: #555;">
				<p style="margin: 0; line-height: 1.6;">As a COCONUT curator, you have been assigned a {{ $event->report->report_category }} request. Please review and leave your comments.</p>
			</div>
			<!-- Details Section -->
			<div style="margin-bottom: 18px; padding-bottom: 18px;">
				<div style="color: #6d4c41; margin-bottom: 12px; font-weight: bold;">
					Title: <span style="font-weight: 500; color: #555;">{{ $event->report->title }}</span>
				</div>
			</div>
			<div style="text-align: center;">
				<a href="{{ $url }}" style="display: inline-block; padding: 12px 30px; background-color: #6d4c41; color: white; text-decoration: none; border-radius: 4px;">
					To the Report
				</a>
			</div>
			<div style="margin-top: 36px; color: #555;">
				<p style="margin: 0; line-height: 1.6;">Thanks,<br>{{ config('app.name') }} Team</p>
			</div>
		</div>
		<div style="background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #777;">
			<p style="margin: 0 0 10px 0;">&copy; {{ date('Y') }} COCONUT Database. All rights reserved.</p>
			<p style="margin: 0;">This is an automated message from the COCONUT system.</p>
		</div>
	</div>
</body>
</html>
