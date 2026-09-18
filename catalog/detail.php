<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php";
$APPLICATION->SetTitle("Карточка товара");

if (!CModule::IncludeModule("iblock")) {
    echo '<div style="max-width:1100px;margin:40px auto;padding:20px;">Модуль инфоблоков недоступен.</div>';
    require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";
    die();
}

$iblockId = defined("SEO_REPORT_IBLOCK_ID") ? intval(SEO_REPORT_IBLOCK_ID) : 0;
if ($iblockId <= 0) {
    $rs = CIBlock::GetList(array(), array("TYPE" => "catalog", "CODE" => "demo_shop", "CHECK_PERMISSIONS" => "N"));
    if ($row = $rs->Fetch()) $iblockId = intval($row["ID"]);
}

$code = isset($_GET["code"]) ? trim($_GET["code"]) : "";
if ($iblockId <= 0 || $code === "") {
    LocalRedirect("/catalog/");
    die();
}

$res = CIBlockElement::GetList(
    array(),
    array("IBLOCK_ID" => $iblockId, "CODE" => $code, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "N"),
    false,
    false,
    array("ID","IBLOCK_ID","NAME","CODE","PREVIEW_TEXT","DETAIL_TEXT","PREVIEW_PICTURE","DETAIL_PICTURE")
);
$ob = $res->GetNextElement();
if (!$ob) {
    LocalRedirect("/catalog/");
    die();
}
$fields = $ob->GetFields();
$props = $ob->GetProperties();

function detailH($v){ return htmlspecialcharsbx((string)$v); }
function detailMoney($v){ return number_format(intval($v),0,"."," ")." ₽"; }
function detailPlainLength($v){ $v=preg_replace("/<[^>]+>/"," ",(string)$v); $v=preg_replace("/\s+/u"," ",$v); return mb_strlen(trim($v)); }
function detailFirstKeyword($name){
    $stop=array("для","и","на","с","в","из");
    $parts=preg_split("/\s+/",trim(preg_replace("/[^a-zа-яё0-9\s-]/ui"," ",mb_strtolower($name))));
    foreach($parts as $p) if($p!==""&&!in_array($p,$stop,true)&&mb_strlen($p)>2) return $p;
    return "";
}

$desc = $fields["DETAIL_TEXT"] !== "" ? $fields["DETAIL_TEXT"] : $fields["PREVIEW_TEXT"];
$metaTitle = isset($props["META_TITLE"]["VALUE"]) ? trim((string)$props["META_TITLE"]["VALUE"]) : "";
$metaDesc = isset($props["META_DESCRIPTION"]["VALUE"]) ? trim((string)$props["META_DESCRIPTION"]["VALUE"]) : "";
$hasPhoto = !empty($fields["DETAIL_PICTURE"]) || !empty($fields["PREVIEW_PICTURE"]);
$pictureId = $fields["DETAIL_PICTURE"] ? $fields["DETAIL_PICTURE"] : $fields["PREVIEW_PICTURE"];
$picture = $pictureId ? CFile::GetFileArray($pictureId) : false;
$descLen=detailPlainLength($desc);
$specFilled=0;
foreach(array("BRAND","MATERIAL","COLOR","COUNTRY") as $pc){ if(isset($props[$pc]["VALUE"]) && trim((string)$props[$pc]["VALUE"])!=="") $specFilled++; }
$titleLen=mb_strlen($metaTitle); $metaDescLen=mb_strlen($metaDesc);
$kw=detailFirstKeyword($fields["NAME"]); $kwOk=$kw!=="" && $metaTitle!=="" && mb_stripos($metaTitle,$kw)!==false;
$score=0; $score+=mb_strlen($fields["NAME"])>=3?5:0; $score+=($descLen>=300&&$descLen<=2000)?20:(($descLen>=150)?14:(($descLen>0)?8:0)); $score+=intval(round(15*min(1,$specFilled/4))); $score+=$hasPhoto?15:0;
$score+=$metaTitle!==""?10:0; if($metaTitle!=="") $score+=(($titleLen>=50&&$titleLen<=60)?10:(($titleLen>=30&&$titleLen<=70)?8:(($titleLen>=20&&$titleLen<=90)?4:0)));
$score+=$metaDesc!==""?10:0; if($metaDesc!=="") $score+=(($metaDescLen>=120&&$metaDescLen<=160)?10:(($metaDescLen>=70&&$metaDescLen<=180)?8:3)); $score+=$kwOk?5:0; $score=min(100,$score);
$tone=$score>=80?"good":($score>=50?"warn":"bad");

$checks=array(
 array("Название",mb_strlen($fields["NAME"])>=3,"Заполнено"),
 array("Описание",$descLen>0,$descLen." символов"),
 array("Характеристики",$specFilled===4,$specFilled." из 4 заполнено"),
 array("Фото",$hasPhoto,"Изображение загружено"),
 array("Meta Title",$metaTitle!=="",$metaTitle!==""?$titleLen." символов":"Не заполнен"),
 array("Meta Description",$metaDesc!=="",$metaDesc!==""?$metaDescLen." символов":"Не заполнен"),
 array("Ключевое слово",$kwOk,$kw!==""?"«".$kw."»":"Нет ключевого слова")
);

echo '<style>
.detail-page{max-width:1180px;margin:0 auto;padding:34px 18px 70px;font-family:Arial,sans-serif;color:#151515}.detail-breadcrumb{font-size:13px;color:#777;margin-bottom:22px}.detail-breadcrumb a{color:#555;text-decoration:none}.detail-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr);gap:34px}.detail-gallery{border:1px solid #e8e8e8;border-radius:18px;background:#fff;min-height:520px;display:flex;align-items:center;justify-content:center;padding:25px}.detail-gallery img{width:100%;height:100%;max-height:560px;object-fit:contain}.detail-no-photo{color:#999}.detail-brand{font-size:13px;color:#777;margin:0 0 8px}.detail-title{font-size:40px;line-height:1.1;margin:0 0 18px}.detail-price{font-size:32px;font-weight:700;margin:22px 0}.detail-description{font-size:16px;line-height:1.65;color:#444}.detail-actions{display:flex;gap:12px;margin:24px 0}.detail-btn{min-height:48px;padding:0 22px;border-radius:10px;border:1px solid #ddd;background:#fff;text-decoration:none;color:#171717;display:inline-flex;align-items:center;justify-content:center}.detail-btn--primary{background:#1677ff;border-color:#1677ff;color:#fff}.detail-sections{margin-top:34px;border-top:1px solid #e8e8e8;padding-top:30px}.detail-cols{display:grid;grid-template-columns:1fr 1fr;gap:28px}.detail-box{border:1px solid #e8e8e8;border-radius:16px;padding:22px}.detail-box h2{font-size:20px;margin:0 0 18px}.detail-row{display:flex;justify-content:space-between;gap:20px;padding:10px 0;border-bottom:1px solid #eee;font-size:14px}.detail-row:last-child{border-bottom:0}.detail-row span:first-child{color:#777}.seo-box{margin-top:28px;border:1px solid #e8e8e8;border-radius:16px;padding:22px}.seo-score{font-size:42px;font-weight:700}.seo-score.good{color:#129447}.seo-score.warn{color:#b77900}.seo-score.bad{color:#c62828}.seo-check{display:flex;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #eee;font-size:14px}.seo-check:last-child{border-bottom:0}.seo-ok{color:#159447}.seo-fail{color:#c62828}.meta-box{margin-top:28px;background:#fafafa;border-radius:16px;padding:22px}.meta-label{font-size:12px;color:#777;margin-top:14px}.meta-value{margin-top:5px;line-height:1.5}.back-link{display:inline-flex;margin-bottom:20px;color:#1677ff;text-decoration:none}@media(max-width:800px){.detail-grid,.detail-cols{grid-template-columns:1fr}.detail-title{font-size:32px}.detail-gallery{min-height:360px}}
</style>';

echo '<div class="detail-page">';
echo '<a class="back-link" href="/catalog/">← Вернуться в каталог</a>';
echo '<div class="detail-breadcrumb"><a href="/">Главная</a> / <a href="/catalog/">Каталог</a> / '.detailH($fields["NAME"]).'</div>';
echo '<div class="detail-grid"><div class="detail-gallery">';
if($picture) echo '<img src="'.detailH($picture["SRC"]).'" alt="'.detailH($fields["NAME"]).'">'; else echo '<span class="detail-no-photo">Фото не заполнено</span>';
echo '</div><div><p class="detail-brand">'.detailH(isset($props["BRAND"]["VALUE"])?$props["BRAND"]["VALUE"]:"Товар").'</p><h1 class="detail-title">'.detailH($fields["NAME"]).'</h1><div class="detail-price">'.detailMoney(isset($props["PRICE"]["VALUE"])?$props["PRICE"]["VALUE"]:0).'</div><div class="detail-description">'.nl2br(detailH($desc)).'</div><div class="detail-actions"><a class="detail-btn detail-btn--primary" href="#">Добавить в корзину</a><a class="detail-btn" href="#specs">Характеристики</a></div></div></div>';

echo '<div class="detail-sections"><div class="detail-cols"><div class="detail-box"><h2>Описание</h2><div class="detail-description">'.nl2br(detailH($desc!==""?$desc:"Описание не заполнено для SEO-проверки.")).'</div></div><div class="detail-box" id="specs"><h2>Характеристики</h2>';
$labels=array("BRAND"=>"Бренд","MATERIAL"=>"Материал","COLOR"=>"Цвет","COUNTRY"=>"Страна");
foreach($labels as $pc=>$label){$v=isset($props[$pc]["VALUE"])?$props[$pc]["VALUE"]:""; echo '<div class="detail-row"><span>'.detailH($label).'</span><strong>'.detailH($v!==""?$v:"Не заполнено").'</strong></div>';}
echo '</div></div>';

echo '<div class="seo-box"><h2>SEO-оценка товара</h2><div class="seo-score '.$tone.'">'.$score.' / 100</div>';
foreach($checks as $c){echo '<div class="seo-check"><strong class="'.($c[1]?'seo-ok':'seo-fail').'">'.($c[1]?'✓':'✕').'</strong><span><b>'.detailH($c[0]).'</b><br>'.detailH($c[2]).'</span></div>';}
echo '</div>';
echo '<div class="meta-box"><h2>SEO-метаданные</h2><div class="meta-label">Meta Title</div><div class="meta-value">'.detailH($metaTitle!==""?$metaTitle:"Не заполнен").'</div><div class="meta-label">Meta Description</div><div class="meta-value">'.detailH($metaDesc!==""?$metaDesc:"Не заполнен").'</div><div class="meta-label">Ключевое слово</div><div class="meta-value">'.detailH($kw!==""?$kw:"Не определено").'</div></div>';
echo '</div></div>';

require $_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php";
