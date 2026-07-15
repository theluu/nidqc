#!/usr/bin/env python3
"""Trích font woff2 từ design bundle của NIDQC và sinh @font-face.

Design bundle nhúng sẵn toàn bộ font dạng base64 trong manifest, và khai báo
@font-face trong helmet của template. Script này đọc cả hai, ghép lại, rồi ghi ra
file woff2 + CSS.

Vì sao sinh CSS bằng script thay vì gõ tay: mỗi @font-face có một `unicode-range`
dài ~200 ký tự. Gõ sai một ký tự là chữ tiếng Việt rơi về font khác, và lỗi đó rất
khó nhìn ra. Script copy nguyên văn từ design.

Dùng:
    python3 scripts/extract-fonts.py --out web/themes/custom/nidqc/fonts \
                                     --css web/themes/custom/nidqc/css/fonts.css
    python3 scripts/extract-fonts.py --list        # xem sẽ trích gì, không ghi

Chỉ đọc design/. Không bao giờ ghi vào đó.
"""

import argparse
import base64
import gzip
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
DEFAULT_BUNDLE = ROOT / "design" / "NIDQC FAQ.html"

TEMPLATE_RE = re.compile(r'<script type="__bundler/template"[^>]*>(.*?)</script>', re.S)
MANIFEST_RE = re.compile(r'<script type="__bundler/manifest"[^>]*>(.*?)</script>', re.S)
FACE_RE = re.compile(r"@font-face\s*\{(.*?)\}", re.S)

# Chỉ self-host hai font này. Roboto Mono CỐ Ý bị loại: dùng đúng 1 lần trong
# toàn bộ design (trang Đào tạo NCKH) mà tốn ~134 KB. Xem tasks/TASK-004.md §3.
KEEP = {"Lexend", "Be Vietnam Pro"}

WOFF2_MAGIC = b"wOF2"


def slug(name: str) -> str:
    return name.lower().replace(" ", "-")


def subset_of(unicode_range: str) -> str:
    """Suy subset từ unicode-range. Dựa vào ký tự đặc trưng của từng dải."""
    if "U+1EA0" in unicode_range:
        return "vietnamese"
    if "U+0100" in unicode_range:
        return "latin-ext"
    return "latin"


def parse_faces(bundle: pathlib.Path) -> dict:
    """uuid -> {family, weights:set, subset, unicode_range}.

    Gom theo uuid vì Lexend là variable font: cả weight 500/600/700 của một subset
    trỏ vào CÙNG một file. Không gom thì sẽ ghi trùng một nội dung 3 lần.
    """
    html = bundle.read_text(encoding="utf-8")
    m = TEMPLATE_RE.search(html)
    if not m:
        raise SystemExit(f"{bundle.name}: không phải file bundle")
    tpl = json.loads(m.group(1))
    head = tpl[: tpl.find("</helmet>")]

    faces = {}
    for body in FACE_RE.findall(head):
        fam = re.search(r"font-family:\s*'([^']+)'", body)
        weight = re.search(r"font-weight:\s*(\d+)", body)
        src = re.search(r'src:\s*url\("([^"]+)"\)', body)
        urange = re.search(r"unicode-range:\s*([^;]+);", body)
        if not (fam and weight and src and urange):
            continue
        if fam.group(1) not in KEEP:
            continue
        uuid = src.group(1)
        entry = faces.setdefault(
            uuid,
            {
                "family": fam.group(1),
                "weights": set(),
                "subset": subset_of(urange.group(1)),
                "unicode_range": urange.group(1).strip(),
            },
        )
        entry["weights"].add(int(weight.group(1)))
    return faces


def load_blobs(bundle: pathlib.Path) -> dict:
    html = bundle.read_text(encoding="utf-8")
    m = MANIFEST_RE.search(html)
    if not m:
        raise SystemExit(f"{bundle.name}: không có manifest")
    out = {}
    for uuid, meta in json.loads(m.group(1)).items():
        data = base64.b64decode(meta["data"])
        if meta.get("compressed"):
            data = gzip.decompress(data)
        out[uuid] = data
    return out


def weight_label(weights: set) -> str:
    ws = sorted(weights)
    return str(ws[0]) if len(ws) == 1 else f"{ws[0]}-{ws[-1]}"


def filename_for(entry: dict) -> str:
    return f"{slug(entry['family'])}-{weight_label(entry['weights'])}-{entry['subset']}.woff2"


# Thứ tự subset khớp design: vietnamese -> latin-ext -> latin.
SUBSET_ORDER = {"vietnamese": 0, "latin-ext": 1, "latin": 2}


def sort_key(entry: dict):
    return (entry["family"], min(entry["weights"]), SUBSET_ORDER[entry["subset"]])


def render_css(entries: list) -> str:
    lines = [
        "/**",
        " * @file",
        " * @font-face cho theme NIDQC — font self-host, KHÔNG gọi Google Fonts CDN.",
        " *",
        " * FILE NÀY ĐƯỢC SINH TỰ ĐỘNG. Đừng sửa tay.",
        " *   python3 scripts/extract-fonts.py --out web/themes/custom/nidqc/fonts \\",
        " *                                    --css web/themes/custom/nidqc/css/fonts.css",
        " *",
        " * unicode-range copy nguyên văn từ design bundle. Sai một ký tự là chữ tiếng",
        " * Việt rơi về font khác — lỗi rất khó nhìn ra bằng mắt.",
        " *",
        " * Lexend là variable font: một file phục vụ cả dải weight (font-weight: 500 700).",
        " * Be Vietnam Pro tách file theo từng weight.",
        " */",
        "",
    ]
    for e in entries:
        ws = sorted(e["weights"])
        weight_css = str(ws[0]) if len(ws) == 1 else f"{ws[0]} {ws[-1]}"
        lines += [
            f"/* {e['family']} — {e['subset']} */",
            "@font-face {",
            f"  font-family: '{e['family']}';",
            "  font-style: normal;",
            f"  font-weight: {weight_css};",
            "  font-display: swap;",
            f"  src: url('../fonts/{filename_for(e)}') format('woff2');",
            f"  unicode-range: {e['unicode_range']};",
            "}",
            "",
        ]
    return "\n".join(lines)


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--bundle", default=str(DEFAULT_BUNDLE), help="file design nguồn")
    p.add_argument("--out", help="thư mục ghi woff2")
    p.add_argument("--css", help="đường dẫn ghi fonts.css")
    p.add_argument("--list", action="store_true", help="chỉ liệt kê, không ghi")
    args = p.parse_args()

    bundle = pathlib.Path(args.bundle)
    if not bundle.is_file():
        print(f"Không tìm thấy: {bundle}", file=sys.stderr)
        return 1

    faces = parse_faces(bundle)
    entries = sorted(faces.values(), key=sort_key)
    uuid_by_name = {filename_for(e): u for u, e in faces.items()}

    if args.list:
        print(f"{'FILE':44} {'WEIGHT':10} SUBSET")
        for e in entries:
            print(f"  {filename_for(e):42} {weight_label(e['weights']):10} {e['subset']}")
        print(f"\n  Tổng: {len(entries)} file")
        return 0

    if not args.out:
        p.print_help()
        return 2

    blobs = load_blobs(bundle)
    out_dir = pathlib.Path(args.out)
    out_dir.mkdir(parents=True, exist_ok=True)

    written = 0
    for name, uuid in sorted(uuid_by_name.items()):
        data = blobs.get(uuid)
        if data is None:
            print(f"⛔ {name}: không có blob cho uuid {uuid}", file=sys.stderr)
            return 1
        # Kiểm magic TRƯỚC khi ghi. Thà không có file còn hơn có file hỏng:
        # file hỏng vẫn "tồn tại", vẫn đếm được, nhưng trình duyệt lặng lẽ bỏ qua
        # và rơi về font fallback — nhìn gần giống nên rất dễ tưởng là đạt.
        if data[:4] != WOFF2_MAGIC:
            print(f"⛔ {name}: không phải woff2 (magic={data[:4]!r})", file=sys.stderr)
            return 1
        (out_dir / name).write_bytes(data)
        written += 1

    print(f"Đã ghi {written} file woff2 -> {out_dir}")

    if args.css:
        css_path = pathlib.Path(args.css)
        css_path.parent.mkdir(parents=True, exist_ok=True)
        css_path.write_text(render_css(entries), encoding="utf-8")
        print(f"Đã sinh {len(entries)} khối @font-face -> {css_path}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
