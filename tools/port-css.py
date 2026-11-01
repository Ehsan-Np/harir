#!/usr/bin/env python3
"""پورت CSS اصلی طرح حریر به فایل‌های تفکیک‌شدهٔ قالب وردپرس."""
import re, pathlib, collections

SRC = pathlib.Path('/home/user/harir-src/harir')
OUT = pathlib.Path('/home/user/zarincode-harir/theme/zarincode-harir/assets/css')
OUT.mkdir(parents=True, exist_ok=True)

# ---------- پارسر بلوک‌ها (پشتیبانی از @media تودرتو) ----------
def parse(css, depth=0):
    """→ list of (selector, body_or_children, kind) ; kind: 'rule'|'at'"""
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    out, i, n, buf = [], 0, len(css), ''
    while i < n:
        c = css[i]
        if c == '{':
            sel = buf.strip(); buf = ''
            j, lvl = i+1, 1
            while j < n and lvl:
                if css[j] == '{': lvl += 1
                elif css[j] == '}': lvl -= 1
                j += 1
            inner = css[i+1:j-1]
            if sel.startswith('@'):
                low = sel.lower()
                if low.startswith('@media'):
                    out.append((sel, parse(inner, depth+1), 'at'))
                else:
                    out.append((sel, inner.strip(), 'atraw'))
            else:
                out.append((sel, inner.strip(), 'rule'))
            i = j
        else:
            buf += c; i += 1
    return out

def collect_selectors(nodes, acc=None):
    acc = acc if acc is not None else []
    for sel, body, kind in nodes:
        if kind == 'at':
            collect_selectors(body, acc)
        else:
            for s in sel.split(','):
                acc.append(s.strip())
    return acc

# ---------- سطل‌ها ----------
BUCKETS = {
 'header': r'\b(topline|top-inner|header|masthead|mob-menu|brand|brand-mark|brand-name|searchbox|head-actions|trade-chip|action-user|action-bag|nav|nav-links|nav-note|all-cats|mobile-search|mobile-bar|mobile-trade|mobile-label|menu-drawer|mega|burger|offcanvas)\b',
 'footer': r'\b(footer|footer-top|footer-grid|footer-column|footer-services|footer-bottom|footer-about|brand-foot|ft-contact|ft-icon|ft-connect|ft-highlight|newsletter|news-input|newsletter-symbol|socials|social-title|seals|site-credit|to-top)\b',
 'woocommerce': r'\b(pd-[a-z-]+|detail-[a-z-]+|product[a-z-]*|price-row|price|basket-[a-z-]+|cart[a-z-]*|checkout[a-z-]*|catalog-[a-z-]+|sort-bar|spec-table|review-[a-z-]+|colors|swatches|sizes|purchase-toggle|trade-cta|trade-request[a-z-]*|wh-[a-z-]+|cat-image|featured-card|metric|quick-action|stock|badge|ribbon|gallery|related|variation|qty|quantity)\b',
 'panel': r'\b(side-item|panel[a-z-]*|app-top|order-row|order[a-z-]*|timeline-step|wish[a-z-]*|address[a-z-]*|business-profile|tracking-[a-z-]+|invoice-[a-z-]+|rfq[a-z-]*|result-info|ticket[a-z-]*|dash[a-z-]*|stat[a-z-]*|wallet|cashback|wheel|affiliate|login-[a-z-]+|otp[a-z-]*|auth[a-z-]*|upgrade[a-z-]*)\b',
 'content': r'\b(page-heading|flow-steps|flow-card[a-z-]*|article-body|journal-body|read-next|policy-[a-z-]+|faq-row|block-heading|section-head|value-card|contact-line|duo-banner|partner-strip|p-hero[a-z-]*|photo-label|overline|toc-row|crumb|page-crumb|hero|lead|copy|grid|card|tile|list|steps|accordion|tabs|notice|banner)\b',
 'themes': r'\b(royal|organic)\b',
}
SKIP = r'\b(demo-note)\b'   # فقط برای نمایش ایستا بود

BASE_ONLY = {'html','body','*','a','p','ul','ol','li','img','svg','table','h1','h2','h3','h4','h5','h6',
             'input','select','textarea','button','label','form','small','strong','b','i','hr','kbd','figure','figcaption','blockquote','code','pre','span','div'}

def bucket_of(rule_sel):
    parts = [p.strip() for p in rule_sel.split(',')]
    heads = []
    for p in parts:
        toks = re.findall(r'[.#]([A-Za-z_][\w-]*)|^([a-z][a-z0-9]*)\b', p)
        for a,b in toks:
            heads.append((a or b).lower())
    if not heads:
        return 'base'
    if all(h in BASE_ONLY for h in heads):
        return 'base'
    joined = ' '.join(parts)
    if re.search(SKIP, joined):
        return None
    for name, pat in BUCKETS.items():
        if re.search(pat, joined):
            return name
    return 'components'

# ---------- جمع‌آوری ----------
all_nodes = []
for f in ['design.css','secondary.css','complete.css']:
    all_nodes += parse((SRC/f).read_text(encoding='utf-8', errors='replace'))

assign = collections.defaultdict(list)   # bucket -> list of (sel, body)
media  = collections.defaultdict(lambda: collections.defaultdict(list))  # bucket -> query -> rules

def walk(nodes, in_media=None):
    for sel, body, kind in nodes:
        if kind == 'at':
            walk(body, sel); continue
        if kind == 'atraw':
            assign['base'].append((sel, body, 'atraw')); continue
        b = bucket_of(sel)
        if b is None: continue
        if in_media: media[b][in_media].append((sel, body))
        else:      assign[b].append((sel, body, 'rule'))

walk(all_nodes)

# ---------- پاک‌سازی ----------
def clean(body):
    body = re.sub(r"font-display\s*:\s*block", 'font-display:swap', body)
    # مسیر فونت‌ها در قالب
    body = body.replace("assets/fonts/", "../fonts/")
    # حذف کامنت‌های باقی‌مانده
    return re.sub(r'\s+', ' ', body).strip()

HEADERS = {
 'base':       'پایه — reset، تایپوگرافی، ظرف، دکمه‌ها و فیلدها',
 'header':     'هدر — نوار بالا، مَستهِد، جست‌وجو، اکشن‌ها و ناوبری',
 'footer':     'فوتر — ستون‌ها، خبرنامه، شبکه‌ها، نمادها و بازگشت به بالا',
 'components': 'کامپوننت‌های عمومی — کارت‌ها، بخش‌ها و الگوهای مشترک',
 'content':    'محتوا — صفحات داخلی، مقالات، سیاست‌ها، پرسش‌ها و مسیرها',
 'woocommerce':'فروشگاه — محصول، گالری، خریدباکس، قیمت، سبد، پرداخت و کاتالوگ',
 'panel':      'پنل کاربری، احراز هویت، سفارش‌ها، استعلام‌ها و تیکت‌ها',
 'themes':     'پوسته‌های رنگی جایگزین (royal / organic)',
}

total = 0
for name in ['base','header','footer','components','content','woocommerce','panel','themes']:
    rules = assign.get(name, [])
    if not rules and name not in media: continue
    lines = [f"/**\n * حریر رویا — {HEADERS.get(name,name)}\n *\n * پورت‌شده از منبع اصلی طرح (design.css + secondary.css + complete.css)\n * با حفظ ۱:۱ سلکتورها تا خروجی دقیقاً مطابق HTML مرجع باشد.\n *\n * @package Zarincode_Harir\n */\n"]
    for sel, body, kind in rules:
        if kind in ('at','atraw'):
            lines.append(f"{sel} {{\n\t{clean(body)}\n}}\n")
        else:
            lines.append(f"{sel} {{ {clean(body)} }}\n")
        total += 1
    for query, mrules in media.get(name, {}).items():
        lines.append(f"\n{query} {{")
        for sel, body in mrules:
            lines.append(f"\t{sel} {{ {clean(body)} }}")
            total += 1
        lines.append("}\n")
    out = OUT / f"{name}.css"
    out.write_text('\n'.join(lines), encoding='utf-8')
    print(f"  ✅ {name}.css  →  {out.stat().st_size//1024}KB")

print(f"\nمجموع قاعده‌های پورت‌شده: {total} از 1389")
