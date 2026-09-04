# Casos de uso del sistema Angelow

> Especificación funcional preparada sin identificadores numéricos. Conserva los 420 requerimientos de la matriz vigente, incluidos objetivos de actores y comportamientos internos trazables; por ello no establece una equivalencia uno a uno entre requerimientos y óvalos UML.

## Criterio de interpretación UML

Los objetivos iniciados por actores externos se representan en los diagramas de casos de uso. Las validaciones, automatismos, sincronizaciones y reglas internas se mantienen en esta especificación y en la trazabilidad de UML, pero no se fuerzan como casos de uso independientes ni como actores internos del sistema.

## Casos de uso

### Cuenta — Registro de usuarios

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir a los visitantes registrarse creando una cuenta con nombre, correo electrónico, teléfono y contraseña. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Registro de usuarios». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Selecciona la opción para registro de usuarios. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El correo debe ser único, la contraseña debe confirmarse y la cuenta se crea con estado activo para uso inmediato. 5. Ejecuta la función y actualiza nombre completo, correo electrónico, teléfono, contraseña cifrada, rol inicial, estado de bloqueo y fecha de creación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El correo debe ser único, la contraseña debe confirmarse y la cuenta se crea con estado activo para uso inmediato», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registro de usuarios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Inicio de sesión

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir iniciar sesión con correo electrónico y contraseña. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Inicio de sesión». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Selecciona la opción para inicio de sesión. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo puede iniciar sesión una cuenta existente, no bloqueada y con credenciales válidas. 5. Ejecuta la función y actualiza correo, contraseña para validación, código de acceso de sesión, datos del usuario, rol y fecha de acceso. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo puede iniciar sesión una cuenta existente, no bloqueada y con credenciales válidas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «inicio de sesión» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Inicio de sesión con Google

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir iniciar sesión o crear cuenta usando Google. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Inicio de sesión con Google». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Selecciona la opción para inicio de sesión con Google. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el correo de Google no existe, el sistema crea el perfil con los datos recibidos; si existe, reutiliza la cuenta. 5. Ejecuta la función y actualiza identificador de Google, nombre, correo, foto, validación externa, rol y fecha de autenticación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el correo de Google no existe, el sistema crea el perfil con los datos recibidos; si existe, reutiliza la cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «inicio de sesión con Google» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Cierre de sesión

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Permitir cerrar sesión desde la tienda, cuenta de usuario o panel administrativo. |
| Precondiciones | El usuario puede acceder a la función «Cierre de sesión» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Selecciona la opción para cierre de sesión. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Al cerrar sesión se elimina el código de sesión activo y se redirige a la página pública correspondiente. 5. Ejecuta la función y actualiza identificador del usuario, código de acceso revocado, fecha de cierre y destino de redirección. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Al cerrar sesión se elimina el código de sesión activo y se redirige a la página pública correspondiente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cierre de sesión» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Ver sesión actual

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Permitir consultar los datos de la sesión activa para mostrar perfil, rol y permisos. |
| Precondiciones | El usuario puede acceder a la función «Ver sesión actual» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Solicita ver sesión actual. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, correo, teléfono, avatar, rol, estado bloqueado y permisos derivados. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La consulta solo responde si el código de sesión enviado sigue vigente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver sesión actual» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Editar perfil

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Permitir al usuario actualizar nombre, teléfono y foto de perfil. |
| Precondiciones | El usuario puede acceder a la función «Editar perfil» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Selecciona la opción para editar perfil. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El correo se conserva como identificador de acceso y la imagen debe enviarse en formato permitido. 5. Ejecuta la función y actualiza nombre actualizado, teléfono actualizado, pantalla de avatar, usuario autenticado y fecha de actualización. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El correo se conserva como identificador de acceso y la imagen debe enviarse en formato permitido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar perfil» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Cambiar contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Permitir cambiar la contraseña desde la configuración de cuenta. |
| Precondiciones | El usuario puede acceder a la función «Cambiar contraseña» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Selecciona la opción para cambiar contraseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Para cambiar la contraseña se debe validar la contraseña actual y confirmar la nueva. 5. Ejecuta la función y actualiza contraseña actual, nueva contraseña cifrada, confirmación, usuario solicitante y fecha del cambio. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Para cambiar la contraseña se debe validar la contraseña actual y confirmar la nueva», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Navegar pasos de registro

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir avanzar y retroceder entre los pasos del formulario de registro. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Navegar pasos de registro». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Solicita navegar pasos de registro. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta paso actual, paso destino, campos validados, errores y estado del formulario. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada paso valida sus campos antes de permitir continuar al siguiente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «navegar pasos de registro» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Validar campos en tiempo real

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Validar nombre, correo, teléfono, contraseña, confirmación y términos mientras el usuario completa el registro. |
| Precondiciones | El usuario puede acceder a la función «Validar campos en tiempo real» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Selecciona la opción para validar campos en tiempo real. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los mensajes de error aparecen antes del envío final cuando un campo no cumple la regla. 5. Ejecuta la función y actualiza campo validado, valor capturado, error mostrado, estado válido y momento de validación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los mensajes de error aparecen antes del envío final cuando un campo no cumple la regla», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «validar campos en tiempo real» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Aceptar términos

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Exigir aceptación de términos y condiciones en el registro. |
| Precondiciones | El usuario puede acceder a la función «Aceptar términos» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Selecciona la opción para aceptar términos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La cuenta no se crea si la casilla de términos no está marcada. 5. Ejecuta la función y actualiza usuario en registro, marca de aceptación, fecha, versión de términos y estado del formulario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La cuenta no se crea si la casilla de términos no está marcada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aceptar términos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Mostrar u ocultar contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Permitir mostrar u ocultar contraseña y confirmación durante el registro. |
| Precondiciones | El usuario puede acceder a la función «Mostrar u ocultar contraseña» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Cuenta». 2. Solicita mostrar u ocultar contraseña. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta campos de contraseña, estado visible u oculto y formulario de registro. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La acción no altera la contraseña capturada ni su confirmación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar u ocultar contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inicio de sesión — Avanzar a contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir validar primero el correo o usuario antes de mostrar el campo de contraseña. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Avanzar a contraseña». |
| Flujo principal | 1. El visitante abre la sección «Inicio de sesión». 2. Selecciona la opción para avanzar a contraseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: No se avanza al segundo paso si el identificador está vacío o no cumple formato válido. 5. Ejecuta la función y actualiza identificador ingresado, estado del paso, errores de validación y pantalla de retorno. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «No se avanza al segundo paso si el identificador está vacío o no cumple formato válido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «avanzar a contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inicio de sesión — Volver a identificador

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir volver desde el paso de contraseña al paso de correo o usuario. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Volver a identificador». |
| Flujo principal | 1. El visitante abre la sección «Inicio de sesión». 2. Solicita volver a identificador. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, paso anterior, paso nuevo y estado del formulario. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al volver se conserva el identificador capturado para que el usuario lo corrija si es necesario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver a identificador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inicio de sesión — Mostrar u ocultar contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir mostrar u ocultar la contraseña en el formulario de acceso. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Mostrar u ocultar contraseña». |
| Flujo principal | 1. El visitante abre la sección «Inicio de sesión». 2. Solicita mostrar u ocultar contraseña. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta campo de contraseña, estado visible u oculto y formulario asociado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La acción solo cambia la visibilidad del campo y no modifica el valor escrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar u ocultar contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inicio de sesión — Redirección segura

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Validar que la pantalla de retorno después de iniciar sesión sea segura. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Redirección segura». |
| Flujo principal | 1. El visitante abre la sección «Inicio de sesión». 2. Selecciona la opción para redirección segura. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se aceptan pantallas del sistema permitidas; las pantallas externas o vacías se reemplazan por el destino del rol. 5. Ejecuta la función y actualiza pantalla solicitada, pantalla validada, rol, usuario y destino final. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se aceptan pantallas del sistema permitidas; las pantallas externas o vacías se reemplazan por el destino del rol», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «redirección segura» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Solicitar código

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir solicitar un código de recuperación de contraseña. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Solicitar código». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Selecciona la opción para solicitar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El identificador debe corresponder a una cuenta registrada y el código generado queda limitado a un tiempo de uso. 5. Ejecuta la función y actualiza correo o identificador, código generado, fecha de solicitud, fecha de expiración y estado del envío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El identificador debe corresponder a una cuenta registrada y el código generado queda limitado a un tiempo de uso», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «solicitar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Reenviar código

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir reenviar el código de recuperación cuando el usuario lo solicite. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Reenviar código». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Selecciona la opción para reenviar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El reenvío solo se realiza para una recuperación activa y reemplaza o reutiliza el código vigente según la configuración. 5. Ejecuta la función y actualiza identificador de usuario, código vigente, cantidad de reenvíos y fecha de reenvío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El reenvío solo se realiza para una recuperación activa y reemplaza o reutiliza el código vigente según la configuración», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reenviar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Verificar código

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir verificar el código de recuperación ingresado por el usuario. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Verificar código». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Solicita verificar código. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, código ingresado, resultado de verificación y fecha de validación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El código debe tener cuatro dígitos, estar vigente y pertenecer al identificador solicitado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Restablecer contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir restablecer la contraseña después de verificar el código. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Restablecer contraseña». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Selecciona la opción para restablecer contraseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La contraseña nueva debe cumplir las reglas de seguridad y solo se acepta si la recuperación fue verificada. 5. Ejecuta la función y actualiza identificador, código verificado, nueva contraseña cifrada, confirmación y fecha de restablecimiento. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La contraseña nueva debe cumplir las reglas de seguridad y solo se acepta si la recuperación fue verificada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «restablecer contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Controlar tiempo de código

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar tiempo restante y enfriamiento para solicitar o reenviar códigos. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Controlar tiempo de código». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Selecciona la opción para controlar tiempo de código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Mientras exista enfriamiento activo no se debe permitir reenviar otro código. 5. Ejecuta la función y actualiza identificador, segundos restantes, expiración, enfriamiento y estado de botones. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Mientras exista enfriamiento activo no se debe permitir reenviar otro código», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «controlar tiempo de código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Volver a identificación

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir regresar al paso de identificación desde la verificación de código. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Volver a identificación». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Solicita volver a identificación. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, paso origen, paso destino, errores y mensajes activos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al regresar se limpian mensajes del paso anterior y se conserva el identificador editable», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver a identificación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación administrativa — Solicitar código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir solicitar un código de recuperación desde la pantalla administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Solicitar código». |
| Flujo principal | 1. El administrador abre la sección «Recuperación administrativa». 2. Selecciona la opción para solicitar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El identificador debe ser correo o documento válido y el botón queda sujeto a enfriamiento para evitar solicitudes repetidas. 5. Ejecuta la función y actualiza identificador normalizado, código generado, tiempo de expiración, enfriamiento, estado de envío y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El identificador debe ser correo o documento válido y el botón queda sujeto a enfriamiento para evitar solicitudes repetidas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «solicitar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación administrativa — Reenviar código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir reenviar el código de recuperación administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Reenviar código». |
| Flujo principal | 1. El administrador abre la sección «Recuperación administrativa». 2. Selecciona la opción para reenviar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El reenvío respeta el tiempo de espera antes de habilitar una nueva solicitud. 5. Ejecuta la función y actualiza identificador, código vigente, segundos restantes, estado de reenvío y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El reenvío respeta el tiempo de espera antes de habilitar una nueva solicitud», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reenviar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación administrativa — Verificar código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir verificar el código recibido para recuperación administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Verificar código». |
| Flujo principal | 1. El administrador abre la sección «Recuperación administrativa». 2. Solicita verificar código. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, código ingresado, código de acceso de sesión temporal, resultado y fecha de validación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El código debe tener formato válido, estar vigente y pertenecer al identificador solicitado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación administrativa — Restablecer contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir restablecer la contraseña administrativa después de validar el código. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Restablecer contraseña». |
| Flujo principal | 1. El administrador abre la sección «Recuperación administrativa». 2. Selecciona la opción para restablecer contraseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La contraseña y su confirmación deben coincidir antes de guardar el cambio. 5. Ejecuta la función y actualiza identificador, código temporal, nueva contraseña cifrada, confirmación y fecha de restablecimiento. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La contraseña y su confirmación deben coincidir antes de guardar el cambio», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «restablecer contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación administrativa — Mostrar u ocultar contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir mostrar u ocultar la contraseña y su confirmación en recuperación administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Mostrar u ocultar contraseña». |
| Flujo principal | 1. El administrador abre la sección «Recuperación administrativa». 2. Solicita mostrar u ocultar contraseña. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta campo afectado, estado visible u oculto, formulario y fecha de interacción. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La acción solo cambia la visibilidad del campo y no modifica el valor capturado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar u ocultar contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Roles y permisos — Redirigir por rol

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Redirigir al usuario a su área correspondiente según el rol después de iniciar sesión. |
| Precondiciones | Ocurre el evento que activa la función «Redirigir por rol» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Roles y permisos». 2. El sistema reúne la información necesaria para redirigir por rol. 3. Valida la regla de negocio: Los administradores entran al panel administrativo y los clientes entran a su cuenta o al destino solicitado. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando rol del usuario, pantalla solicitada, pantalla final, código de acceso y permisos asociados. |
| Alternativas | Si no se cumple la regla «Los administradores entran al panel administrativo y los clientes entran a su cuenta o al destino solicitado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «redirigir por rol» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Roles y permisos — Proteger panel administrativo

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Bloquear el acceso al panel administrativo a usuarios sin rol administrador. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Proteger panel administrativo». |
| Flujo principal | 1. El administrador abre la sección «Roles y permisos». 2. Selecciona la opción para proteger panel administrativo. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si un cliente intenta ingresar a una pantalla administrativa, el sistema lo redirige a su cuenta. 5. Ejecuta la función y actualiza usuario, rol, pantalla intentada, permiso requerido y acción tomada. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si un cliente intenta ingresar a una pantalla administrativa, el sistema lo redirige a su cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «proteger panel administrativo» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Roles y permisos — Proteger cuenta de usuario

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Exigir sesión iniciada para entrar a las pantallas de cuenta, pedidos, direcciones, favoritos y configuración. |
| Precondiciones | Ocurre el evento que activa la función «Proteger cuenta de usuario» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Roles y permisos». 2. El sistema reúne la información necesaria para proteger cuenta de usuario. 3. Valida la regla de negocio: Si no hay sesión, se conserva la pantalla de retorno para continuar después del inicio de sesión. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando pantalla protegida, estado de sesión, destino de inicio de sesión y pantalla de retorno. |
| Alternativas | Si no se cumple la regla «Si no hay sesión, se conserva la pantalla de retorno para continuar después del inicio de sesión», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «proteger cuenta de usuario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Registrar intento de acceso

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Registrar intentos de inicio de sesión para proteger las cuentas ante accesos repetidos fallidos. |
| Precondiciones | Ocurre el evento que activa la función «Registrar intento de acceso» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para registrar intento de acceso. 3. Valida la regla de negocio: Cada intento conserva usuario o correo, dirección de origen, fecha y resultado para revisión de seguridad. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando identificador ingresado, dirección de origen, fecha del intento y resultado asociado. |
| Alternativas | Si no se cumple la regla «Cada intento conserva usuario o correo, dirección de origen, fecha y resultado para revisión de seguridad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar intento de acceso» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Verificar registro nativo

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Exigir verificación de seguridad antes de crear una cuenta con correo y contraseña. |
| Precondiciones | Ocurre el evento que activa la función «Verificar registro nativo» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para verificar registro nativo. 3. Valida la regla de negocio: La cuenta no se crea si la verificación falta, expira, se reutiliza o no puede validarse con el proveedor externo. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando token de verificación, dirección de origen, nombre, correo, teléfono, contraseña cifrada, resultado de validación y fecha. |
| Alternativas | Si no se cumple la regla «La cuenta no se crea si la verificación falta, expira, se reutiliza o no puede validarse con el proveedor externo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar registro nativo» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Exigir verificación tras fallos de acceso

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Solicitar verificación adicional en el inicio de sesión nativo después de intentos fallidos repetidos. |
| Precondiciones | Ocurre el evento que activa la función «Exigir verificación tras fallos de acceso» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para exigir verificación tras fallos de acceso. 3. Valida la regla de negocio: La decisión de exigir verificación corresponde al backend según credencial normalizada e IP, sin depender de que el frontend haya mostrado el widget. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando identificador ingresado, dirección de origen, contador de fallos, estado de verificación, fecha del último fallo y respuesta de acceso. |
| Alternativas | Si no se cumple la regla «La decisión de exigir verificación corresponde al backend según credencial normalizada e IP, sin depender de que el frontend haya mostrado el widget», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exigir verificación tras fallos de acceso» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Bloquear temporalmente acceso repetido

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Aplicar bloqueo temporal cuando una misma combinación de credencial e IP supera el límite de fallos permitido. |
| Precondiciones | Ocurre el evento que activa la función «Bloquear temporalmente acceso repetido» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para bloquear temporalmente acceso repetido. 3. Valida la regla de negocio: Durante el bloqueo no se procesan credenciales y se muestra un mensaje controlado sin revelar detalles internos de seguridad. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando identificador ingresado, dirección de origen, cantidad de fallos, fecha de bloqueo, fecha de desbloqueo y estado de respuesta. |
| Alternativas | Si no se cumple la regla «Durante el bloqueo no se procesan credenciales y se muestra un mensaje controlado sin revelar detalles internos de seguridad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «bloquear temporalmente acceso repetido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Verificar recuperación de contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Exigir verificación de seguridad antes de enviar o reenviar códigos de recuperación de contraseña. |
| Precondiciones | Ocurre el evento que activa la función «Verificar recuperación de contraseña» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para verificar recuperación de contraseña. 3. Valida la regla de negocio: No se envía correo de recuperación si la verificación falta, expira, se reutiliza o falla por error de red del proveedor. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando identificador de cuenta, token de verificación, dirección de origen, resultado de validación, estado de envío y fecha de solicitud. |
| Alternativas | Si no se cumple la regla «No se envía correo de recuperación si la verificación falta, expira, se reutiliza o falla por error de red del proveedor», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar recuperación de contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguridad — Administrar sesiones de acceso

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Administrar las sesiones activas para permitir el acceso seguro a la tienda y al panel administrativo. |
| Precondiciones | Ocurre el evento que activa la función «Administrar sesiones de acceso» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Seguridad». 2. El sistema reúne la información necesaria para administrar sesiones de acceso. 3. Valida la regla de negocio: Las sesiones deben cerrarse al finalizar sesión o vencer automáticamente por seguridad. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando usuario, código de sesión, permisos, último uso, vencimiento, fecha de creación y cierre. |
| Alternativas | Si no se cumple la regla «Las sesiones deben cerrarse al finalizar sesión o vencer automáticamente por seguridad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «administrar sesiones de acceso» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Registrar administrador

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear cuentas administrativas desde el panel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Registrar administrador». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona la opción para registrar administrador. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo un administrador autenticado puede crear administradores y el correo debe ser único. 5. Ejecuta la función y actualiza nombre, correo, contraseña temporal cifrada, teléfono, avatar, rol, estado y usuario creador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo un administrador autenticado puede crear administradores y el correo debe ser único», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar administrador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Listar administradores

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar una listado con el equipo administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar administradores». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Solicita listar administradores. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, correo, teléfono, avatar, rol, estado bloqueado, fecha de creación y último acceso. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado muestra administradores con métricas de activos, bloqueados y últimos accesos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar administradores» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Editar administrador

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar los datos de contacto y estado de un administrador. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar administrador». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona la opción para editar administrador. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El correo debe seguir siendo único y los cambios se aplican solo a cuentas administrativas existentes. 5. Ejecuta la función y actualiza identificador, nombre, correo, teléfono, avatar, estado bloqueado, usuario editor y fecha de edición. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El correo debe seguir siendo único y los cambios se aplican solo a cuentas administrativas existentes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar administrador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Bloquear administrador

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir bloquear una cuenta administrativa desde la listado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Bloquear administrador». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona bloquear administrador. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: El bloqueo requiere confirmación y se aplica de inmediato para impedir nuevos accesos. 5. Aplica la acción y actualiza identificador del administrador, estado anterior, estado bloqueado, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «El bloqueo requiere confirmación y se aplica de inmediato para impedir nuevos accesos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «bloquear administrador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Reactivar administrador

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir reactivar una cuenta administrativa bloqueada. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Reactivar administrador». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona la opción para reactivar administrador. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La reactivación requiere confirmación y solo aplica a administradores existentes. 5. Ejecuta la función y actualiza identificador del administrador, estado anterior, estado activo, usuario ejecutor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La reactivación requiere confirmación y solo aplica a administradores existentes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reactivar administrador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Eliminar administrador

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar cuentas administrativas que ya no se usarán. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar administrador». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona eliminar administrador. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación debe confirmarse para evitar borrar accesos por accidente. 5. Aplica la acción y actualiza identificador del administrador, correo, usuario que elimina, confirmación y fecha de eliminación. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación debe confirmarse para evitar borrar accesos por accidente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar administrador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administradores — Subir foto administrativa

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar y previsualizar una foto para un administrador. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Subir foto administrativa». |
| Flujo principal | 1. El administrador abre la sección «Administradores». 2. Selecciona la opción para subir foto administrativa. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen se convierte en dato del formulario antes de crear o actualizar la cuenta. 5. Ejecuta la función y actualiza administrador, archivo, vista previa, pantalla o contenido enviado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen se convierte en dato del formulario antes de crear o actualizar la cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «subir foto administrativa» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Clientes — Listar clientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir a los administradores listar clientes registrados con búsqueda y filtros. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar clientes». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Solicita listar clientes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, correo, teléfono, avatar, estado, fecha de registro, compras y total gastado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo los administradores pueden consultar datos de clientes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar clientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Clientes — Editar cliente

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar desde el listado los datos de contacto, el estado y el rol de un cliente. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar cliente». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Selecciona un cliente y la opción para editarlo. 3. Actualiza los datos de contacto, el estado o el rol permitidos. 4. El sistema valida la regla de negocio: Solo administradores autenticados pueden editar; el correo permanece único y el rol se valida entre cliente, repartidor y administrador. Un administrador no puede desactivar su propia cuenta ni retirarse su propio rol. 5. Guarda los cambios y actualiza identificador, nombre, correo, teléfono, rol anterior, rol nuevo, estado de cuenta, usuario editor y fecha de edición. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si el correo ya está registrado, el rol no es válido o el administrador intenta desactivar su propia cuenta o retirarse su rol, el sistema no guarda los cambios e indica cómo corregirlos. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar cliente», conserva los datos validados y deja los cambios visibles y disponibles para continuar el proceso. |

### Clientes — Bloquear cliente

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir bloquear o desbloquear clientes desde el panel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Bloquear cliente». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Selecciona bloquear cliente. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: El bloqueo impide el acceso normal de la cuenta sin eliminar su historial. 5. Aplica la acción y actualiza identificador del cliente, estado anterior, estado nuevo, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «El bloqueo impide el acceso normal de la cuenta sin eliminar su historial», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «bloquear cliente» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Clientes — Ver estadísticas de clientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar métricas de clientes registrados, activos, bloqueados y con compras. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver estadísticas de clientes». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Solicita ver estadísticas de clientes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta totales de clientes, activos, bloqueados, nuevos, con compras y sin compras. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las métricas se calculan con base en los clientes visibles para administración», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver estadísticas de clientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Clientes — Ver detalle de cliente

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de un cliente en ventana. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de cliente». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Solicita ver detalle de cliente. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta cliente, órdenes, total gastado, última compra, segmento, estado y administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle incluye perfil, compras recientes, métricas y estado de bloqueo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de cliente» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Clientes — Exportar clientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar clientes a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar clientes». |
| Flujo principal | 1. El administrador abre la sección «Clientes». 2. Selecciona la opción para exportar clientes. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos y solo está disponible para administradores. 5. Ejecuta la función y actualiza clientes, filtros, formato, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos y solo está disponible para administradores», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar clientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Mi cuenta — Ver resumen de cuenta

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar un resumen con pedidos recientes, direcciones, favoritos y recomendaciones. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ver resumen de cuenta». |
| Flujo principal | 1. El cliente abre la sección «Mi cuenta». 2. Solicita ver resumen de cuenta. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, pedidos recientes, direcciones, favoritos, recomendaciones, contadores y últimas fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada usuario solo puede ver información asociada a su propia cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver resumen de cuenta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Mi cuenta — Abrir historial desde resumen

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir desde el resumen de cuenta al historial completo de pedidos. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir historial desde resumen». |
| Flujo principal | 1. El cliente abre la sección «Mi cuenta». 2. Solicita abrir historial desde resumen. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, pantalla de destino, cantidad de pedidos y filtros aplicables. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El acceso conserva la sesión activa y solo muestra pedidos propios», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir historial desde resumen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Mi cuenta — Abrir direcciones desde resumen

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir desde el resumen de cuenta a la gestión de direcciones. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir direcciones desde resumen». |
| Flujo principal | 1. El cliente abre la sección «Mi cuenta». 2. Solicita abrir direcciones desde resumen. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, pantalla de destino y contador de direcciones guardadas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El usuario debe estar autenticado para administrar sus direcciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir direcciones desde resumen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Mi cuenta — Abrir favoritos desde resumen

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir desde el resumen de cuenta a la lista de favoritos. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir favoritos desde resumen». |
| Flujo principal | 1. El cliente abre la sección «Mi cuenta». 2. Solicita abrir favoritos desde resumen. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, pantalla de destino y cantidad de productos favoritos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La lista de favoritos es personal y privada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir favoritos desde resumen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Ver preferencias

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ver preferencias de notificaciones y datos básicos de cuenta. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ver preferencias». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Solicita ver preferencias. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta preferencias por tipo, correo del usuario, estado de cuenta y fecha de actualización. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las preferencias se cargan para el usuario autenticado y usan valores predeterminados si no existen», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver preferencias» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Actualizar preferencias

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir activar o desactivar preferencias de notificación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Actualizar preferencias». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Selecciona la opción para actualizar preferencias. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada preferencia se puede modificar de forma independiente. 5. Ejecuta la función y actualiza usuario, preferencias seleccionadas, canal, tipo de notificación y fecha de guardado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada preferencia se puede modificar de forma independiente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar preferencias» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Cambiar pestaña

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir alternar entre perfil, seguridad y notificaciones. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cambiar pestaña». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Selecciona la opción para cambiar pestaña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La pestaña activa se refleja en el enlace para conservar el estado de navegación y cualquier hash no disponible vuelve a perfil. 5. Ejecuta la función y actualiza usuario, pestaña activa, hash de navegación y sección visible. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La pestaña activa se refleja en el enlace para conservar el estado de navegación y cualquier hash no disponible vuelve a perfil», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar pestaña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Cambiar foto de perfil

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar una nueva foto y previsualizarla antes de guardar el perfil. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cambiar foto de perfil». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Selecciona la opción para cambiar foto de perfil. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen seleccionada debe ser un archivo válido y se envía junto con los datos del perfil. 5. Ejecuta la función y actualiza archivo de avatar, vista previa, usuario, nombre, teléfono y estado de carga. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen seleccionada debe ser un archivo válido y se envía junto con los datos del perfil», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar foto de perfil» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Mostrar correo de acceso

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar el correo de la cuenta como dato informativo de solo lectura dentro del perfil. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Mostrar correo de acceso». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Solicita mostrar correo de acceso. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, correo actual, estado de solo lectura y fecha de consulta. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El cliente no puede solicitar cambio de correo desde configuración; el correo se conserva como identificador de acceso», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar correo de acceso» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Cancelar cambio de contraseña

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ocultar el formulario de cambio de contraseña sin guardar cambios. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cancelar cambio de contraseña». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Selecciona cancelar cambio de contraseña. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al cancelar no se envía ninguna contraseña al servicio. 5. Aplica la acción y actualiza usuario, estado del formulario, campos descartados y fecha de cancelación. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al cancelar no se envía ninguna contraseña al servicio», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cancelar cambio de contraseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de cuenta — Cerrar sesión desde cuenta

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir cerrar sesión desde la pestaña de seguridad de la cuenta. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cerrar sesión desde cuenta». |
| Flujo principal | 1. El cliente abre la sección «Configuración de cuenta». 2. Selecciona cerrar sesión desde cuenta. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al cerrar sesión se elimina la sesión activa y se redirige a la página pública. 5. Aplica la acción y actualiza usuario, código de acceso, origen de cierre, fecha y pantalla destino. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al cerrar sesión se elimina la sesión activa y se redirige a la página pública», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cerrar sesión desde cuenta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Compatibilidad de pantallas — Redirigir pantallas antiguas

| Campo | Descripción |
|---|---|
| Actor principal | Usuario |
| Objetivo | Redirigir pantallas públicas antiguas de cuenta a sus pantallas actuales. |
| Precondiciones | El usuario puede acceder a la función «Redirigir pantallas antiguas» y cuenta con la información necesaria para continuar. |
| Flujo principal | 1. El usuario abre la sección «Compatibilidad de pantallas». 2. Selecciona la opción para redirigir pantallas antiguas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los accesos `/dashboard`, `/mis-pedidos`, `/notificaciones`, `/mis-direcciones`, `/mis-favoritos`, `/configuracion-cuenta` y `/favoritos` deben llevar al módulo equivalente dentro de `mi-cuenta`. 5. Ejecuta la función y actualiza pantalla solicitada, pantalla destino, usuario, estado de sesión y fecha de navegación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los accesos `/dashboard`, `/mis-pedidos`, `/notificaciones`, `/mis-direcciones`, `/mis-favoritos`, `/configuracion-cuenta` y `/favoritos` deben llevar al módulo equivalente dentro de `mi-cuenta`», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «redirigir pantallas antiguas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Abrir menú móvil

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir el menú principal en dispositivos móviles. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Abrir menú móvil». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita abrir menú móvil. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado del menú, pantalla actual, enlaces disponibles y tamaño de pantalla. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al abrir el menú se muestra un panel lateral con navegación pública y accesos de cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir menú móvil» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Cerrar menú móvil

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir cerrar el menú móvil con botón o fondo. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Cerrar menú móvil». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Selecciona cerrar menú móvil. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al cerrar el menú se limpia el estado visual sin cambiar la pantalla actual. 5. Aplica la acción y actualiza estado anterior, estado cerrado, pantalla actual y acción usada. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al cerrar el menú se limpia el estado visual sin cambiar la pantalla actual», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cerrar menú móvil» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Acceder a mi cuenta desde encabezado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir la cuenta desde el icono del encabezado. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Acceder a mi cuenta desde encabezado». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita acceder a mi cuenta desde encabezado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de sesión, rol, pantalla destino y pantalla de retorno. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si el usuario no está autenticado, el sistema lo lleva al inicio de sesión con retorno a cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «acceder a mi cuenta desde encabezado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Acceder a favoritos desde encabezado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir favoritos desde el icono del encabezado. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Acceder a favoritos desde encabezado». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita acceder a favoritos desde encabezado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de sesión, usuario, pantalla de favoritos y pantalla de retorno. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si no hay sesión, se redirige al inicio de sesión conservando el retorno a favoritos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «acceder a favoritos desde encabezado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Acceder a notificaciones desde encabezado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir las notificaciones desde el icono del encabezado. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Acceder a notificaciones desde encabezado». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita acceder a notificaciones desde encabezado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de sesión, usuario, pantalla de notificaciones, contador visible y pantalla de retorno. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si no hay sesión, se redirige al inicio de sesión conservando el retorno a notificaciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «acceder a notificaciones desde encabezado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Acceder al carrito desde encabezado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir el carrito desde el encabezado. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Acceder al carrito desde encabezado». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita acceder al carrito desde encabezado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pantalla de carrito, contador de ítems, usuario o sesión y fecha de navegación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El acceso al carrito está disponible para usuarios autenticados o visitantes con sesión de carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «acceder al carrito desde encabezado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Navegación — Navegar desde pie de página

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir navegar desde el pie de página hacia niñas, niños, bebés y ofertas. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Navegar desde pie de página». |
| Flujo principal | 1. El visitante abre la sección «Navegación». 2. Solicita navegar desde pie de página. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta enlace seleccionado, filtro aplicado, pantalla destino y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada enlace aplica el filtro correspondiente en la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «navegar desde pie de página» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Ver página principal

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar la página principal con sliders, categorías, colecciones, productos destacados y anuncios activos. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver página principal». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita ver página principal. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta sliders, categorías, colecciones, productos destacados, anuncios, imágenes y enlaces. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se muestran contenidos activos y vigentes para la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver página principal» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Ver tienda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir consultar productos en la tienda con paginación y filtros. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver tienda». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita ver tienda. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta productos, precio, imagen, categoría, género, colección, disponibilidad, página y total de resultados. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se muestran productos activos y disponibles para venta pública», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver tienda» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Filtrar por categoría

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir filtrar productos por categoría. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Filtrar por categoría». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita filtrar por categoría. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta categoría seleccionada, productos resultantes, contador y parámetros de consulta. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El filtro se combina con los demás filtros activos sin recargar toda la aplicación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar por categoría» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Filtrar por género

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir filtrar productos por género: niña, niño, bebé u ofertas. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Filtrar por género». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita filtrar por género. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta género seleccionado, indicador de oferta, resultados y pantalla consultada. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El menú principal envía el filtro correspondiente a la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar por género» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Filtrar por precio

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir filtrar productos por rango de precio. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Filtrar por precio». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita filtrar por precio. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta precio mínimo, precio máximo, productos filtrados y cantidad de resultados. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los valores de precio deben mantenerse dentro del rango válido disponible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar por precio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Filtrar por colección

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir filtrar productos por colección. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Filtrar por colección». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita filtrar por colección. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador de colección, nombre, productos asociados y parámetros de navegación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La colección seleccionada se conserva en la enlace para compartir o volver a la consulta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar por colección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir limpiar todos los filtros de tienda con un botón. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al limpiar filtros se vuelve al listado general de productos. 5. Aplica la acción y actualiza filtros anteriores, parámetros eliminados, listado resultante y fecha de acción. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al limpiar filtros se vuelve al listado general de productos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Ordenar o paginar resultados

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir navegar entre páginas de productos. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ordenar o paginar resultados». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita ordenar o paginar resultados. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta página actual, total de páginas, filtros, cantidad por página y resultados. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La paginación respeta los filtros activos y el total disponible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ordenar o paginar resultados» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Ver colecciones

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ver el listado público de colecciones. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver colecciones». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita ver colecciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, descripción, imagen, estado y cantidad de productos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se muestran colecciones activas o publicables», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver colecciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Ordenar productos

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ordenar productos por novedad, popularidad, precio menor y precio mayor. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ordenar productos». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita ordenar productos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta criterio de orden, filtros activos, página, resultados y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El ordenamiento debe conservar filtros activos y quedar reflejado en la consulta de tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ordenar productos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tienda pública — Abrir o cerrar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir y cerrar grupos de filtros en la tienda. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Abrir o cerrar filtros». |
| Flujo principal | 1. El visitante abre la sección «Tienda pública». 2. Solicita abrir o cerrar filtros. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta grupo de filtro, estado abierto o cerrado, filtros activos y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada grupo conserva su estado visual mientras el usuario interactúa con la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir o cerrar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Buscar desde encabezado

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir buscar productos desde el encabezado del sitio. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Buscar desde encabezado». |
| Flujo principal | 1. El visitante abre la sección «Búsqueda». 2. Solicita buscar desde encabezado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término buscado, usuario o sesión, pantalla de resultados y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al enviar una búsqueda se navega a tienda con el término ingresado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «buscar desde encabezado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Mostrar sugerencias

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar sugerencias de productos y términos mientras el usuario escribe. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Mostrar sugerencias». |
| Flujo principal | 1. El visitante abre la sección «Búsqueda». 2. Solicita mostrar sugerencias. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término, productos sugeridos, imágenes, identificador de navegacións, términos relacionados y usuario. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las sugerencias se muestran solo cuando hay un término suficiente y se limitan para no saturar la pantalla», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar sugerencias» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Guardar historial

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Guardar el historial de búsquedas del usuario o visitante. |
| Precondiciones | Ocurre el evento que activa la función «Guardar historial» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Búsqueda». 2. El sistema reúne la información necesaria para guardar historial. 3. Valida la regla de negocio: Para usuarios registrados se guarda en el servicio; para visitantes se conserva localmente. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando término, usuario o invitado, fecha, origen y cantidad de resultados. |
| Alternativas | Si no se cumple la regla «Para usuarios registrados se guarda en el servicio; para visitantes se conserva localmente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «guardar historial» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Seleccionar sugerencia de producto

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir un producto desde las sugerencias de búsqueda. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Seleccionar sugerencia de producto». |
| Flujo principal | 1. El visitante abre la sección «Búsqueda». 2. Selecciona la opción para seleccionar sugerencia de producto. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Al seleccionar una sugerencia se registra el término y se cierra el panel de sugerencias. 5. Ejecuta la función y actualiza producto seleccionado, identificador de navegación, término asociado, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Al seleccionar una sugerencia se registra el término y se cierra el panel de sugerencias», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar sugerencia de producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Seleccionar término sugerido

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir buscar usando un término sugerido. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Seleccionar término sugerido». |
| Flujo principal | 1. El visitante abre la sección «Búsqueda». 2. Selecciona la opción para seleccionar término sugerido. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El término seleccionado se toma como búsqueda actual y se navega a resultados. 5. Ejecuta la función y actualiza término seleccionado, usuario, pantalla generada y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El término seleccionado se toma como búsqueda actual y se navega a resultados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar término sugerido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Sugerir búsquedas populares

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Incluir términos populares dentro de las sugerencias de búsqueda. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Sugerir búsquedas populares». |
| Flujo principal | 1. El visitante abre la sección «Búsqueda». 2. Selecciona la opción para sugerir búsquedas populares. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los términos populares se combinan con historial del usuario y se limitan para no duplicar resultados. 5. Ejecuta la función y actualiza término ingresado, términos populares encontrados, contador de búsquedas, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los términos populares se combinan con historial del usuario y se limitan para no duplicar resultados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «sugerir búsquedas populares» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Búsqueda — Actualizar contador popular

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Incrementar el contador de una búsqueda popular cuando se registra una búsqueda. |
| Precondiciones | Ocurre el evento que activa la función «Actualizar contador popular» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Búsqueda». 2. El sistema reúne la información necesaria para actualizar contador popular. 3. Valida la regla de negocio: Si el término no existe, se crea; si ya existe, se actualiza su contador y última fecha buscada. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando término, contador anterior, contador nuevo, fecha de última búsqueda y usuario o sesión. |
| Alternativas | Si no se cumple la regla «Si el término no existe, se crea; si ya existe, se actualiza su contador y última fecha buscada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar contador popular» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tarjeta de producto — Marcar favorito desde tarjeta

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir marcar o quitar favorito desde una tarjeta de producto. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Marcar favorito desde tarjeta». |
| Flujo principal | 1. El cliente abre la sección «Tarjeta de producto». 2. Selecciona la opción para marcar favorito desde tarjeta. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el usuario no inició sesión, se redirige al inicio de sesión con retorno a la pantalla actual. 5. Ejecuta la función y actualiza usuario, producto, pantalla actual, estado favorito y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el usuario no inició sesión, se redirige al inicio de sesión con retorno a la pantalla actual», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar favorito desde tarjeta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tarjeta de producto — Abrir detalle desde tarjeta

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir el detalle desde imagen, nombre o botón de la tarjeta. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Abrir detalle desde tarjeta». |
| Flujo principal | 1. El visitante abre la sección «Tarjeta de producto». 2. Solicita abrir detalle desde tarjeta. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, identificador de navegación, origen del clic y pantalla destino. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La tarjeta debe navegar por su identificador de navegación del producto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir detalle desde tarjeta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tarjeta de producto — Mostrar imagen alternativa

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar una imagen alternativa cuando la imagen del producto no está disponible. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Mostrar imagen alternativa». |
| Flujo principal | 1. El visitante abre la sección «Tarjeta de producto». 2. Solicita mostrar imagen alternativa. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pantalla original, pantalla alternativa, tipo de producto y estado de carga. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «No se deben mostrar imágenes rotas al usuario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar imagen alternativa» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Ver detalle

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ver la ficha completa de un producto por su identificador de navegación. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver detalle». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita ver detalle. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, identificador de navegación, descripción, precios, categoría, colección, imágenes, colores, tallas, stock, reseñas y preguntas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si el producto no existe o no está disponible, se muestra un estado controlado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Volver a tienda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir volver desde la ficha del producto a la tienda. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Volver a tienda». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita volver a tienda. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pantalla anterior, producto actual y destino de retorno. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El botón respeta el historial de navegación cuando existe», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver a tienda» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Ampliar imagen

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ampliar imágenes del producto en un ventana. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ampliar imagen». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita ampliar imagen. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta imagen seleccionada, texto alternativo, estado del ventana y producto. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El ventana se puede cerrar con botón, clic fuera o tecla de escape», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ampliar imagen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Cambiar imagen

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir cambiar la imagen principal desde miniaturas. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Cambiar imagen». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para cambiar imagen. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen principal debe corresponder al producto o a la variante seleccionada. 5. Ejecuta la función y actualiza índice de imagen, pantalla, variante asociada y producto. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen principal debe corresponder al producto o a la variante seleccionada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar imagen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Seleccionar color

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir seleccionar color disponible del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Seleccionar color». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para seleccionar color. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La selección de color actualiza imágenes y disponibilidad relacionada. 5. Ejecuta la función y actualiza identificador de variante de color, nombre, código visual, producto y stock asociado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La selección de color actualiza imágenes y disponibilidad relacionada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar color» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Seleccionar talla

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir seleccionar talla disponible del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Seleccionar talla». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para seleccionar talla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se puede comprar una talla habilitada y con stock suficiente. 5. Ejecuta la función y actualiza identificador de talla, nombre, variante, stock disponible y producto. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se puede comprar una talla habilitada y con stock suficiente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Ajustar cantidad

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir aumentar o disminuir la cantidad antes de comprar. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ajustar cantidad». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para ajustar cantidad. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La cantidad no puede ser menor que uno ni superar el stock disponible. 5. Ejecuta la función y actualiza cantidad solicitada, stock disponible, variante seleccionada y producto. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La cantidad no puede ser menor que uno ni superar el stock disponible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ajustar cantidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Agregar al carrito

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir agregar el producto al carrito desde la ficha. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Agregar al carrito». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para agregar al carrito. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Antes de agregar se exige color, talla y cantidad válida. 5. Ejecuta la función y actualiza producto, variante, color, talla, cantidad, precio, usuario o sesión y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Antes de agregar se exige color, talla y cantidad válida», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «agregar al carrito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Comprar ahora

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir iniciar compra inmediata desde la ficha del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Comprar ahora». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para comprar ahora. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La compra inmediata crea un carrito temporal y lleva directamente al paso de envío. 5. Ejecuta la función y actualiza producto, variante, cantidad, usuario o sesión, indicador de compra inmediata y subtotal. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La compra inmediata crea un carrito temporal y lleva directamente al paso de envío», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «comprar ahora» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Agregar o quitar favorito

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir marcar o desmarcar el producto como favorito. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Agregar o quitar favorito». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Selecciona la opción para agregar o quitar favorito. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo los usuarios autenticados pueden mantener favoritos sincronizados. 5. Ejecuta la función y actualiza usuario, producto, estado favorito y fecha de acción. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo los usuarios autenticados pueden mantener favoritos sincronizados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «agregar o quitar favorito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Ver pestaña descripción

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir la pestaña de descripción del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver pestaña descripción». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita ver pestaña descripción. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, descripción, imágenes descriptivas, pestaña activa y fecha de visualización. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La pestaña activa cambia sin recargar la ficha y las imágenes descriptivas deben mantenerse responsivas, centradas y con tamaño máximo controlado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver pestaña descripción» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Ver pestaña especificaciones

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir la pestaña de especificaciones o guía de tallas. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver pestaña especificaciones». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita ver pestaña especificaciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, tallas, colores, materiales, medidas y pestaña activa. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La información debe corresponder al producto actual y sus variantes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver pestaña especificaciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Detalle de producto — Abrir guía de tallas

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir la guía de tallas desde la ficha del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Abrir guía de tallas». |
| Flujo principal | 1. El visitante abre la sección «Detalle de producto». 2. Solicita abrir guía de tallas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, pestaña activa, tallas disponibles, selección actual y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La acción cambia a la pestaña de especificaciones sin perder selección de color, talla o cantidad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir guía de tallas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Listar productos admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar productos en administración con búsqueda, filtros, imágenes, variantes y stock. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar productos admin». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Solicita listar productos admin. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, imagen, precio, categoría, género, stock, variantes, estado y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado debe mostrar productos activos e inactivos para gestión del sistema», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar productos admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Ver vista rápida

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir una vista rápida del producto desde el listado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver vista rápida». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Solicita ver vista rápida. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, imágenes, variantes, stock, categoría, precio y estado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La vista rápida muestra información sin abandonar el listado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver vista rápida» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Crear producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir registrar un producto nuevo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear producto». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona la opción para crear producto. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El producto debe tener nombre, precio, categoría y datos mínimos válidos. 5. Ejecuta la función y actualiza nombre, descripción, precio, categoría, género, imágenes, variantes, estado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El producto debe tener nombre, precio, categoría y datos mínimos válidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Editar producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar la información de un producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar producto». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona la opción para editar producto. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios se reflejan en la tienda y se conservan las relaciones válidas de variantes e imágenes. 5. Ejecuta la función y actualiza identificador, nuevos datos, imágenes, variantes, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios se reflejan en la tienda y se conservan las relaciones válidas de variantes e imágenes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Desactivar producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir activar o desactivar un producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Desactivar producto». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona desactivar producto. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Un producto inactivo no debe mostrarse como disponible en la tienda. 5. Aplica la acción y actualiza producto, estado anterior, estado nuevo, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Un producto inactivo no debe mostrarse como disponible en la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «desactivar producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Eliminar producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar un producto desde administración. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar producto». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona eliminar producto. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación y debe respetar restricciones por relaciones existentes. 5. Aplica la acción y actualiza producto, relaciones, usuario ejecutor, confirmación y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación y debe respetar restricciones por relaciones existentes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Exportar productos CSV

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar productos a CSV. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar productos CSV». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona la opción para exportar productos CSV. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta los filtros aplicados en el listado. 5. Ejecuta la función y actualiza productos exportados, filtros, columnas, usuario solicitante y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta los filtros aplicados en el listado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar productos CSV» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Exportar productos PDF

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar productos a PDF. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar productos PDF». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona la opción para exportar productos PDF. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El PDF debe incluir la información visible y formato apto para consulta administrativa. 5. Ejecuta la función y actualiza productos, imágenes, precios, stock, filtros, archivo PDF y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El PDF debe incluir la información visible y formato apto para consulta administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar productos PDF» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Aplicar filtros admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar productos por búsqueda, categoría, estado, género y orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Aplicar filtros admin». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona la opción para aplicar filtros admin. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los filtros se aplican al listado administrativo y se conservan en la consulta de datos. 5. Ejecuta la función y actualiza búsqueda, categoría, estado, género, orden, página y total de resultados. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los filtros se aplican al listado administrativo y se conservan en la consulta de datos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar filtros admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Limpiar filtros admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar todos los filtros del listado administrativo de productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros admin». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Selecciona limpiar filtros admin. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al limpiar se recarga el listado con parámetros predeterminados. 5. Aplica la acción y actualiza filtros eliminados, listado resultante, página y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al limpiar se recarga el listado con parámetros predeterminados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Ampliar imagen rápida

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir ampliar imágenes desde la vista rápida administrativa del producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ampliar imagen rápida». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Solicita ampliar imagen rápida. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, imagen seleccionada, título, estado del ventana y administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La imagen ampliada se muestra en ventana y se puede cerrar sin perder la vista rápida», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ampliar imagen rápida» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de productos — Filtrar vista rápida por color

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar imágenes y variantes por color dentro de la vista rápida. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar vista rápida por color». |
| Flujo principal | 1. El administrador abre la sección «Administración de productos». 2. Solicita filtrar vista rápida por color. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, color seleccionado, imágenes filtradas, variantes y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El filtro solo afecta la vista rápida abierta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar vista rápida por color» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Cambiar pestaña de edición

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir alternar entre información general y variantes en el formulario de producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cambiar pestaña de edición». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para cambiar pestaña de edición. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El cambio de pestaña conserva los datos capturados antes de guardar. 5. Ejecuta la función y actualiza producto, pestaña activa, formulario general, variantes y estado de edición. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El cambio de pestaña conserva los datos capturados antes de guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar pestaña de edición» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Generar identificador de navegación

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Generar automáticamente el identificador de navegación del producto a partir del nombre cuando no ha sido editado manualmente. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Generar identificador de navegación». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para generar identificador de navegación. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el administrador modifica el identificador de navegación manualmente, el sistema respeta ese valor. 5. Ejecuta la función y actualiza nombre, identificador de navegación generado, estado de edición manual y producto. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el administrador modifica el identificador de navegación manualmente, el sistema respeta ese valor», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «generar identificador de navegación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Generar SKU

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Generar SKU para variantes usando marca, categoría, género, estilo, color y talla. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Generar SKU». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para generar SKU. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el SKU existente fue editado manualmente, el sistema no lo sobrescribe automáticamente. 5. Ejecuta la función y actualiza producto, categoría, género, color, talla, SKU generado, estado manual y variante. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el SKU existente fue editado manualmente, el sistema no lo sobrescribe automáticamente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «generar SKU» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Marcar variante predeterminada

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir definir una variante de color como predeterminada. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Marcar variante predeterminada». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para marcar variante predeterminada. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Siempre debe existir una variante predeterminada cuando el producto tiene variantes. 5. Ejecuta la función y actualiza producto, variante, estado predeterminado anterior, nuevo predeterminado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Siempre debe existir una variante predeterminada cuando el producto tiene variantes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar variante predeterminada» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Quitar variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir quitar una variante de color del formulario antes de guardar. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Quitar variante». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona quitar variante. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: No se permite quitar la última variante disponible en el formulario. 5. Aplica la acción y actualiza producto, variante retirada, variantes restantes y estado del formulario. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «No se permite quitar la última variante disponible en el formulario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Agregar talla a variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir agregar tallas a una variante desde el ventana de tallas y precios. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Agregar talla a variante». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para agregar talla a variante. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La talla seleccionada no debe duplicarse dentro de la misma variante. 5. Ejecuta la función y actualiza variante, talla, precio, stock, SKU, alerta mínima y fecha de captura. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La talla seleccionada no debe duplicarse dentro de la misma variante», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «agregar talla a variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Quitar talla de variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir quitar una talla de una variante antes de guardar. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Quitar talla de variante». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona quitar talla de variante. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación solo afecta el formulario hasta que se guarde el producto. 5. Aplica la acción y actualiza variante, talla retirada, tallas restantes y estado del formulario. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación solo afecta el formulario hasta que se guarde el producto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar talla de variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Marcar imagen principal de variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir marcar una imagen de variante como principal. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Marcar imagen principal de variante». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona la opción para marcar imagen principal de variante. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo una imagen puede quedar como principal por variante. 5. Ejecuta la función y actualiza variante, imagen anterior, imagen principal nueva y fecha de selección. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo una imagen puede quedar como principal por variante», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar imagen principal de variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Quitar imagen de variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir quitar una imagen de variante del formulario. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Quitar imagen de variante». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Selecciona quitar imagen de variante. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Si la imagen ya existía, debe registrarse para eliminar su referencia al guardar. 5. Aplica la acción y actualiza variante, imagen, pantalla anterior, estado eliminado y producto. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Si la imagen ya existía, debe registrarse para eliminar su referencia al guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar imagen de variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Formulario de producto — Cargar catálogos auxiliares

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Cargar categorías, colecciones, colores y tallas antes de editar productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cargar catálogos auxiliares». |
| Flujo principal | 1. El administrador abre la sección «Formulario de producto». 2. Solicita cargar catálogos auxiliares. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta categorías, colecciones, colores, tallas, estado de carga y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El formulario no debe guardar variantes con opciones que no existan en los catálogos auxiliares», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cargar catálogos auxiliares» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Variantes e imágenes — Agregar color

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir asociar colores a un producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Agregar color». |
| Flujo principal | 1. El administrador abre la sección «Variantes e imágenes». 2. Selecciona la opción para agregar color. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada color debe existir en el catálogo de colores y asociarse a variantes del producto. 5. Ejecuta la función y actualiza producto, color, código visual, SKU o identificador de variante y estado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada color debe existir en el catálogo de colores y asociarse a variantes del producto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «agregar color» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Variantes e imágenes — Agregar talla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir asociar tallas y stock a un producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Agregar talla». |
| Flujo principal | 1. El administrador abre la sección «Variantes e imágenes». 2. Selecciona la opción para agregar talla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada talla debe existir en el catálogo de tallas y tener stock numérico válido. 5. Ejecuta la función y actualiza producto, talla, stock, umbral, variante y estado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada talla debe existir en el catálogo de tallas y tener stock numérico válido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «agregar talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Variantes e imágenes — Subir imagen general

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir subir imágenes generales del producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Subir imagen general». |
| Flujo principal | 1. El administrador abre la sección «Variantes e imágenes». 2. Selecciona la opción para subir imagen general. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen debe cumplir formato y tamaño permitidos y reemplazar referencias obsoletas cuando aplique. 5. Ejecuta la función y actualiza producto, archivo, pantalla, orden, indicador principal y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen debe cumplir formato y tamaño permitidos y reemplazar referencias obsoletas cuando aplique», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «subir imagen general» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Variantes e imágenes — Subir imagen por variante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir subir imágenes específicas para variantes de color. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Subir imagen por variante». |
| Flujo principal | 1. El administrador abre la sección «Variantes e imágenes». 2. Selecciona la opción para subir imagen por variante. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen debe quedar ligada al color correspondiente para mostrarse al seleccionar la variante. 5. Ejecuta la función y actualiza variante de color, producto, archivo, pantalla, orden e indicador principal. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen debe quedar ligada al color correspondiente para mostrarse al seleccionar la variante», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «subir imagen por variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Variantes e imágenes — Eliminar imagen

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir quitar imágenes del producto o de una variante. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar imagen». |
| Flujo principal | 1. El administrador abre la sección «Variantes e imágenes». 2. Selecciona eliminar imagen. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: No se debe dejar un producto sin imagen usable si la vista pública la requiere. 5. Aplica la acción y actualiza identificador de imagen, producto, variante, pantalla anterior, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «No se debe dejar un producto sin imagen usable si la vista pública la requiere», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar imagen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Listar categorías

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar categorías en la tienda y administración. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar categorías». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Solicita listar categorías. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, identificador de navegación, descripción, imagen, estado y cantidad de productos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las categorías organizan productos y pueden mostrarse como filtros o tarjetas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar categorías» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Crear categoría

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear categorías de productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear categoría». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Selecciona la opción para crear categoría. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El nombre debe ser obligatorio y generar una identificación única de navegación. 5. Ejecuta la función y actualiza nombre, identificador de navegación, descripción, imagen, estado, orden y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El nombre debe ser obligatorio y generar una identificación única de navegación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear categoría» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Editar categoría

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar categorías existentes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar categoría». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Selecciona la opción para editar categoría. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios deben conservar los productos asociados a la categoría. 5. Ejecuta la función y actualiza categoría, nuevos datos, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios deben conservar los productos asociados a la categoría», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar categoría» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Eliminar categoría

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar categorías sin productos asociados. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar categoría». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Selecciona eliminar categoría. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: No se puede eliminar una categoría usada por productos. 5. Aplica la acción y actualiza categoría, cantidad de productos, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «No se puede eliminar una categoría usada por productos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar categoría» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Activar o desactivar categoría

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir activar o desactivar categorías. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Activar o desactivar categoría». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Selecciona la opción para activar o desactivar categoría. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las categorías inactivas no deben aparecer como opción pública principal. 5. Ejecuta la función y actualiza categoría, estado anterior, estado nuevo, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las categorías inactivas no deben aparecer como opción pública principal», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «activar o desactivar categoría» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Categorías — Gestionar imagen

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir subir, cambiar o quitar imagen de una categoría. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Gestionar imagen». |
| Flujo principal | 1. El administrador abre la sección «Categorías». 2. Selecciona la opción para gestionar imagen. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen debe cumplir formato permitido y se previsualiza antes de guardar. 5. Ejecuta la función y actualiza categoría, archivo, pantalla, vista previa, estado de eliminación y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen debe cumplir formato permitido y se previsualiza antes de guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «gestionar imagen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Listar colecciones admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar colecciones en administración. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar colecciones admin». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Solicita listar colecciones admin. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta colección, imagen, descripción, estado, productos asociados y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado muestra cantidad de productos y estado de cada colección», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar colecciones admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Crear colección

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear colecciones. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear colección». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Selecciona la opción para crear colección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La colección debe tener nombre y puede asociarse a productos para campañas. 5. Ejecuta la función y actualiza nombre, identificador de navegación, descripción, imagen, estado, productos asociados y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La colección debe tener nombre y puede asociarse a productos para campañas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear colección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Editar colección

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar colecciones y sus productos asociados. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar colección». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Selecciona la opción para editar colección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Un producto puede pertenecer a varias colecciones. 5. Ejecuta la función y actualiza colección, datos actualizados, productos agregados o quitados, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Un producto puede pertenecer a varias colecciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar colección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Eliminar colección

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar colecciones. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar colección». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Selecciona eliminar colección. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al eliminar una colección no se eliminan los productos asociados. 5. Aplica la acción y actualiza colección, productos relacionados, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al eliminar una colección no se eliminan los productos asociados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar colección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Activar o desactivar colección

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir activar o desactivar colecciones. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Activar o desactivar colección». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Selecciona la opción para activar o desactivar colección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las colecciones inactivas no deben mostrarse en la tienda. 5. Ejecuta la función y actualiza colección, estado anterior, estado nuevo, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las colecciones inactivas no deben mostrarse en la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «activar o desactivar colección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Colecciones — Gestionar imagen

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir subir, cambiar o quitar imagen de una colección. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Gestionar imagen». |
| Flujo principal | 1. El administrador abre la sección «Colecciones». 2. Selecciona la opción para gestionar imagen. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen se valida y previsualiza antes de guardar. 5. Ejecuta la función y actualiza colección, archivo, pantalla, vista previa, estado de eliminación y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen se valida y previsualiza antes de guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «gestionar imagen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Listar tallas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar tallas disponibles. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar tallas». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Solicita listar tallas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, nombre, descripción, orden, estado y cantidad de productos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las tallas se ordenan por el orden configurado para formularios y selectores», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar tallas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Crear talla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear tallas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear talla». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Selecciona la opción para crear talla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El nombre es obligatorio y el orden no puede ser negativo. 5. Ejecuta la función y actualiza nombre, descripción, orden, estado y fecha de creación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El nombre es obligatorio y el orden no puede ser negativo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Editar talla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar tallas existentes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar talla». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Selecciona la opción para editar talla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios se aplican a los selectores administrativos sin perder relaciones. 5. Ejecuta la función y actualiza talla, nombre, descripción, orden, estado, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios se aplican a los selectores administrativos sin perder relaciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Activar o desactivar talla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir activar o desactivar tallas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Activar o desactivar talla». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Selecciona la opción para activar o desactivar talla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las tallas inactivas no deben ofrecerse para nuevas configuraciones. 5. Ejecuta la función y actualiza talla, estado anterior, estado nuevo, usuario ejecutor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las tallas inactivas no deben ofrecerse para nuevas configuraciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «activar o desactivar talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Eliminar talla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar tallas sin productos asociados. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar talla». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Selecciona eliminar talla. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Si la talla tiene productos relacionados, no se muestra o no procede la eliminación. 5. Aplica la acción y actualiza talla, cantidad de productos, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Si la talla tiene productos relacionados, no se muestra o no procede la eliminación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar talla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Filtrar tallas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir buscar y filtrar tallas por estado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar tallas». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Solicita filtrar tallas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término de búsqueda, estado, tallas resultantes, totales y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El filtro se aplica sobre el listado administrativo sin eliminar las tallas cargadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar tallas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Tallas — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar filtros del listado de tallas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Tallas». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La listado vuelve al estado predeterminado con todas las tallas cargadas. 5. Aplica la acción y actualiza filtros anteriores, tallas visibles, administrador y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La listado vuelve al estado predeterminado con todas las tallas cargadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reseñas del cliente — Ver reseñas públicas

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ver reseñas, calificación promedio y distribución de estrellas en la ficha del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver reseñas públicas». |
| Flujo principal | 1. El visitante abre la sección «Reseñas del cliente». 2. Solicita ver reseñas públicas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta calificación promedio, total de reseñas, distribución, usuario, comentario, fecha, estado y verificación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las reseñas se muestran con prioridad para compras verificadas y estados aprobados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver reseñas públicas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reseñas del cliente — Escribir reseña

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir iniciar la acción de escribir reseña desde la ficha del producto. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Escribir reseña». |
| Flujo principal | 1. El cliente abre la sección «Reseñas del cliente». 2. Selecciona la opción para escribir reseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el usuario no ha iniciado sesión, se le envía al inicio de sesión con retorno al producto. 5. Ejecuta la función y actualiza producto, usuario, pantalla de retorno y estado de autenticación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el usuario no ha iniciado sesión, se le envía al inicio de sesión con retorno al producto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «escribir reseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Preguntas del cliente — Ver preguntas públicas

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir ver preguntas y respuestas del producto. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Ver preguntas públicas». |
| Flujo principal | 1. El visitante abre la sección «Preguntas del cliente». 2. Solicita ver preguntas públicas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pregunta, respuesta, usuario, administrador, producto, fechas y estado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se publican respuestas asociadas a preguntas visibles», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver preguntas públicas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Preguntas del cliente — Hacer pregunta

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir iniciar una pregunta sobre el producto. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Hacer pregunta». |
| Flujo principal | 1. El cliente abre la sección «Preguntas del cliente». 2. Selecciona la opción para hacer pregunta. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el usuario no ha iniciado sesión, se le solicita autenticarse antes de preguntar. 5. Ejecuta la función y actualiza producto, usuario, texto de pregunta, estado pendiente y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el usuario no ha iniciado sesión, se le solicita autenticarse antes de preguntar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «hacer pregunta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Favoritos — Cargar favoritos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir cargar la lista de deseos completa del usuario. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cargar favoritos». |
| Flujo principal | 1. El cliente abre la sección «Favoritos». 2. Solicita cargar favoritos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario, correo, productos favoritos, cantidad total, estado de carga y mensaje de error. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si el usuario no está autenticado, se muestra un mensaje controlado o se redirige según la pantalla», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cargar favoritos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Favoritos — Abrir producto favorito

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir abrir la ficha de un producto desde la lista de deseos. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir producto favorito». |
| Flujo principal | 1. El cliente abre la sección «Favoritos». 2. Solicita abrir producto favorito. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, identificador de navegación, usuario y pantalla de destino. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La navegación usa el identificador de navegación del producto y no modifica la lista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir producto favorito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Favoritos — Quitar favorito individual

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir quitar un producto individual de la lista de deseos desde la tarjeta. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Quitar favorito individual». |
| Flujo principal | 1. El cliente abre la sección «Favoritos». 2. Selecciona quitar favorito individual. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al quitar un producto se actualiza la lista y el contador visible. 5. Aplica la acción y actualiza usuario, producto, estado favorito, contador actualizado y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al quitar un producto se actualiza la lista y el contador visible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar favorito individual» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Favoritos — Limpiar lista completa

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir limpiar todos los productos de la lista de deseos después de confirmación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Limpiar lista completa». |
| Flujo principal | 1. El cliente abre la sección «Favoritos». 2. Selecciona limpiar lista completa. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La acción ejecuta la eliminación producto por producto y muestra resumen de resultado. 5. Aplica la acción y actualiza usuario, productos eliminados, productos fallidos, contador final y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La acción ejecuta la eliminación producto por producto y muestra resumen de resultado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar lista completa» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Favoritos — Explorar desde lista vacía

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir a la tienda cuando la lista de deseos está vacía. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Explorar desde lista vacía». |
| Flujo principal | 1. El cliente abre la sección «Favoritos». 2. Selecciona la opción para explorar desde lista vacía. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción se muestra solo en el estado vacío de favoritos. 5. Ejecuta la función y actualiza usuario, estado vacío, pantalla destino y fecha de navegación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción se muestra solo en el estado vacío de favoritos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «explorar desde lista vacía» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Listar reseñas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar reseñas con filtros y estadísticas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar reseñas». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Solicita listar reseñas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta reseña, producto, cliente, calificación, título, comentario, estado, verificación y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La administración puede revisar calificaciones, estados y reseñas verificadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar reseñas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Ver detalle de reseña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de una reseña. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de reseña». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Solicita ver detalle de reseña. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta reseña, contenido, cliente, producto, estado, verificación y fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle se muestra en ventana sin abandonar la bandeja de moderación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de reseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Publicar reseña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aprobar una reseña. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Publicar reseña». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona la opción para publicar reseña. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo reseñas aprobadas deben mostrarse como publicadas. 5. Ejecuta la función y actualiza reseña, estado anterior, estado aprobado, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo reseñas aprobadas deben mostrarse como publicadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «publicar reseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Enviar reseña a revisión

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir devolver una reseña a estado pendiente. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Enviar reseña a revisión». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona la opción para enviar reseña a revisión. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Una reseña pendiente no debe destacarse como publicada. 5. Ejecuta la función y actualiza reseña, estado anterior, estado pendiente, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Una reseña pendiente no debe destacarse como publicada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar reseña a revisión» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Marcar compra verificada

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir marcar o quitar verificación de una reseña. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Marcar compra verificada». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona la opción para marcar compra verificada. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La verificación se usa para destacar reseñas de compra comprobada. 5. Ejecuta la función y actualiza reseña, estado de verificación anterior, nuevo estado, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La verificación se usa para destacar reseñas de compra comprobada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar compra verificada» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Eliminar reseña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar reseñas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar reseña». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona eliminar reseña. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación administrativa. 5. Aplica la acción y actualiza reseña, producto, cliente, administrador y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar reseña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Exportar reseñas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar reseñas a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar reseñas». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona la opción para exportar reseñas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros visibles de la bandeja. 5. Ejecuta la función y actualiza reseñas exportadas, filtros, formato, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros visibles de la bandeja», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar reseñas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Filtrar reseñas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar reseñas por búsqueda, estado, calificación y compra verificada. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar reseñas». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Solicita filtrar reseñas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término, estado, calificación, verificación, resultados y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada cambio de filtro actualiza la bandeja administrativa de reseñas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar reseñas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar todos los filtros de reseñas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al limpiar, la bandeja vuelve a la vista general de moderación. 5. Aplica la acción y actualiza filtros anteriores, filtros predeterminados, resultados y administrador. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al limpiar, la bandeja vuelve a la vista general de moderación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de reseñas — Ver gráficos de reseñas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar estadísticas visuales de reseñas para apoyar la moderación. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver gráficos de reseñas». |
| Flujo principal | 1. El administrador abre la sección «Administración de reseñas». 2. Solicita ver gráficos de reseñas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta total de reseñas, calificaciones, estados, verificadas, pendientes y fecha de cálculo. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los gráficos usan la distribución de calificaciones y estados disponibles en la bandeja», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver gráficos de reseñas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Listar preguntas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar preguntas de productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar preguntas». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Solicita listar preguntas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pregunta, producto, cliente, estado, respuestas, fecha y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las preguntas pendientes deben ser visibles para respuesta administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar preguntas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Responder pregunta

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir responder preguntas de clientes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Responder pregunta». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Selecciona la opción para responder pregunta. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Al responder, la respuesta queda asociada a la pregunta y puede notificarse al cliente. 5. Ejecuta la función y actualiza pregunta, respuesta, administrador, producto, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Al responder, la respuesta queda asociada a la pregunta y puede notificarse al cliente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «responder pregunta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Eliminar pregunta

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar preguntas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar pregunta». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Selecciona eliminar pregunta. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación y retira sus respuestas asociadas cuando aplique. 5. Aplica la acción y actualiza pregunta, respuestas, producto, administrador y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación y retira sus respuestas asociadas cuando aplique», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar pregunta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Filtrar preguntas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar preguntas por búsqueda y estado de respuesta. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar preguntas». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Solicita filtrar preguntas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término, estado respondida o pendiente, resultados, administrador y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El filtro por búsqueda usa espera breve para no consultar en cada tecla de forma inmediata», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar preguntas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar filtros de preguntas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La bandeja vuelve a mostrar todas las preguntas disponibles. 5. Aplica la acción y actualiza filtros anteriores, listado resultante, administrador y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La bandeja vuelve a mostrar todas las preguntas disponibles», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Exportar preguntas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar preguntas a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar preguntas». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Selecciona la opción para exportar preguntas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta la bandeja visible y los filtros aplicados. 5. Ejecuta la función y actualiza preguntas, filtros, formato, administrador y fecha de exportación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta la bandeja visible y los filtros aplicados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar preguntas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de preguntas — Ver gráfico de estado

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar un gráfico de preguntas respondidas y pendientes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver gráfico de estado». |
| Flujo principal | 1. El administrador abre la sección «Administración de preguntas». 2. Solicita ver gráfico de estado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta total de preguntas, respondidas, pendientes, porcentaje y fecha de cálculo. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si no hay datos suficientes, se muestra un estado vacío», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver gráfico de estado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Listar direcciones

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ver todas las direcciones guardadas del usuario. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Listar direcciones». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Solicita listar direcciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta identificador, dirección, barrio, ciudad, coordenadas, destinatario, teléfono, tipo, instrucciones y estado predeterminado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado se sincroniza en tiempo real entre pestañas y se actualiza al enfocar la ventana», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar direcciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Crear dirección

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir crear una dirección en un formulario por pasos. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Crear dirección». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona la opción para crear dirección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Se deben completar los datos obligatorios de ubicación, destinatario y contacto antes de guardar. 5. Ejecuta la función y actualiza tipo de dirección, dirección, barrio, ciudad, edificio, destinatario, teléfono, instrucciones, latitud, longitud y usuario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Se deben completar los datos obligatorios de ubicación, destinatario y contacto antes de guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear dirección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Editar dirección

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir editar una dirección guardada. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Editar dirección». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona la opción para editar dirección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se puede editar una dirección que pertenezca al usuario actual. 5. Ejecuta la función y actualiza identificador de dirección, datos actualizados, usuario propietario y fecha de actualización. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se puede editar una dirección que pertenezca al usuario actual», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar dirección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Eliminar dirección

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir eliminar una dirección después de confirmación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Eliminar dirección». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona eliminar dirección. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación se valida contra el usuario propietario para evitar borrar direcciones ajenas. 5. Aplica la acción y actualiza identificador de dirección, usuario propietario, confirmación y fecha de eliminación. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación se valida contra el usuario propietario para evitar borrar direcciones ajenas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar dirección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Marcar como predeterminada

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir marcar una dirección como predeterminada. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Marcar como predeterminada». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona la opción para marcar como predeterminada. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Al marcar una dirección, las demás direcciones del usuario dejan de ser predeterminadas. 5. Ejecuta la función y actualiza identificador de dirección, usuario, estado predeterminado y fecha del cambio. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Al marcar una dirección, las demás direcciones del usuario dejan de ser predeterminadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar como predeterminada» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Abrir asistente GPS

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir abrir un asistente de mapa para seleccionar coordenadas. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir asistente GPS». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Solicita abrir asistente GPS. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta latitud, longitud, dirección sugerida, barrio, ciudad y origen de selección. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La ubicación seleccionada debe devolver coordenadas válidas antes de aplicarse al formulario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir asistente GPS» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Buscar ubicación

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir buscar direcciones con autocompletado de OpenStreetMap. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Buscar ubicación». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Solicita buscar ubicación. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta texto buscado, resultados, nombre mostrado, barrio, ciudad, país, latitud y longitud. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La búsqueda requiere al menos dos caracteres y limita los resultados para Colombia», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «buscar ubicación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Aplicar ubicación al formulario

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir aplicar la ubicación elegida en el mapa al formulario de dirección. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Aplicar ubicación al formulario». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona la opción para aplicar ubicación al formulario. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si las coordenadas son inválidas, el sistema no permite continuar. 5. Ejecuta la función y actualiza coordenadas finales, dirección confirmada, ciudad, barrio y fecha de selección. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si las coordenadas son inválidas, el sistema no permite continuar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar ubicación al formulario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Visualizar mapa

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar el mapa de una dirección cuando existen coordenadas guardadas. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Visualizar mapa». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Solicita visualizar mapa. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta dirección, latitud, longitud, estado de mapa, usuario y fecha de visualización. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si la dirección no tiene coordenadas, el sistema debe mostrar un estado controlado sin romper la vista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «visualizar mapa» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Direcciones — Geocodificar dirección visible

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Convertir coordenadas en datos legibles para mostrar mejor la ubicación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Geocodificar dirección visible». |
| Flujo principal | 1. El cliente abre la sección «Direcciones». 2. Selecciona la opción para geocodificar dirección visible. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La geocodificación debe validar rangos antes de construir la dirección mostrada. 5. Ejecuta la función y actualiza latitud, longitud, dirección resultante, ciudad, barrio, país y estado de consulta. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La geocodificación debe validar rangos antes de construir la dirección mostrada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «geocodificar dirección visible» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Revisar resumen de compra

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar el resumen de productos antes del pago. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Revisar resumen de compra». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para revisar resumen de compra. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El acceso al checkout desde envío en adelante requiere una sesión de cliente válida, incluso si el usuario intenta entrar por URL directa. 5. Ejecuta la función y actualiza ítems del carrito, subtotal, descuentos, envío, total, usuario, estado de sesión y pantalla solicitada. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El acceso al checkout desde envío en adelante requiere una sesión de cliente válida, incluso si el usuario intenta entrar por URL directa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «revisar resumen de compra» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Seleccionar dirección

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar una dirección guardada para la entrega. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Seleccionar dirección». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para seleccionar dirección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La dirección debe pertenecer al usuario y tener datos suficientes para envío. 5. Ejecuta la función y actualiza identificador de dirección, destinatario, teléfono, dirección, ciudad, barrio, latitud y longitud. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La dirección debe pertenecer al usuario y tener datos suficientes para envío», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar dirección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Gestionar direcciones desde checkout

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir desde checkout a la página de direcciones para agregar o corregir una dirección. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Gestionar direcciones desde checkout». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para gestionar direcciones desde checkout. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La navegación conserva el flujo para regresar al checkout después de gestionar la dirección. 5. Ejecuta la función y actualiza pantalla origen, usuario, dirección faltante o seleccionada y pantalla destino. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La navegación conserva el flujo para regresar al checkout después de gestionar la dirección», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «gestionar direcciones desde checkout» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Seleccionar método de envío

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar un método de envío activo. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Seleccionar método de envío». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para seleccionar método de envío. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Debe existir un método predeterminado y los costos se recalculan al cambiar la selección. 5. Ejecuta la función y actualiza método, descripción, costo base, tiempo de entrega, estado activo, indicador predeterminado y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Debe existir un método predeterminado y los costos se recalculan al cambiar la selección», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar método de envío» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Calcular regla de envío

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Calcular recargos o envío gratis según el subtotal. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Calcular regla de envío». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para calcular regla de envío. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El sistema aplica la regla cuyo rango coincide con el subtotal de compra. 5. Ejecuta la función y actualiza subtotal, rango mínimo, rango máximo, recargo, costo base, envío calculado y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El sistema aplica la regla cuyo rango coincide con el subtotal de compra», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «calcular regla de envío» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Aplicar cupón

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir aplicar un código de descuento en checkout. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Aplicar cupón». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para aplicar cupón. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El código debe estar activo, vigente, con usos disponibles y aplicable al carrito. 5. Ejecuta la función y actualiza código, tipo, valor, vigencia, productos, usuario, subtotal y descuento calculado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El código debe estar activo, vigente, con usos disponibles y aplicable al carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar cupón» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Quitar cupón

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir eliminar un descuento aplicado. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Quitar cupón». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona quitar cupón. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al quitar el cupón se recalculan subtotal, envío y total. 5. Aplica la acción y actualiza código eliminado, carrito, descuento anterior, total actualizado y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al quitar el cupón se recalculan subtotal, envío y total», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar cupón» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Aplicar descuento por cantidad

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Aplicar automáticamente descuentos por cantidad cuando el carrito cumple las reglas. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Aplicar descuento por cantidad». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para aplicar descuento por cantidad. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si existen varios descuentos posibles, se conserva el beneficio más conveniente permitido. 5. Ejecuta la función y actualiza cantidad total, regla aplicada, porcentaje, descuento calculado, subtotal y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si existen varios descuentos posibles, se conserva el beneficio más conveniente permitido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar descuento por cantidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Continuar a pago

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir continuar al paso de pago después de seleccionar dirección y método de envío. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Continuar a pago». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Selecciona la opción para continuar a pago. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: No se puede continuar sin carrito válido, dirección seleccionada y método de envío activo. 5. Ejecuta la función y actualiza carrito, dirección, método, notas, descuentos, subtotal, envío y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «No se puede continuar sin carrito válido, dirección seleccionada y método de envío activo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «continuar a pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Volver al carrito

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir volver desde el paso de envío al carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Volver al carrito». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Solicita volver al carrito. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta carrito, usuario, pantalla origen, pantalla destino y estado del checkout. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La navegación conserva los productos y descuentos vigentes del carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver al carrito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de envío — Abrir producto del resumen

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir abrir productos desde el resumen de checkout. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir producto del resumen». |
| Flujo principal | 1. El cliente abre la sección «Checkout de envío». 2. Solicita abrir producto del resumen. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta ítem, producto, identificador de navegación, pantalla destino y checkout actual. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La pantalla usa el identificador de navegación del producto o vuelve a tienda si no existe», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir producto del resumen» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Listar métodos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar métodos de envío configurados. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar métodos». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Solicita listar métodos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta método, nombre, descripción, costo base, tiempo de entrega, icono, estado y orden. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo los métodos activos aparecen para clientes durante checkout», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar métodos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Crear método

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear un método de envío. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear método». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para crear método. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El nombre y costo deben ser válidos y el método puede quedar activo o inactivo. 5. Ejecuta la función y actualiza nombre, descripción, costo base, tiempo estimado, icono, estado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El nombre y costo deben ser válidos y el método puede quedar activo o inactivo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear método» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Editar método

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar un método de envío. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar método». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para editar método. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios se reflejan en checkout para métodos activos. 5. Ejecuta la función y actualiza identificador, datos actualizados, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios se reflejan en checkout para métodos activos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar método» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Eliminar método

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar un método de envío. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar método». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona eliminar método. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación y no debe dejar el checkout sin opciones válidas. 5. Aplica la acción y actualiza identificador, método, usuario ejecutor, confirmación y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación y no debe dejar el checkout sin opciones válidas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar método» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Listar reglas por precio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar reglas de recargo por rango de subtotal. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar reglas por precio». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Solicita listar reglas por precio. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta regla, mínimo, máximo, recargo, estado, etiqueta de rango y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las reglas activas se aplican al cálculo de envío», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar reglas por precio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Crear regla por precio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear reglas de envío por rango de precio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear regla por precio». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para crear regla por precio. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El mínimo no puede ser negativo y el máximo debe ser mayor o igual al mínimo cuando exista. 5. Ejecuta la función y actualiza precio mínimo, precio máximo, recargo, estado, usuario creador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El mínimo no puede ser negativo y el máximo debe ser mayor o igual al mínimo cuando exista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear regla por precio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Editar regla por precio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar reglas de envío por rango de precio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar regla por precio». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para editar regla por precio. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los valores deben validarse antes de guardar y aplicarse al siguiente cálculo de checkout. 5. Ejecuta la función y actualiza identificador, valores actualizados, estado, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los valores deben validarse antes de guardar y aplicarse al siguiente cálculo de checkout», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar regla por precio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Eliminar regla por precio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar una regla de envío por precio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar regla por precio». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona eliminar regla por precio. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación administrativa. 5. Aplica la acción y actualiza identificador de regla, rango, usuario ejecutor y fecha de eliminación. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar regla por precio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Ver detalle de método

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de un método de envío. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de método». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Solicita ver detalle de método. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta método, costo, tiempo, descripción, estado y fecha de creación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle se muestra en ventana sin salir del listado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de método» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Exportar métodos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar métodos de envío a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar métodos». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para exportar métodos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos de la vista. 5. Ejecuta la función y actualiza métodos, filtros, formato, administrador y fecha de exportación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos de la vista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar métodos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Ver detalle de regla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de una regla de envío por precio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de regla». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Solicita ver detalle de regla. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta regla, rango mínimo, rango máximo, recargo, estado y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle muestra rango, recargo y narrativa de aplicación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de regla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de envíos — Exportar reglas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar reglas de envío a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar reglas». |
| Flujo principal | 1. El administrador abre la sección «Administración de envíos». 2. Selecciona la opción para exportar reglas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos de la vista. 5. Ejecuta la función y actualiza reglas, filtros, formato, administrador y fecha de exportación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos de la vista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar reglas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Ver carrito

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ver los productos agregados al carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ver carrito». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Solicita ver carrito. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta ítems, producto, imagen, variante, cantidad, precio, subtotal, total, usuario, sesión y estado de vinculación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El carrito se resuelve por usuario o sesión; cuando existen ambas identidades, el carrito invitado se vincula al usuario antes de calcular totales», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver carrito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Ir a tienda desde carrito vacío

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir a la tienda cuando el carrito está vacío. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ir a tienda desde carrito vacío». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para ir a tienda desde carrito vacío. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El botón se muestra como acción principal del estado vacío. 5. Ejecuta la función y actualiza estado del carrito, pantalla destino y sesión. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El botón se muestra como acción principal del estado vacío», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ir a tienda desde carrito vacío» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Actualizar cantidad

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir modificar la cantidad de un ítem del carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Actualizar cantidad». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para actualizar cantidad. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La cantidad debe ser mayor que cero y no superar la disponibilidad validada. 5. Ejecuta la función y actualiza identificador del ítem, cantidad anterior, cantidad nueva, stock, subtotal y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La cantidad debe ser mayor que cero y no superar la disponibilidad validada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar cantidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Eliminar producto

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir eliminar un producto individual del carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Eliminar producto». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona eliminar producto. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al eliminar se recalculan contador y total del carrito. 5. Aplica la acción y actualiza identificador del ítem, producto, usuario o sesión, total actualizado y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al eliminar se recalculan contador y total del carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Continuar compra

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir continuar desde el carrito al checkout de envío. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Continuar compra». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para continuar compra. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Para continuar debe existir al menos un producto disponible seleccionado y una sesión de cliente válida; si no hay sesión, el sistema redirige al inicio de sesión conservando el retorno al checkout. 5. Ejecuta la función y actualiza carrito, usuario o sesión, productos seleccionados, subtotal seleccionado, total de ítems, estado de autenticación y pantalla de envío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Para continuar debe existir al menos un producto disponible seleccionado y una sesión de cliente válida; si no hay sesión, el sistema redirige al inicio de sesión conservando el retorno al checkout», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «continuar compra» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Consultar productos del carrito

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir consultar los identificadores de productos presentes en el carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Consultar productos del carrito». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Solicita consultar productos del carrito. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario o sesión, identificadores de producto y cantidad de ítems. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La consulta se usa para sincronizar estados como favoritos, botones y disponibilidad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar productos del carrito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Mostrar disponibilidad

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Informar si un producto del carrito está agotado o no disponible. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Mostrar disponibilidad». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Solicita mostrar disponibilidad. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta ítem, producto, variante, stock disponible, estado de disponibilidad y mensaje. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los productos agotados deben impedir una compra inválida o guiar al usuario para corregir el carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar disponibilidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Seleccionar producto para pago

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar o deseleccionar productos disponibles del carrito antes de continuar al envío. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Seleccionar producto para pago». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para seleccionar producto para pago. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se pueden seleccionar productos con disponibilidad suficiente; los agotados o inválidos quedan excluidos del pago. 5. Ejecuta la función y actualiza ítem, producto, variante, cantidad, disponibilidad, estado seleccionado, usuario o sesión y subtotal seleccionado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se pueden seleccionar productos con disponibilidad suficiente; los agotados o inválidos quedan excluidos del pago», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar producto para pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Seleccionar todos los productos disponibles

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar o deseleccionar todos los productos disponibles del carrito en una sola acción. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Seleccionar todos los productos disponibles». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para seleccionar todos los productos disponibles. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La selección masiva solo aplica a productos que puedan comprarse y conserva fuera del pago los productos agotados o con ajuste pendiente. 5. Ejecuta la función y actualiza carrito, productos disponibles, productos excluidos, selección anterior, selección final, usuario o sesión y total seleccionado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La selección masiva solo aplica a productos que puedan comprarse y conserva fuera del pago los productos agotados o con ajuste pendiente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar todos los productos disponibles» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Validar selección antes de continuar

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Impedir avanzar al envío cuando no exista al menos un producto disponible seleccionado. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Validar selección antes de continuar». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Selecciona la opción para validar selección antes de continuar. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si la selección está vacía o solo contiene productos no disponibles, el sistema muestra una advertencia clara para corregir el carrito. 5. Ejecuta la función y actualiza carrito, productos seleccionados, productos bloqueados, mensaje de validación, usuario o sesión y pantalla de envío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si la selección está vacía o solo contiene productos no disponibles, el sistema muestra una advertencia clara para corregir el carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «validar selección antes de continuar» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito — Abrir producto desde carrito

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir abrir la ficha del producto desde el carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir producto desde carrito». |
| Flujo principal | 1. El cliente abre la sección «Carrito». 2. Solicita abrir producto desde carrito. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta ítem, producto, identificador de navegación, pantalla destino y usuario o sesión. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si el producto no tiene identificador de navegación válido, se redirige a la tienda», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir producto desde carrito» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrito abandonado — Despachar recordatorios

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir despachar recordatorios de carritos abandonados desde opción administrativa. |
| Precondiciones | Ocurre el evento que activa la función «Despachar recordatorios» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Carrito abandonado». 2. El sistema reúne la información necesaria para despachar recordatorios. 3. Valida la regla de negocio: El disparo debe procesar carritos elegibles según reglas de abandono y evitar duplicados innecesarios. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando carritos elegibles, usuario o correo, productos, fecha de abandono, estado de envío y resultado. |
| Alternativas | Si no se cumple la regla «El disparo debe procesar carritos elegibles según reglas de abandono y evitar duplicados innecesarios», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «despachar recordatorios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Ver cuenta bancaria

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar la cuenta bancaria activa para realizar transferencia. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ver cuenta bancaria». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Solicita ver cuenta bancaria. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta banco, tipo de cuenta, número parcialmente visible, titular, identificación, correo y teléfono. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Debe existir una cuenta activa; si no existe, el sistema informa que no se puede completar el pago», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver cuenta bancaria» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Seleccionar banco

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir seleccionar el banco relacionado con la transferencia. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Seleccionar banco». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para seleccionar banco. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El banco seleccionado debe pertenecer al catálogo de bancos disponible. 5. Ejecuta la función y actualiza código de banco, nombre, logo, usuario y pago en preparación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El banco seleccionado debe pertenecer al catálogo de bancos disponible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar banco» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Ingresar referencia

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Exigir el número de referencia de transferencia. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ingresar referencia». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para ingresar referencia. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La referencia debe tener longitud mínima para ser aceptada. 5. Ejecuta la función y actualiza referencia bancaria, banco, usuario, orden prevista y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La referencia debe tener longitud mínima para ser aceptada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ingresar referencia» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Subir comprobante

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir subir comprobante de pago. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Subir comprobante». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para subir comprobante. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se aceptan archivos permitidos y con tamaño máximo configurado. 5. Ejecuta la función y actualiza archivo, nombre, tipo, tamaño, vista previa, pantalla almacenada y fecha de carga. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se aceptan archivos permitidos y con tamaño máximo configurado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «subir comprobante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Quitar comprobante

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir quitar el comprobante seleccionado antes de confirmar. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Quitar comprobante». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona quitar comprobante. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al quitar el archivo se limpia la vista previa y el comprobante deja de enviarse. 5. Aplica la acción y actualiza archivo eliminado de selección, estado del formulario y fecha de acción. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al quitar el archivo se limpia la vista previa y el comprobante deja de enviarse», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «quitar comprobante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Aceptar términos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Exigir aceptación de términos antes de crear la orden. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Aceptar términos». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para aceptar términos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La orden no se crea si la casilla de términos no está marcada. 5. Ejecuta la función y actualiza usuario, aceptación de términos, fecha, carrito y pago asociado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La orden no se crea si la casilla de términos no está marcada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aceptar términos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Confirmar pedido

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Crear la orden, registrar el pago y enviar confirmación al terminar el checkout. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Confirmar pedido». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para confirmar pedido. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción valida formulario, carrito, dirección, cuenta bancaria y comprobante antes de confirmar. 5. Ejecuta la función y actualiza orden, número, usuario, ítems, dirección, método, subtotal, envío, descuento, total, referencia y comprobante. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción valida formulario, carrito, dirección, cuenta bancaria y comprobante antes de confirmar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «confirmar pedido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Enviar confirmación

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Enviar confirmación de checkout con el detalle del pedido. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Enviar confirmación». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Selecciona la opción para enviar confirmación. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El envío se realiza después de crear la orden y puede incluir documento adjunto cuando esté disponible. 5. Ejecuta la función y actualiza orden, correo, productos, totales, dirección, estado de pago, PDF y fecha de envío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El envío se realiza después de crear la orden y puede incluir documento adjunto cuando esté disponible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar confirmación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Volver a envío

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir volver desde el pago al paso de envío. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Volver a envío». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Solicita volver a envío. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta datos de envío, usuario, pantalla origen, pantalla destino y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al volver se conserva dirección, método, notas y descuentos guardados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver a envío» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Listar facturas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar facturas desde el panel administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar facturas». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Solicita listar facturas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta factura, orden, cliente, correo, total, estado, fecha y archivo disponible. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado se puede filtrar por cliente, fecha, estado o número relacionado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar facturas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Descargar factura

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir descargar una factura en PDF. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Descargar factura». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Selecciona la opción para descargar factura. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La descarga solo procede para facturas existentes y accesibles. 5. Ejecuta la función y actualiza identificador, orden, archivo PDF, usuario solicitante y fecha de descarga. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La descarga solo procede para facturas existentes y accesibles», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «descargar factura» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Reenviar factura

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir reenviar una factura al cliente por correo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Reenviar factura». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Selecciona la opción para reenviar factura. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El reenvío se registra como acción administrativa. 5. Ejecuta la función y actualiza factura, orden, correo destino, usuario ejecutor, estado de envío y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El reenvío se registra como acción administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reenviar factura» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Aplicar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar facturas por búsqueda, estado, pago y fechas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Aplicar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Selecciona la opción para aplicar filtros. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El rango de fechas debe validarse antes de consultar. 5. Ejecuta la función y actualiza búsqueda, estado, pago, fecha inicial, fecha final, página y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El rango de fechas debe validarse antes de consultar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar filtros de facturas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La acción recarga la bandeja con filtros predeterminados. 5. Aplica la acción y actualiza filtros eliminados, listado resultante y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La acción recarga la bandeja con filtros predeterminados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Recargar facturas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir recargar manualmente el listado de facturas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Recargar facturas». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Solicita recargar facturas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta filtros, página, total, fecha de consulta y administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La recarga conserva los filtros activos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «recargar facturas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de facturas — Vista rápida de factura

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de una factura consultando la orden relacionada. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Vista rápida de factura». |
| Flujo principal | 1. El administrador abre la sección «Administración de facturas». 2. Selecciona la opción para vista rápida de factura. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si la orden no se puede consultar, el sistema muestra un mensaje controlado. 5. Ejecuta la función y actualiza factura, orden, cliente, productos, totales, estado de consulta y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si la orden no se puede consultar, el sistema muestra un mensaje controlado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «vista rápida de factura» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Pagos del cliente — Registrar pago

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir registrar una transacción de pago asociada a una orden. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Registrar pago». |
| Flujo principal | 1. El cliente abre la sección «Pagos del cliente». 2. Selecciona la opción para registrar pago. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Toda transacción debe tener orden, monto, método y estado inicial. 5. Ejecuta la función y actualiza orden, usuario, método, banco, referencia, comprobante, monto, estado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Toda transacción debe tener orden, monto, método y estado inicial», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Pagos del cliente — Listar pagos del usuario

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir consultar pagos asociados al usuario o sus órdenes. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Listar pagos del usuario». |
| Flujo principal | 1. El cliente abre la sección «Pagos del cliente». 2. Solicita listar pagos del usuario. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta transacción, orden, referencia, estado, monto, método, comprobante y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El usuario solo puede consultar pagos propios», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar pagos del usuario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Listar pagos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir a administradores listar pagos y comprobantes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar pagos». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Solicita listar pagos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pago, orden, cliente, banco, referencia, comprobante, monto, estado y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los pagos pendientes deben destacarse para revisión», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar pagos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Ver comprobante

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el comprobante de pago en un ventana administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver comprobante». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Solicita ver comprobante. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pago, pantalla pública del comprobante, tipo de archivo, estado de disponibilidad y orden. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si el archivo no está disponible, se muestra un mensaje controlado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver comprobante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Verificar pago

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aprobar o rechazar un pago desde administración. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Verificar pago». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Solicita verificar pago. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pago, estado anterior, estado nuevo, motivo, administrador y fecha de verificación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Al rechazar se debe registrar motivo; al aprobar puede actualizarse el estado de la orden», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Filtrar pagos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir filtrar pagos administrativos por estado y búsqueda. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar pagos». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Solicita filtrar pagos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado, búsqueda, pagos resultantes, total y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los filtros ayudan a priorizar comprobantes pendientes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar pagos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Abrir configuración bancaria

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir un ventana para editar la configuración bancaria desde pagos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir configuración bancaria». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Solicita abrir configuración bancaria. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta cuenta bancaria, estado de carga, formulario y administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El ventana carga la configuración actual antes de permitir guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir configuración bancaria» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de pagos — Sincronizar pago con orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Sincronizar el estado de pago con la orden después de aprobar o rechazar un comprobante. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Sincronizar pago con orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de pagos». 2. Selecciona la opción para sincronizar pago con orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si falla la actualización de la orden, el sistema revierte o conserva el estado seguro del pago. 5. Ejecuta la función y actualiza pago, orden, estado de pago, estado de orden, resultado de sincronización y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si falla la actualización de la orden, el sistema revierte o conserva el estado seguro del pago», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «sincronizar pago con orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de pagos — Ver cuenta bancaria

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir a administradores ver la configuración de cuenta bancaria. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver cuenta bancaria». |
| Flujo principal | 1. El administrador abre la sección «Configuración de pagos». 2. Solicita ver cuenta bancaria. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta banco, número de cuenta, tipo, titular, identificación, contacto, estado y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Por seguridad se muestra el número de cuenta parcialmente oculto cuando aplique», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver cuenta bancaria» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de pagos — Guardar cuenta bancaria

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir guardar o actualizar la cuenta bancaria de recepción. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Guardar cuenta bancaria». |
| Flujo principal | 1. El administrador abre la sección «Configuración de pagos». 2. Selecciona la opción para guardar cuenta bancaria. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Debe existir una cuenta activa para que el checkout pueda recibir transferencias. 5. Ejecuta la función y actualiza banco, número, tipo, titular, identificación, correo, teléfono, estado y usuario editor. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Debe existir una cuenta activa para que el checkout pueda recibir transferencias», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «guardar cuenta bancaria» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración de pagos — Listar bancos colombianos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Listar el catálogo de bancos colombianos activos para formularios de pago y configuración bancaria. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar bancos colombianos». |
| Flujo principal | 1. El administrador abre la sección «Configuración de pagos». 2. Solicita listar bancos colombianos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta código del banco, nombre del banco, estado activo y fecha de consulta. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo los bancos activos deben aparecer como opciones seleccionables», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar bancos colombianos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Confirmación — Mostrar confirmación

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar una página de confirmación con número de pedido y resumen. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Mostrar confirmación». |
| Flujo principal | 1. El cliente abre la sección «Confirmación». 2. Solicita mostrar confirmación. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta número de pedido, fecha, total, productos, dirección, método, correo y estado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Si no hay resultado de compra, el sistema ofrece volver a tienda o ver pedidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar confirmación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Confirmación — Descargar PDF

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir descargar el PDF de la orden cuando esté disponible. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Descargar PDF». |
| Flujo principal | 1. El cliente abre la sección «Confirmación». 2. Selecciona la opción para descargar PDF. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El botón solo debe estar disponible si el documento fue generado correctamente. 5. Ejecuta la función y actualiza identificador de orden, estado de PDF, archivo, nombre de descarga y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El botón solo debe estar disponible si el documento fue generado correctamente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «descargar PDF» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Confirmación — Ir a mis pedidos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ir desde la confirmación al historial de pedidos. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ir a mis pedidos». |
| Flujo principal | 1. El cliente abre la sección «Confirmación». 2. Selecciona la opción para ir a mis pedidos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El usuario debe estar autenticado para ver sus pedidos. 5. Ejecuta la función y actualiza usuario, número de pedido y pantalla de historial. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El usuario debe estar autenticado para ver sus pedidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ir a mis pedidos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Confirmación — Volver a tienda

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir volver a la tienda desde la confirmación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Volver a tienda». |
| Flujo principal | 1. El cliente abre la sección «Confirmación». 2. Solicita volver a tienda. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden confirmada, usuario, pantalla destino y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La acción no modifica la orden ya creada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «volver a tienda» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Listar mis órdenes

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al usuario ver su historial de órdenes. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Listar mis órdenes». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Solicita listar mis órdenes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, fecha, estado, estado de pago, total, cantidad de productos y usuario. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada usuario solo ve órdenes asociadas a su cuenta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar mis órdenes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Ver detalle de mi orden

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir ver el detalle completo de una orden propia. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Ver detalle de mi orden». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Solicita ver detalle de mi orden. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, productos, variantes, dirección, pago, comprobante, estados, totales y fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La orden debe pertenecer al usuario que consulta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de mi orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Rastrear estado

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar una línea de tiempo de estados de la orden. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Rastrear estado». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Solicita rastrear estado. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado actual, historial, fechas, responsable, estado de reembolso, porcentaje de avance y tiempo estimado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los estados completados, actual y pendientes se muestran de forma diferenciada; si existe reembolso, el progreso debe cambiar dinámicamente a solicitado, en proceso y reembolsado según `payment_status` o `refund_request_status`», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «rastrear estado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Cancelar pedido

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al cliente cancelar pedidos en estado permitido. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Cancelar pedido». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Selecciona cancelar pedido. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Solo se pueden cancelar pedidos propios que estén pendientes o en preparación. 5. Aplica la acción y actualiza orden, usuario, estado anterior, estado cancelado, motivo, estado de reembolso y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Solo se pueden cancelar pedidos propios que estén pendientes o en preparación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cancelar pedido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Repetir compra

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir volver a tienda desde una orden para comprar nuevamente. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Repetir compra». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Selecciona la opción para repetir compra. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción navega a la tienda sin modificar la orden existente. 5. Ejecuta la función y actualiza orden de referencia, usuario, pantalla destino y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción navega a la tienda sin modificar la orden existente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «repetir compra» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Procesamiento de órdenes — Prevenir envío duplicado

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Prevenir la creación duplicada de órdenes por doble envío del formulario. |
| Precondiciones | Ocurre el evento que activa la función «Prevenir envío duplicado» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Procesamiento de órdenes». 2. El sistema reúne la información necesaria para prevenir envío duplicado. 3. Valida la regla de negocio: Si se detecta una solicitud duplicada, se rechaza o se reutiliza el control de control de duplicados. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando usuario, carrito, firma de solicitud, orden relacionada, fecha y resultado. |
| Alternativas | Si no se cumple la regla «Si se detecta una solicitud duplicada, se rechaza o se reutiliza el control de control de duplicados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «prevenir envío duplicado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Listar órdenes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir a administradores listar órdenes recientes con filtros y búsqueda. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar órdenes». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Solicita listar órdenes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, cliente, correo, total, estado, pago, fecha, origen y acciones disponibles. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las órdenes se ordenan por fecha y pueden filtrarse por estado, pago, cliente o rango de fechas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar órdenes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Ver detalle de orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir ver el detalle administrativo de una orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Solicita ver detalle de orden. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, cliente, productos, variantes, pagos, comprobantes, dirección, notas, historial, navegación vigente y totales. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle incluye datos de cliente, productos, dirección, comprobante y cronología, y debe descartar cargas obsoletas si la navegación cambia durante la consulta», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Editar datos de orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar datos operativos de una orden desde el panel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar datos de orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para editar datos de orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo administradores pueden modificar datos y deben conservar trazabilidad del cambio. 5. Ejecuta la función y actualiza orden, cliente, dirección, teléfono, notas, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo administradores pueden modificar datos y deben conservar trazabilidad del cambio», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar datos de orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Cambiar estado de orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cambiar el estado de una orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cambiar estado de orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para cambiar estado de orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada cambio de estado genera historial y, si corresponde, notificación al cliente. 5. Ejecuta la función y actualiza orden, estado anterior, estado nuevo, usuario ejecutor, motivo y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada cambio de estado genera historial y, si corresponde, notificación al cliente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar estado de orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Cambiar estado de pago

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cambiar el estado de pago de una orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cambiar estado de pago». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para cambiar estado de pago. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el pago se rechaza o reembolsa debe registrarse el motivo operativo. 5. Ejecuta la función y actualiza orden, pago anterior, pago nuevo, motivo, usuario ejecutor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el pago se rechaza o reembolsa debe registrarse el motivo operativo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar estado de pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Desactivar orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir desactivar una orden cuando ya no debe operar en listados activos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Desactivar orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona desactivar orden. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La desactivación requiere validación administrativa y conserva la información para auditoría. 5. Aplica la acción y actualiza orden, estado activo anterior, estado inactivo, usuario ejecutor, motivo y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La desactivación requiere validación administrativa y conserva la información para auditoría», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «desactivar orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Marcar órdenes vistas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Registrar cuando un administrador visualiza órdenes nuevas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Marcar órdenes vistas». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para marcar órdenes vistas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada administrador tiene su propio contador de órdenes no vistas. 5. Ejecuta la función y actualiza orden, administrador, fecha de vista y contador actualizado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada administrador tiene su propio contador de órdenes no vistas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar órdenes vistas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Ver contador de órdenes nuevas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar un contador de órdenes nuevas en el panel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver contador de órdenes nuevas». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Solicita ver contador de órdenes nuevas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta administrador, total sin ver, fecha de cálculo y estado del indicador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El contador desaparece cuando no hay órdenes pendientes de visualización para el administrador», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver contador de órdenes nuevas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Aplicar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aplicar filtros de órdenes por búsqueda, estado, pago y fechas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Aplicar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para aplicar filtros. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El rango de fechas debe ser válido antes de consultar. 5. Ejecuta la función y actualiza búsqueda, estado, pago, fecha inicial, fecha final, página y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El rango de fechas debe ser válido antes de consultar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar filtros del listado de órdenes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al limpiar se recarga el listado con valores predeterminados. 5. Aplica la acción y actualiza filtros eliminados, listado resultante, página y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al limpiar se recarga el listado con valores predeterminados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Exportar órdenes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar órdenes a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar órdenes». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para exportar órdenes. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros y columnas visibles de la bandeja. 5. Ejecuta la función y actualiza órdenes, filtros, formato, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros y columnas visibles de la bandeja», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar órdenes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Seleccionar orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar órdenes individuales para acciones masivas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Seleccionar orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para seleccionar orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La selección se identifica por orden y origen para evitar mezclar registros. 5. Ejecuta la función y actualiza orden, origen, estado seleccionado, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La selección se identifica por orden y origen para evitar mezclar registros», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Seleccionar todas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar o deseleccionar todas las órdenes visibles. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Seleccionar todas». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para seleccionar todas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se seleccionan órdenes de la página o filtro visible. 5. Ejecuta la función y actualiza órdenes visibles, selección anterior, selección final y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se seleccionan órdenes de la página o filtro visible», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar todas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Ejecutar acción masiva

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aplicar cambios masivos de estado, pago o desactivación. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ejecutar acción masiva». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para ejecutar acción masiva. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada orden seleccionada se valida individualmente y se reportan errores por registro. 5. Ejecuta la función y actualiza órdenes seleccionadas, acción, estado nuevo, pago nuevo, motivo, éxitos, fallas y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada orden seleccionada se valida individualmente y se reportan errores por registro», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ejecutar acción masiva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Vista rápida de orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir una vista rápida de orden desde el listado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Vista rápida de orden». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para vista rápida de orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La vista rápida no reemplaza el detalle completo y ofrece enlace a la orden. 5. Ejecuta la función y actualiza orden, cliente, productos, totales, estado, pago y pantalla de detalle. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La vista rápida no reemplaza el detalle completo y ofrece enlace a la orden», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «vista rápida de orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Abrir detalle desde fila

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle completo de una orden al presionar cualquier fila del listado administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir detalle desde fila». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Solicita abrir detalle desde fila. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, origen, fila seleccionada, pantalla destino, administrador y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La navegación no debe interferir con botones, enlaces, selectores ni casillas de selección de la misma fila», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir detalle desde fila» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Completar orden rápida

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir marcar una orden como completada desde un botón directo en el listado administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Completar orden rápida». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para completar orden rápida. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción requiere confirmación, bloquea doble envío y no aplica sobre órdenes cerradas, canceladas o reembolsadas. 5. Ejecuta la función y actualiza orden, estado anterior, estado completado, administrador, descripción del cambio y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción requiere confirmación, bloquea doble envío y no aplica sobre órdenes cerradas, canceladas o reembolsadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «completar orden rápida» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de órdenes — Iniciar reembolso por pago verificado

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Pasar el pago a reembolso en proceso cuando una orden cancelada recibe verificación de pago. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Iniciar reembolso por pago verificado». |
| Flujo principal | 1. El administrador abre la sección «Administración de órdenes». 2. Selecciona la opción para iniciar reembolso por pago verificado. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Una orden cancelada no se reactiva por verificar el pago; el sistema conserva la cancelación e inicia el flujo de reembolso. 5. Ejecuta la función y actualiza orden, estado cancelado, estado de pago solicitado, estado de reembolso, historial, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Una orden cancelada no se reactiva por verificar el pago; el sistema conserva la cancelación e inicia el flujo de reembolso», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «iniciar reembolso por pago verificado» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Órdenes del cliente — Descargar factura desde detalle

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir descargar la factura PDF generada desde el detalle de un pedido propio. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Descargar factura desde detalle». |
| Flujo principal | 1. El cliente abre la sección «Órdenes del cliente». 2. Selecciona la opción para descargar factura desde detalle. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El botón solo se muestra cuando la factura ya existe y la descarga valida que el pedido pertenezca al usuario autenticado. 5. Ejecuta la función y actualiza pedido, usuario, correo, número de factura, fecha de factura, archivo PDF, origen del pedido y fecha de descarga. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El botón solo se muestra cuando la factura ya existe y la descarga valida que el pedido pertenezca al usuario autenticado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «descargar factura desde detalle» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Ver inventario

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir ver inventario por producto, color y talla. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver inventario». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Solicita ver inventario. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, color, talla, stock, reservado, disponible, umbral, estado y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El inventario muestra alertas cuando el stock está bajo, crítico o agotado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver inventario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Ajustar stock

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir ajustar manualmente el stock de una variante. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ajustar stock». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Selecciona la opción para ajustar stock. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Todo ajuste debe indicar cantidad y motivo operativo. 5. Ejecuta la función y actualiza variante, stock anterior, ajuste, stock resultante, motivo, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Todo ajuste debe indicar cantidad y motivo operativo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ajustar stock» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Transferir stock

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir transferir stock entre variantes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Transferir stock». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Selecciona la opción para transferir stock. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La variante origen debe tener unidades suficientes para la transferencia. 5. Ejecuta la función y actualiza variante origen, variante destino, cantidad, motivo, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La variante origen debe tener unidades suficientes para la transferencia», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «transferir stock» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Ver historial

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir consultar historial de movimientos de inventario. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver historial». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Solicita ver historial. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta movimiento, variante, tipo, cantidad, stock antes, stock después, motivo, usuario, orden y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El historial conserva entradas, salidas, ajustes, transferencias y movimientos automáticos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver historial» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Confirmar inventario por orden

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Descontar o confirmar inventario cuando se confirma una orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Confirmar inventario por orden». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Selecciona la opción para confirmar inventario por orden. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si la orden se cancela o falla, el stock reservado debe liberarse o revertirse según el estado. 5. Ejecuta la función y actualiza orden, variantes, cantidades, stock anterior, stock final, reserva y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si la orden se cancela o falla, el stock reservado debe liberarse o revertirse según el estado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «confirmar inventario por orden» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Filtrar por pestañas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir alternar inventario entre todo, bajo stock y sin stock. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Filtrar por pestañas». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Solicita filtrar por pestañas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pestaña activa, productos filtrados, totales y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada pestaña filtra el listado sin perder los datos cargados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «filtrar por pestañas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Abrir detalle de producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de inventario de un producto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir detalle de producto». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Solicita abrir detalle de producto. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, variantes, stock, historial, estado de ventana y administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle muestra variantes, historial y acciones de ajuste o transferencia», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir detalle de producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Actualizar historial

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir actualizar manualmente el historial de inventario del producto abierto. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Actualizar historial». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Selecciona la opción para actualizar historial. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La actualización consulta movimientos recientes sin cerrar el detalle. 5. Ejecuta la función y actualiza producto, movimientos, fecha de consulta y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La actualización consulta movimientos recientes sin cerrar el detalle», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar historial» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Inventario general — Exportar inventario

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar inventario a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar inventario». |
| Flujo principal | 1. El administrador abre la sección «Inventario general». 2. Selecciona la opción para exportar inventario. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación incluye el estado de stock y respeta la vista filtrada. 5. Ejecuta la función y actualiza productos, variantes, stock, filtros, formato y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación incluye el estado de stock y respeta la vista filtrada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar inventario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Reservar inventario

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Reservar inventario en registros del sistema al crear una orden. |
| Precondiciones | Ocurre el evento que activa la función «Reservar inventario» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para reservar inventario. 3. Valida la regla de negocio: La reserva valida stock, bloquea la variante durante la operación y falla si no hay disponibilidad. El tiempo de reserva debe leerse desde la configuración operativa vigente. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, producto, variante de talla, cantidad, llave de reserva, fecha límite, estado y tiempo restante. |
| Alternativas | Si no se cumple la regla «La reserva valida stock, bloquea la variante durante la operación y falla si no hay disponibilidad. El tiempo de reserva debe leerse desde la configuración operativa vigente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reservar inventario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Extender reserva

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir extender el tiempo de una reserva activa. |
| Precondiciones | Ocurre el evento que activa la función «Extender reserva» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para extender reserva. 3. Valida la regla de negocio: La nueva expiración no debe reducir el tiempo de una reserva que ya vence más tarde. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, reserva, expiración anterior, expiración nueva, tiempo restante y fecha. |
| Alternativas | Si no se cumple la regla «La nueva expiración no debe reducir el tiempo de una reserva que ya vence más tarde», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «extender reserva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Confirmar reserva

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Confirmar la reserva de stock cuando la orden se consolida. |
| Precondiciones | Ocurre el evento que activa la función «Confirmar reserva» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para confirmar reserva. 3. Valida la regla de negocio: Al confirmar se descuenta inventario en catálogo y la reserva pasa a estado confirmado. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, variantes, cantidades, estado confirmado, inventario descontado y fecha. |
| Alternativas | Si no se cumple la regla «Al confirmar se descuenta inventario en catálogo y la reserva pasa a estado confirmado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «confirmar reserva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Liberar reserva

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Liberar stock reservado cuando una orden se cancela manual o automáticamente. |
| Precondiciones | Ocurre el evento que activa la función «Liberar reserva» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para liberar reserva. 3. Valida la regla de negocio: La liberación devuelve unidades disponibles y marca la reserva como cancelada; no debe persistirse un estado final de orden vencida. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, variantes, cantidades liberadas, estado final cancelado, motivo y fecha. |
| Alternativas | Si no se cumple la regla «La liberación devuelve unidades disponibles y marca la reserva como cancelada; no debe persistirse un estado final de orden vencida», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «liberar reserva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Cancelar por vencimiento de reserva

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Cancelar automáticamente las órdenes con reserva vencida mediante proceso operativo. |
| Precondiciones | Ocurre el evento que activa la función «Cancelar por vencimiento de reserva» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para cancelar por vencimiento de reserva. 3. Valida la regla de negocio: Cuando vence el tiempo configurado para pagar y sostener stock, la orden pasa a cancelada, se libera inventario, se registra historial, se publica evento websocket y se notifica al cliente con la acción recomendada de crear un nuevo pedido si aún hay disponibilidad. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, reserva, estado anterior, estado cancelado, motivo `reservation_ttl_expired`, historial, evento websocket, instrucción para cliente, instrucción para administrador y fecha. |
| Alternativas | Si no se cumple la regla «Cuando vence el tiempo configurado para pagar y sostener stock, la orden pasa a cancelada, se libera inventario, se registra historial, se publica evento websocket y se notifica al cliente con la acción recomendada de crear un nuevo pedido si aún hay disponibilidad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cancelar por vencimiento de reserva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Reconciliar contadores Redis

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Reconciliar las reservas activas de inventario entre base de datos y Redis para evitar bloqueos de compra por claves huérfanas. |
| Precondiciones | Ocurre el evento que activa la función «Reconciliar contadores Redis» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para reconciliar contadores Redis. 3. Valida la regla de negocio: Las claves `reserved` sin reserva activa deben limpiarse y el stock disponible debe recalcularse desde el catálogo cuando Redis quede desfasado. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando variante, stock físico, stock disponible, reservado en Redis, reserva activa, resultado de reconciliación y fecha. |
| Alternativas | Si no se cumple la regla «Las claves `reserved` sin reserva activa deben limpiarse y el stock disponible debe recalcularse desde el catálogo cuando Redis quede desfasado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reconciliar contadores Redis» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Reserva temporal — Reconciliar reservas

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Reconciliar reservas vencidas y contadores de control del sistema de disponibilidad desde proceso operativo. |
| Precondiciones | Ocurre el evento que activa la función «Reconciliar reservas» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Reserva temporal». 2. El sistema reúne la información necesaria para reconciliar reservas. 3. Valida la regla de negocio: El proceso procesa lotes y reporta órdenes canceladas por vencimiento, contadores actualizados y errores, manteniendo la configuración de lote vigente. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando tamaño de lote, órdenes revisadas, órdenes canceladas por vencimiento, contadores actualizados, errores y fecha. |
| Alternativas | Si no se cumple la regla «El proceso procesa lotes y reporta órdenes canceladas por vencimiento, contadores actualizados y errores, manteniendo la configuración de lote vigente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reconciliar reservas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Alertas — Sincronizar alerta

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Sincronizar el estado de alerta cuando una variante queda sin stock o recupera unidades. |
| Precondiciones | Ocurre el evento que activa la función «Sincronizar alerta» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Alertas». 2. El sistema reúne la información necesaria para sincronizar alerta. 3. Valida la regla de negocio: Una alerta sin stock se marca como resuelta cuando la variante vuelve a tener unidades disponibles. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando variante, producto, color, talla, SKU, stock, estado, fecha sin stock y fecha resuelta. |
| Alternativas | Si no se cumple la regla «Una alerta sin stock se marca como resuelta cuando la variante vuelve a tener unidades disponibles», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «sincronizar alerta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Alertas — Enviar alerta inicial

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Enviar correo inicial cuando una variante llega a cero unidades. |
| Precondiciones | Ocurre el evento que activa la función «Enviar alerta inicial» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Alertas». 2. El sistema reúne la información necesaria para enviar alerta inicial. 3. Valida la regla de negocio: El correo solo se envía si existen destinatarios configurados y la alerta no fue notificada antes. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando variante, producto, destinatarios, asunto, fecha de envío y estado de alerta. |
| Alternativas | Si no se cumple la regla «El correo solo se envía si existen destinatarios configurados y la alerta no fue notificada antes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar alerta inicial» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Alertas — Enviar recordatorio

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Enviar recordatorios de reposición para variantes que siguen agotadas. |
| Precondiciones | Ocurre el evento que activa la función «Enviar recordatorio» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Alertas». 2. El sistema reúne la información necesaria para enviar recordatorio. 3. Valida la regla de negocio: El recordatorio respeta los días configurados entre avisos. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando variante, días sin stock, último recordatorio, destinatarios, estado de envío y fecha. |
| Alternativas | Si no se cumple la regla «El recordatorio respeta los días configurados entre avisos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar recordatorio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Listar códigos públicos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir consultar códigos de descuento disponibles para validación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Listar códigos públicos». |
| Flujo principal | 1. El cliente abre la sección «Códigos y aplicación». 2. Solicita listar códigos públicos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta código, tipo, valor, vigencia, usos, estado y aplicabilidad. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo los códigos activos y vigentes deben considerarse aplicables», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar códigos públicos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Validar código

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Validar un código de descuento ingresado. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Validar código». |
| Flujo principal | 1. El cliente abre la sección «Códigos y aplicación». 2. Selecciona la opción para validar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El código debe cumplir vigencia, límite de uso, estado y condiciones de productos o categorías. 5. Ejecuta la función y actualiza código, usuario, carrito, resultado, descuento calculado y mensaje. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El código debe cumplir vigencia, límite de uso, estado y condiciones de productos o categorías», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «validar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Listar códigos admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar códigos de descuento en administración. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar códigos admin». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Solicita listar códigos admin. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta código, tipo, valor, fecha inicial, fecha final, usos, estado y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La bandeja muestra vigencia, usos y estado para gestión comercial», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar códigos admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Crear código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear códigos de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear código». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Selecciona la opción para crear código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El código debe ser único y tener tipo, valor y vigencia válidos. 5. Ejecuta la función y actualiza código, tipo, valor, fechas, límites, productos, categorías, estado y usuario creador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El código debe ser único y tener tipo, valor y vigencia válidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Editar código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar códigos de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar código». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Selecciona la opción para editar código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: No se deben romper usos ya registrados al actualizar condiciones futuras. 5. Ejecuta la función y actualiza código, datos actualizados, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «No se deben romper usos ya registrados al actualizar condiciones futuras», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Eliminar código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar códigos de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar código». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Selecciona eliminar código. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación administrativa. 5. Aplica la acción y actualiza código, estado, usos, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Registrar uso de código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Registrar el uso de un código de descuento cuando se vincula a una orden. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Registrar uso de código». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Selecciona la opción para registrar uso de código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El registro de uso permite controlar límites globales y por usuario. 5. Ejecuta la función y actualiza código, usuario, orden, fecha de uso y contador actualizado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El registro de uso permite controlar límites globales y por usuario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar uso de código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Códigos y aplicación — Registrar descuento aplicado al usuario

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Conservar descuentos aplicados o asignados al usuario hasta que sean usados o expiren. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Registrar descuento aplicado al usuario». |
| Flujo principal | 1. El administrador abre la sección «Códigos y aplicación». 2. Selecciona la opción para registrar descuento aplicado al usuario. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Un descuento aplicado debe marcarse como usado cuando se consume en una compra. 5. Ejecuta la función y actualiza usuario, código, monto, fecha de aplicación, fecha de expiración, estado usado y fecha de uso. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Un descuento aplicado debe marcarse como usado cuando se consume en una compra», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar descuento aplicado al usuario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de códigos — Ver detalle de código

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de un código de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de código». |
| Flujo principal | 1. El administrador abre la sección «Administración de códigos». 2. Solicita ver detalle de código. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta código, tipo, valor, fechas, usos, estado y aplicabilidad. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle muestra condiciones, usos y vigencia del código», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de códigos — Exportar códigos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar códigos de descuento a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar códigos». |
| Flujo principal | 1. El administrador abre la sección «Administración de códigos». 2. Selecciona la opción para exportar códigos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos de la bandeja. 5. Ejecuta la función y actualiza códigos, filtros, formato, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos de la bandeja», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar códigos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de códigos — Generar código automático

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir generar o regenerar un código automático en el formulario. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Generar código automático». |
| Flujo principal | 1. El administrador abre la sección «Administración de códigos». 2. Selecciona la opción para generar código automático. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El código generado debe mantener formato alfanumérico y no reemplaza un código manual salvo que el administrador lo active. 5. Ejecuta la función y actualiza código generado, prefijo o semilla, estado automático, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El código generado debe mantener formato alfanumérico y no reemplaza un código manual salvo que el administrador lo active», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «generar código automático» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Listar clientes de campaña

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir consultar clientes elegibles para campañas de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar clientes de campaña». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Solicita listar clientes de campaña. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta cliente, correo, nombre, historial, elegibilidad y búsqueda. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La búsqueda permite seleccionar destinatarios específicos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar clientes de campaña» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Enviar campaña masiva

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir enviar campañas masivas con códigos de descuento. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Enviar campaña masiva». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Selecciona la opción para enviar campaña masiva. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La campaña debe tener mensaje, código o configuración y destinatarios válidos. 5. Ejecuta la función y actualiza código, asunto, mensaje, destinatarios, estado de envío y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La campaña debe tener mensaje, código o configuración y destinatarios válidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar campaña masiva» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Enviar campaña específica

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir enviar códigos a usuarios específicos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Enviar campaña específica». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Selecciona la opción para enviar campaña específica. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada destinatario seleccionado debe tener correo válido. 5. Ejecuta la función y actualiza código, cliente, correo, mensaje, estado de envío y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada destinatario seleccionado debe tener correo válido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar campaña específica» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Seleccionar clientes filtrados

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar todos los clientes filtrados para una campaña específica. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Seleccionar clientes filtrados». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Selecciona la opción para seleccionar clientes filtrados. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se seleccionan clientes visibles que cumplan el filtro actual. 5. Ejecuta la función y actualiza clientes filtrados, seleccionados, filtro aplicado y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se seleccionan clientes visibles que cumplan el filtro actual», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar clientes filtrados» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Limpiar selección de clientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar la selección de clientes de una campaña específica. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar selección de clientes». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Selecciona limpiar selección de clientes. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La acción no borra el filtro ni los clientes disponibles, solo la selección. 5. Aplica la acción y actualiza selección anterior, selección final, filtro activo y administrador. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La acción no borra el filtro ni los clientes disponibles, solo la selección», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar selección de clientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Campañas — Configurar canales

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar si una campaña específica se envía por notificación del sistema, correo o ambos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Configurar canales». |
| Flujo principal | 1. El administrador abre la sección «Campañas». 2. Selecciona la opción para configurar canales. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Debe existir al menos un canal seleccionado para enviar la campaña. 5. Ejecuta la función y actualiza campaña, canal de notificación, canal de correo, destinatarios y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Debe existir al menos un canal seleccionado para enviar la campaña», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «configurar canales» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Validar descuento por cantidad

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Validar descuentos automáticos por cantidad en el carrito. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Validar descuento por cantidad». |
| Flujo principal | 1. El cliente abre la sección «Descuentos por cantidad». 2. Selecciona la opción para validar descuento por cantidad. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La regla se aplica cuando cantidad y productos cumplen las condiciones. 5. Ejecuta la función y actualiza carrito, cantidad, regla, porcentaje, descuento y total. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La regla se aplica cuando cantidad y productos cumplen las condiciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «validar descuento por cantidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Listar reglas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar reglas de descuento por cantidad. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar reglas». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Solicita listar reglas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta regla, mínimo, máximo, porcentaje, vigencia, estado y aplicabilidad. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las reglas activas se muestran para cálculo automático», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar reglas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Crear regla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear reglas de descuento por cantidad. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear regla». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Selecciona la opción para crear regla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La cantidad mínima y el porcentaje deben ser válidos. 5. Ejecuta la función y actualiza cantidad mínima, máxima, porcentaje, productos, categorías, fechas, estado y usuario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La cantidad mínima y el porcentaje deben ser válidos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear regla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Editar regla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar reglas de descuento por cantidad. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar regla». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Selecciona la opción para editar regla. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios se aplican a nuevos cálculos de carrito. 5. Ejecuta la función y actualiza regla, datos actualizados, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios se aplican a nuevos cálculos de carrito», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar regla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Eliminar regla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar reglas de descuento por cantidad. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar regla». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Selecciona eliminar regla. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación administrativa. 5. Aplica la acción y actualiza regla, usuario ejecutor y fecha de eliminación. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar regla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Ver detalle de regla

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de una regla de descuento por cantidad. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de regla». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Solicita ver detalle de regla. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta regla, cantidad mínima, cantidad máxima, porcentaje, vigencia y estado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle muestra rango de cantidad, porcentaje y vigencia», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de regla» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Descuentos por cantidad — Exportar reglas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar reglas de descuento por cantidad a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar reglas». |
| Flujo principal | 1. El administrador abre la sección «Descuentos por cantidad». 2. Selecciona la opción para exportar reglas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos de la vista. 5. Ejecuta la función y actualiza reglas, filtros, formato, administrador y fecha de exportación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos de la vista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar reglas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrusel público — Avanzar slider

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir avanzar al siguiente slide de la página principal. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Avanzar slider». |
| Flujo principal | 1. El visitante abre la sección «Carrusel público». 2. Selecciona la opción para avanzar slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción solo se muestra cuando existe más de un slide activo. 5. Ejecuta la función y actualiza slide actual, slide destino, total de slides y fecha de acción. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción solo se muestra cuando existe más de un slide activo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «avanzar slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrusel público — Retroceder slider

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir volver al slide anterior de la página principal. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Retroceder slider». |
| Flujo principal | 1. El visitante abre la sección «Carrusel público». 2. Selecciona la opción para retroceder slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción respeta el orden configurado de sliders. 5. Ejecuta la función y actualiza slide actual, slide destino, orden y fecha de acción. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción respeta el orden configurado de sliders», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «retroceder slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrusel público — Seleccionar indicador

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir seleccionar un slide desde sus indicadores. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Seleccionar indicador». |
| Flujo principal | 1. El visitante abre la sección «Carrusel público». 2. Selecciona la opción para seleccionar indicador. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El temporizador del carrusel se reinicia al cambiar manualmente de slide. 5. Ejecuta la función y actualiza índice seleccionado, slide activo, total de slides y estado del temporizador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El temporizador del carrusel se reinicia al cambiar manualmente de slide», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar indicador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Carrusel público — Resolver enlace de slider

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir abrir el enlace configurado de un slider. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Resolver enlace de slider». |
| Flujo principal | 1. El visitante abre la sección «Carrusel público». 2. Selecciona la opción para resolver enlace de slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El enlace debe resolverse como pantalla del sistema o enlace válida según la configuración del slide. 5. Ejecuta la función y actualiza slider, enlace configurado, pantalla resuelta y fecha de navegación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El enlace debe resolverse como pantalla del sistema o enlace válida según la configuración del slide», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «resolver enlace de slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Listar sliders

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar sliders de la página principal. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar sliders». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Solicita listar sliders. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta slider, título, subtítulo, imagen, enlace, orden, estado y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los sliders se muestran ordenados por posición y estado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar sliders» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Crear slider

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear un slider. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear slider». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona la opción para crear slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El título, enlace válido, orden e imagen son obligatorios para guardar. 5. Ejecuta la función y actualiza título, subtítulo, enlace, imagen, orden, estado, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El título, enlace válido, orden e imagen son obligatorios para guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Previsualizar slider

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir previsualizar el slider antes de guardar. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Previsualizar slider». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona la opción para previsualizar slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La previsualización usa los datos capturados en el formulario. 5. Ejecuta la función y actualiza título, subtítulo, imagen temporal, enlace y estado visual. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La previsualización usa los datos capturados en el formulario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «previsualizar slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Editar slider

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar un slider existente. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar slider». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona la opción para editar slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si se reemplaza la imagen, debe quedar registrada la pantalla vigente. 5. Ejecuta la función y actualiza slider, datos actualizados, imagen nueva o actual, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si se reemplaza la imagen, debe quedar registrada la pantalla vigente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Activar o desactivar slider

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir activar o desactivar sliders. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Activar o desactivar slider». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona la opción para activar o desactivar slider. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo sliders activos se muestran en la página principal. 5. Ejecuta la función y actualiza slider, estado anterior, estado nuevo, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo sliders activos se muestran en la página principal», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «activar o desactivar slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Reordenar sliders

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir reordenar sliders. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Reordenar sliders». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona la opción para reordenar sliders. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El orden debe quedar persistido sin duplicados ni huecos. 5. Ejecuta la función y actualiza lista de sliders, posiciones nuevas, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El orden debe quedar persistido sin duplicados ni huecos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reordenar sliders» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de sliders — Eliminar slider

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar sliders. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar slider». |
| Flujo principal | 1. El administrador abre la sección «Administración de sliders». 2. Selecciona eliminar slider. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación y retira la configuración del carrusel. 5. Aplica la acción y actualiza slider, imagen, orden, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación y retira la configuración del carrusel», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar slider» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Listar anuncios

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir listar anuncios comerciales. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Listar anuncios». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Solicita listar anuncios. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta anuncio, tipo, título, mensaje, icono, imagen, prioridad, estado, fechas y acciones. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La grilla muestra tipo, prioridad, fechas, estado y acciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar anuncios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Crear anuncio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir crear anuncios de barra superior o banner promocional. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Crear anuncio». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Selecciona la opción para crear anuncio. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se permiten dos anuncios activos simultáneamente y las fechas deben ser coherentes. 5. Ejecuta la función y actualiza tipo, título, mensaje, icono, subtítulo, botón, enlace, imagen, prioridad, fechas, estado y usuario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se permiten dos anuncios activos simultáneamente y las fechas deben ser coherentes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear anuncio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Editar anuncio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar anuncios comerciales. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar anuncio». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Selecciona la opción para editar anuncio. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La imagen debe cumplir formato y tamaño permitido cuando se cargue. 5. Ejecuta la función y actualiza anuncio, datos actualizados, archivo, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La imagen debe cumplir formato y tamaño permitido cuando se cargue», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «editar anuncio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Eliminar anuncio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir eliminar anuncios comerciales. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Eliminar anuncio». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Selecciona eliminar anuncio. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación requiere confirmación administrativa. 5. Aplica la acción y actualiza anuncio, tipo, título, usuario ejecutor y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación requiere confirmación administrativa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar anuncio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Mostrar barra superior

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar el anuncio activo de tipo barra superior. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Mostrar barra superior». |
| Flujo principal | 1. El visitante abre la sección «Anuncios». 2. Solicita mostrar barra superior. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta título, mensaje, icono, color, enlace, prioridad, fechas y estado. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se muestra el anuncio vigente de mayor prioridad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar barra superior» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Mostrar banner promocional

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar el banner promocional activo en la página principal. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Mostrar banner promocional». |
| Flujo principal | 1. El visitante abre la sección «Anuncios». 2. Solicita mostrar banner promocional. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta imagen, subtítulo, texto, botón, enlace, prioridad, estado y fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El banner se oculta automáticamente fuera de vigencia o si está inactivo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar banner promocional» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Ver detalle de anuncio

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle y previsualización de un anuncio comercial. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de anuncio». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Solicita ver detalle de anuncio. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta anuncio, tipo, imagen, botón, enlace, colores, estado y fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La previsualización debe respetar tipo, imagen, color, botón y vigencia configurada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de anuncio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Exportar anuncios

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar anuncios comerciales a PDF o Excel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar anuncios». |
| Flujo principal | 1. El administrador abre la sección «Anuncios». 2. Selecciona la opción para exportar anuncios. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta filtros activos de la vista. 5. Ejecuta la función y actualiza anuncios, filtros, formato, administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta filtros activos de la vista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar anuncios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Anuncios — Consultar anuncios de inicio

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir consultar anuncios vigentes para la página principal. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Consultar anuncios de inicio». |
| Flujo principal | 1. El visitante abre la sección «Anuncios». 2. Solicita consultar anuncios de inicio. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta anuncios activos, tipo, prioridad, título, mensaje, imagen, botón, enlace y vigencia. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo se devuelven anuncios activos y dentro de su ventana de fechas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar anuncios de inicio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración administrativa — Ver configuración general

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir ver configuración general del sitio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver configuración general». |
| Flujo principal | 1. El administrador abre la sección «Configuración administrativa». 2. Solicita ver configuración general. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta nombre del sitio, logos, contacto, redes, textos, estado y fechas. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La configuración alimenta textos, logos y datos compartidos del storefront», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver configuración general» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración administrativa — Actualizar configuración general

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir actualizar configuración general del sitio. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Actualizar configuración general». |
| Flujo principal | 1. El administrador abre la sección «Configuración administrativa». 2. Selecciona la opción para actualizar configuración general. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los cambios se reflejan en encabezado, pie, tienda y exportaciones que usan la configuración compartida. 5. Ejecuta la función y actualiza valores actualizados, archivos subidos, usuario editor y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los cambios se reflejan en encabezado, pie, tienda y exportaciones que usan la configuración compartida», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar configuración general» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración administrativa — Cambiar sección

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cambiar entre secciones de configuración general. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cambiar sección». |
| Flujo principal | 1. El administrador abre la sección «Configuración administrativa». 2. Selecciona la opción para cambiar sección. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El cambio conserva los valores capturados antes de guardar. 5. Ejecuta la función y actualiza sección activa, campos visibles, errores por sección y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El cambio conserva los valores capturados antes de guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar sección» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración administrativa — Seleccionar colores

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir seleccionar colores corporativos mediante selector visual. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Seleccionar colores». |
| Flujo principal | 1. El administrador abre la sección «Configuración administrativa». 2. Selecciona la opción para seleccionar colores. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El color capturado debe ser un valor hexadecimal válido. 5. Ejecuta la función y actualiza campo de color, valor anterior, valor nuevo, sección y administrador. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El color capturado debe ser un valor hexadecimal válido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar colores» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración administrativa — Gestionar imágenes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir subir, cambiar o quitar imágenes de configuración como logos y recursos visuales. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Gestionar imágenes». |
| Flujo principal | 1. El administrador abre la sección «Configuración administrativa». 2. Selecciona la opción para gestionar imágenes. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las imágenes se previsualizan y se reemplazan de forma controlada al guardar. 5. Ejecuta la función y actualiza campo de imagen, archivo, pantalla anterior, pantalla nueva, estado de eliminación y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las imágenes se previsualizan y se reemplazan de forma controlada al guardar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «gestionar imágenes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Configuración pública — Consultar ajustes del sitio

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir consultar ajustes públicos del sitio para encabezado, pie, logos y colores. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Consultar ajustes del sitio». |
| Flujo principal | 1. El visitante abre la sección «Configuración pública». 2. Solicita consultar ajustes del sitio. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta nombre del sitio, logos, colores, contacto, redes, textos públicos y fecha de actualización. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La tienda debe usar la configuración vigente sin exponer datos del sistema de administración», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar ajustes del sitio» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Listar notificaciones

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al usuario ver su bandeja de notificaciones. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Listar notificaciones». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Solicita listar notificaciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta notificación, usuario, título, mensaje, tipo, entidad relacionada, leído, fecha y enlace. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La bandeja muestra notificaciones propias con filtros por estado y tipo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «listar notificaciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Marcar una como leída

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir marcar una notificación como leída. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Marcar una como leída». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Selecciona la opción para marcar una como leída. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción solo aplica a notificaciones del usuario propietario. 5. Ejecuta la función y actualiza notificación, usuario, fecha de lectura y contador actualizado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción solo aplica a notificaciones del usuario propietario», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar una como leída» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Marcar todas como leídas

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir marcar todas las notificaciones como leídas. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Marcar todas como leídas». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Selecciona la opción para marcar todas como leídas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Se actualiza el contador sin recargar toda la página. 5. Ejecuta la función y actualiza usuario, total afectado, fecha de lectura y contador final. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Se actualiza el contador sin recargar toda la página», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar todas como leídas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Eliminar notificación

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir eliminar una notificación individual. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Eliminar notificación». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Selecciona eliminar notificación. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La eliminación valida propiedad de la notificación. 5. Aplica la acción y actualiza notificación, usuario, fecha de eliminación y contador actualizado. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La eliminación valida propiedad de la notificación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «eliminar notificación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Abrir acción relacionada

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir abrir el pedido o entidad relacionada desde una notificación. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Abrir acción relacionada». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Solicita abrir acción relacionada. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta tipo, entidad relacionada, pantalla destino, usuario y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La pantalla de destino depende del tipo y entidad de la notificación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir acción relacionada» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Actualizar contador

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Actualizar periódicamente el contador de notificaciones sin leer. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Actualizar contador». |
| Flujo principal | 1. El cliente abre la sección «Notificaciones». 2. Selecciona la opción para actualizar contador. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El contador solo aparece para usuarios autenticados. 5. Ejecuta la función y actualiza usuario, cantidad sin leer, fecha de cálculo y estado de consulta. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El contador solo aparece para usuarios autenticados», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar contador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Crear notificación

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir crear notificaciones del sistema desde procesos del sistema. |
| Precondiciones | Ocurre el evento que activa la función «Crear notificación» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Notificaciones». 2. El sistema reúne la información necesaria para crear notificación. 3. Valida la regla de negocio: La notificación debe pertenecer a un usuario y tener tipo válido. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando usuario, título, mensaje, tipo, entidad relacionada, estado leído y fecha. |
| Alternativas | Si no se cumple la regla «La notificación debe pertenecer a un usuario y tener tipo válido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «crear notificación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Ejecutar disparador

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir disparar notificaciones por eventos operativos. |
| Precondiciones | Ocurre el evento que activa la función «Ejecutar disparador» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Notificaciones». 2. El sistema reúne la información necesaria para ejecutar disparador. 3. Valida la regla de negocio: Cada disparador debe validar tipo de evento, destinatario y datos requeridos. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando evento, usuario, entidad, mensaje, canal, estado y fecha. |
| Alternativas | Si no se cumple la regla «Cada disparador debe validar tipo de evento, destinatario y datos requeridos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ejecutar disparador» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Encolar notificación

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Crear registros en cola de envío para notificaciones del sistema o correos. |
| Precondiciones | Ocurre el evento que activa la función «Encolar notificación» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Notificaciones». 2. El sistema reúne la información necesaria para encolar notificación. 3. Valida la regla de negocio: Cada notificación encolada inicia con estado pendiente y número de intentos en cero. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando notificación, canal, estado, intentos, fecha programada y usuario destino. |
| Alternativas | Si no se cumple la regla «Cada notificación encolada inicia con estado pendiente y número de intentos en cero», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «encolar notificación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Respetar preferencias

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Respetar preferencias de notificación antes de enviar alertas o correos. |
| Precondiciones | Ocurre el evento que activa la función «Respetar preferencias» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Notificaciones». 2. El sistema reúne la información necesaria para respetar preferencias. 3. Valida la regla de negocio: Si el usuario desactiva un evento o canal, el despacho se omite con razón registrada. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando usuario, evento, tipo, canal, preferencia, resultado y razón de omisión. |
| Alternativas | Si no se cumple la regla «Si el usuario desactiva un evento o canal, el despacho se omite con razón registrada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «respetar preferencias» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Notificaciones — Enviar correo de evento

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Enviar correo para eventos de producto, promoción, carrito, pedidos o reembolsos cuando el canal esté permitido. |
| Precondiciones | Ocurre el evento que activa la función «Enviar correo de evento» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Notificaciones». 2. El sistema reúne la información necesaria para enviar correo de evento. 3. Valida la regla de negocio: El correo usa enlaces distintos según el evento: tienda, ofertas, carrito o bandeja de notificaciones, y los cambios de reembolso usan la plantilla global de notificación al cliente. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando usuario, correo, evento, título, mensaje, enlace, estado de envío y fecha. |
| Alternativas | Si no se cumple la regla «El correo usa enlaces distintos según el evento: tienda, ofertas, carrito o bandeja de notificaciones, y los cambios de reembolso usan la plantilla global de notificación al cliente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar correo de evento» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de notificaciones — Ocultar alertas administrativas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir registrar alertas administrativas descartadas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ocultar alertas administrativas». |
| Flujo principal | 1. El administrador abre la sección «Administración de notificaciones». 2. Selecciona la opción para ocultar alertas administrativas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Cada administrador conserva sus propios descartes. 5. Ejecuta la función y actualiza administrador, alerta, entidad, fecha de descarte y estado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Cada administrador conserva sus propios descartes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ocultar alertas administrativas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Postventa — Enviar factura al entregar

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Generar y enviar factura cuando una orden pasa a entregada. |
| Precondiciones | Ocurre el evento que activa la función «Enviar factura al entregar» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Postventa». 2. El sistema reúne la información necesaria para enviar factura al entregar. 3. Valida la regla de negocio: Si el correo falla, debe conservarse el registro del sistema de notificación o log operativo. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, cliente, correo, PDF, productos, totales, estado de envío y notificación. |
| Alternativas | Si no se cumple la regla «Si el correo falla, debe conservarse el registro del sistema de notificación o log operativo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «enviar factura al entregar» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Postventa — Notificar cancelación

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Notificar al cliente cuando una orden se cancela. |
| Precondiciones | Ocurre el evento que activa la función «Notificar cancelación» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Postventa». 2. El sistema reúne la información necesaria para notificar cancelación. 3. Valida la regla de negocio: La cancelación debe actualizar estado, historial y notificaciones. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, motivo, estado anterior, estado cancelado, cliente, correo, notificación y fecha. |
| Alternativas | Si no se cumple la regla «La cancelación debe actualizar estado, historial y notificaciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «notificar cancelación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Postventa — Registrar reembolso

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Registrar movimientos negativos cuando existe reembolso. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Registrar reembolso». |
| Flujo principal | 1. El cliente abre la sección «Postventa». 2. Selecciona la opción para registrar reembolso. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El reembolso debe quedar asociado a la orden y al pago original cuando exista. 5. Ejecuta la función y actualiza orden, pago, monto negativo, referencia, administrador, cliente, estado y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El reembolso debe quedar asociado a la orden y al pago original cuando exista», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar reembolso» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Postventa — Solicitar reembolso cliente

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al cliente solicitar reembolso desde Mis pedidos cuando la orden esté completada y el producto tenga política activa. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Solicitar reembolso cliente». |
| Flujo principal | 1. El cliente abre la sección «Postventa». 2. Selecciona la opción para solicitar reembolso cliente. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El cliente no puede cancelar la compra desde Mis pedidos; solo puede solicitar reembolso dentro de los días configurados por el administrador. 5. Ejecuta la función y actualiza orden, cliente, productos, fecha base de entrega, días válidos, motivo, evidencia, estado de solicitud y estado de pago. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El cliente no puede cancelar la compra desde Mis pedidos; solo puede solicitar reembolso dentro de los días configurados por el administrador», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «solicitar reembolso cliente» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Postventa — Administrar reembolsos

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al administrador listar solicitudes de reembolso, revisar motivo y evidencia, aceptar, rechazar o completar el reembolso desde una vista que inicialice correctamente su estado reactivo. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Administrar reembolsos». |
| Flujo principal | 1. El cliente abre la sección «Postventa». 2. Selecciona la opción para administrar reembolsos. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Al aceptar una solicitud, el reembolso debe pasar inmediatamente a en proceso y el estado de pago debe quedar sincronizado como reembolso en proceso; cada acción debe bloquear doble envío, cerrar el modal al finalizar, registrar historial, notificar al cliente con correo, publicar WebSocket y reflejar pendientes en notificaciones/badge de Reembolsos sin mostrar estados, motivos ni valores técnicos provenientes de base de datos. 5. Ejecuta la función y actualiza solicitud, orden, cliente, motivo, evidencia, estado anterior, estado nuevo, estado de pago, nota operativa, administrador, notificación, correo, evento WebSocket y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Al aceptar una solicitud, el reembolso debe pasar inmediatamente a en proceso y el estado de pago debe quedar sincronizado como reembolso en proceso; cada acción debe bloquear doble envío, cerrar el modal al finalizar, registrar historial, notificar al cliente con correo, publicar WebSocket y reflejar pendientes en notificaciones/badge de Reembolsos sin mostrar estados, motivos ni valores técnicos provenientes de base de datos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «administrar reembolsos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Catálogo administrativo — Configurar reembolso de producto

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir al administrador activar opcionalmente una política de reembolso al crear o editar productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Configurar reembolso de producto». |
| Flujo principal | 1. El administrador abre la sección «Catálogo administrativo». 2. Selecciona la opción para configurar reembolso de producto. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Si el administrador activa el reembolso, debe indicar obligatoriamente el número de días válidos antes de guardar el producto. 5. Ejecuta la función y actualiza producto, marca de reembolso, días válidos, administrador, fecha de creación o actualización y estado del producto. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Si el administrador activa el reembolso, debe indicar obligatoriamente el número de días válidos antes de guardar el producto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «configurar reembolso de producto» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Panel — Abrir menú lateral

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el menú lateral administrativo en pantallas pequeñas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir menú lateral». |
| Flujo principal | 1. El administrador abre la sección «Panel». 2. Solicita abrir menú lateral. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado del menú, tamaño de pantalla, pantalla actual y usuario administrador. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El panel lateral se adapta al tamaño de pantalla sin recargar la página», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir menú lateral» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Panel — Cerrar menú lateral

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cerrar el menú lateral administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cerrar menú lateral». |
| Flujo principal | 1. El administrador abre la sección «Panel». 2. Selecciona cerrar menú lateral. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: El menú se cierra al navegar o al usar el botón de cierre. 5. Aplica la acción y actualiza estado anterior, estado cerrado, pantalla actual y acción usada. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «El menú se cierra al navegar o al usar el botón de cierre», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cerrar menú lateral» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Panel — Expandir submenús

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir expandir y contraer submenús del panel administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Expandir submenús». |
| Flujo principal | 1. El administrador abre la sección «Panel». 2. Selecciona la opción para expandir submenús. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El submenú relacionado con la pantalla activa se abre automáticamente. 5. Ejecuta la función y actualiza submenú, pantalla actual, estado abierto o cerrado y usuario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El submenú relacionado con la pantalla activa se abre automáticamente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «expandir submenús» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Panel — Cerrar sesión admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cerrar sesión desde el menú administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cerrar sesión admin». |
| Flujo principal | 1. El administrador abre la sección «Panel». 2. Selecciona cerrar sesión admin. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al cerrar sesión se revoca la sesión y se redirige al inicio de sesión. 5. Aplica la acción y actualiza administrador, código de acceso, fecha de cierre y pantalla destino. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al cerrar sesión se revoca la sesión y se redirige al inicio de sesión», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cerrar sesión admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Encabezado — Abrir notificaciones

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir y cerrar el panel de notificaciones administrativas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir notificaciones». |
| Flujo principal | 1. El administrador abre la sección «Encabezado». 2. Solicita abrir notificaciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta administrador, notificaciones, contador, estado del panel y fecha de consulta. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El panel muestra alertas recientes y mantiene el contador actualizado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir notificaciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Encabezado — Actualizar notificaciones

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir actualizar manualmente las notificaciones administrativas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Actualizar notificaciones». |
| Flujo principal | 1. El administrador abre la sección «Encabezado». 2. Selecciona la opción para actualizar notificaciones. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La acción consulta nuevamente las fuentes configuradas sin recargar la página. 5. Ejecuta la función y actualiza administrador, notificaciones actualizadas, hora de consulta y resultado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La acción consulta nuevamente las fuentes configuradas sin recargar la página», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar notificaciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Encabezado — Marcar notificaciones admin como leídas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir marcar todas las notificaciones administrativas como leídas. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Marcar notificaciones admin como leídas». |
| Flujo principal | 1. El administrador abre la sección «Encabezado». 2. Selecciona la opción para marcar notificaciones admin como leídas. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El contador se actualiza después de aplicar la acción. 5. Ejecuta la función y actualiza administrador, total marcado, contador final y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El contador se actualiza después de aplicar la acción», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «marcar notificaciones admin como leídas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Encabezado — Abrir notificación admin

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir la pantalla relacionada de una notificación administrativa. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir notificación admin». |
| Flujo principal | 1. El administrador abre la sección «Encabezado». 2. Solicita abrir notificación admin. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta notificación, entidad, pantalla destino, administrador y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La pantalla depende del tipo de alerta y la entidad relacionada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir notificación admin» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Encabezado — Cerrar búsqueda global

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cerrar el panel de búsqueda global. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cerrar búsqueda global». |
| Flujo principal | 1. El administrador abre la sección «Encabezado». 2. Selecciona cerrar búsqueda global. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: Al cerrar se limpia la vista de resultados sin perder la sesión. 5. Aplica la acción y actualiza término de búsqueda, resultados visibles, estado cerrado y administrador. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «Al cerrar se limpia la vista de resultados sin perder la sesión», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cerrar búsqueda global» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Ver métricas principales

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar métricas principales del negocio en el dashboard. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver métricas principales». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita ver métricas principales. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta órdenes del día, ingresos, clientes nuevos, pagos pendientes, stock bajo y comparativos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las métricas se calculan con datos recientes de órdenes, clientes, inventario y pagos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver métricas principales» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Ver alertas de inventario

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar alertas de productos con stock bajo o agotado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver alertas de inventario». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita ver alertas de inventario. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, color, talla, stock, umbral, severidad y pantalla de acción. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las alertas críticas deben llevar al inventario o producto afectado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver alertas de inventario» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Ver órdenes recientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar accesos a órdenes recientes desde el dashboard. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver órdenes recientes». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita ver órdenes recientes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, cliente, estado, total, fecha y pantalla de detalle. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada acceso lleva al detalle de la orden seleccionada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver órdenes recientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Ver gráficos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Mostrar gráficos de ventas, productos y tendencias. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver gráficos». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita ver gráficos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta fechas, ventas, ingresos, productos, categorías, clientes y período. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los gráficos deben responder al período seleccionado cuando exista filtro», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver gráficos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Abrir acción rápida

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir acciones rápidas desde el encabezado administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir acción rápida». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita abrir acción rápida. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta acción, etiqueta, descripción, icono, pantalla y usuario. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las acciones rápidas deben llevar a pantallas administrativas válidas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir acción rápida» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Dashboard — Buscar globalmente

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir buscar órdenes, productos, clientes y atajos desde el encabezado administrativo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Buscar globalmente». |
| Flujo principal | 1. El administrador abre la sección «Dashboard». 2. Solicita buscar globalmente. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta término, resultados por grupo, identificador, título, detalle, pantalla y categoría. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La búsqueda requiere un término mínimo y limita resultados por grupo», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «buscar globalmente» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Ver informe de ventas

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Generar informe de ventas por período. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver informe de ventas». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Solicita ver informe de ventas. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta ingresos, cantidad de órdenes, ticket promedio, métodos de pago, descuentos, fechas, agrupación y comparación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Las órdenes canceladas se excluyen por defecto del cálculo de ventas y las gráficas deben partir de cero para evitar lecturas comprimidas cuando existe un único período», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver informe de ventas» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Ver informe de productos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Generar informe de productos populares y stock. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver informe de productos». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Solicita ver informe de productos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta productos más vendidos, cantidad, ingresos, stock crítico, inventario y período. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El ranking usa ingresos y cantidad vendida en el período», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver informe de productos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Ver informe de clientes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Generar informe de clientes recurrentes y mostrar el top de clientes por valor acumulado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver informe de clientes». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Solicita ver informe de clientes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta cliente, correo, órdenes, total gastado, última compra, promedio, distribución, top por valor y período. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El mínimo de órdenes se aplica sobre la tabla de recurrencia, mientras que la gráfica de valor debe usar clientes con compras reales aunque no alcancen el mínimo recurrente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver informe de clientes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Exportar informe

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir exportar informes administrativos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Exportar informe». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Selecciona la opción para exportar informe. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La exportación respeta el tipo de informe y filtros activos. 5. Ejecuta la función y actualiza tipo de informe, filtros, formato, datos exportados, usuario y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La exportación respeta el tipo de informe y filtros activos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «exportar informe» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Cambiar tipo de informe

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir cambiar entre informes de ventas, productos populares y clientes recurrentes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Cambiar tipo de informe». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Selecciona la opción para cambiar tipo de informe. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El cambio actualiza la pantalla administrativa y carga los datos del informe seleccionado. 5. Ejecuta la función y actualiza tipo de informe, pantalla, filtros activos, usuario administrador y fecha. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El cambio actualiza la pantalla administrativa y carga los datos del informe seleccionado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «cambiar tipo de informe» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Aplicar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aplicar filtros del informe activo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Aplicar filtros». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Selecciona la opción para aplicar filtros. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las fechas deben ser válidas y la fecha inicial no puede superar la fecha final. 5. Ejecuta la función y actualiza tipo de informe, fecha inicial, fecha final, estado, agrupación, límite, mínimo de órdenes y búsqueda. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las fechas deben ser válidas y la fecha inicial no puede superar la fecha final», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aplicar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Limpiar filtros

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir limpiar filtros del informe activo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Limpiar filtros». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Selecciona limpiar filtros. 3. El sistema muestra la información afectada y solicita confirmación cuando corresponde. 4. Valida la regla de negocio: La limpieza restaura valores predeterminados según el tipo de informe. 5. Aplica la acción y actualiza tipo de informe, filtros anteriores, filtros predeterminados y fecha. 6. Informa el resultado. |
| Alternativas | Si no se cumple la regla «La limpieza restaura valores predeterminados según el tipo de informe», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «limpiar filtros» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Ver detalle de fila

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir abrir el detalle de una fila de informe. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Ver detalle de fila». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Solicita ver detalle de fila. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta tipo de informe, fila seleccionada, datos de detalle, estado del ventana y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El detalle se muestra en ventana y usa el contexto de ventas, productos o clientes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «ver detalle de fila» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Informes — Imprimir informe

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir imprimir el informe activo. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Imprimir informe». |
| Flujo principal | 1. El administrador abre la sección «Informes». 2. Selecciona la opción para imprimir informe. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo se imprime si existen datos visibles para el informe seleccionado. 5. Ejecuta la función y actualiza tipo de informe, filas visibles, filtros, usuario administrador y fecha de impresión. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo se imprime si existen datos visibles para el informe seleccionado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «imprimir informe» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Auditoría — Consultar auditoría de órdenes

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir consultar auditoría de órdenes. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Consultar auditoría de órdenes». |
| Flujo principal | 1. El administrador abre la sección «Auditoría». 2. Solicita consultar auditoría de órdenes. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, acción, usuario, detalles, fecha y valores asociados. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Solo personal autorizado debe acceder a registros de auditoría», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar auditoría de órdenes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Auditoría — Consultar auditoría de usuarios

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir consultar auditoría de usuarios. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Consultar auditoría de usuarios». |
| Flujo principal | 1. El administrador abre la sección «Auditoría». 2. Solicita consultar auditoría de usuarios. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta usuario afectado, acción, usuario modificador, detalles y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los registros de auditoría se conservan para trazabilidad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar auditoría de usuarios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Auditoría — Consultar auditoría de productos

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir consultar auditoría de productos. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Consultar auditoría de productos». |
| Flujo principal | 1. El administrador abre la sección «Auditoría». 2. Solicita consultar auditoría de productos. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta producto, acción, valores, responsable y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Los cambios y eliminaciones de productos deben poder revisarse», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar auditoría de productos» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Disponibilidad — Consultar disponibilidad

| Campo | Descripción |
|---|---|
| Actor principal | Personal de soporte |
| Objetivo | Permitir consultar si las áreas principales están disponibles para operar. |
| Precondiciones | La plataforma está desplegada y el personal de soporte tiene acceso a la comprobación «Consultar disponibilidad». |
| Flujo principal | 1. El personal de soporte abre la sección «Disponibilidad». 2. Solicita consultar disponibilidad. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta área, estado, fecha de consulta y respuesta de disponibilidad. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «Cada área principal debe indicar si está disponible para atender sus funciones», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar disponibilidad» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Sincronización — Consultar perfiles de usuarios

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir consultar perfiles de usuarios para completar pedidos, reportes y atención al cliente. |
| Precondiciones | Ocurre el evento que activa la función «Consultar perfiles de usuarios» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Sincronización». 2. El sistema reúne la información necesaria para consultar perfiles de usuarios. 3. Valida la regla de negocio: La consulta devuelve solo los datos necesarios para completar la operación solicitada. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando identificadores, nombre, correo, teléfono, avatar, rol y estado. |
| Alternativas | Si no se cumple la regla «La consulta devuelve solo los datos necesarios para completar la operación solicitada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar perfiles de usuarios» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Sincronización — Consultar producto compartido

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir consultar información de un producto para usarla en pedidos, carrito, pagos o reportes. |
| Precondiciones | Ocurre el evento que activa la función «Consultar producto compartido» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Sincronización». 2. El sistema reúne la información necesaria para consultar producto compartido. 3. Valida la regla de negocio: Si el producto no existe, se responde con un estado controlado. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando producto, nombre, identificador público, precio, imagen, categoría, estado y stock. |
| Alternativas | Si no se cumple la regla «Si el producto no existe, se responde con un estado controlado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar producto compartido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Sincronización — Consultar disponibilidad de variante

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Permitir consultar una variante para validar disponibilidad antes de vender. |
| Precondiciones | Ocurre el evento que activa la función «Consultar disponibilidad de variante» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Sincronización». 2. El sistema reúne la información necesaria para consultar disponibilidad de variante. 3. Valida la regla de negocio: La variante debe mostrar cantidad disponible antes de confirmar una compra. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando variante, producto, color, talla, stock, SKU y estado. |
| Alternativas | Si no se cumple la regla «La variante debe mostrar cantidad disponible antes de confirmar una compra», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar disponibilidad de variante» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Sincronización — Confirmar inventario vendido

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Confirmar el inventario vendido cuando una orden queda creada. |
| Precondiciones | Ocurre el evento que activa la función «Confirmar inventario vendido» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Sincronización». 2. El sistema reúne la información necesaria para confirmar inventario vendido. 3. Valida la regla de negocio: La confirmación debe respetar la disponibilidad registrada y dejar el resultado trazable en la orden. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, variantes, cantidades, resultado y fecha. |
| Alternativas | Si no se cumple la regla «La confirmación debe respetar la disponibilidad registrada y dejar el resultado trazable en la orden», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «confirmar inventario vendido» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Pagos Admin — Mostrar comprobante de pago

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al administrador abrir y previsualizar comprobantes de pago desde el panel de pagos o el detalle de la orden. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Mostrar comprobante de pago». |
| Flujo principal | 1. El cliente abre la sección «Pagos Admin». 2. Solicita mostrar comprobante de pago. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta pago, orden, ruta del comprobante, URL pública, nombre de archivo, estado de existencia, referencia y estado de pago. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La URL del comprobante debe resolverse contra el origen público del servicio de pagos y, si el archivo no existe, debe mostrarse un estado controlado sin exponer rutas internas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «mostrar comprobante de pago» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Mis pedidos — Actualizar progreso en tiempo real

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Actualizar el badge y el componente de progreso del pedido cuando reciba eventos websocket de cambio de estado o pago. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Actualizar progreso en tiempo real». |
| Flujo principal | 1. El cliente abre la sección «Mis pedidos». 2. Selecciona la opción para actualizar progreso en tiempo real. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La vista debe aplicar el cambio visible de inmediato, tolerar variantes del payload realtime y sincronizar en segundo plano los datos derivados del pedido. 5. Ejecuta la función y actualiza pedido, estado anterior, estado nuevo, estado de pago, evento websocket, fecha de publicación y usuario propietario. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La vista debe aplicar el cambio visible de inmediato, tolerar variantes del payload realtime y sincronizar en segundo plano los datos derivados del pedido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «actualizar progreso en tiempo real» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Verificar correo en registro

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Enviar y validar un código de cuatro dígitos antes de permitir continuar al paso de teléfono del registro. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Verificar correo en registro». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Solicita verificar correo en registro. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta correo electrónico, código generado, fecha de expiración, enfriamiento de reenvío, token temporal y resultado de validación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La cuenta no puede crearse si el correo no fue verificado o si el token temporal no corresponde al correo enviado», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar correo en registro» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Recuperación de cuenta — Reutilizar componente de código

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Usar el mismo componente visual para ingresar y reenviar códigos en registro y recuperación de contraseña. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Reutilizar componente de código». |
| Flujo principal | 1. El visitante abre la sección «Recuperación de cuenta». 2. Selecciona la opción para reutilizar componente de código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los estados de pendiente, validado, expirado y reenvío deben mantenerse consistentes en ambos flujos. 5. Ejecuta la función y actualiza código ingresado, estado visual, tiempo restante, enfriamiento, error mostrado y acción de reenvío. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los estados de pendiente, validado, expirado y reenvío deben mantenerse consistentes en ambos flujos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «reutilizar componente de código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Acceso móvil — Verificar correo

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Enviar y validar un código de seis dígitos antes de decidir el siguiente paso de acceso. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Verificar correo». |
| Flujo principal | 1. El repartidor abre la sección «Acceso móvil». 2. Solicita verificar correo. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta correo normalizado, código cifrado, expiración, intentos, token temporal y siguiente paso. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La existencia de la cuenta y su rol solo se revelan después de verificar el correo; el código es temporal, limitado por intentos y no reutilizable», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «verificar correo» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Acceso móvil — Restringir por rol

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir iniciar sesión únicamente a cuentas con rol de repartidor. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Restringir por rol». |
| Flujo principal | 1. El repartidor abre la sección «Acceso móvil». 2. Selecciona la opción para restringir por rol. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Una cuenta cliente o administrativa verificada debe remitirse a `angelow.online` y no recibe sesión móvil. 5. Ejecuta la función y actualiza usuario, correo verificado, rol, estado de cuenta, motivo de bloqueo y destino alternativo. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Una cuenta cliente o administrativa verificada debe remitirse a `angelow.online` y no recibe sesión móvil», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «restringir por rol» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Vinculación — Registrar identidad de repartidor

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir crear la identidad de un nuevo repartidor después de verificar su correo. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Registrar identidad de repartidor». |
| Flujo principal | 1. El repartidor abre la sección «Vinculación». 2. Selecciona la opción para registrar identidad de repartidor. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El correo debe ser único, la persona debe ser mayor de edad y debe aceptar términos y tratamiento de datos. 5. Ejecuta la función y actualiza nombre, correo, teléfono, contraseña cifrada, documento, nacimiento, ciudad, dirección y consentimientos versionados. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El correo debe ser único, la persona debe ser mayor de edad y debe aceptar términos y tratamiento de datos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar identidad de repartidor» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Vinculación — Seleccionar medio de transporte

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Indicar si entrega a pie, en bicicleta, moto, auto o camión. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Seleccionar medio de transporte». |
| Flujo principal | 1. El repartidor abre la sección «Vinculación». 2. Selecciona la opción para seleccionar medio de transporte. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Los datos y documentos del vehículo se exigen solo para categorías aplicables. 5. Ejecuta la función y actualiza tipo, marca, modelo, color, año, placa y modalidad de tenencia. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Los datos y documentos del vehículo se exigen solo para categorías aplicables», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «seleccionar medio de transporte» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Vinculación — Adjuntar soportes

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir adjuntar los documentos necesarios para revisar la solicitud. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Adjuntar soportes». |
| Flujo principal | 1. El repartidor abre la sección «Vinculación». 2. Selecciona la opción para adjuntar soportes. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Se aceptan JPG, PNG o PDF de hasta 8 MB; identidad, foto y seguridad social son base, y licencia, tránsito, SOAT y revisión se condicionan al vehículo y vigencia legal. 5. Ejecuta la función y actualiza tipo documental, ruta controlada, extensión, vencimiento, estado de revisión y observación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Se aceptan JPG, PNG o PDF de hasta 8 MB; identidad, foto y seguridad social son base, y licencia, tránsito, SOAT y revisión se condicionan al vehículo y vigencia legal», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «adjuntar soportes» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Vinculación — Revisar solicitud

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir aprobar, rechazar, editar el estado documental y desactivar repartidores. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Revisar solicitud». |
| Flujo principal | 1. El administrador abre la sección «Vinculación». 2. Selecciona la opción para revisar solicitud. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo administradores autenticados pueden revisar; cada decisión notifica al solicitante y conserva motivo cuando se rechaza. 5. Ejecuta la función y actualiza repartidor, documentos, vehículo, estado, motivo, revisor y fechas de revisión. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo administradores autenticados pueden revisar; cada decisión notifica al solicitante y conserva motivo cuando se rechaza», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «revisar solicitud» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración de perfiles de repartidor — Editar repartidor

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir editar desde el panel la identidad, contacto, rol, estado de cuenta y disponibilidad operativa de un repartidor. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Editar repartidor». |
| Flujo principal | 1. El administrador abre la sección «Administración de perfiles de repartidor». 2. Selecciona un repartidor y la opción para editarlo. 3. Actualiza la identidad, los datos de contacto, el rol, el estado de cuenta o la disponibilidad operativa. 4. El sistema valida la regla de negocio: Solo administradores autenticados pueden editar. El correo e identidad se conservan en autenticación y el correo, teléfono y dirección se sincronizan con el perfil de envíos; al asignar otro rol se desactiva la disponibilidad para entregas. 5. Guarda los cambios y actualiza identificador de usuario, perfil de repartidor, nombre, correo, teléfono, dirección, rol, estado de cuenta, disponibilidad, administrador y fecha de edición. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si faltan datos obligatorios, el correo no es válido o la sincronización del perfil no se completa, el sistema no guarda cambios parciales e informa cómo corregirlos. Si se asigna un rol diferente de repartidor, desactiva la disponibilidad operativa para entregas. |
| Resultado esperado | El sistema completa «editar repartidor», mantiene sincronizados los datos permitidos y deja el resultado visible y disponible para continuar el proceso. |

### Ofertas — Publicar entrega elegible

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Publicar una entrega cuando el pago de la orden sea aprobado y el método no sea punto de recogida. |
| Precondiciones | Ocurre el evento que activa la función «Publicar entrega elegible» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Ofertas». 2. El sistema reúne la información necesaria para publicar entrega elegible. 3. Valida la regla de negocio: La publicación es idempotente y conserva el método y tiempo estimado escogidos por el cliente. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando orden, pago, cliente, dirección, coordenadas, método, tiempo estimado y estado de oferta. |
| Alternativas | Si no se cumple la regla «La publicación es idempotente y conserva el método y tiempo estimado escogidos por el cliente», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «publicar entrega elegible» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ofertas — Aceptar entrega

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir a un repartidor aprobado aceptar una entrega disponible. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Aceptar entrega». |
| Flujo principal | 1. El repartidor abre la sección «Ofertas». 2. Selecciona la opción para aceptar entrega. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La aceptación es atómica; al primer repartidor se le asigna y deja de aparecer a los demás, la orden cambia a enviada solo si la actualización remota se confirma y los administradores reciben una notificación. 5. Ejecuta la función y actualiza asignación, repartidor, orden, estado, fecha, administradores destino y resultado de sincronización. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La aceptación es atómica; al primer repartidor se le asigna y deja de aparecer a los demás, la orden cambia a enviada solo si la actualización remota se confirma y los administradores reciben una notificación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «aceptar entrega» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Entrega — Generar código de confirmación

| Campo | Descripción |
|---|---|
| Actor principal | Sistema |
| Objetivo | Generar un código de seis dígitos al asignar un repartidor. |
| Precondiciones | Ocurre el evento que activa la función «Generar código de confirmación» y la información necesaria se encuentra disponible. |
| Flujo principal | 1. Se produce el evento relacionado con «Entrega». 2. El sistema reúne la información necesaria para generar código de confirmación. 3. Valida la regla de negocio: El código se entrega al cliente por notificación, correo y detalle de orden; se almacena cifrado y con hash y solo se revela mientras la entrega está activa. 4. Ejecuta la función solicitada. 5. Registra el resultado utilizando código, hash, cifrado, cliente, orden, fecha y canales de notificación. |
| Alternativas | Si no se cumple la regla «El código se entrega al cliente por notificación, correo y detalle de orden; se almacena cifrado y con hash y solo se revela mientras la entrega está activa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «generar código de confirmación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ruta — Iniciar navegación

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir al repartidor calcular e iniciar una ruta hacia las coordenadas de entrega. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Iniciar navegación». |
| Flujo principal | 1. El repartidor abre la sección «Ruta». 2. Selecciona la opción para iniciar navegación. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: La app solicita permiso del sistema y usa el perfil de navegación acorde con el medio de transporte; si faltan coordenadas informa la imposibilidad. 5. Ejecuta la función y actualiza origen GPS, destino, geometría, distancia, duración, instrucciones y perfil Mapbox. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «La app solicita permiso del sistema y usa el perfil de navegación acorde con el medio de transporte; si faltan coordenadas informa la imposibilidad», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «iniciar navegación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ruta — Consentir ubicación en tiempo real

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Preguntar si el repartidor autoriza compartir su ubicación con el cliente durante la ruta. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Consentir ubicación en tiempo real». |
| Flujo principal | 1. El repartidor abre la sección «Ruta». 2. Selecciona la opción para consentir ubicación en tiempo real. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Negarse no impide entregar; solo se envían y muestran coordenadas con consentimiento y se detiene al finalizar. 5. Ejecuta la función y actualiza consentimiento, latitud, longitud, precisión, rumbo, velocidad y fecha de captura. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Negarse no impide entregar; solo se envían y muestran coordenadas con consentimiento y se detiene al finalizar», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consentir ubicación en tiempo real» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Seguimiento — Consultar ubicación

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar un mapa OpenStreetMap cuando exista ubicación compartida. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Consultar ubicación». |
| Flujo principal | 1. El cliente abre la sección «Seguimiento». 2. Solicita consultar ubicación. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, usuario, estado de entrega, consentimiento, última coordenada y fecha. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El endpoint valida que el pedido pertenezca al cliente y omite la ubicación cuando no hay consentimiento o ruta activa», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar ubicación» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Entrega — Registrar llegada

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Permitir al repartidor marcar que llegó al destino después de iniciar la ruta. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Registrar llegada». |
| Flujo principal | 1. El repartidor abre la sección «Entrega». 2. Selecciona la opción para registrar llegada. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Solo el repartidor asignado y aprobado puede cambiar una entrega en ruta a llegada. 5. Ejecuta la función y actualiza asignación, repartidor, estado anterior, estado nuevo y hora de llegada. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Solo el repartidor asignado y aprobado puede cambiar una entrega en ruta a llegada», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «registrar llegada» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Entrega — Finalizar con código

| Campo | Descripción |
|---|---|
| Actor principal | Repartidor |
| Objetivo | Ingresar el código del cliente para finalizar la entrega. |
| Precondiciones | El repartidor completó la verificación requerida y se encuentra habilitado para utilizar la función «Finalizar con código». |
| Flujo principal | 1. El repartidor abre la sección «Entrega». 2. Selecciona la opción para finalizar con código. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: El código debe coincidir con el hash vigente; entonces la orden pasa a entregada, cesa el seguimiento y el secreto se elimina. 5. Ejecuta la función y actualiza código ingresado, hash, orden, asignación, hora de entrega y resultado. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «El código debe coincidir con el hash vigente; entonces la orden pasa a entregada, cesa el seguimiento y el secreto se elimina», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «finalizar con código» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Administración — Consultar envíos asignados

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Listar las entregas, sus estados, método de envío y repartidor asignado. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Consultar envíos asignados». |
| Flujo principal | 1. El administrador abre la sección «Administración». 2. Solicita consultar envíos asignados. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta orden, repartidor, método, estado, dirección y marcas de tiempo. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El listado se puede filtrar y debe reflejar aceptación, ruta, llegada y entrega sin alterar la propiedad del pedido», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar envíos asignados» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Información legal — Consultar términos y condiciones

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Permitir consultar una vista pública con los términos y condiciones del sitio. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Consultar términos y condiciones». |
| Flujo principal | 1. El visitante abre la sección «Información legal». 2. Solicita consultar términos y condiciones. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta versión del documento, fecha de actualización, secciones legales, referencias normativas y enlaces internos. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «La vista debe estar disponible sin iniciar sesión y debe presentar contenido claro sobre uso, compras, pagos, envíos, postventa y tratamiento de datos», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar términos y condiciones» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Cuenta — Consultar términos durante registro

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Mostrar un enlace navegable a términos y condiciones junto a la aceptación del registro. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Consultar términos durante registro». |
| Flujo principal | 1. El visitante abre la sección «Cuenta». 2. Solicita consultar términos durante registro. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de aceptación, enlace legal, ruta de términos, formulario de registro y usuario en creación. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El enlace debe abrir la vista legal sin marcar o desmarcar accidentalmente la casilla de aceptación», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar términos durante registro» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Checkout de pago — Consultar términos antes de pagar

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Mostrar un enlace navegable a términos y condiciones al registrar el comprobante de pago. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Consultar términos antes de pagar». |
| Flujo principal | 1. El cliente abre la sección «Checkout de pago». 2. Solicita consultar términos antes de pagar. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de aceptación, referencia de pago, comprobante, ruta de términos, pedido y formulario de pago. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El cliente debe poder revisar las condiciones antes de confirmar el pedido y la aceptación del comprobante debe seguir siendo obligatoria», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «consultar términos antes de pagar» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ayuda pública — Abrir centro de ayuda

| Campo | Descripción |
|---|---|
| Actor principal | Visitante o cliente |
| Objetivo | Permitir a visitantes y clientes abrir el manual interactivo desde el pie de página. |
| Precondiciones | La tienda está disponible y la persona puede acceder a la función «Abrir centro de ayuda» según su estado de sesión. |
| Flujo principal | 1. El visitante o cliente abre la sección «Ayuda pública». 2. Solicita abrir centro de ayuda. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta estado de sesión, audiencia, vista actual, catálogo visible, búsqueda y guía seleccionada. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El catálogo debe mostrar únicamente vistas permitidas para el estado de sesión actual y no debe revelar el dashboard a visitantes», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir centro de ayuda» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ayuda de acceso — Recorrer inicio de sesión y registro

| Campo | Descripción |
|---|---|
| Actor principal | Visitante |
| Objetivo | Explicar paso a paso las funciones de inicio de sesión, registro y recuperación de cuenta. |
| Precondiciones | La plataforma está disponible y el visitante puede acceder a la función «Recorrer inicio de sesión y registro». |
| Flujo principal | 1. El visitante abre la sección «Ayuda de acceso». 2. Selecciona la opción para recorrer inicio de sesión y registro. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Un visitante debe recibir orientación para autenticarse o registrarse antes de mostrar funciones privadas. 5. Ejecuta la función y actualiza vista de acceso, pasos visibles, campos, acciones, progreso y destino de navegación. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Un visitante debe recibir orientación para autenticarse o registrarse antes de mostrar funciones privadas», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «recorrer inicio de sesión y registro» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ayuda de cliente — Recorrer tienda, checkout y cuenta

| Campo | Descripción |
|---|---|
| Actor principal | Cliente |
| Objetivo | Permitir al cliente autenticado iniciar recorridos sobre todas las vistas públicas, de checkout y de su cuenta. |
| Precondiciones | El cliente tiene una sesión activa y existen los datos necesarios para utilizar la función «Recorrer tienda, checkout y cuenta». |
| Flujo principal | 1. El cliente abre la sección «Ayuda de cliente». 2. Selecciona la opción para recorrer tienda, checkout y cuenta. 3. Ingresa, elige o confirma la información solicitada. 4. El sistema valida la regla de negocio: Las guías se deben construir con las funciones visibles de la vista y omitir secciones que todavía no estén disponibles. 5. Ejecuta la función y actualiza ruta, título, descripción, elementos visibles, número de paso, progreso y cliente. 6. Muestra el resultado y las acciones disponibles para continuar. |
| Alternativas | Si no se cumple la regla «Las guías se deben construir con las funciones visibles de la vista y omitir secciones que todavía no estén disponibles», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «recorrer tienda, checkout y cuenta» y deja el resultado visible, actualizado y disponible para continuar el proceso. |

### Ayuda administrativa — Abrir manual completo

| Campo | Descripción |
|---|---|
| Actor principal | Administrador |
| Objetivo | Permitir al administrador abrir desde Configuración general el catálogo de recorridos del panel. |
| Precondiciones | El administrador tiene una sesión activa, dispone de los permisos necesarios y puede acceder a la función «Abrir manual completo». |
| Flujo principal | 1. El administrador abre la sección «Ayuda administrativa». 2. Solicita abrir manual completo. 3. El sistema verifica el acceso y la regla de negocio aplicable. 4. Consulta rol, ruta administrativa, grupo funcional, guía seleccionada, elementos visibles y progreso. 5. Presenta el resultado de forma clara y permite continuar con las acciones disponibles. |
| Alternativas | Si no se cumple la regla «El catálogo administrativo no debe incluir guías de cliente y las vistas con identificador solo se ofrecen dentro del registro abierto», el sistema no completa la acción y explica cómo continuar. Si falta información o algún dato no es válido, solicita corregirlo sin perder los valores aceptados. |
| Resultado esperado | El sistema completa «abrir manual completo» y deja el resultado visible, actualizado y disponible para continuar el proceso. |
