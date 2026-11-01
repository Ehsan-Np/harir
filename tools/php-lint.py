#!/usr/bin/env python3
"""بررسی سبک نحو PHP بدون نیاز به مفسر PHP (برای محیط‌های بدون php-cli).

موارد بررسی: تعادل {} () [] خارج از رشته/کامنت، باز بودن رشته، heredoc نابسته،
وجود <?php در ابتدای فایل، وجود `defined('ABSPATH')`، و استفاده از ساختارهای
PHP 8+ در پروژه‌ای که حداقل PHP آن 7.4 اعلام شده.
"""
import re, sys, pathlib

MIN_PHP = (7, 4)

def strip(src):
    """حذف رشته‌ها و کامنت‌ها؛ جای‌گذاری با فاصله تا موقعیت‌ها حفظ شود."""
    out, i, n = [], 0, len(src)
    while i < n:
        c = src[i]
        if c == '/' and i + 1 < n and src[i+1] == '/':
            while i < n and src[i] != '\n':
                out.append(' '); i += 1
        elif c == '#':
            while i < n and src[i] != '\n':
                out.append(' '); i += 1
        elif c == '/' and i + 1 < n and src[i+1] == '*':
            while i < n and not (src[i] == '*' and i+1 < n and src[i+1] == '/'):
                out.append('\n' if src[i] == '\n' else ' '); i += 1
            out.append('  '); i += 2
        elif c in '"\'':
            q = c; out.append(' '); i += 1
            while i < n and src[i] != q:
                if src[i] == '\\':
                    out.append('  '); i += 2; continue
                out.append('\n' if src[i] == '\n' else ' '); i += 1
            if i >= n:
                return ''.join(out), 'رشتهٔ باز'
            out.append(' '); i += 1
        else:
            out.append(c); i += 1
    return ''.join(out), None

def line_of(src, pos):
    return src.count('\n', 0, pos) + 1

PHP8_ONLY = [
    (r'\bmatch\s*\(', 'match() — فقط PHP 8.0+'),
    (r'\breadonly\s+(?:class|var|public|private|protected)', 'readonly — فقط PHP 8.1+'),
    (r'\benum\s+\w+\s*[:{]', 'enum — فقط PHP 8.1+'),
    (r'function\s+__construct\s*\(\s*(?:public|private|protected)\s', 'constructor promotion — فقط PHP 8.0+'),
    (r'\?\->', 'nullsafe operator — فقط PHP 8.0+'),
    (r'(?:\(|,)\s*[a-z_][a-z0-9_]*\s*:\s*(?!:)', 'named arguments — احتمالاً PHP 8.0+'),
]

def check(path):
    src = path.read_text(encoding='utf-8', errors='replace')
    errs = []
    if not src.lstrip().startswith('<?php'):
        errs.append('فایل با <?php شروع نمی‌شود')
    if "<?php" in src and "ABSPATH" not in src and 'autoload' not in path.name:
        errs.append("محافظ `defined( 'ABSPATH' ) || exit;` ندارد")
    clean, err = strip(src)
    if err:
        errs.append(err)
        return errs
    stack = []
    pairs = {'}': '{', ')': '(', ']': '['}
    for i, c in enumerate(clean):
        if c in '{([':
            stack.append((c, i))
        elif c in '})]':
            if not stack:
                errs.append(f'خط {line_of(src,i)}: «{c}» بدون جفت باز')
            elif stack[-1][0] != pairs[c]:
                errs.append(f'خط {line_of(src,i)}: «{c}» با «{stack[-1][0]}» (خط {line_of(src,stack[-1][1])}) جفت نیست')
                stack.pop()
            else:
                stack.pop()
    for c, i in stack:
        errs.append(f'خط {line_of(src,i)}: «{c}» بسته نشده')
    if '<<<' in clean:
        errs.append('heredoc پیدا شد — با دقت بازبینی شود')
    for pat, msg in PHP8_ONLY:
        for m in re.finditer(pat, clean):
            errs.append(f'خط {line_of(src,m.start())}: {msg}')
    if re.search(r'^\t* {4,}', clean, re.M):
        pass  # تورفتگی با فاصله — فقط اطلاع‌رسانی در گزارش، خطا نیست
    return errs

def main():
    root = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else '.')
    files = sorted(root.rglob('*.php'))
    bad = 0
    for f in files:
        e = check(f)
        if e:
            bad += 1
            print(f'✗ {f}')
            for x in e:
                print(f'    - {x}')
    print(f'\n{len(files)} فایل PHP بررسی شد · {len(files)-bad} سالم · {bad} دارای مشکل')
    return 1 if bad else 0

sys.exit(main())
