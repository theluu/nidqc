#!/usr/bin/env python3
"""Trích NỘI DUNG từ design bundle ra JSON để nhập vào Drupal.

Design đã được NIDQC duyệt, nên nội dung trong đó là nội dung khởi tạo của site.
Script này đọc các cấu trúc dữ liệu JS trong design và xuất ra JSON chuẩn để
`scripts/import-content.php` tạo node.

Vì sao tách 2 bước (trích -> JSON -> nhập):
  - Trích chạy được trên máy không có Drupal.
  - JSON xem được bằng mắt trước khi nhập vào DB.
  - Nhập lại được nhiều lần (idempotent) mà không cần đọc lại design.

Dùng:
    python3 scripts/extract-content.py -o /tmp/nidqc-content.json
    python3 scripts/extract-content.py --stats

Chỉ đọc design/. Không bao giờ ghi vào đó.
"""

import argparse
import json
import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parent.parent
DESIGN = ROOT / "design"
TEMPLATE_RE = re.compile(r'<script type="__bundler/template"[^>]*>(.*?)</script>', re.S)


def component_js(page: str) -> str:
    """Phần JS của component trong một trang design."""
    html = (DESIGN / f"NIDQC {page}.html").read_text(encoding="utf-8")
    m = TEMPLATE_RE.search(html)
    if not m:
        raise SystemExit(f"{page}: không phải file bundle")
    tpl = json.loads(m.group(1))
    return tpl[tpl.find("class Component"):]


def literal(js: str, name: str, open_ch: str = "["):
    """Cắt ra một literal JS (mảng hoặc object) theo tên biến, cân bằng ngoặc."""
    m = re.search(r"(?:const\s+)?" + re.escape(name) + r"\s*[:=]\s*" + re.escape(open_ch), js)
    if not m:
        return None
    close_ch = "]" if open_ch == "[" else "}"
    i = js.index(open_ch, m.start())
    depth = 0
    in_str = None
    k = i
    while k < len(js):
        c = js[k]
        if in_str:
            if c == "\\":
                k += 2
                continue
            if c == in_str:
                in_str = None
        elif c in "'\"":
            in_str = c
        elif c == open_ch:
            depth += 1
        elif c == close_ch:
            depth -= 1
            if depth == 0:
                return js[i:k + 1]
        k += 1
    return None


def objects(literal_src: str) -> list:
    """Tách các object `{...}` cấp 1 trong một literal, trả về list chuỗi."""
    out = []
    depth = 0
    start = None
    in_str = None
    k = 0
    while k < len(literal_src):
        c = literal_src[k]
        if in_str:
            if c == "\\":
                k += 2
                continue
            if c == in_str:
                in_str = None
        elif c in "'\"":
            in_str = c
        elif c == "{":
            if depth == 0:
                start = k
            depth += 1
        elif c == "}":
            depth -= 1
            if depth == 0 and start is not None:
                out.append(literal_src[start:k + 1])
                start = None
        k += 1
    return out


def fields(obj_src: str) -> dict:
    """Đọc các cặp key: 'value' cấp 1 của một object JS."""
    out = {}
    for m in re.finditer(r"(\w+)\s*:\s*'((?:[^'\\]|\\.)*)'", obj_src):
        out[m.group(1)] = m.group(2).replace("\\'", "'").replace('\\"', '"')
    return out


def extract() -> dict:
    data = {}

    # --- Tin tức: allItems[{tag, cat, title, date, img}]
    js = component_js("Tin tuc danh sach")
    cats = {f["id"]: f["label"] for f in map(fields, objects(literal(js, "catDefs")))}
    data["news"] = []
    for o in objects(literal(js, "allItems")):
        f = fields(o)
        if not f.get("title"):
            continue
        data["news"].append({
            "title": f["title"],
            "tag": f.get("tag", ""),
            "category": cats.get(f.get("cat", ""), ""),
            "date": f.get("date", ""),
            "image": f.get("img", ""),
        })

    # --- Văn bản: docsByTab là OBJECT {phapquy:[...], chuyenmon:[...]}
    js = component_js("Van ban tai lieu")
    tabs = {f["id"]: f["label"] for f in map(fields, objects(literal(js, "tabDefs")))}
    src = literal(js, "docsByTab", "{")
    data["document"] = []
    for tab_id, tab_label in tabs.items():
        m = re.search(re.escape(tab_id) + r"\s*:\s*\[", src)
        if not m:
            continue
        inner = literal(src[m.start():], tab_id)
        for o in objects(inner or ""):
            f = fields(o)
            if not f.get("title"):
                continue
            data["document"].append({
                "title": f["title"],
                "meta": f.get("meta", ""),
                "group": tab_label,
            })

    # --- FAQ: rawGroups[{title, items:[{question, answer}]}]
    js = component_js("FAQ")
    data["faq"] = []
    src = literal(js, "rawGroups")
    for g in objects(src):
        gm = re.search(r"title:\s*'((?:[^'\\]|\\.)*)'", g)
        group = gm.group(1) if gm else ""
        items_src = literal(g, "items")
        for o in objects(items_src or ""):
            f = fields(o)
            if not f.get("question"):
                continue
            data["faq"].append({
                "title": f["question"],
                "answer": f.get("answer", ""),
                "group": group,
            })

    # --- {name, desc} / {title, desc, year}
    for page, var, key in [
        ("Co cau to chuc", "depts", "department"),
        ("Nang luc", "equipment", "equipment"),
        ("Nang luc", "certs", "certificate"),
        ("Dao tao NCKH", "projects", "project"),
    ]:
        js = component_js(page)
        src = literal(js, var)
        data[key] = []
        for o in objects(src or ""):
            f = fields(o)
            title = f.get("name") or f.get("title")
            if not title:
                continue
            row = {"title": title, "description": f.get("desc", "")}
            if f.get("year"):
                row["year"] = f["year"]
            data[key].append(row)

    return data


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("-o", "--out", help="ghi JSON ra đây")
    p.add_argument("--stats", action="store_true", help="chỉ đếm")
    args = p.parse_args()

    data = extract()

    if args.stats or not args.out:
        total = 0
        for k, v in data.items():
            print(f"  {k:14} {len(v):3} bản ghi")
            total += len(v)
        print(f"  {'TỔNG':14} {total:3}")
        if not args.out:
            return 0

    pathlib.Path(args.out).write_text(
        json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    print(f"Đã ghi -> {args.out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
