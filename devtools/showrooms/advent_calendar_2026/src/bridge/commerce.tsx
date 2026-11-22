import React from 'react';
import {useAdvent,useT,type Offer} from './advent-context';

function mainOffer(offers:Offer[]):Offer|undefined {
  const matches=offers.filter(o=>!o.owned && o.role==='calendar');
  if(matches.length===1) return matches[0];
  const eligible=offers.filter(o=>o.role!=='gift' && o.role!=='donation');
  return eligible.length===1?eligible[0]:undefined;
}
export function ProductPrice(){
  const {offers,busy}=useAdvent(),t=useT();
  const offer=mainOffer(offers);
  return <span aria-live="polite" className="tabular-nums">{busy?t('loading_price'):((offer?.priceid||0)>0 && offer?.priceformatted?offer.priceformatted:t('unavailable'))}</span>;
}
export function CurrencySelector({compact=false}:{compact?:boolean}){
  const {currency,options,changeCurrency,busy,error}=useAdvent(),t=useT();
  const id=React.useId();
  const enabled=options.length>1 && options.every(o=>!!o.url);
  return <div className={`inline-flex flex-wrap items-center justify-center gap-2 ${compact?'text-[11px]':'text-sm'}`}>
    <label htmlFor={id} className="font-bold">{t('currency')}</label>
    <select id={id} value={currency} onChange={e=>void changeCurrency(e.currentTarget.value)}
      disabled={!enabled||busy} aria-busy={busy} className="max-w-36 rounded-lg border border-glow/50 bg-night-deep px-2 py-1.5 text-night-foreground">
      {options.map(o=><option value={o.value} key={o.value}>{o.label||o.value}</option>)}
    </select>
    {error&&<span role="alert" className="w-full text-center text-xs text-primary">{t(error)}</span>}
  </div>;
}
function postAction(action:string|undefined) {
  if(!action) return null;
  try {const u=new URL(action,window.location.href);if(u.origin===window.location.origin &&
    u.protocol==='https:' && u.pathname==='/local/subscriptions/cart_action.php' &&
    !u.username && !u.password && !u.search && !u.hash) return u.href;}
  catch {return null;}
  return null;
}
export function NativePurchaseButton(){
  const {offers,currency,busy,payload}=useAdvent(),t=useT();
  const offer=mainOffer(offers);
  const action=postAction(offer?.cartaction);
  const ready=!!offer && !busy && !payload.showroom.ispreview && action!==null &&
    offer.available===true && offer.canbuy===true && offer.owned!==true &&
    offer.currency===currency && (offer.priceid||0)>0 && !!offer.sku && !!offer.sesskey && !!payload.showroom.key;
  const fields=ready?{
    action:'buynow',currency,sku:offer!.sku!,priceid:String(offer!.priceid),quantity:'1',
    sesskey:offer!.sesskey!,source:'showroom',showroom:payload.showroom.key,
    showroomoffer:offer!.role,returnurl:offer!.returnurl||window.location.pathname,
  }:null;
  return <form method="post" action={ready?action||undefined:undefined} onSubmit={event=>{
      if(!ready || !event.nativeEvent.isTrusted) event.preventDefault();
    }} className="mt-5">
    {fields&&Object.entries(fields).map(([name,value])=><input type="hidden" name={name} value={value} key={name}/>)}
    <button type="submit" disabled={!ready} className="block w-full cursor-pointer rounded-2xl bg-primary py-4 text-center text-base font-extrabold text-primary-foreground shadow-pink transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">{ready?t('buy'):t('not_eligible')}</button>
  </form>;
}
// The separate gift fulfillment journey has not been proven; never use the normal SKU to simulate a gift.
export function GiftInterest(){const t=useT();return <button disabled type="button" className="rounded-2xl bg-primary px-3 py-2 text-xs font-extrabold text-primary-foreground opacity-60 sm:px-5 sm:text-sm" title={t('gift_unavailable')}>{t('gift_unavailable')}</button>;}
