<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang bảo trì | RoyalBread</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1412 0%, #2d1f1a 40%, #1a1412 100%);
            color: #f5efe9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .maintenance-card {
            max-width: 520px;
            width: 100%;
            text-align: center;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(212, 175, 125, 0.15);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4);
        }
        
        .maintenance-icon {
            font-size: 3.5rem;
            margin-bottom: 1.2rem;
            display: block;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.85; }
        }
        
        .maintenance-card h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: #d4af7d;
            margin-bottom: 0.8rem;
            letter-spacing: -0.02em;
        }
        
        .maintenance-card p {
            font-size: 0.95rem;
            line-height: 1.7;
            color: rgba(245, 239, 233, 0.7);
            margin-bottom: 1rem;
        }
        
        .hotline-box {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            margin-top: 1.5rem;
            padding: 0.9rem 1.6rem;
            background: linear-gradient(135deg, rgba(212, 175, 125, 0.15), rgba(212, 175, 125, 0.08));
            border: 1px solid rgba(212, 175, 125, 0.25);
            border-radius: 12px;
            color: #d4af7d;
            font-weight: 600;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.25s ease;
        }
        
        .hotline-box:hover {
            background: linear-gradient(135deg, rgba(212, 175, 125, 0.25), rgba(212, 175, 125, 0.15));
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(212, 175, 125, 0.15);
        }
        
        .hotline-box svg {
            flex-shrink: 0;
        }
        
        .brand-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(212, 175, 125, 0.1);
            font-size: 0.8rem;
            color: rgba(245, 239, 233, 0.35);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <span class="maintenance-icon">🛠️</span>
        <h1>Hệ thống đang bảo trì</h1>
        <p>
            RoyalBread đang nâng cấp hệ thống để phục vụ bạn tốt hơn.
            Xin lỗi vì sự bất tiện này — bạn có thể đặt hàng trực tiếp qua Hotline nhé!
        </p>
        <p>
            Chúng mình sẽ quay lại hoạt động bình thường trong thời gian sớm nhất.
        </p>
        <a href="tel:0879866636" class="hotline-box">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            0879 866 636
        </a>
        <div class="brand-footer">RoyalBread — Bánh mì chảo Hoàng Gia</div>
    </div>
</body>
</html>
