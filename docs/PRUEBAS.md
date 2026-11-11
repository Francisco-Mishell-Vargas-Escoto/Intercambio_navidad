# Plan de pruebas · Intercambio Navideño 🎄

Este documento reúne **todas** las pruebas del proyecto: las automáticas (se ejecutan con un comando) y las manuales (se hacen en el navegador). Marca cada casilla al comprobarla.

---

## 1. Preparación

1. Enciende **Apache** y **MySQL** en XAMPP.
2. En phpMyAdmin → **Importar**:
   1. `database/intercambio.sql` (crea las tablas vacías).
   2. `database/datos_prueba.sql` (carga cuentas y datos de ejemplo).
3. Todas las cuentas de prueba usan la contraseña **`Navidad2026`**:

| Correo | Situación |
|---|---|
| ana@prueba.com | Organiza **Amigos 2026** (registro abierto, 5 participantes) y participa en **Oficina 2026** |
| luis@prueba.com | En Amigos 2026 · pareja de Sofía (restricción) |
| sofia@prueba.com | En Amigos 2026 · pareja de Luis · no ha confirmado |
| carlos@prueba.com | En Amigos 2026 (perfil vacío) · organiza **Oficina 2026** (resultados ya publicados) |
| mariana@prueba.com | En ambos intercambios |
| diego@prueba.com | Solo en Oficina 2026 · sirve para probar accesos prohibidos |

- Código de Amigos 2026: **NAV-AMI-GOS**
- Invitación personal pendiente (para `nuevo@prueba.com`):
  `http://localhost/intercambio_navideno/invitacion/a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90`

> Consejo: usa una ventana normal y otra de **incógnito** para tener dos cuentas abiertas a la vez.

---

## 2. Pruebas automáticas

```
cd C:\xampp\htdocs\intercambio_navideno
chcp 65001
C:\xampp\php\php.exe tests\ejecutar_todas.php
```

| Archivo | Qué comprueba | Pruebas |
|---|---|---|
| `tests/test_sorteo.php` | Algoritmo: miles de sorteos válidos, restricciones, casos imposibles, aleatoriedad, 200 participantes | 11 |
| `tests/test_validaciones.php` | Validación de formularios y fechas, presupuesto, códigos, escape XSS, subida de imágenes | 28 |
| `tests/test_base_datos.php` | Contra tu MySQL: correos y códigos únicos, participantes sin cuenta, tokens únicos, UNIQUE de asignaciones, bloqueo del sorteo, cascadas, utf8mb4, inyección SQL. **Deshace todo al terminar.** | 15–16 |

- [ ] Resultado esperado: `¡Todas las pruebas pasaron! 🎄`

---

## 3. Pruebas funcionales (navegador)

### 3.1 Cuentas y sesión
- [ ] Registro con formulario vacío → campos en rojo, no se envía.
- [ ] Registro con contraseña `abc` → "Mínimo 8 caracteres, con letras y números".
- [ ] Registro con un correo ya usado (`ana@prueba.com`) → "Ya existe una cuenta con ese correo".
- [ ] Registro correcto → entra directo a "Mis intercambios".
- [ ] Login con contraseña incorrecta → "Correo o contraseña incorrectos" (el correo sigue escrito).
- [ ] 5 intentos fallidos → aviso de esperar 5 minutos.
- [ ] Cerrar sesión desde el menú → vuelve al inicio.

### 3.2 Participar SIN cuenta (enlace personal)
- [ ] En una ventana de incógnito (sin sesión), abre el enlace del intercambio → pide solo **nombre** (correo opcional), no pide login.
- [ ] Al unirte aparece **"Tu código y tu enlace personal"** con "¡Guárdalos!": el código (ej. `K7M2-QX9P`), botones Copiar y WhatsApp.
- [ ] En otro navegador, menú **Entrar con mi código** → escribe tu código (con o sin guion, en minúsculas) → entras como tú.
- [ ] Un código inventado → "Ese código no existe"; al 5.º intento fallido → espera de 10 minutos.
- [ ] Cierra la pestaña y vuelve a abrir el intercambio → entras directo (el navegador te recuerda).
- [ ] Abre tu enlace personal en otro navegador → entras como tú.
- [ ] Unirse con un nombre que ya existe → "Ya hay alguien con ese nombre".
- [ ] Perfil, deseos, confirmación y revelación funcionan sin cuenta.
- [ ] "No soy … · salir en este dispositivo" → el navegador te olvida (404 al volver sin enlace).
- [ ] Paola (datos de prueba) entra con su código **PAQT-2345** o con `…/p/bbbb…bbbb` (64 letras b).
- [ ] Organizador: en Participantes ve el código de cada uno, copia su enlace o se los envía por WhatsApp; ↻ genera código y enlace nuevos y los anteriores dejan de funcionar.
- [ ] Crear intercambio **sigue** pidiendo iniciar sesión.

### 3.3 Crear y unirse con cuenta
- [ ] Ana crea un intercambio nuevo: presupuesto mínimo > máximo → error; fecha límite después del evento → el navegador no lo permite.
- [ ] Al crearlo aparece el código, la cuenta regresiva corre y dice "Eres el organizador".
- [ ] **Copiar enlace** cambia a "¡Copiado!"; **WhatsApp** abre con el mensaje armado.
- [ ] Diego se une a Amigos 2026 escribiendo `nav ami gos` (minúsculas y espacios) → lo encuentra y pide confirmar.
- [ ] Abrir el enlace de invitación sin sesión → pide login y luego regresa a la invitación.
- [ ] El inicio muestra el próximo intercambio real del usuario con su cuenta regresiva.

### 3.4 Perfil y lista de deseos
- [ ] Sofía confirma su participación → aparece ✓ junto a su nombre.
- [ ] Sofía sube una foto (vista previa antes de guardar) → aparece en la lista de participantes.
- [ ] Agregar deseo con enlace sin `https://` → error. Con todo correcto → aparece con su prioridad.
- [ ] Editar y eliminar un deseo (con ventana de confirmación).
- [ ] Imagen de más de 2 MB → marcada en rojo. Un `.txt` renombrado a `.jpg` → rechazado por el servidor.

### 3.5 Panel de administración (Ana en Amigos 2026)
- [ ] Resumen muestra 5 participantes, 3 confirmados, porcentajes y días restantes.
- [ ] Agregar a "Rosa Medina" solo con su nombre → aparece en la tabla con sus botones de enlace personal (🔑 y WhatsApp).
- [ ] Editar a Carlos y marcarlo como confirmado.
- [ ] Eliminar a un participante → desaparece junto con sus deseos.
- [ ] Cerrar registro → con otra cuenta, el código ya no deja unirse. Reabrir registro.
- [ ] Generar código nuevo → el enlace anterior dice "no es válido".
- [ ] Configuración: cambiar el presupuesto y ver el cambio en la página del intercambio.
- [ ] Tras guardar, el panel vuelve a abrir la misma pestaña.

### 3.6 Sorteo
- [ ] La restricción Luis ↔ Sofía aparece en la pestaña Sorteo.
- [ ] Con solo 3 personas, una restricción que haga imposible el sorteo → rechazada.
- [ ] **Realizar sorteo** → "N asignaciones guardadas en secreto" (sin nombres).
- [ ] En phpMyAdmin (`asignaciones`): una fila por participante, ningún `dador_id = receptor_id`, Luis y Sofía no se regalan.
- [ ] Repetir el sorteo 3–4 veces (Reiniciar → Realizar) → las asignaciones cambian y la restricción se respeta siempre.
- [ ] Después del sorteo no se puede agregar/eliminar participantes ni cambiar restricciones.
- [ ] **Publicar resultados** → el botón Reiniciar desaparece.

### 3.7 Revelación
- [ ] Los participantes ven el botón **Descubrir a mi amigo secreto** (con latido).
- [ ] La animación: el regalo tiembla → la tapa vuela → confeti → nombre, pistas y lista de deseos.
- [ ] Al recargar ya no pide abrir el regalo y ofrece "Ver la animación otra vez".
- [ ] El panel del organizador muestra "X de N ya descubrieron a su amigo secreto".
- [ ] En **Oficina 2026** (ya publicado): Ana ve a Diego, Diego a Mariana, Mariana a Carlos y Carlos a Ana.

### 3.8 Notificaciones, FAQ y extras
- [ ] La campanita muestra el número de no leídas; al abrir una se marca como leída.
- [ ] Unirse, confirmar, sortear y publicar generan los avisos correctos (ver tabla del Módulo 8).
- [ ] "Marcar todas como leídas" quita el contador.
- [ ] La sección de Preguntas frecuentes abre y cierra cada respuesta.
- [ ] El pie de página y el inicio muestran mensajes navideños distintos; "Otro mensaje" cambia el texto.

---

## 4. Pruebas de seguridad

| # | Prueba | Resultado esperado | ✔ |
|---|---|---|---|
| S1 | Diego abre `/intercambios/1` (no participa) | "¡Ups! Este regalo no existe" | [ ] |
| S2 | Luis abre `/intercambios/1/admin` (no es el organizador) | "no encontrado" | [ ] |
| S3 | Luis abre `/deseos/1/editar` (deseo de Ana) | "no encontrado" | [ ] |
| S4 | Diego abre `/intercambios/1/mi-amigo-secreto` | "no encontrado" | [ ] |
| S5 | Abrir `/mi-amigo-secreto` cuando el sorteo NO está publicado | Regresa con aviso de que aún no se publica | [ ] |
| S6 | Buscar en todo el sitio la lista completa de asignaciones | No existe ninguna pantalla que la muestre | [ ] |
| S7 | Sin sesión, abrir `/mis-intercambios` | Redirige al login | [ ] |
| S8 | Enviar un formulario sin token CSRF (borrar el campo oculto con F12) | "La sesión del formulario expiró" (error 419) | [ ] |
| S9 | Escribir `<script>alert(1)</script>` como nombre de intercambio o deseo | Se muestra como texto, no se ejecuta | [ ] |
| S10 | Escribir `' OR '1'='1` como correo en el login | "Correo o contraseña incorrectos" | [ ] |
| S11 | Revisar `usuarios.password_hash` en phpMyAdmin | Empieza con `$2y$`, nunca la contraseña real | [ ] |
| S12 | Abrir `http://localhost/intercambio_navideno/config/config.php` | No muestra el archivo (todo pasa por `public/`) | [ ] |
| S13 | Doble envío del sorteo (F12 → Consola: `document.querySelector('form[action$="/admin/sorteo"]').submit()` justo después de sortear) | "El sorteo ya se había realizado. No se hizo nada" | [ ] |
| S14 | Una invitación con correo, abierta con otra cuenta | "Esta invitación es para …" | [ ] |
| S15 | Abrir la notificación de otra persona (`/notificaciones/ID`) | Regresa a tu propia lista | [ ] |
| S16 | Subir un `.php` como foto | Rechazado; además en `uploads/` PHP no se ejecuta | [ ] |

---

## 5. Diseño responsive

En el navegador presiona **F12 → icono de celular** (o `Ctrl+Shift+M`) y revisa con estos tamaños:

| Pantalla | Tamaño | Qué revisar | ✔ |
|---|---|---|---|
| Celular | 375 × 667 | Menú hamburguesa, botones a lo ancho, cuenta regresiva legible, tabla del panel con desplazamiento horizontal | [ ] |
| Tablet | 768 × 1024 | Tarjetas en 2 columnas, pestañas del panel en varias líneas | [ ] |
| Computadora | 1366 × 768 | Diseño completo en columnas, hover de tarjetas | [ ] |

Revisa en cada tamaño: inicio, crear intercambio, página del intercambio, perfil/deseos, panel (todas las pestañas), revelación y notificaciones.

- [ ] La nieve se ve sutil y no tapa botones ni textos.
- [ ] Con "Reducir movimiento" activado en Windows (Configuración → Accesibilidad → Efectos visuales → desactivar animaciones) la nieve y las animaciones se desactivan.
