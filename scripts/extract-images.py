#!/usr/bin/env python3
"""Trích ảnh từ design bundle, map theo TIÊU ĐỀ nội dung để import vào node.

Chuỗi map trong bundle:
  news item { title, img: 'images/real-xxx.png' }
    -> RESMAP['images/real-xxx.png'] = 'imgXxx'          (trong component JS)
    -> ext_resources: id 'imgXxx' -> uuid                (<script __bundler/ext_resources>)
    -> manifest[uuid] = ảnh base64                       (<script __bundler/manifest>)

Xuất: ảnh ra thư mục + JSON { "tiêu đề": "file.png" } để import-images.php gắn
vào node theo title.

Dùng:
    python3 scripts/extract-images.py --out web/sites/default/files/design-images \
                                      --map /tmp/nidqc-images.json

Chỉ đọc design/. Không ghi vào đó.
"""

import argparse
import base64
import gzip
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
DESIGN = ROOT / "design"

TEMPLATE_RE = re.compile(r'<script type="__bundler/template"[^>]*>(.*?)</script>', re.S)
MANIFEST_RE = re.compile(r'<script type="__bundler/manifest"[^>]*>(.*?)</script>', re.S)
EXTRES_RE = re.compile(r'<script type="__bundler/ext_resources"[^>]*>(.*?)</script>', re.S)
EXT = {"image/png": "png", "image/jpeg": "jpg", "image/webp": "webp", "image/gif": "gif"}


def parse_bundle(path: pathlib.Path):
    html = path.read_text(encoding="utf-8")
    tpl_m = TEMPLATE_RE.search(html)
    man_m = MANIFEST_RE.search(html)
    ext_m = EXTRES_RE.search(html)
    if not (tpl_m and man_m):
        return None, None, None
    tpl = json.loads(tpl_m.group(1))
    manifest = json.loads(man_m.group(1))
    ext = {r["id"]: r["uuid"] for r in json.loads(ext_m.group(1))} if ext_m else {}
    return tpl, manifest, ext


def resmap(js: str) -> dict:
    """Đọc const RESMAP = { 'images/x.png': 'imgX', ... }."""
    m = re.search(r"RESMAP\s*=\s*\{", js)
    if not m:
        return {}
    depth = 0
    i = js.index("{", m.start())
    for k in range(i, len(js)):
        if js[k] == "{":
            depth += 1
        elif js[k] == "}":
            depth -= 1
            if depth == 0:
                block = js[i : k + 1]
                break
    else:
        return {}
    return {a: b for a, b in re.findall(r"'([^']+)'\s*:\s*'([^']+)'", block)}


def title_img_pairs(js: str) -> list:
    """Mọi cặp (title, img) trong dữ liệu bundle."""
    pairs = []
    for m in re.finditer(
        r"title:\s*'((?:[^'\\]|\\.)*)'[^{}]*?img:\s*'([^']*)'", js
    ):
        title = m.group(1).replace("\\'", "'").replace('\\"', '"')
        pairs.append((title, m.group(2)))
    return pairs


def image_bytes(manifest: dict, uuid: str):
    meta = manifest.get(uuid)
    if not meta:
        return None, None
    data = base64.b64decode(meta["data"])
    if meta.get("compressed"):
        data = gzip.decompress(data)
    return data, EXT.get(meta.get("mime", ""), "png")


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--out", required=True, help="thư mục ghi ảnh")
    p.add_argument("--map", required=True, help="JSON title -> filename")
    args = p.parse_args()

    out = pathlib.Path(args.out)
    out.mkdir(parents=True, exist_ok=True)

    title_to_file = {}
    seen_uuid = {}  # uuid -> filename (khử trùng: 1 ảnh dùng cho nhiều tin)

    for bundle in sorted(DESIGN.glob("*.html")):
        tpl, manifest, ext = parse_bundle(bundle)
        if tpl is None:
            continue
        js = tpl[tpl.find("class Component"):]
        rmap = resmap(js)
        for title, img in title_img_pairs(js):
            if not title or not img:
                continue
            name = rmap.get(img)
            uuid = ext.get(name) if name else None
            if not uuid:
                continue
            if uuid in seen_uuid:
                title_to_file.setdefault(title, seen_uuid[uuid])
                continue
            data, e = image_bytes(manifest, uuid)
            if not data:
                continue
            fn = f"{uuid[:8]}.{e}"
            (out / fn).write_bytes(data)
            seen_uuid[uuid] = fn
            title_to_file.setdefault(title, fn)

    pathlib.Path(args.map).write_text(
        json.dumps(title_to_file, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    print(f"Đã ghi {len(seen_uuid)} ảnh -> {out}")
    print(f"Map {len(title_to_file)} tiêu đề -> {args.map}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
