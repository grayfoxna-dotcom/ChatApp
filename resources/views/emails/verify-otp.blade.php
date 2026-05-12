<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực OTP - Chat App</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #F0F2F5;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .header {
            background: linear-gradient(135deg, #0084FF 0%, #9B51E0 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header img {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px;
            text-align: center;
            color: #050505;
        }
        .content p {
            font-size: 16px;
            line-height: 1.6;
            color: #8E8E93;
            margin-bottom: 32px;
        }
        .otp-container {
            background-color: #F0F2F5;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #0084FF;
            font-family: 'Courier New', Courier, monospace;
        }
        .footer {
            padding: 20px;
            text-align: center;
            background-color: #F9FAFB;
            border-top: 1px solid #F0F2F5;
        }
        .footer p {
            font-size: 13px;
            color: #8E8E93;
            margin: 0;
        }
        .security-note {
            font-size: 12px !important;
            color: #FF3B30 !important;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Xác thực tài khoản</h1>
        </div>
        <div class="content">
            <p>Chào bạn!<br>Chào mừng bạn đến với <strong>Chat App</strong>. Vui lòng sử dụng mã bên dưới để xác thực địa chỉ email của bạn:</p>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otpCode }}</div>
            </div>
            
            <p>Mã này sẽ hết hạn trong vòng <strong>1 phút</strong>. Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
            
            <p class="security-note">Bảo mật: Tuyệt đối không chia sẻ mã này với bất kỳ ai.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 Chat App Team. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
