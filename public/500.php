<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطای سرور - 500 | IPNA PMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * { font-family: 'Vazirmatn', sans-serif; }
        body {
            background-color: #f5f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 3rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            color: #c62828;
            line-height: 1;
            opacity: 0.15;
        }
        .error-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #c62828;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        .error-details {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            text-align: right;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .error-details h5 {
            font-size: 1rem;
            color: #c62828;
            margin-bottom: 0.75rem;
        }
        .error-details p {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 0;
            line-height: 1.8;
        }
        .btn-home {
            background-color: #1a237e;
            color: #fff;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-home:hover {
            background-color: #0d1642;
            color: #fff;
        }
        .btn-retry {
            background-color: transparent;
            color: #1a237e;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #1a237e;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 1rem;
        }
        .btn-retry:hover {
            background-color: #1a237e;
            color: #fff;
        }
        .illustration {
            font-size: 6rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="illustration">
            <i class="bi bi-server text-danger"></i>
        </div>
        <div class="error-code">500</div>
        <h1 class="error-title">خطای داخلی سرور</h1>
        <p class="error-message">
            متأسفانه مشکلی در سرور رخ داده است.
            <br>
            تیم فنی از این مشکل مطلع شده و در حال رفع آن هستند.
        </p>
        <div class="error-details">
            <h5><i class="bi bi-info-circle"></i> راهنمای رفع مشکل</h5>
            <p>
                در صورت تکرار مشکل، لطفاً با مدیر سیستم تماس بگیرید.
                <br>
                کد پیگیری: <?= date('Ymd') . '-' . substr(md5(uniqid()), 0, 8) ?>
            </p>
        </div>
        <div>
            <a href="javascript:location.reload()" class="btn-retry">
                <i class="bi bi-arrow-clockwise"></i>
                تلاش مجدد
            </a>
            <a href="/" class="btn-home">
                <i class="bi bi-house-door"></i>
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
</body>
</html>
