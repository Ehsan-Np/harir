#!/bin/bash
# همگام‌سازی درخت فعال پروژه با کلون گیت (rsync در سندباکس نیست).
# استفاده: tools/sync-to-repo.sh
set -e

SRC=/home/user/zarincode-harir
DST=/home/user/harir

for top in docs plugin theme tools README.md; do
	if [ -e "$SRC/$top" ]; then
		mkdir -p "$DST/$top"
		cp -a "$SRC/$top/." "$DST/$top/" 2>/dev/null || cp -a "$SRC/$top" "$DST/"
	fi
done

# حذف فایل‌هایی که در مقصد مانده‌اند ولی در مبدأ نیستند
cd "$DST"
git ls-files -z | while IFS= read -r -d '' f; do
	case "$f" in
		harir.zip|.gitignore|.git/*) continue ;;
	esac
	if [ ! -e "$SRC/$f" ]; then
		echo "حذف می‌شود: $f"
		rm -f "$DST/$f"
	fi
done

echo "همگام‌سازی کامل شد."
