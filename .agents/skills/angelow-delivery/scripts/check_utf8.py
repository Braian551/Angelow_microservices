\
#!/usr/bin/env python3
"""Valida UTF-8, BOM y mojibake en archivos de texto modificados o indicados."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

TEXT_EXTENSIONS = {
    ".css", ".env", ".html", ".js", ".json", ".jsx", ".md", ".php",
    ".py", ".scss", ".sql", ".ts", ".tsx", ".txt", ".vue", ".xml", ".yaml", ".yml",
}
SKIP_PARTS = {".git", "node_modules", "vendor", "dist", "build", ".next", ".vite"}
MOJIBAKE_MARKERS = ("\u00c3", "\u00c2", "\u00e2\u20ac", "\u00e2\u20ac\u2122", "\u00e2\u20ac\u0153", "\ufffd")


def git_changed_files(root: Path) -> list[Path]:
    commands = [
        ["git", "diff", "--name-only", "--diff-filter=ACMRT"],
        ["git", "diff", "--cached", "--name-only", "--diff-filter=ACMRT"],
        ["git", "ls-files", "--others", "--exclude-standard"],
    ]
    found: set[Path] = set()
    for command in commands:
        try:
            result = subprocess.run(command, cwd=root, check=True, capture_output=True, text=True)
        except (OSError, subprocess.CalledProcessError):
            continue
        for line in result.stdout.splitlines():
            path = (root / line.strip()).resolve()
            if path.is_file():
                found.add(path)
    return sorted(found)


def eligible(path: Path) -> bool:
    return path.is_file() and path.suffix.lower() in TEXT_EXTENSIONS and not any(part in SKIP_PARTS for part in path.parts)


def main() -> int:
    root = Path.cwd().resolve()
    paths = [Path(arg).resolve() for arg in sys.argv[1:]] if len(sys.argv) > 1 else git_changed_files(root)
    paths = [path for path in paths if eligible(path)]

    if not paths:
        print("UTF-8: no hay archivos de texto modificados para revisar.")
        return 0

    issues: list[str] = []
    for path in paths:
        data = path.read_bytes()
        if data.startswith(b"\xef\xbb\xbf"):
            issues.append(f"{path}: contiene BOM UTF-8")
        try:
            text = data.decode("utf-8")
        except UnicodeDecodeError as error:
            issues.append(f"{path}: no es UTF-8 válido ({error})")
            continue
        markers = sorted({marker for marker in MOJIBAKE_MARKERS if marker in text})
        if markers:
            issues.append(f"{path}: posible mojibake {markers}")

    if issues:
        print("UTF-8: se encontraron problemas:")
        for issue in issues:
            print(f"- {issue}")
        return 1

    print(f"UTF-8: {len(paths)} archivo(s) revisado(s) sin BOM ni mojibake conocido.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
