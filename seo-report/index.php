<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php";
$APPLICATION->SetTitle("SEO-отчёт по товарам");
$APPLICATION->SetPageProperty("description", "Отчёт SEO-готовности карточек каталога");
?>
<?php
$APPLICATION->IncludeComponent(
    "custom:catalog.seo.report",
    ".default",
    array(
        "IBLOCK_TYPE" => "catalog",
        "IBLOCK_ID" => defined("SEO_REPORT_IBLOCK_ID") ? SEO_REPORT_IBLOCK_ID : "1",
        "CACHE_TYPE" => "N",
        "CACHE_TIME" => "0",
        "CACHE_GROUPS" => "Y",
    ),
    false
);
?>
<?php require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"; ?>
