#!/usr/bin/env python3
"""Trích template + tài nguyên từ file design bundled của NIDQC.

Design trong design/*.html là HTML tự giải nén: markup thật nằm trong
<script type="__bundler/template"> dưới dạng chuỗi JSON, tài nguyên (font, ảnh)
nằm trong <script type="__bundler/manifest"> dạng base64 (có thể gzip).

Dùng:
    python3 scripts/extract-design.py "design/NIDQC FAQ.html"
    python3 scripts/extract-design.py --all --colors
    python3 scripts/extract-design.py "design/NIDQC FAQ.html" -o /tmp/out

Chỉ đọc. Không bao giờ ghi vào design/.
"""

import argparse
import base64
import collections
import gzip
import json
import pathlib
import re
import sys

TEMPLATE_RE = re.compile(r'<script type="__bundler/template"[^>]*>(.*?)</script>', re.S)
MANIFEST_RE = re.compile(r'<script type="__bundler/manifest"[^>]*>(.*?)</script>', re.S)
HEX_RE = re.compile(r"#[0-9a-fA-F]{6}\b")
DESIGN_DIR = pathlib.Path(__file__).resolve().parent.parent / "design"


def read_template(path: pathlib.Path) -> str:
    """Trả về markup thật của một file design. Rỗng nếu không phải bundle."""
    html = path.read_text(encoding="utf-8")
    m = TEMPLATE_RE.search(html)
    if not m:
        return ""
    return json.loads(m.group(1))


def read_body(path: pathlib.Path) -> str:
    """Markup sau khối <helmet> (bỏ phần @font-face dài dòng)."""
    tpl = read_template(path)
    i = tpl.find("</helmet>")
    return tpl[i:] if i != -1 else tpl


def read_resources(path: pathlib.Path) -> dict:
    """uuid -> (mime, bytes) cho mọi tài nguyên nhúng."""
    html = path.read_text(encoding="utf-8")
    m = MANIFEST_RE.search(html)
    if not m:
        return {}
    out = {}
    for uuid, meta in json.loads(m.group(1)).items():
        data = base64.b64decode(meta["data"])
        if meta.get("compressed"):
            data = gzip.decompress(data)
        out[uuid] = (meta.get("mime", "application/octet-stream"), data)
    return out


def design_files() -> list:
    return sorted(DESIGN_DIR.glob("*.html"))


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("file", nargs="?", help="file design cần trích")
    p.add_argument("--all", action="store_true", help="xử lý toàn bộ design/")
    p.add_argument("--colors", action="store_true", help="thống kê màu thay vì in markup")
    p.add_argument("--resources", action="store_true", help="liệt kê tài nguyên nhúng")
    p.add_argument("-o", "--out", help="thư mục ghi markup đã trích")
    args = p.parse_args()

    if args.all:
        targets = design_files()
    elif args.file:
        targets = [pathlib.Path(args.file)]
    else:
        p.print_help()
        return 2

    missing = [t for t in targets if not t.is_file()]
    if missing:
        print(f"Không tìm thấy: {', '.join(str(m) for m in missing)}", file=sys.stderr)
        return 1

    if args.colors:
        counter = collections.Counter()
        for t in targets:
            counter.update(c.upper() for c in HEX_RE.findall(read_body(t)))
        if not counter:
            print("Không tìm thấy màu nào.", file=sys.stderr)
            return 1
        width = max(len(str(n)) for n in counter.values())
        for hex_code, n in counter.most_common():
            print(f"{n:>{width}}  {hex_code}")
        return 0

    if args.resources:
        for t in targets:
            res = read_resources(t)
            print(f"\n=== {t.name} — {len(res)} tài nguyên ===")
            by_mime = collections.Counter(mime for mime, _ in res.values())
            for mime, n in by_mime.most_common():
                total = sum(len(b) for m, b in res.values() if m == mime)
                print(f"  {n:>3}  {mime:<28} {total:>10,} bytes")
        return 0

    out_dir = pathlib.Path(args.out) if args.out else None
    if out_dir:
        out_dir.mkdir(parents=True, exist_ok=True)

    for t in targets:
        body = read_body(t)
        if not body:
            print(f"{t.name}: không phải file bundle, bỏ qua", file=sys.stderr)
            continue
        if out_dir:
            dest = out_dir / (t.stem + ".tpl.html")
            dest.write_text(body, encoding="utf-8")
            print(f"{t.name}  ->  {dest}  ({len(body):,} bytes)")
        else:
            print(body)
    return 0


if __name__ == "__main__":
    sys.exit(main())
