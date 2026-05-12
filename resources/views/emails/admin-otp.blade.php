<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #495057;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #1b1b1b;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .header p {
            color: #94a3b8;
            margin: 5px 0 0;
            font-size: 14px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .otp-container {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 700;
            color: #22c55e; /* Success color matching Rizz template feel */
            letter-spacing: 8px;
            margin: 0;
        }
        .type-label {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            background-color: #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .footer {
            background-color: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0084ff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Xác thực Quản trị</h1>
            <p>Hệ thống quản trị Chat App</p>
        </div>
        <div class="content">
            <span class="type-label">
                @if($type === 'reset')
                    Khôi phục mật khẩu
                @elseif($type === 'change')
                    Đổi mật khẩu
                @else
                    Xác thực đăng ký
                @endif
            </span>
            <p style="margin-top: 0;">Xin chào,</p>
            <p>Bạn đã yêu cầu mã xác thực OTP cho tài khoản Admin. Vui lòng sử dụng mã bên dưới:</p>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <p style="color: #ef4444; font-size: 13px; font-weight: 500;">
                Mã này sẽ hết hạn sau <strong>1 phút</strong>.
            </p>
            <p style="font-size: 13px; color: #64748b;">Nếu bạn không yêu cầu thay đổi này, bạn có thể an tâm bỏ qua email này.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Chat App Admin. All rights reserved.
        </div>
    </div>
</body>
</html>
