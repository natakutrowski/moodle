<?php
// Included only by native preview.php after login and capability verification.
declare(strict_types=1);
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomLovableDraftService as Draft;
use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver as Currency;

$meta = Draft::metadata($record);
if ($meta === null || (string)$record->status !== 'draft') {
    throw new invalid_parameter_exception('Ce showroom n’est pas un brouillon Lovable.');
}
$lang = optional_param('lang', 'ru', PARAM_ALPHA);
if (!in_array($lang, ['ru', 'fr', 'en'], true)) {
    $lang = 'ru';
}
$currency = strtoupper(optional_param('currency', 'EUR', PARAM_ALPHA));
$currencies = Currency::active_currencies($DB);
if (!in_array($currency, $currencies, true)) {
    $currency = (string)($currencies[0] ?? 'EUR');
}
$frameurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_frame.php', [
    'id' => (int)$record->id, 'lang' => $lang, 'currency' => $currency,
]);
$backurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_edit.php', [
    'id' => (int)$record->id, 'lang' => $lang,
]);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php',
    ['id' => (int)$record->id, 'lang' => $lang, 'currency' => $currency]));
$PAGE->set_pagelayout('showroom');
$PAGE->add_body_class('commerce-showroom-page');
$PAGE->add_body_class('commerce-showroom-admin-preview');
$PAGE->add_body_class('campusfr-lovable-canvas-preview');
$PAGE->add_body_class('campusfr-lovable-cms-preview');
$PAGE->set_title('Brouillon CMS · ' . $meta['designid']);
$PAGE->set_heading('Brouillon CMS · ' . $meta['designid']);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/showroom.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/lovable_campus_preview.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/lovable_campus_preview_v43.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/lovable_campus_preview_v44.css'));
$PAGE->requires->js_init_code(<<<'CAMPUSFR_NATIVE_SCROLL_V44'
(function(){
'use strict';
const frame=document.querySelector('iframe.campusfr-lovable-campus-canvas-preview-frame');
if(!frame)return;
let currentHeight=0, scrolling=false;
function sendViewport(){
  const bar=document.querySelector('.showroom-shell__topbar');
  const barHeight=bar?Math.ceil(bar.getBoundingClientRect().height):70;
  const visibleHeight=Math.max(200,Math.min(3500,Math.round(window.innerHeight-barHeight)));
  const scrollOffset=Math.max(0,Math.round(-frame.getBoundingClientRect().top+barHeight));
  frame.contentWindow?.postMessage({type:'campusfr-preview-viewport-v44',visibleHeight,scrollOffset},'*');
}
function scheduleViewport(){
  if(scrolling)return;
  scrolling=true;
  requestAnimationFrame(()=>{scrolling=false;sendViewport();});
}
frame.addEventListener('load',sendViewport);
window.addEventListener('scroll',scheduleViewport,{passive:true});
window.addEventListener('resize',scheduleViewport,{passive:true});
window.addEventListener('message',event=>{
  if(event.source!==frame.contentWindow||event.origin!=='null')return;
  const data=event.data;
  if(!data||typeof data!=='object')return;
  if(data.type==='campusfr-preview-ready-v44'){
    sendViewport();
  }else if(data.type==='campusfr-preview-height-v44'){
    const height=data.height;
    if(!Number.isInteger(height)||height<300||height>30000)return;
    if(Math.abs(currentHeight-height)>1){
      currentHeight=height;
      frame.style.setProperty('height',height+'px','important');
      scheduleViewport();
    }
  }else if(data.type==='campusfr-preview-scroll-v44'){
    const offset=data.offset;
    if(!Number.isInteger(offset)||offset<0||offset>30000)return;
    const bar=document.querySelector('.showroom-shell__topbar');
    const barHeight=bar?Math.ceil(bar.getBoundingClientRect().height):70;
    const top=window.scrollY+frame.getBoundingClientRect().top+offset-barHeight;
    window.scrollTo({top:Math.max(0,Math.round(top)),behavior:'smooth'});
  }
});
sendViewport();
})();
CAMPUSFR_NATIVE_SCROLL_V44
);
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
// V4.1: the trusted Moodle parent retrieves ONLY read-only Commerce quotes.
// The opaque sandbox frame cannot fetch/authenticate, navigate or submit a form.
$allowedjson = json_encode(array_values($currencies), JSON_HEX_TAG | JSON_HEX_AMP
    | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$endpoint = new moodle_url('/local/subscriptions/admin/commerce/showrooms/lovable_cms_currency_v41.php', [
    'id' => (int)$record->id,
]);
$endpointjson = json_encode($endpoint->out(false), JSON_HEX_TAG | JSON_HEX_AMP
    | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$PAGE->requires->js_init_code('(function(){'
    . '"use strict";'
    . 'const allowed=' . $allowedjson . ';'
    . 'const endpoint=' . $endpointjson . ';'
    . 'let pending=false;'
    . 'window.addEventListener("message",async function(event){'
    . 'const frame=document.querySelector("iframe.campusfr-lovable-campus-canvas-preview-frame");'
    . 'if(!frame||event.source!==frame.contentWindow||event.origin!=="null"||pending)return;'
    . 'const data=event.data;'
    . 'if(!data||typeof data!=="object"||data.type!=="campusfr-preview-currency-v41")return;'
    . 'const next=data.currency;'
    . 'if(typeof next!=="string"||!allowed.includes(next))return;'
    . 'pending=true;'
    . 'try{'
    . 'const url=new URL(endpoint,window.location.href);'
    . 'if(url.origin!==window.location.origin)throw Error("Currency origin refused");'
    . 'url.searchParams.set("currency",next);'
    . 'const response=await fetch(url.href,{method:"GET",credentials:"same-origin",cache:"no-store",redirect:"error",headers:{Accept:"application/json"}});'
    . 'if(!response.ok)throw Error("Currency HTTP status " + response.status);'
    . 'const quote=await response.json();'
    . 'if(!quote||quote.ok!==true||quote.currency!==next||!Array.isArray(quote.offers)||quote.offers.length>3)throw Error("Currency payload refused");'
    . 'if(!quote.offers.every(o=>o&&typeof o==="object"&&["course","bundle","donation"].includes(o.role)&&o.currency===next&&typeof o.priceformatted==="string"))throw Error("Offer payload refused");'
    . 'if(frame.contentWindow===event.source)frame.contentWindow.postMessage({type:"campusfr-preview-currency-result-v41",currency:next,offers:quote.offers},"*");'
    . '}catch(e){console.error("CampusFR preview currency read-only request failed",e);'
    . 'if(frame.contentWindow===event.source)frame.contentWindow.postMessage({type:"campusfr-preview-currency-error-v41",currency:next},"*");'
    . '}finally{pending=false;}'
    . '});'
    . '})();');
echo $OUTPUT->header();
// Native CampusFR header only. Currency selection lives above the offer cards.
echo html_writer::tag('iframe', '', [
    'class' => 'campusfr-lovable-campus-canvas-preview-frame',
    'src' => $frameurl->out(false),
    'title' => 'Brouillon CMS ' . $meta['designid'],
    'sandbox' => 'allow-scripts', 'referrerpolicy' => 'no-referrer', 'loading' => 'eager',
]);
echo $OUTPUT->footer();
