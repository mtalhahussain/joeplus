<!DOCTYPE html>
<html>
<head>
    <title>Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #333;
        }
        p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>You're Invited!</h2>
        <p>You have been invited to join {{ $invitation->project_id ? 'a project' : 'a task' }}.</p>
        <a href="{{ url('/api/v1').'/accept-invite/'.$invitation->token }}" class="btn">
            Accept Invitation
        </a>
        <p class="footer">If you did not request this invitation, please ignore this email.</p>
    </div>
</body>
</html>
