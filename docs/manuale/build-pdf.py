#!/usr/bin/env python3
"""Genera i PDF del manuale d'uso di Roosko dai file Markdown di questa cartella.

Uso (dalla cartella del manuale o da qualsiasi altra):

    python3 build-pdf.py

Requisiti: Python 3, `pip install markdown playwright` e `playwright install chromium`.

Produce in `pdf/`:
- `roosko-manuale-uso.pdf`: il manuale completo;
- un PDF per ogni tipo di utente (operatore di piazzale, accettazione e uscite, responsabile
  tecnico, area clienti, amministrazione), da consegnare a chi fa quel lavoro.

Il Markdown resta la fonte: dopo ogni modifica ai `.md` o alle schermate in `img/` si rilancia lo
script e si committano insieme `.md` e `.pdf`.
"""

from __future__ import annotations

import base64
import datetime
import re
from pathlib import Path

import markdown
from playwright.sync_api import sync_playwright

HERE = Path(__file__).resolve().parent
OUT = HERE / "pdf"

INTRO = "README.md"
CHAPTERS = {
    "piazzale.md": ("piazzale", "Operatore di piazzale", "manuale-operatore-piazzale.pdf"),
    "accettazione-uscite.md": ("accettazione", "Pesa, accettazione e uscite", "manuale-accettazione-uscite.pdf"),
    "responsabile-tecnico.md": ("tecnico", "Responsabile tecnico", "manuale-responsabile-tecnico.pdf"),
    "area-clienti.md": ("clienti", "Cliente dell'impianto", "manuale-area-clienti.pdf"),
    "amministrazione.md": ("amministrazione", "Amministratore", "manuale-amministrazione.pdf"),
}

CSS = """
@page { size: A4; }
body { font-family: Inter, 'DejaVu Sans', Arial, sans-serif; font-size: 10.5pt; line-height: 1.5; color: #18181b; }
section { page-break-before: always; }
h1 { font-size: 22pt; color: #0f766e; margin: 0 0 10pt; }
h2 { font-size: 14pt; margin: 16pt 0 6pt; border-bottom: 1px solid #e4e4e7; padding-bottom: 3pt; }
h3 { font-size: 11.5pt; margin: 12pt 0 4pt; }
table { border-collapse: collapse; width: 100%; font-size: 9pt; margin: 6pt 0; }
th, td { border: 1px solid #e4e4e7; padding: 4pt 6pt; text-align: left; vertical-align: top; }
th { background: #f4f4f5; }
img.shot { width: 100%; border: 1px solid #e4e4e7; border-radius: 6pt; margin: 6pt 0 2pt; page-break-inside: avoid; }
img.phone { width: 42%; border: 1px solid #e4e4e7; border-radius: 10pt; margin: 6pt 0 2pt; display: block; }
p > em:only-child { display: block; color: #71717a; font-size: 9pt; margin-bottom: 8pt; }
code { font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt; background: #f4f4f5; padding: 0 2pt; border-radius: 3pt; }
blockquote { border-left: 3pt solid #0f766e; margin: 8pt 0; padding: 2pt 10pt; color: #3f3f46; background: #f0fdfa; }
.cover { height: 240mm; display: flex; flex-direction: column; justify-content: center; }
.cover .logo { width: 90mm; }
.cover h1 { font-size: 34pt; }
.cover p { font-size: 13pt; color: #52525b; }
"""

FOOTER = (
    '<div style="font-size:8px;width:100%;text-align:center;color:#71717a">'
    'Roosko — {title} · <span class="pageNumber"></span>/<span class="totalPages"></span></div>'
)


def chapter_html(file_name: str, anchors: dict[str, str]) -> str:
    """Converte un capitolo in HTML: immagini incorporate, link fra capitoli come ancore interne."""
    text = (HERE / file_name).read_text(encoding="utf-8")
    text = re.sub(r"\A---\n.*?\n---\n", "", text, flags=re.S)

    def image(match: re.Match[str]) -> str:
        path = HERE / match.group(2)
        data = base64.b64encode(path.read_bytes()).decode()
        css_class = "phone" if path.name.startswith("02") else "shot"
        return f'<img class="{css_class}" alt="{match.group(1)}" src="data:image/png;base64,{data}">'

    text = re.sub(r"!\[([^\]]*)\]\((img/[^)]+\.png)\)", image, text)

    def link(match: re.Match[str]) -> str:
        label, target = match.group(1), match.group(2)
        page = target.split("#")[0]
        if page in anchors:
            return f"[{label}](#{anchors[page]})"
        if target.startswith(("http://", "https://", "#")):
            return match.group(0)
        return label  # link a file fuori dal PDF: resta il testo

    text = re.sub(r"(?<!!)\[([^\]]+)\]\(([^)]+)\)", link, text)
    body = markdown.markdown(text, extensions=["tables", "fenced_code"])
    anchor = anchors.get(file_name, Path(file_name).stem)
    return f'<section id="{anchor}">{body}</section>'


def cover(subtitle: str) -> str:
    logo = (HERE / "img" / "roosko-logo.svg").read_text(encoding="utf-8")
    today = datetime.date.today().strftime("%d/%m/%Y")
    return (
        f'<div class="cover"><div class="logo">{logo}</div><h1>Manuale d\'uso</h1>'
        f"<p>{subtitle}</p><p style=\"font-size:10pt\">Edizione del {today}</p></div>"
    )


def render(page, html: str, target: Path, title: str) -> None:
    page.set_content(html, wait_until="load")
    page.pdf(
        path=str(target),
        format="A4",
        print_background=True,
        display_header_footer=True,
        header_template="<span></span>",
        footer_template=FOOTER.format(title=title),
        margin={"top": "16mm", "bottom": "18mm", "left": "15mm", "right": "15mm"},
    )
    print(f"{target.relative_to(HERE)}")


def document(title: str, body: str) -> str:
    return f'<!doctype html><html lang="it"><meta charset="utf-8"><title>{title}</title><style>{CSS}</style><body>{body}</body></html>'


def main() -> None:
    OUT.mkdir(exist_ok=True)
    all_anchors = {INTRO: "intro", **{name: data[0] for name, data in CHAPTERS.items()}}
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch()
        page = browser.new_page()

        complete = cover("Gestione dei rifiuti in impianto: piazzale, accettazione, lavorazioni, uscite, RENTRI, area clienti.")
        complete += chapter_html(INTRO, all_anchors)
        complete += "".join(chapter_html(name, all_anchors) for name in CHAPTERS)
        render(page, document("Roosko — manuale d'uso", complete), OUT / "roosko-manuale-uso.pdf", "manuale d'uso")

        for name, (anchor, role, pdf_name) in CHAPTERS.items():
            anchors = {name: anchor}
            body = cover(f"Per: {role}") + chapter_html(name, anchors)
            render(page, document(f"Roosko — {role}", body), OUT / pdf_name, role)

        browser.close()


if __name__ == "__main__":
    main()
