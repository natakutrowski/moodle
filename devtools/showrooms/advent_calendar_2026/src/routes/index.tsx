import { useT, useAdvent } from "@/bridge/advent-context";
import {storyCover,previewAudio} from "@/bridge/media";
import { CurrencySelector, ProductPrice, NativePurchaseButton, GiftInterest } from "@/bridge/commerce";
import boxBase from "@/assets/gift-box-base.webp";
import boxLid from "@/assets/gift-box-lid.webp";
import firBranch from "@/assets/fir-branch.webp";
import { Button } from "@/components/ui/button";
import { useEffect, useRef, useState, type ReactNode } from "react";
import {
  ArrowRight, Sparkles, Gift, Headphones, CalendarDays, Star, CheckCircle2, Plus, HelpCircle,
  Lock, Play, Pause, Clock, Infinity as InfinityIcon, GraduationCap, Mail, Send, Phone, TreePine,
  BookOpen, Feather, Mic,
} from "lucide-react";
import hero from "@/assets/hero-christmas-paris.jpg";
import c1 from "@/assets/book-1.jpg";
import c2 from "@/assets/book-2.jpg";
import c3 from "@/assets/book-3.jpg";
import c4 from "@/assets/book-4.jpg";
import c5 from "@/assets/book-5.jpg";
import c6 from "@/assets/book-6.jpg";






















// Editorial signatures from the original Lovable artwork are always in French,
// even when an older CMS override contains an RU/EN translation.
const FRENCH_DECOR = {
  decor_hero: 'Joyeux Noël, mon ami…',
  decor_original: 'Nos propres histoires…',
  decor_gift_card: 'Pour toi, mon ami(e)…',
  decor_gift_end: 'Joyeux Noël, mon ami(e) !',
  decor_goodbye: 'Joyeux Noël et à très vite !',
} as const;

// French editorial titles are canonical; they are NOT translated by the UI locale.
const STORIES: Record<number, [string, string?]> = {
  1: ["La lettre sans adresse", c1],
  2: ["Le chat qui détestait Noël", c2],
  3: ["La boulangerie de minuit", c3],
  4: ["Le royaume de l'hiver éternel", c4],
  5: ["Le marchand de minutes", c5],
  6: ["L'âne de Saint Nicolas", c6],
  7: ["Le train de 23 h 59", undefined],
  8: ["Le Noël des grandes personnes", undefined],
  9: ["Le village dans la boule à neige", undefined],
  10: ["La couturière des étoiles", undefined],
  11: ["Le dernier bal de Noël", undefined],
  12: ["La princesse aux pieds nus", undefined],
  13: ["La vingt-cinquième case", undefined],
  14: ["Le cœur de glace", undefined],
  15: ["Le lutin qui rêvait trop grand", undefined],
  16: ["La ville aux mille lanternes", undefined],
  17: ["L’horloge aux treize coups", undefined],
  18: ["Le bonhomme de neige qui avait froid", undefined],
  19: ["La bibliothèque des vies possibles", undefined],
  20: ["Le bal des ombres", undefined],
  21: ["Le santon qui voulait voyager", undefined],
  22: ["Le renard aux yeux d’or", undefined],
  23: ["Le château des souvenirs", undefined],
  24: ["La dernière étoile de Noël", undefined],
};

function Pill({ icon, children }: { icon?: ReactNode; children: ReactNode }) {
  return (
    <span className="advent-section-pill inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-night-card px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-primary sm:gap-2 sm:px-4 sm:py-1.5 sm:text-xs sm:tracking-widest">
      {icon}
      {children}
    </span>
  );
}

function SectionHead({ pill, icon, title, accent, sub, wide = false }: { pill: string; icon: ReactNode; title: ReactNode; accent: ReactNode; sub?: ReactNode; wide?: boolean }) {
  return (
    <div className={`mx-auto text-center ${wide ? "max-w-6xl" : "max-w-4xl"}`}>
      <div className="mx-auto mb-3 flex w-fit items-center gap-3 text-glow/70 sm:mb-5"><span className="h-px w-12 bg-gradient-to-r from-transparent to-glow/60" /><Sparkles className="size-4 animate-flicker" /><span className="h-px w-12 bg-gradient-to-l from-transparent to-glow/60" /></div>
      <Pill icon={icon}>{pill}</Pill>
      <h2 className="mt-4 text-[1.35rem] font-black leading-tight text-night-foreground sm:text-3xl md:text-5xl">
        {title} <span className="text-primary">{accent}</span>
      </h2>
      {sub && <p className={`mx-auto mt-4 text-sm text-night-muted sm:mt-5 sm:text-lg ${wide ? "max-w-none" : "max-w-3xl"}`}>{sub}</p>}
    </div>
  );
}

function CtaButton({ children, href = "#offer" }: { children: ReactNode; href?: string }) {
  return (
    <a href={href} className="inline-flex items-center gap-2 rounded-2xl bg-primary px-6 py-3 text-sm font-extrabold text-primary-foreground shadow-pink transition hover:-translate-y-0.5 hover:brightness-110 sm:px-7 sm:py-4 sm:text-base">
      {children} <ArrowRight className="size-5" />
    </a>
  );
}

const FLAKES = Array.from({ length: 60 }, (_, i) => ({ l: (i * 37) % 100, d: 8 + ((i * 13) % 10), dl: -((i * 7) % 15), s: 2 + (i % 3) }));
function Snow() {
  return (
    <div aria-hidden className="pointer-events-none absolute inset-0 overflow-hidden">
      {FLAKES.map((f, i) => (
        <span key={i} className="absolute top-0 rounded-full bg-night-foreground/80 animate-snow" style={{ left: `${f.l}%`, width: f.s, height: f.s, animationDuration: `${f.d}s`, animationDelay: `${f.dl}s` }} />
      ))}
    </div>
  );
}

function Index() {
  return (
    <main className="overflow-x-hidden bg-night-deep">
      <Hero />
      <Calendar />
      <Formats />
      <Offer />
      <GiftBlock />
      <Faq />
    </main>
  );
}

function Hero() {
  const t = useT();
  return (
    <section className="night-scope relative flex min-h-[92vh] flex-col overflow-hidden">
      <img src={hero} alt={t("hero_alt")} width={1920} height={1088} className="absolute inset-0 h-full w-full object-cover" />
      <div className="absolute inset-0 bg-gradient-to-b from-night-deep/80 via-night-deep/45 to-night-deep" />
      <Snow />
      {/* CampusFR logo intentionally displayed by Moodle topbar, not duplicated here. */}
      <div className="relative z-10 mx-auto my-auto max-w-4xl px-4 py-16 text-center sm:max-w-5xl sm:px-5">
        <p className="font-hand text-xl text-glow sm:text-3xl">{FRENCH_DECOR.decor_hero}</p>
        <h1 className="mt-3 text-2xl font-black leading-[1.08] text-night-foreground sm:text-5xl md:text-6xl">
          <span className="block">{t("hero_title1")}</span>
          <span className="block text-glow">{t("hero_title2")}</span>
        </h1>
        <p className="mx-auto mt-6 max-w-2xl text-base font-extrabold text-night-foreground sm:text-xl">
          {t("hero_tagline")}
        </p>
        <p className="mx-auto mt-3 max-w-2xl text-sm text-night-muted sm:text-lg">
          {t("hero_intro")}
        </p>
        <div className="mt-9"><CtaButton>{t("buy")}</CtaButton></div>
        <div className="mx-auto mt-9 grid max-w-sm grid-cols-2 justify-center gap-2 sm:flex sm:max-w-3xl sm:flex-nowrap sm:gap-3">
          {[[<CalendarDays key="a" className="size-4" />, t("period")], [<Headphones key="b" className="size-4" />, t("stories24")], [<Mic key="d" className="size-4" />, t("narration")], [<InfinityIcon key="c" className="size-4" />, t("lifetime")]].map(([i, t]) => (
            <span key={t as string} className="advent-hero-badge flex shrink-0 items-center justify-center gap-1.5 rounded-full border border-glow/60 bg-night-card/70 px-2.5 py-2 text-[11px] font-extrabold text-night-foreground shadow-glow backdrop-blur sm:gap-2 sm:px-4 sm:text-sm"><span className="text-glow [&_svg]:size-3.5 sm:[&_svg]:size-4">{i}</span>{t}</span>
          ))}
        </div>
      </div>
    </section>
  );
}

function PreviewPlayer() {
  const t = useT();
  const media = useAdvent().media.previewAudioUrl || previewAudio;
  const [el, setEl] = useState<HTMLAudioElement | null>(null);
  const [playing, setPlaying] = useState(false);
  return (
    <div className="advent-audio-frame mx-auto mt-12 flex max-w-2xl items-center gap-3 rounded-2xl border border-border bg-card p-3 shadow-card sm:gap-4 sm:rounded-3xl sm:p-5">
      <Button aria-label={playing ? t("pause") : t("listen")} onClick={() => { if (!el) return; if (el.paused) void el.play().catch(() => setPlaying(false)); else el.pause(); }} disabled={!media} className="grid size-11 shrink-0 place-items-center rounded-full bg-primary text-primary-foreground shadow-pink transition hover:brightness-110 sm:size-14">
        {playing ? <Pause className="size-5 sm:size-6" /> : <Play className="size-5 translate-x-0.5 sm:size-6" />}
      </Button>
      <div className="min-w-0 text-left">
        <div className="text-[10px] font-black uppercase tracking-widest text-primary sm:text-xs">{t("audio_sample")}</div>
        <div className="mt-0.5 text-base font-black sm:mt-1 sm:text-lg">{STORIES[1]?.[0] ?? "La lettre sans adresse"}</div>
        <div className="text-xs text-muted-foreground sm:text-sm">{media ? t("audio_hint") : t("medias_missing")}</div>
      </div>
      <audio ref={setEl} src={media || undefined} preload="none" onPlay={() => setPlaying(true)} onPause={() => setPlaying(false)} onEnded={() => setPlaying(false)} />
    </div>
  );
}

function Garland() {
  const colors = ["text-primary", "text-glow", "text-night-foreground", "text-glow"];
  return (
    <div aria-hidden className="pointer-events-none mx-auto mt-10 flex max-w-5xl justify-between px-2">
      {Array.from({ length: 22 }, (_, i) => (
        <span key={i} className={`size-3 rounded-full bg-current animate-garland ${colors[i % 4]}`} style={{ animationDelay: `${(i % 5) * 0.35}s`, marginTop: `${Math.sin((i / 21) * Math.PI) * 18}px` }} />
      ))}
    </div>
  );
}

function Door({ d }: { d: number }) {
  const t = useT();
  const [flipped, setFlipped] = useState(false);
  const story = STORIES[d];
  const cover = useAdvent().media.storyCovers?.[d] || storyCover(d) || story?.[1];
  return (
    <Button variant="ghost" type="button" onClick={() => setFlipped((v) => !v)} aria-label={`${d} ${t("december")}`} aria-pressed={flipped} className="flip-scene door block h-auto aspect-[3/5] w-full whitespace-normal rounded-2xl p-0 hover:bg-transparent sm:aspect-[3/4]">
      <div className={`flip-card h-full w-full ${flipped ? "flipped" : ""}`}>
        <div className={`advent-door-front flip-face flex flex-col items-center justify-center overflow-hidden rounded-2xl border ${d === 24 ? "border-glow/60 bg-primary/25" : "border-border bg-card"}`}>
          <span className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-glow/70 to-transparent" />
          {d === 24 ? <TreePine className="mb-1 size-5 text-glow" /> : story ? <Star className="mb-1 size-4 fill-glow text-glow animate-flicker" /> : <Gift className="mb-1 size-4 text-primary" />}
          <span className="text-3xl font-black text-night-foreground sm:text-4xl">{d}</span>
          <span className="text-[10px] font-extrabold uppercase tracking-widest text-muted-foreground">{t("december")}</span>
        </div>
        <div className="advent-door-back flip-face flip-back overflow-hidden rounded-2xl border border-glow/60 shadow-glow">
          {story ? (
            <>
              {cover ? <img src={cover} alt="" aria-hidden loading="lazy" width={512} height={512} className="absolute inset-0 h-full w-full object-cover" /> : <span aria-hidden className="absolute inset-0 bg-night-deep" />}
              <div className="story-cover-label absolute inset-x-0 bottom-0 flex flex-col items-center justify-end gap-1 px-1.5 pb-3 pt-10 text-center text-night-foreground sm:px-2.5">
                <div className="text-[9px] font-extrabold uppercase text-glow">{d} {t("december")}</div>
                <div className="story-cover-title text-[13px] font-extrabold leading-[1.15] sm:text-[15px]">{story[0]}</div>
              </div>
            </>
          ) : (
            <>
              <img src={hero} alt="" loading="lazy" width={1920} height={1088} className="h-full w-full scale-110 object-cover blur-md" />
              <div className="absolute inset-0 grid place-items-center bg-night-deep/60 p-2 text-center">
                <div><Lock className="mx-auto size-5 text-glow" /><div className="mt-1 font-hand text-lg text-night-foreground">{t("surprise")}</div><div className="text-[10px] font-extrabold uppercase tracking-widest text-night-muted">{t("opens")} {d} {t("december")}</div></div>
              </div>
            </>
          )}
        </div>
      </div>
    </Button>
  );
}

function Calendar() {
  const t = useT();
  return (
    <section id="calendar" className="night-scope bg-stars relative px-5 py-16 sm:px-6 sm:py-24">
      <Snow />
      <SectionHead wide pill={t("how_pill")} icon={<Gift className="size-3.5" />} title={t("behind_title")} accent={t("behind_accent")} sub={<>{t("behind_intro")}<br className="hidden lg:block" /> {t("behind_more")}</>} />
      <Garland />
      <div className="relative mx-auto mt-8 grid max-w-5xl grid-cols-3 gap-3 sm:grid-cols-4 sm:gap-4 md:grid-cols-6">
        {Array.from({ length: 24 }, (_, i) => <Door key={i} d={i + 1} />)}
      </div>
      <PreviewPlayer />
      <Countdown />
    </section>
  );
}

function Formats() {
  const t = useT();
  const ways: [ReactNode, string, string][] = [
    [<Headphones key="1" className="size-6" />, t("listen"), t("format_listen_desc")],
    [<BookOpen key="2" className="size-6" />, t("read"), t("format_read_desc")],
    [<GraduationCap key="3" className="size-6" />, t("learn"), t("format_learn_desc")],
  ];
  return (
    <section id="formats" className="night-scope bg-stars relative px-5 py-16 sm:px-6 sm:py-24">
      <SectionHead pill={t("formats_pill")} icon={<BookOpen className="size-3.5" />} title={t("formats_title")} accent={t("formats_accent")} sub={t("formats_intro")} />
      <div className="mx-auto mt-14 grid max-w-5xl gap-5 md:grid-cols-3">
        {ways.map(([i, t, d], n) => (
          <div key={t} className="advent-format-card lantern relative overflow-hidden rounded-3xl border border-border bg-card p-6">
            <span aria-hidden className="absolute right-5 top-2 font-hand text-5xl text-glow/40">{n + 1}</span>
            <span className="grid size-12 place-items-center rounded-2xl bg-primary/15 text-primary">{i}</span>
            <h3 className="mt-4 text-base font-black">{t}</h3>
            <p className="mt-1.5 text-sm text-muted-foreground">{d}</p>
          </div>
        ))}
      </div>
      <article className="advent-original-frame relative mx-auto mt-10 max-w-5xl overflow-hidden rounded-[2rem] border border-glow/40 bg-card shadow-glow md:grid md:grid-cols-[1.15fr_1fr]">
        <div className="p-7 sm:p-9">
          <p className="font-hand text-2xl text-glow sm:text-3xl">{FRENCH_DECOR.decor_original}</p>
          <h3 className="mt-3 text-lg font-black sm:text-2xl">{t("original_title")}</h3>
          <p className="mt-3 text-muted-foreground">{t("original_desc")}</p>
          <ul className="mt-6 space-y-2.5">
            {[t("original_bullet1"), t("original_bullet2"), t("original_bullet3")].map((x) => (
              <li key={x} className="flex gap-2"><CheckCircle2 className="size-5 shrink-0 fill-primary text-card" />{x}</li>
            ))}
          </ul>
        </div>
        <div className="relative min-h-64">
          <img src={c2} alt={t("illustration_alt")} loading="lazy" width={1024} height={1536} className="absolute inset-0 h-full w-full object-cover" />
          <span aria-hidden className="advent-cat-image-frame absolute inset-3 rounded-2xl border border-glow/50" />
        </div>
      </article>
      <div className="mt-12 text-center"><CtaButton>{t("want_all")}</CtaButton></div>
    </section>
  );
}

function Offer() {
  const t = useT();
  return (
    <section id="offer" className="night-scope bg-fog relative px-5 py-16 sm:px-6 sm:py-24">
      <Snow />
      <SectionHead pill={t("offer_pill")} icon={<Gift className="size-3.5" />} title={t("offer_title")} accent={t("offer_accent")} sub={<>{t("offer_intro")}<span className="mt-1 block font-extrabold text-glow">{t("offer_intro2")}</span></>} />
      <article className="advent-offer-frame relative mx-auto mt-14 max-w-md overflow-hidden rounded-3xl border-2 border-primary bg-card shadow-pink">
        <img src={hero} alt={t("christmas_paris_alt")} loading="lazy" width={1920} height={1088} className="aspect-[2/1] w-full object-cover" />
        <div className="pointer-events-none absolute inset-x-0 top-0 aspect-[2/1] bg-gradient-to-b from-night-deep/40 via-transparent to-card" />
        <div className="flex flex-col p-7">
          <div className="text-xs font-black uppercase tracking-widest text-primary">{t("offer_product")}</div>
          <h3 className="mt-2 text-lg font-black sm:text-2xl">{t("hero_title1")}</h3>
          <ul className="mt-6 space-y-2.5">
            {[t("offer_bullet1"), t("offer_bullet2"), t("offer_bullet3"), t("lifetime")].map((x) => (
              <li key={x} className="flex gap-2"><CheckCircle2 className="size-5 shrink-0 fill-primary text-card" />{x}</li>
            ))}
          </ul>
          <div className="mt-8 text-center text-4xl font-black"><ProductPrice /></div>
          <div className="mt-3 flex justify-center"><CurrencySelector /></div>
          <NativePurchaseButton />
        </div>
      </article>
    </section>
  );
}

function Countdown() {
  const t = useT();
  const [left, setLeft] = useState<number[] | null>(null);
  useEffect(() => {
    const tick = () => {
      const now = new Date();
      const target = new Date("2026-11-30T23:00:00Z");
      const ms = Math.max(0, target.getTime() - now.getTime());
      setLeft([Math.floor(ms / 864e5), Math.floor(ms / 36e5) % 24, Math.floor(ms / 6e4) % 60, Math.floor(ms / 1e3) % 60]);
    };
    tick();
    const t = setInterval(tick, 1000);
    return () => clearInterval(t);
  }, []);
  const opened = left !== null && left.every(x=>x===0);
  return (
    <div className="mx-auto mt-10 max-w-xl text-center">
      <p className="font-hand text-xl text-glow sm:text-2xl">{t(opened?"until25":"countdown_title")}</p>
      {!opened && <>
      <div className="mt-3 flex justify-center gap-2 sm:gap-3">
        {[t("days"), t("hours"), t("minutes"), t("seconds")].map((l, i) => (
          <div key={l} className="advent-timer-box w-16 rounded-2xl border border-glow/40 bg-card py-3 shadow-glow sm:w-20">
            <div className="text-2xl font-black tabular-nums text-night-foreground sm:text-3xl">{left ? String(left[i]).padStart(2, "0") : "--"}</div>
            <div className="text-[10px] font-extrabold uppercase tracking-widest text-muted-foreground">{l}</div>
          </div>
        ))}
      </div></>}
    </div>
  );
}

function GiftBlock() {
  const t = useT();
  const [open, setOpen] = useState(false);
  const stageRef = useRef<HTMLDivElement>(null);
  // Подарок открывается сам один раз, когда до него долистали
  useEffect(() => {
    const el = stageRef.current;
    if (!el || typeof IntersectionObserver === "undefined") return;
    let t: ReturnType<typeof setTimeout> | undefined;
    const io = new IntersectionObserver(([e]) => {
      if (e?.isIntersecting) { t = setTimeout(() => setOpen(true), 700); io.disconnect(); }
    }, { threshold: 0.55 });
    io.observe(el);
    return () => { io.disconnect(); if (t) clearTimeout(t); };
  }, []);
  const toggle = () => setOpen((v) => !v);
  const steps: [ReactNode, string][] = [
    [<Gift key="1" className="size-3.5" />, t("gift_step1")],
    [<Mail key="2" className="size-3.5" />, t("gift_step2")],
    [<CalendarDays key="3" className="size-3.5" />, t("gift_step3")],
  ];
  const ease = "ease-[cubic-bezier(.2,.8,.2,1)] motion-reduce:transition-none";
  return (
    <section id="gift" className="gift-scene night-scope bg-stars relative overflow-hidden px-5 py-12 sm:px-6 sm:py-16">
      <SectionHead pill={t("gift_pill")} icon={<Gift className="size-3.5" />} title={t("gift_title")} accent={<span className="block">{t("gift_accent")}</span>} sub={t("gift_intro")} />
      <div ref={stageRef} className="gift-stage relative mx-auto mt-4 flex max-w-[980px] justify-center pt-[80px] sm:pt-[65px]">
        {/* еловые ветки по краям — как будто подарок под ёлкой */}
        <img src={firBranch} alt="" aria-hidden loading="lazy" width={900} height={841} className="gift-foliage pointer-events-none absolute left-[-30px] top-[-30px] w-[160px] max-w-none brightness-[.9] sm:left-[-35px] sm:top-[-50px] sm:w-[350px] lg:left-0 lg:w-[390px]" />
        <img src={firBranch} alt="" aria-hidden loading="lazy" width={900} height={841} className="gift-foliage pointer-events-none absolute right-[-30px] top-[-10px] w-[160px] max-w-none -scale-x-100 brightness-[.9] sm:right-[-35px] sm:top-[-30px] sm:w-[350px] lg:right-0 lg:w-[390px]" />
        {/* тёплый ореол за подарком */}
        <span aria-hidden className="gift-warmth pointer-events-none absolute bottom-0 left-1/2 h-[90%] w-[min(760px,140%)] -translate-x-1/2" />
        <div className="relative aspect-[1/1.632] w-[280px] max-w-full sm:w-[390px]">
          {/* тень на полу */}
          <span aria-hidden className="absolute -bottom-4 left-1/2 h-8 w-[115%] -translate-x-1/2 rounded-[50%] bg-night-deep/80 blur-md" />
          {/* дно коробки */}
          <Button variant="ghost" type="button" onClick={toggle} aria-expanded={open} aria-label={open ? t("gift_close") : t("gift_open")} className="absolute bottom-0 left-0 z-10 h-auto w-full rounded-none p-0 hover:bg-transparent">
            <img src={boxBase} alt={t("gift_alt")} loading="lazy" width={800} height={753} className="gift-object w-full" />
          </Button>
          {/* Обложки появляются из подарка вместе с открыткой. */}
          <div aria-hidden className={`pointer-events-none absolute left-[-12%] top-[26%] z-[8] w-[31%] transition-[transform,opacity] duration-[1400ms] ${ease} ${open ? "-translate-x-[8%] -translate-y-[15%] -rotate-[18deg] opacity-100 delay-[1100ms]" : "translate-x-[65%] translate-y-[60%] opacity-0"}`}>
            <img src={c1} alt="" className="gift-object w-full rounded-md" />
          </div>
          <div aria-hidden className={`pointer-events-none absolute right-[-12%] top-[26%] z-[8] w-[31%] transition-[transform,opacity] duration-[1400ms] ${ease} ${open ? "translate-x-[8%] -translate-y-[15%] rotate-[18deg] opacity-100 delay-[1200ms]" : "-translate-x-[65%] translate-y-[60%] opacity-0"}`}>
            <img src={c3} alt="" className="gift-object w-full rounded-md" />
          </div>
          {/* свет изнутри */}
          <span aria-hidden className={`gift-light pointer-events-none absolute left-1/2 bottom-[40%] z-[5] h-[100%] w-[150%] -translate-x-1/2 transition-opacity duration-[1400ms] ${ease} ${open ? "opacity-100" : "opacity-0"}`} />
          {/* открытка выезжает из коробки */}
          <div className="absolute inset-x-[7%] bottom-[45.5%] top-[-160px] z-20 flex items-end overflow-hidden sm:top-[-120px]">
            <div className={`w-full transition-transform duration-[1300ms] ${ease} ${open ? "translate-y-[8%] delay-[1250ms]" : "translate-y-[105%]"}`}>
              <div className="advent-gift-paper rounded-2xl border-2 border-glow/70 bg-paper-light p-1.5">
                <div className="rounded-xl border border-dashed border-glow/70 px-3 pb-[52px] pt-3 text-center text-night-deep sm:px-5 sm:pb-[72px] sm:pt-4">
                  <p className="text-[9px] font-extrabold uppercase tracking-[0.2em] text-primary sm:text-[10px]">{t("gift_card_title")}</p>
                  <p className="mt-1 font-hand text-[19px] leading-tight sm:text-3xl">{FRENCH_DECOR.decor_gift_card}</p>
                  <ol className="mt-2 space-y-1.5 text-left sm:mt-3 sm:space-y-2">
                    {steps.map(([icon, t]) => (
                      <li key={t} className="flex items-center gap-2 text-[11.5px] font-bold leading-snug sm:gap-2.5 sm:text-sm">
                        <span className="grid size-5 shrink-0 place-items-center rounded-full bg-primary text-primary-foreground sm:size-6">{icon}</span>{t}
                      </li>
                    ))}
                  </ol>
                  <div className="mt-2 text-xl font-black sm:mt-3 sm:text-[28px]"><ProductPrice /></div>
                  <div className="mt-2 flex justify-center"><CurrencySelector compact /></div>
                  <div className="mt-2 flex justify-center [&_a]:whitespace-nowrap [&_a]:px-3 [&_a]:py-2 [&_a]:text-[12px] sm:[&_a]:px-5 sm:[&_a]:py-2.5 sm:[&_a]:text-sm"><GiftInterest /></div>
                </div>
              </div>
            </div>
          </div>
          {/* крышка с бантом */}
          <Button variant="ghost" type="button" onClick={toggle} disabled={open} tabIndex={open ? -1 : 0} aria-hidden={open} aria-expanded={open} aria-label={t("gift_open")} className={`gift-lid z-30 h-auto rounded-none p-0 hover:bg-transparent ${open ? "gift-lid-open" : ""}`}>
            <img src={boxLid} alt="" aria-hidden loading="lazy" width={800} height={690} className="gift-object w-full" />
          </Button>
        </div>
      </div>
      <p className={`relative mt-5 text-center font-hand text-2xl text-glow transition-opacity duration-700 motion-reduce:transition-none sm:text-3xl ${open ? "opacity-100" : "opacity-0"}`}>{FRENCH_DECOR.decor_gift_end}</p>
      <p className={`relative -mt-8 text-center text-sm font-extrabold text-night-muted transition-opacity duration-500 ${open ? "opacity-0" : "opacity-100"}`}>{t("gift_hint")}</p>
    </section>
  );
}

function FaqItem({ icon, q, a }: { icon: ReactNode; q: string; a: string }) {
  const [open, setOpen] = useState(false);
  return (
    <div className="advent-faq-item border-b border-solid border-border last:border-0">
      <button type="button" onClick={() => setOpen((v) => !v)} aria-expanded={open} className={`advent-faq-trigger flex w-full cursor-pointer items-center gap-3 rounded-2xl px-2 py-4 text-left text-sm font-extrabold transition hover:bg-primary/5 sm:gap-4 sm:px-3 sm:text-base ${open ? "bg-primary/10" : ""}`}>
        <span className={`grid size-10 shrink-0 place-items-center rounded-xl transition ${open ? "bg-primary text-primary-foreground shadow-pink" : "bg-primary/15 text-primary"}`}>{icon}</span>
        <span className="flex-1">{q}</span>
        <Plus className={`size-5 shrink-0 text-primary transition duration-500 ${open ? "rotate-[135deg]" : ""}`} />
      </button>
      <div className={`grid transition-[grid-template-rows,opacity] duration-500 ${open ? "grid-rows-[1fr] opacity-100" : "grid-rows-[0fr] opacity-0"}`}>
        <div className="overflow-hidden"><div className="advent-faq-answer mb-4 mt-2 rounded-2xl border border-glow/30 bg-night-deep/60 px-5 py-4 text-muted-foreground">{a}</div></div>
      </div>
    </div>
  );
}

function Faq() {
  const t = useT();
  const contactLinks = useAdvent().links;
  const qs: [ReactNode, string, string][] = [
    [<GraduationCap key="1" className="size-5" />, t("faq1_q"), t("faq1_a")],
    [<CalendarDays key="2" className="size-5" />, t("faq2_q"), t("faq2_a")],
    [<Clock key="3" className="size-5" />, t("faq3_q"), t("faq3_a")],
    [<Feather key="6" className="size-5" />, t("faq4_q"), t("faq4_a")],
    [<Gift key="5" className="size-5" />, t("faq5_q"), t("gift_faq_temp")],
    [<InfinityIcon key="4" className="size-5" />, t("faq6_q"), t("faq6_a")],
  ];
  return (
    <section className="night-scope bg-stars relative px-5 py-16 sm:px-6 sm:py-24">
      <SectionHead pill={t("faq_pill")} icon={<HelpCircle className="size-3.5" />} title={t("faq_title")} accent={t("faq_accent")} />
      <div className="advent-faq-frame mx-auto mt-14 max-w-4xl rounded-[2rem] border border-border bg-card p-3 backdrop-blur md:p-5">
        {qs.map(([i, q, a]) => <FaqItem key={q} icon={i} q={q} a={a} />)}
      </div>
      <div className="mx-auto mt-10 flex max-w-4xl flex-wrap justify-center gap-2">
        {[[<Send key="t" className="size-4" />, "Telegram", contactLinks.telegram], [<Phone key="w" className="size-4" />, "WhatsApp", contactLinks.whatsapp], [<Mail key="m" className="size-4" />, "support@campusfr.fr", contactLinks.support]].filter((x) => Boolean(x[2])).map(([i, l, h]) => (
          <a key={l as string} href={(h as string) || undefined} aria-disabled={!h} className="advent-contact-button flex items-center gap-2 rounded-full border border-border bg-night-deep/60 py-1 pl-1 pr-4 text-sm font-extrabold text-night-foreground transition hover:border-glow/60"><span className="grid size-8 place-items-center rounded-full bg-primary text-primary-foreground">{i}</span>{l}</a>
        ))}
      </div>
      <p className="mt-12 text-center font-hand text-xl text-glow sm:text-2xl">{FRENCH_DECOR.decor_goodbye}</p>
      <p className="mt-2 text-center text-sm font-extrabold text-night-foreground sm:text-base">{t("goodbye")}</p>

    </section>
  );
}

export default Index;
