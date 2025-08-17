<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>站点维护中</title>
    <style>
        html,body{height:100%;margin:0}
        body{display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#111;font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;}
        .card{max-width:720px;padding:36px;border-radius:12px;text-align:center;background:white;box-shadow:0 8px 30px rgba(2,6,23,0.08)}
        h1{margin:0 0 12px;font-size:28px;color:#0f172a}
        p{margin:0;color:#475569;font-size:16px}
        .en{display:block;margin-top:8px;color:#94a3b8;font-size:12px}
        .logo{height:48px;margin-bottom:18px}
    </style>
</head>
<body>
    <div class="card" role="main" aria-labelledby="title">
    <img src="{{ asset('images/poemwiki.svg') }}" alt="{{ config('app.name', '站点') }}" class="logo" onerror="this.style.display='none'">
        <h1 id="title">站点正在维护，马上回来</h1>
        <p>我们正在进行短暂维护以提升服务质量，请稍后重试。</p>
        <span class="en">We are performing a short maintenance to improve the site — we'll be back shortly.</span>
    </div>
</body>
</html>
