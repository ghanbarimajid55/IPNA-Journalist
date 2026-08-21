<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آفلاین | IPNA PMS</title>
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
        .offline-container {
            text-align: center;
            padding: 3rem;
            max-width: 480px;
        }
        .offline-icon {
            font-size: 6rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .offline-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 1rem;
        }
        .offline-message {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.8;
        }
        .features-list {
            text-align: right;
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .features-list h5 {
            font-size: 1rem;
            color: #1a237e;
            margin-bottom: 1rem;
        }
        .features-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .features-list li {
            padding: 0.5rem 0;
            color: #6c757d;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .features-list li i {
            color: #2e7d32;
        }
        .btn-retry {
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
        .btn-retry:hover {
            background-color: #0d1642;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">
            <i class="bi bi-wifi-off"></i>
        </div>
        <h1 class="offline-title">ارتباط با شبکه قطع است</h1>
        <p class="offline-message">
            شما در حال حاضر به اینترنت دسترسی ندارید.
            <br>
            لطفاً اتصال شبکه خود را بررسی کنید و دوباره تلاش کنید.
        </p>
        <div class="features-list">
            <h5><i class="bi bi-check-circle"></i> امکانات موجود در حالت آفلاین:</h5>
            <ul>
                <li><i class="bi bi-check2"></i> مشاهده داشبورد آخرین وضعیت</li>
                <li><i class="bi bi-check2"></i> دسترسی به گزارش‌های ذخیره‌شده</li>
                <li><i class="bi bi-check2"></i> مشاهده لیست خبرنگاران</li>
            </ul>
        </div>
        <button onclick="location.reload()" class="btn-retry">
            <i class="bi bi-arrow-clockwise"></i>
            تلاش مجدد
        </button>
    </div>

    <script>
        window.addEventListener('online', () => {
            location.reload();
        });
    </script>
</body>
</html>
