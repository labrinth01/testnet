<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php";
$APPLICATION->SetTitle("Магазин");
?>
<section style="max-width:720px;margin:48px auto;padding:0 16px;font-family:IBM Plex Sans,Segoe UI,sans-serif;color:#161616;background:#fff;">
    <p style="letter-spacing:.16em;text-transform:uppercase;font-size:11px;color:#6b6b6b;">Bitrix Store / тестовое задание</p>
    <h1 style="font-size:clamp(32px,5vw,48px);letter-spacing:-.03em;font-weight:500;margin:8px 0 16px;">Интернет-магазин с контролем качества каталога</h1>
    <p style="color:#6b6b6b;line-height:1.5;max-width:54ch;">20 тестовых товаров. Отдельный компонент Битрикс считает SEO-готовность каждой карточки.</p>
    <p style="margin-top:28px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="/seo-report/" style="display:inline-flex;align-items:center;min-height:44px;padding:0 20px;background:#c41e3a;color:#fff;text-decoration:none;border-radius:999px;">Открыть SEO-отчёт</a>
        <a href="/catalog/" style="display:inline-flex;align-items:center;min-height:44px;padding:0 20px;background:#fff;color:#161616;text-decoration:none;border:1px solid #e6e6e6;border-radius:999px;">Каталог</a>
    </p>
</section>
<?php require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"; ?>
