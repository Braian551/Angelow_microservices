# Contenido para póster de investigación en curso - ANGELOW

Nota de diligenciamiento: el bloque de asesores del formato se deja sin completar por solicitud.

## Ficha del proyecto

### Información general

- Título del proyecto: Desarrollo de un Sistema de Gestión de Ventas para Ropa Infantil (ANGELOW).
- Sector productivo objetivo del proyecto: Tienda virtual de ropa infantil con causa, orientada a venta al detal y mayorista.
- Entidad asociada: Centro Textil y de Gestión Industrial.
- Programa de formación asociado: Análisis y Desarrollo de Software (ADSO).
- Nro. de ficha: 3147208.
- Instructor titular: Edilfredo Pineda.
- Fecha de inicio: 14-11-2024.

### Instructores participantes

| Nombres y apellidos | Competencia que imparte | Observaciones | Fecha |
|---|---|---|---|
| Edilfredo Pineda | Diseñar la solución de software de acuerdo con procedimientos y requisitos técnicos |  | Segundo trimestre |
| Héctor Maya | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares |  | Segundo trimestre |
| Juan David Carvajal | Evaluar requisitos de la solución de software de acuerdo con metodologías de análisis y estándares |  | Segundo trimestre |

### Información del aprendiz que desarrolla el proyecto

| Documento de identidad | Nombres y apellidos | E-mail | Teléfono |
|---|---|---|---|
| 1023526011 | Braian Andrés Oquendo Durango | braianoquen@gmail.com | 302 2613326 |

## Texto listo para diligenciar el póster

### Título

Desarrollo de un Sistema de Gestión de Ventas para Ropa Infantil (ANGELOW)

### Autor(es)

- SENA.
- Centro: Centro Textil y de Gestión Industrial.
- Programa de formación: ADSO.
- Nombre del autor: Braian Andrés Oquendo Durango.
- Correo electrónico: braianoquen@gmail.com.

### Introducción

El proyecto ANGELOW propone el desarrollo de una plataforma de comercio electrónico especializada en la comercialización mayorista y minorista de ropa infantil. La solución integra una interfaz web tipo SPA y servicios API por dominio para gestionar usuarios, catálogo, inventario, carrito, pedidos, pagos, envíos, notificaciones, auditoría y administración. La propuesta surge de la necesidad de ofrecer una experiencia de compra más precisa para este sector, con manejo de variantes por talla y color, validación de comprobantes, seguimiento de pedidos y herramientas administrativas para la operación diaria.

### Planteamiento del problema

Las tiendas virtuales generalistas suelen resolver la venta en línea de forma amplia, pero no siempre cubren de manera adecuada las necesidades específicas del mercado de ropa infantil. Este tipo de negocio requiere control de variantes, manejo de inventario por talla y color, seguimiento de pedidos, métodos de pago confiables, atención al cliente y una administración integral del proceso comercial. Cuando estas funciones no están bien integradas, se generan dificultades en la experiencia de compra, inconsistencias operativas y menor capacidad de crecimiento del negocio. Por ello, se plantea el diseño e implementación de una plataforma especializada que responda a estas exigencias con un enfoque funcional, seguro y escalable.

### Justificación

El proyecto se justifica por la oportunidad de fortalecer la transformación digital del sector de ropa infantil mediante una solución enfocada en sus necesidades reales. Una plataforma especializada puede mejorar la experiencia del usuario final, optimizar la gestión de productos e inventario, facilitar procesos de pago y aumentar la trazabilidad de las entregas. Además, el desarrollo del sistema aporta valor académico y tecnológico al integrar arquitectura modular, bases de datos por dominio, servicios API, geolocalización, seguridad en formularios y despliegue reproducible con contenedores. En conjunto, esto favorece la competitividad del negocio y la calidad del servicio ofrecido a clientes y administradores.

### Objetivo general

Desarrollar una plataforma de E-commerce integral para la comercialización mayorista y minorista de ropa infantil, incorporando gestión especializada de productos, seguimiento de pedidos, pagos seguros, administración operativa y reportes para apoyar la transformación digital de la tienda.

### Objetivos específicos

1. Planificar los requerimientos del software, definiendo funcionalidades esenciales y deseables, tecnologías, recursos y criterios de calidad.
2. Diseñar la base de datos y las interfaces gráficas del sistema, garantizando una estructura eficiente y una experiencia de uso intuitiva.
3. Verificar que los requerimientos definidos se implementen de manera correcta mediante pruebas funcionales, validaciones e integración entre módulos.
4. Implementar la plataforma, ejecutar pruebas finales y capacitar a los usuarios para asegurar un uso adecuado y una operación estable.

### Metodología

La investigación se desarrolla con un enfoque aplicado y un alcance descriptivo-propositivo, orientado a resolver una necesidad real del sector comercial. El proceso metodológico se organiza en fases iterativas: levantamiento de información, análisis de requerimientos, diseño de arquitectura y bases de datos, elaboración de mockups, construcción de módulos, pruebas funcionales e integración, documentación y preparación para implementación. La población objetivo está compuesta por clientes minoristas, compradores mayoristas y personal administrador de la tienda. Como técnicas de trabajo se emplean revisión documental, análisis de procesos, modelado de datos, prototipado de interfaces, pruebas de software y validación operativa con apoyo de tecnologías como Vue, Laravel, PostgreSQL, Redis, Docker, OpenStreetMap y reCAPTCHA.

### Referente teórico

El proyecto se fundamenta en conceptos de comercio electrónico especializado, arquitectura de software modular, diseño de interfaces centradas en el usuario y gestión de datos por dominio. Desde la perspectiva del E-commerce, se retoman principios de usabilidad, confianza digital, conversión y trazabilidad de pedidos. En el plano técnico, la solución adopta una estructura de frontend SPA y servicios API independientes para mejorar mantenibilidad, escalabilidad y separación de responsabilidades. También incorpora bases de datos relacionales, mecanismos de autenticación segura, procesamiento asíncrono con colas, contenedorización para despliegue reproducible y geolocalización para enriquecer la experiencia de entrega y seguimiento.

### Resultados encontrados

La revisión técnica y funcional del sistema evidencia avances concretos en el desarrollo de la solución. Actualmente se cuenta con una arquitectura funcional compuesta por un frontend SPA en Vue y servicios API organizados por dominios de autenticación, catálogo, carrito, pedidos, pagos, descuentos, envíos, notificaciones y auditoría. La infraestructura local está soportada por Docker Compose, Redis y bases de datos PostgreSQL independientes por servicio. A nivel funcional, la documentación técnica y la implementación muestran registro e inicio de sesión, catálogo de productos, inventario, carrito de compras, checkout, pagos por transferencia y contra entrega, gestión de direcciones, seguimiento con mapa, notificaciones operativas, panel administrativo, reportes y trazabilidad. Estos hallazgos demuestran coherencia entre la necesidad identificada, la solución propuesta y el estado técnico alcanzado.

### Conclusiones

El proyecto presenta alta pertinencia para el sector de ropa infantil, ya que responde a necesidades comerciales y operativas que no siempre son atendidas por plataformas generalistas. La estructura técnica implementada permite organizar la solución por dominios, mejorar la mantenibilidad del software y facilitar su crecimiento futuro. La planeación y el desarrollo evidencian una ruta de trabajo consistente con las fases de análisis, diseño, construcción, validación e implementación, mientras que el estado actual del sistema demuestra que ya existe una base funcional sólida sobre la cual continuar pruebas, documentación, capacitación y cierre académico. En consecuencia, ANGELOW constituye una propuesta viable tanto desde lo tecnológico como desde su aporte formativo y productivo.

### Referencias

1. Cámara Colombiana de Comercio Electrónico. Informe de industria eCommerce en Colombia. https://www.ccce.org.co/
2. Cámara de Comercio de Medellín para Antioquia. Transformación digital y competitividad empresarial en Medellín. https://www.camaramedellin.com.co/
3. Revista Semana. Transformación digital, comercio electrónico y experiencia del cliente en Colombia. https://www.semana.com/
4. OpenStreetMap Foundation. OpenStreetMap Documentation. https://www.openstreetmap.org/
5. Nominatim. Documentation. https://nominatim.org/
6. Google. reCAPTCHA Documentation. https://developers.google.com/recaptcha
7. Laravel. Documentation 12.x. https://laravel.com/docs/12.x
8. Vue.js. Guide. https://vuejs.org/guide/
9. Docker. Docker Compose documentation. https://docs.docker.com/compose/
10. PostgreSQL Global Development Group. PostgreSQL Documentation. https://www.postgresql.org/docs/

## Observaciones de uso para el formato del póster

- El bloque de asesores se deja vacío.
- Si el espacio del póster es reducido, se puede conservar este mismo contenido y recortar primero la introducción y el referente teórico.
- En autores conviene dejar un solo responsable, porque el cronograma y la ficha identifican un único aprendiz desarrollador.
- En resultados encontrados se recomienda acompañar el texto con capturas del panel administrativo, catálogo, checkout y mapa de seguimiento.