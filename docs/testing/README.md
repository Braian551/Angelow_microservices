# Documentación de testing

<!-- indice:auto:start -->
## Índice rápido

- [Objetivo](#objetivo)
- [Documentos relacionados](#documentos-relacionados)
- [Regla de uso](#regla-de-uso)
<!-- indice:auto:end -->

## Objetivo

Reservar una ubicación clara para evidencias, guías operativas y bitácoras de validación compartidas cuando no pertenezcan a un único servicio o módulo.

## Documentos relacionados

- `../README.md`
- `../operaciones/manual-tecnico.md`

## Regla de uso

- Si la evidencia de pruebas aplica a un solo servicio, debe vivir en la carpeta `docs/` de ese servicio o junto a su suite de pruebas.
- Si la validación atraviesa varios dominios o sirve como guía transversal del repositorio, debe registrarse en esta carpeta.
- No crear archivos temporales de debug o pruebas ad hoc sin eliminarlos al finalizar la validación.