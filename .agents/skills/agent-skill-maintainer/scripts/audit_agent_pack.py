\
#!/usr/bin/env python3
"""Audita un paquete AGENTS.md + .agents/skills sin dependencias externas."""

from __future__ import annotations

import re
import sys
from collections import Counter, defaultdict
from pathlib import Path

FRONTMATTER_RE = re.compile(r"\A---\s*\n(.*?)\n---\s*\n", re.DOTALL)
FIELD_RE = re.compile(r"^([A-Za-z0-9_-]+):\s*(.+)$")
SKILL_REF_RE = re.compile(r"\$([a-z0-9][a-z0-9-]*)")
MD_LINK_RE = re.compile(r"\[[^\]]+\]\(([^)]+\.md(?:#[^)]+)?)\)")
NAME_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


def parse_frontmatter(path: Path) -> tuple[dict[str, str], str]:
    text = path.read_text(encoding="utf-8")
    match = FRONTMATTER_RE.search(text)
    if not match:
        return {}, text
    fields: dict[str, str] = {}
    for raw_line in match.group(1).splitlines():
        line = raw_line.strip()
        if not line:
            continue
        field = FIELD_RE.match(line)
        if field:
            fields[field.group(1)] = field.group(2).strip().strip('"\'')
        else:
            fields[f"<invalid:{line}>"] = ""
    return fields, text[match.end():]


def dependency_refs(body: str) -> set[str]:
    section = re.search(r"^## Dependencias\s*$([\s\S]*?)(?=^## |\Z)", body, re.MULTILINE)
    return set(SKILL_REF_RE.findall(section.group(1))) if section else set()


def find_cycle(graph: dict[str, set[str]]) -> list[str] | None:
    state: dict[str, int] = defaultdict(int)
    stack: list[str] = []

    def visit(node: str) -> list[str] | None:
        state[node] = 1
        stack.append(node)
        for nxt in graph.get(node, set()):
            if nxt not in graph:
                continue
            if state[nxt] == 0:
                cycle = visit(nxt)
                if cycle:
                    return cycle
            elif state[nxt] == 1:
                index = stack.index(nxt)
                return stack[index:] + [nxt]
        stack.pop()
        state[node] = 2
        return None

    for node in graph:
        if state[node] == 0:
            cycle = visit(node)
            if cycle:
                return cycle
    return None


def normalize_rule(line: str) -> str | None:
    value = re.sub(r"^\s*(?:[-*]|\d+[.)])\s+", "", line).strip().lower()
    value = re.sub(r"`[^`]+`", "<code>", value)
    value = re.sub(r"\s+", " ", value)
    if len(value) < 90 or value.startswith(("requiere:", "al cerrar:", "si ")):
        return None
    return value


def main() -> int:
    repo = Path(sys.argv[1] if len(sys.argv) > 1 else ".").resolve()
    skills_root = repo / ".agents" / "skills"
    agents_file = repo / "AGENTS.md"
    design_file = repo / "DESIGN.md"

    errors: list[str] = []
    warnings: list[str] = []

    if not agents_file.is_file():
        errors.append("Falta AGENTS.md en la raíz.")
    if not design_file.is_file():
        errors.append("Falta DESIGN.md en la raíz.")
    if not skills_root.is_dir():
        errors.append("Falta .agents/skills.")

    skill_paths = sorted(skills_root.glob("*/SKILL.md")) if skills_root.is_dir() else []
    names: list[str] = []
    graph: dict[str, set[str]] = {}
    bodies: dict[str, str] = {}

    for skill_path in skill_paths:
        fields, body = parse_frontmatter(skill_path)
        allowed = {"name", "description"}
        unknown = set(fields) - allowed
        missing = allowed - set(fields)
        if missing:
            errors.append(f"{skill_path}: faltan campos {sorted(missing)}.")
        if unknown:
            errors.append(f"{skill_path}: frontmatter no permitido {sorted(unknown)}.")

        name = fields.get("name", "")
        description = fields.get("description", "")
        names.append(name)
        bodies[name] = body

        if not NAME_RE.fullmatch(name):
            errors.append(f"{skill_path}: nombre inválido '{name}'.")
        if skill_path.parent.name != name:
            errors.append(f"{skill_path}: la carpeta no coincide con name '{name}'.")
        if not description or len(description) < 40:
            errors.append(f"{skill_path}: description demasiado vaga o corta.")
        if len(description) > 420:
            warnings.append(f"{skill_path}: description larga ({len(description)} caracteres).")
        if len(skill_path.read_text(encoding="utf-8").splitlines()) > 500:
            warnings.append(f"{skill_path}: supera 500 líneas; dividir mediante references/.")

        graph[name] = dependency_refs(body)

        for link in MD_LINK_RE.findall(body):
            relative = link.split("#", 1)[0]
            target = (skill_path.parent / relative).resolve()
            if not target.is_file():
                errors.append(f"{skill_path}: enlace Markdown roto hacia {relative}.")

        yaml_path = skill_path.parent / "agents" / "openai.yaml"
        if not yaml_path.is_file():
            warnings.append(f"{skill_path.parent}: falta agents/openai.yaml recomendado.")

    counts = Counter(name for name in names if name)
    for name, count in counts.items():
        if count > 1:
            errors.append(f"Nombre de skill duplicado '{name}' ({count} veces).")

    known = set(counts)
    for owner, refs in graph.items():
        for ref in refs:
            if ref not in known:
                errors.append(f"{owner}: dependencia inexistente ${ref}.")

    cycle = find_cycle(graph)
    if cycle:
        errors.append("Ciclo de dependencias: " + " -> ".join(cycle))

    if agents_file.is_file():
        agents_text = agents_file.read_text(encoding="utf-8")
        routed = set(SKILL_REF_RE.findall(agents_text))
        for ref in routed - known:
            errors.append(f"AGENTS.md enruta una skill inexistente: ${ref}.")
        for name in known - routed:
            warnings.append(f"AGENTS.md no menciona ${name}.")
        if "DESIGN.md" not in agents_text:
            warnings.append("AGENTS.md no enlaza DESIGN.md.")

    repeated: dict[str, list[str]] = defaultdict(list)
    for name, body in bodies.items():
        for line in body.splitlines():
            normalized = normalize_rule(line)
            if normalized:
                repeated[normalized].append(name)
    for rule, owners in repeated.items():
        unique = sorted(set(owners))
        if len(unique) > 1:
            warnings.append(f"Regla posiblemente duplicada en {', '.join(unique)}: {rule[:120]}")

    if warnings:
        print("ADVERTENCIAS:")
        for warning in warnings:
            print(f"- {warning}")

    if errors:
        print("ERRORES:")
        for error in errors:
            print(f"- {error}")
        return 1

    print(f"OK: {len(skill_paths)} skills válidas, nombres únicos, referencias resueltas y dependencias acíclicas.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
