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

- `casos-de-prueba/README.md`: índice maestro de los 490 casos de prueba funcionales y transversales, separados por módulo.
- `../README.md`
- `../operaciones/manual-tecnico.md`

## Regla de uso

- Si la evidencia de pruebas aplica a un solo servicio, debe vivir en la carpeta `docs/` de ese servicio o junto a su suite de pruebas.
- Si la validación atraviesa varios dominios o sirve como guía transversal del repositorio, debe registrarse en esta carpeta.
- Los casos manuales compartidos se mantienen en `casos-de-prueba/`, con un archivo por módulo; por decisión temporal de ejecución, los campos de contraseña usan `Braian8052@` hasta que se actualicen las credenciales QA.
- No crear archivos temporales de debug o pruebas ad hoc sin eliminarlos al finalizar la validación.
