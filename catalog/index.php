<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php";
$APPLICATION->SetTitle("Каталог товаров");

if (!CModule::IncludeModule("iblock")) {
    echo '<div style="max-width:1100px;margin:40px auto;padding:20px;">Модуль инфоблоков недоступен.</div>';
    require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";
    die();
}

$iblockId = defined("SEO_REPORT_IBLOCK_ID") ? intval(SEO_REPORT_IBLOCK_ID) : 0;
if ($iblockId <= 0) {
    $rs = CIBlock::GetList(array(), array("TYPE" => "catalog", "CODE" => "demo_shop", "CHECK_PERMISSIONS" => "N"));
    if ($row = $rs->Fetch()) {
        $iblockId = intval($row["ID"]);
    }
}

function catalogH($value) {
    return htmlspecialcharsbx((string)$value);
}

function catalogMoney($value) {
    return number_format(intval($value), 0, ".", " ")." ₽";
}

echo '<style>
.catalog-page{max-width:1180px;margin:0 auto;padding:34px 18px 60px;color:#171717;font-family:Arial,sans-serif}
.catalog-top{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:28px}
.catalog-kicker{font-size:12px;color:#777;text-transform:uppercase;letter-spacing:.12em;margin:0 0 8px}
.catalog-title{font-size:36px;line-height:1.1;font-weight:700;margin:0}
.catalog-links{display:flex;gap:10px;flex-wrap:wrap}.catalog-link{display:inline-flex;align-items:center;min-height:42px;padding:0 16px;border:1px solid #ddd;border-radius:10px;text-decoration:none;color:#222;background:#fff}.catalog-link--primary{background:#1677ff;border-color:#1677ff;color:#fff}
.catalog-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.catalog-card{display:block;border:1px solid #e6e6e6;border-radius:16px;overflow:hidden;background:#fff;text-decoration:none;color:#171717;transition:transform .15s ease,box-shadow .15s ease}
.catalog-card:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(0,0,0,.08)}
.catalog-photo{height:245px;background:#fff;display:flex;align-items:center;justify-content:center;border-bottom:1px solid #eee}.catalog-photo img{width:100%;height:100%;object-fit:contain;display:block}.catalog-no-photo{color:#999;font-size:13px}
.catalog-body{padding:16px}.catalog-name{font-size:17px;line-height:1.3;margin:0 0 9px;font-weight:600}.catalog-price{font-size:20px;font-weight:700;margin:0}.catalog-hint{font-size:13px;color:#777;margin:10px 0 0}
@media(max-width:900px){.catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-title{font-size:30px}}
@media(max-width:520px){.catalog-top{display:block}.catalog-links{margin-top:16px}.catalog-grid{grid-template-columns:1fr}.catalog-photo{height:270px}}
</style>';

echo '<div class="catalog-page">';
echo '<div class="catalog-top"><div><p class="catalog-kicker">TechStore / demo catalog</p><h1 class="catalog-title">Каталог товаров</h1></div><div class="catalog-links"><a class="catalog-link" href="/">Главная</a><a class="catalog-link catalog-link--primary" href="/seo-report/">SEO-отчёт</a></div></div>';

if ($iblockId <= 0) {
    echo '<p>Каталог ещё не создан. Откройте <a href="/local/tools/seed_catalog.php">сидер</a> под администратором.</p></div>';
    require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";
    die();
}

$res = CIBlockElement::GetList(
    array("SORT" => "ASC", "ID" => "ASC"),
    array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"),
    false,
    false,
    array("ID","NAME","CODE","PREVIEW_PICTURE","DETAIL_PICTURE","PROPERTY_PRICE","PROPERTY_BRAND")
);

echo '<div class="catalog-grid">';
while ($item = $res->GetNext()) {
    $pictureId = $item["PREVIEW_PICTURE"] ? $item["PREVIEW_PICTURE"] : $item["DETAIL_PICTURE"];
    $src = "";
    if ($pictureId) {
        $f = CFile::GetFileArray($pictureId);
        if ($f) $src = $f["SRC"];
    }
    $url = "/catalog/detail.php?code=" . rawurlencode($item["CODE"]);
    echo '<a class="catalog-card" href="'.catalogH($url).'">';
    echo '<div class="catalog-photo">';
    if ($src) echo '<img src="'.catalogH($src).'" alt="'.catalogH($item["NAME"]).'">';
    else echo '<span class="catalog-no-photo">Фото не заполнено</span>';
    echo '</div>';
    echo '<div class="catalog-body">';
    echo '<h2 class="catalog-name">'.catalogH($item["NAME"]).'</h2>';
    if ($item["PROPERTY_BRAND_VALUE"] !== "") echo '<div class="catalog-hint">'.catalogH($item["PROPERTY_BRAND_VALUE"]).'</div>';
    echo '<p class="catalog-price">'.catalogMoney($item["PROPERTY_PRICE_VALUE"]).'</p>';
    echo '<p class="catalog-hint">Открыть карточку →</p>';
    echo '</div></a>';
}
echo '</div></div>';

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";
