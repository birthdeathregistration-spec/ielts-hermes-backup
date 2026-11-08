"""Index every page locally. Output JSONL privately for the cPanel importer."""
import argparse
import hashlib
import json
import subprocess
from pathlib import Path
import tempfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("file", type=Path)
parser.add_argument("--title", required=True)
parser.add_argument("--lang", default="ben+eng")
parser.add_argument("--rights-confirmed", action="store_true", required=True)
args = parser.parse_args()
data = args.file.read_bytes()
print(json.dumps({"title": args.title, "sha": hashlib.sha256(data).hexdigest()}))
with tempfile.TemporaryDirectory() as folder:
    def read_page(path):
        return subprocess.check_output(["tesseract", str(path), "stdout", "-l", args.lang], timeout=90).decode().strip()

    def save(page, text):
        for start in range(0, len(text.strip()), 1100):
            print(json.dumps({"page":page, "text":text[start:start+1250]}, ensure_ascii=False))

    suffix = args.file.suffix.lower()
    if suffix == ".pdf":
        import pypdfium2 as pdfium
        with pdfium.PdfDocument(data) as doc:
            for number, page in enumerate(doc, 1):
                text = page.get_textpage().get_text_bounded().strip()
                if len(text) < 40:
                    picture = Path(folder) / "page.png"
                    page.render(scale=min(2.5, 2400/max(page.get_size()))).to_pil().save(picture)
                    text = read_page(picture) or text
                save(number, text)
                page.close()
    elif suffix == ".txt":
        save(1, data.decode("utf-8-sig"))
    elif suffix in {".png", ".jpg", ".jpeg"}:
        save(1, read_page(args.file))
    else:
        parser.error("Use PDF, UTF-8 TXT, PNG or JPG")
print(json.dumps({"complete": True}))
