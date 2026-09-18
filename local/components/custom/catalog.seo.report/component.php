<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!Loader::includeModule("iblock")) {
    ShowError("Модуль инфоблоков не установлен");
    return;
}

$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["CACHE_TIME"] = isset($arParams["CACHE_TIME"]) ? intval($arParams["CACHE_TIME"]) : 0;

if ($arParams["IBLOCK_ID"] <= 0) {
    ShowError("Не задан инфоблок. Укажите IBLOCK_ID в параметрах компонента.");
    return;
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$sort = $request->get("sort");
$order = $request->get("order");
$seoMin = $request->get("seo_min");
$seoMax = $request->get("seo_max");
$range = $request->get("range");
$q = trim((string)$request->get("q"));

$rangeMap = array(
    "weak" => array(0, 49),
    "mid" => array(50, 79),
    "ready" => array(80, 100),
    "all" => array(0, 100),
);
if ($range && isset($rangeMap[$range])) {
    $seoMin = $rangeMap[$range][0];
    $seoMax = $rangeMap[$range][1];
} else {
    $range = "all";
}

$allowedSort = array("seo", "name", "id", "price");
$allowedOrder = array("asc", "desc");
$arResult["FILTER"] = array(
    "SORT" => in_array($sort, $allowedSort, true) ? $sort : "seo",
    "ORDER" => in_array($order, $allowedOrder, true) ? $order : "desc",
    "SEO_MIN" => ($seoMin === null || $seoMin === "") ? 0 : max(0, min(100, intval($seoMin))),
    "SEO_MAX" => ($seoMax === null || $seoMax === "") ? 100 : max(0, min(100, intval($seoMax))),
    "RANGE" => $range,
    "Q" => $q,
);

if ($arResult["FILTER"]["SEO_MIN"] > $arResult["FILTER"]["SEO_MAX"]) {
    $tmp = $arResult["FILTER"]["SEO_MIN"];
    $arResult["FILTER"]["SEO_MIN"] = $arResult["FILTER"]["SEO_MAX"];
    $arResult["FILTER"]["SEO_MAX"] = $tmp;
}

function customSeoFirstKeyword($name)
{
    $stop = array("для", "и", "на", "с", "в", "из", "the", "a");
    $clean = preg_replace("/[^a-zа-яё0-9\s-]/ui", " ", mb_strtolower($name));
    $parts = preg_split("/\s+/", trim($clean));
    foreach ($parts as $p) {
        if ($p !== "" && !in_array($p, $stop, true) && mb_strlen($p) > 2) {
            return $p;
        }
    }
    return isset($parts[0]) ? $parts[0] : "";
}

function customSeoPlainLength($html)
{
    $text = preg_replace("/<[^>]+>/", " ", (string)$html);
    $text = preg_replace("/\s+/u", " ", $text);
    return mb_strlen(trim($text));
}

function customSeoScoreProduct(array $fields, array $properties, array $seoMeta)
{
    $checks = array();

    $name = trim($fields["NAME"]);
    $nameOk = mb_strlen($name) >= 3;
    $checks[] = array(
        "ID" => "name",
        "LABEL" => "Название",
        "STATUS" => $nameOk ? "ok" : "fail",
        "DETAIL" => $nameOk ? mb_strlen($name)." символов" : "Пустое или слишком короткое",
        "POINTS" => $nameOk ? 5 : 0,
        "MAX" => 5,
    );

    $desc = $fields["DETAIL_TEXT"] !== "" ? $fields["DETAIL_TEXT"] : $fields["PREVIEW_TEXT"];
    $dLen = customSeoPlainLength($desc);
    $dPts = 0;
    $dStatus = "fail";
    $dDetail = "Нет описания";
    if ($dLen === 0) {
        $dPts = 0;
    } elseif ($dLen < 150) {
        $dPts = 8;
        $dStatus = "warn";
        $dDetail = $dLen." символов — мало (рекомендуем ≥ 300)";
    } elseif ($dLen < 300) {
        $dPts = 14;
        $dStatus = "warn";
        $dDetail = $dLen." символов — лучше ≥ 300";
    } elseif ($dLen <= 2000) {
        $dPts = 20;
        $dStatus = "ok";
        $dDetail = $dLen." символов";
    } else {
        $dPts = 12;
        $dStatus = "warn";
        $dDetail = $dLen." символов — слишком длинное";
    }
    $checks[] = array(
        "ID" => "description",
        "LABEL" => "Описание",
        "STATUS" => $dStatus,
        "DETAIL" => $dDetail,
        "POINTS" => $dPts,
        "MAX" => 20,
    );

    $specCodes = array("BRAND", "MATERIAL", "COLOR", "COUNTRY");
    $filledSpecs = 0;
    $specValues = array();
    foreach ($specCodes as $code) {
        $val = "";
        if (isset($properties[$code])) {
            $raw = $properties[$code]["VALUE"];
            if (is_array($raw)) {
                $val = implode(", ", array_filter($raw));
            } else {
                $val = (string)$raw;
            }
        }
        $specValues[$code] = $val;
        if (trim($val) !== "") {
            $filledSpecs++;
        }
    }
    $specPts = (int)round(15 * min(1, $filledSpecs / 4));
    $specStatus = "fail";
    if ($filledSpecs >= 4) {
        $specStatus = "ok";
    } elseif ($filledSpecs > 0) {
        $specStatus = "warn";
    }
    $checks[] = array(
        "ID" => "specs",
        "LABEL" => "Характеристики",
        "STATUS" => $specStatus,
        "DETAIL" => $filledSpecs." из 4 ключевых свойств",
        "POINTS" => $specPts,
        "MAX" => 15,
        "VALUES" => $specValues,
    );

    $hasPhoto = !empty($fields["DETAIL_PICTURE"]) || !empty($fields["PREVIEW_PICTURE"]);
    $checks[] = array(
        "ID" => "photo",
        "LABEL" => "Фото",
        "STATUS" => $hasPhoto ? "ok" : "fail",
        "DETAIL" => $hasPhoto ? "Есть изображение" : "Нет фото",
        "POINTS" => $hasPhoto ? 15 : 0,
        "MAX" => 15,
    );

    $metaTitle = "";
    if (!empty($properties["META_TITLE"]["VALUE"])) {
        $metaTitle = (string)$properties["META_TITLE"]["VALUE"];
    } elseif (!empty($seoMeta["ELEMENT_META_TITLE"])) {
        $metaTitle = (string)$seoMeta["ELEMENT_META_TITLE"];
    }

    $tLen = mb_strlen(trim($metaTitle));
    $tPts = 0;
    $tStatus = "fail";
    $tDetail = "Meta title не заполнен";
    if ($tLen > 0) {
        $tPts += 10;
        if ($tLen >= 50 && $tLen <= 60) {
            $tPts += 10;
            $tStatus = "ok";
            $tDetail = $tLen." символов — оптимально (50–60)";
        } elseif ($tLen >= 30 && $tLen <= 70) {
            $tPts += 8;
            $tStatus = "ok";
            $tDetail = $tLen." символов — допустимо (30–70)";
        } elseif ($tLen >= 20 && $tLen <= 90) {
            $tPts += 4;
            $tStatus = "warn";
            $tDetail = $tLen." символов — вне диапазона 30–70";
        } else {
            $tStatus = "warn";
            $tDetail = $tLen." символов — слишком короткий или длинный";
        }
    }
    $checks[] = array(
        "ID" => "metaTitle",
        "LABEL" => "Meta title",
        "STATUS" => $tStatus,
        "DETAIL" => $tDetail,
        "POINTS" => $tPts,
        "MAX" => 20,
        "VALUE" => $metaTitle,
    );

    $metaDesc = "";
    if (!empty($properties["META_DESCRIPTION"]["VALUE"])) {
        $metaDesc = (string)$properties["META_DESCRIPTION"]["VALUE"];
    } elseif (!empty($seoMeta["ELEMENT_META_DESCRIPTION"])) {
        $metaDesc = (string)$seoMeta["ELEMENT_META_DESCRIPTION"];
    }

    $mLen = mb_strlen(trim($metaDesc));
    $mPts = 0;
    $mStatus = "fail";
    $mDetail = "Meta description не заполнен";
    if ($mLen > 0) {
        $mPts += 10;
        if ($mLen >= 120 && $mLen <= 160) {
            $mPts += 10;
            $mStatus = "ok";
            $mDetail = $mLen." символов — оптимально (120–160)";
        } elseif ($mLen >= 70 && $mLen <= 180) {
            $mPts += 8;
            $mStatus = "ok";
            $mDetail = $mLen." символов — допустимо";
        } else {
            $mPts += 3;
            $mStatus = "warn";
            $mDetail = $mLen." символов — вне 70–180";
        }
    }
    $checks[] = array(
        "ID" => "metaDescription",
        "LABEL" => "Meta description",
        "STATUS" => $mStatus,
        "DETAIL" => $mDetail,
        "POINTS" => $mPts,
        "MAX" => 20,
        "VALUE" => $metaDesc,
    );

    $kw = customSeoFirstKeyword($name);
    $kwOk = ($kw !== "" && $metaTitle !== "" && mb_stripos($metaTitle, $kw) !== false);
    $kwStatus = $kwOk ? "ok" : ($metaTitle ? "warn" : "fail");
    $checks[] = array(
        "ID" => "keyword",
        "LABEL" => "Ключевое слово в title",
        "STATUS" => $kwStatus,
        "DETAIL" => $kwOk
            ? "«".$kw."» есть в title"
            : ($kw !== "" ? "«".$kw."» нет в title" : "Нечего проверять"),
        "POINTS" => $kwOk ? 5 : 0,
        "MAX" => 5,
    );

    $score = 0;
    foreach ($checks as $c) {
        $score += $c["POINTS"];
    }
    if ($score < 0) {
        $score = 0;
    }
    if ($score > 100) {
        $score = 100;
    }

    return array(
        "SCORE" => $score,
        "CHECKS" => $checks,
        "META_TITLE" => $metaTitle,
        "META_DESCRIPTION" => $metaDesc,
    );
}

$select = array(
    "ID",
    "IBLOCK_ID",
    "NAME",
    "CODE",
    "PREVIEW_TEXT",
    "DETAIL_TEXT",
    "PREVIEW_PICTURE",
    "DETAIL_PICTURE",
    "DETAIL_PAGE_URL",
);

$res = CIBlockElement::GetList(
    array("SORT" => "ASC", "ID" => "ASC"),
    array(
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "ACTIVE" => "Y",
        "ACTIVE_DATE" => "Y",
    ),
    false,
    false,
    $select
);

$items = array();
while ($ob = $res->GetNextElement()) {
    $fields = $ob->GetFields();
    $properties = $ob->GetProperties();

    $seoMeta = array();
    if (class_exists("\\Bitrix\\Iblock\\InheritedProperty\\ElementValues")) {
        try {
            $iprop = new \Bitrix\Iblock\InheritedProperty\ElementValues($fields["IBLOCK_ID"], $fields["ID"]);
            $seoMeta = $iprop->getValues();
        } catch (\Exception $e) {
            $seoMeta = array();
        }
    }

    $scored = customSeoScoreProduct($fields, $properties, $seoMeta);
    $pictureId = $fields["PREVIEW_PICTURE"] ? $fields["PREVIEW_PICTURE"] : $fields["DETAIL_PICTURE"];
    $picture = $pictureId ? CFile::GetFileArray($pictureId) : false;

    $price = 0;
    if (!empty($properties["PRICE"]["VALUE"])) {
        $price = intval($properties["PRICE"]["VALUE"]);
    }

    $items[] = array(
        "ID" => $fields["ID"],
        "NAME" => $fields["NAME"],
        "CODE" => $fields["CODE"],
        "DETAIL_PAGE_URL" => $fields["DETAIL_PAGE_URL"],
        "PICTURE" => $picture,
        "PRICE" => $price,
        "SCORE" => $scored["SCORE"],
        "CHECKS" => $scored["CHECKS"],
        "META_TITLE" => $scored["META_TITLE"],
        "META_DESCRIPTION" => $scored["META_DESCRIPTION"],
    );
}

$filtered = array();
foreach ($items as $item) {
    if ($item["SCORE"] < $arResult["FILTER"]["SEO_MIN"] || $item["SCORE"] > $arResult["FILTER"]["SEO_MAX"]) {
        continue;
    }
    if ($arResult["FILTER"]["Q"] !== "") {
        $hay = mb_strtolower($item["NAME"]." ".$item["CODE"]);
        if (mb_strpos($hay, mb_strtolower($arResult["FILTER"]["Q"])) === false) {
            continue;
        }
    }
    $filtered[] = $item;
}

usort($filtered, function ($a, $b) use ($arResult) {
    $dir = $arResult["FILTER"]["ORDER"] === "desc" ? -1 : 1;
    switch ($arResult["FILTER"]["SORT"]) {
        case "name":
            return $dir * strcmp($a["NAME"], $b["NAME"]);
        case "id":
            return $dir * ($a["ID"] - $b["ID"]);
        case "price":
            return $dir * ($a["PRICE"] - $b["PRICE"]);
        default:
            if ($a["SCORE"] === $b["SCORE"]) {
                return $a["ID"] - $b["ID"];
            }
            return $dir * ($a["SCORE"] - $b["SCORE"]);
    }
});

$sum = 0;
$low = 0;
$ready = 0;
foreach ($items as $item) {
    $sum += $item["SCORE"];
    if ($item["SCORE"] < 50) {
        $low++;
    }
    if ($item["SCORE"] >= 80) {
        $ready++;
    }
}

$arResult["ITEMS"] = $filtered;
$arResult["ALL_COUNT"] = count($items);
$arResult["SHOWN_COUNT"] = count($filtered);
$arResult["AVG_SCORE"] = $arResult["ALL_COUNT"] > 0 ? round($sum / $arResult["ALL_COUNT"]) : 0;
$arResult["LOW_COUNT"] = $low;
$arResult["READY_COUNT"] = $ready;
$arResult["FORM_ACTION"] = $APPLICATION->GetCurPage();

$this->IncludeComponentTemplate();
