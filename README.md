# 🎄 Intercambio Navideño

Plataforma web para organizar intercambios de regalos entre amigos: crea el evento, invita con un código o enlace, cada quien arma su lista de deseos, el sistema hace un sorteo justo y secreto, y cada participante descubre a su amigo secreto con una animación especial.

**Tecnologías:** PHP 7.2+ (compatible con PHP 8) · MySQL / MariaDB · HTML5 · CSS3 · JavaScript · Bootstrap 5 · Apache (XAMPP). Sin frameworks ni Composer.

---

## 1. Requisitos

| Requisito | Versión | Notas |
|---|---|---|
| XAMPP | 7.2 o más reciente | Incluye Apache, PHP y MySQL/MariaDB |
| Navegador | Chrome, Edge o Firefox actuales | |
| Internet | Al usar la app | Bootstrap, los íconos y las tipografías se cargan desde CDN |

Extensiones de PHP necesarias (vienen activas en XAMPP): `pdo_mysql`, `mbstring`.

---

## 2. Instalación paso a paso

### Paso 1 · Copiar el proyecto
Coloca la carpeta del proyecto en:
```
C:\xampp\htdocs\intercambio_navideno\
```
Debe quedar `C:\xampp\htdocs\intercambio_navideno\public\index.php` (no una carpeta dentro de otra).

### Paso 2 · Encender los servicios
Abre el **XAMPP Control Panel** y presiona **Start** en **Apache** y **MySQL** (ambos en verde).

### Paso 3 · Crear la base de datos
1. Entra a `http://localhost/phpmyadmin`.
2. Pestaña **Importar** → **Seleccionar archivo** → `database/intercambio.sql` → **Continuar**.
   Se crea la base `intercambio_navideno` con sus 8 tablas.
3. *(Opcional)* Importa también `database/datos_prueba.sql` para tener cuentas y datos de ejemplo
   (contraseña de todas las cuentas: `Navidad2026`; ver `docs/PRUEBAS.md`).

### Paso 4 · Verificar la conexión
Abre `http://localhost/intercambio_navideno/prueba-conexion`.
Debe decir **"Conexión correcta"** con las 8 tablas en verde.

### Paso 5 · ¡Listo!
Abre **`http://localhost/intercambio_navideno/`**, crea una cuenta y organiza tu primer intercambio.

### Configuración (solo si es necesario)
En XAMPP no hay que configurar nada: la URL se detecta sola y se usan los datos de MySQL de XAMPP.
Si tu MySQL tiene contraseña (o al publicar en internet), **no edites `config/config.php`**:

1. Copia `config/config.local.ejemplo.php` como **`config/config.local.php`**.
2. Escribe ahí tus datos (`DB_HOST`, `DB_NOMBRE`, `DB_USUARIO`, `DB_PASSWORD`, `MODO_DESARROLLO`).

`config.local.php` está en `.gitignore`: nunca se sube a GitHub, así tus contraseñas quedan privadas.

---

## 3. Cómo se usa

1. **Crear cuenta** → **Crear intercambio** (fechas, presupuesto, lugar, reglas). Recibes un código como `NAV-7K2-Q9X`.
2. **Invitar**: comparte el código o el enlace (copiar, WhatsApp) o crea invitaciones personales desde el panel.
3. Cada participante **se une**, **confirma** su asistencia y llena **su perfil y lista de deseos**.
4. El organizador entra al **Panel de administración** → pestaña **Sorteo**: agrega restricciones si quiere (ej. parejas), **realiza el sorteo** y, cuando esté conforme, **publica los resultados** (puede reiniciar mientras no publique).
5. Cada participante pulsa **Descubrir a mi amigo secreto** → animación → ve a quién le regala y su lista de deseos.

---

## 4. Estructura del proyecto

```
intercambio_navideno/
├── .htaccess                 → manda todo a public/ (app/ y config/ quedan ocultos)
├── README.md                 → este archivo
├── config/
│   ├── config.php            → constantes: URL (automática), base de datos, zona horaria
│   ├── config.local.ejemplo.php → plantilla de datos privados (se copia como config.local.php)
│   └── database.php          → conexión PDO única (Singleton)
├── database/
│   ├── intercambio.sql       → crea la base de datos y las 8 tablas (XAMPP)
│   ├── intercambio_hosting.sql → lo mismo, para hosting (sin CREATE DATABASE)
│   └── datos_prueba.sql      → cuentas y datos de ejemplo
├── app/
│   ├── core/                 → piezas base
│   │   ├── Router.php        → URL → controlador
│   │   ├── Controller.php    → mostrar vistas, JSON, errores de formulario
│   │   ├── Auth.php          → sesión del usuario
│   │   ├── Csrf.php          → tokens de formularios
│   │   ├── Validator.php     → validación en el servidor
│   │   └── helpers.php       → e(), url(), fechas, dinero, mensajes navideños…
│   ├── controllers/          → 11 controladores (uno por área)
│   ├── models/               → 8 modelos (uno por tabla)
│   ├── services/             → lógica reutilizable
│   │   ├── SorteoService.php       → ALGORITMO DEL SORTEO
│   │   ├── CodigoService.php       → códigos de acceso
│   │   ├── UploadService.php       → subida segura de imágenes
│   │   ├── NotificacionService.php → avisos de cada evento
│   │   └── IntercambioValidacion.php
│   └── views/                → pantallas (HTML + PHP) por carpeta
├── public/                   → ÚNICA carpeta accesible desde el navegador
│   ├── index.php             → punto de entrada y lista de rutas
│   ├── .htaccess
│   ├── assets/css/estilos.css
│   ├── assets/js/            → app.js, nieve.js, revelacion.js
│   └── uploads/              → fotos (PHP bloqueado con .htaccess)
├── tests/                    → pruebas automáticas
└── docs/PRUEBAS.md           → plan de pruebas manuales y de seguridad
```

**Arquitectura:** MVC ligero. Cada petición entra por `public/index.php` → el `Router` elige el controlador → el controlador valida, usa modelos y servicios → muestra una vista dentro de `layouts/main.php`.

### Base de datos

| Tabla | Contenido | Relación |
|---|---|---|
| `usuarios` | Cuentas (contraseña con hash) | — |
| `intercambios` | Eventos; `estado`: abierto → cerrado → sorteado → revelado | `admin_id` → usuarios |
| `participantes` | Usuario dentro de un intercambio + su perfil | → intercambios, usuarios |
| `deseos` | Lista de deseos | → participantes |
| `restricciones` | "A no le regala a B" | → participantes |
| `asignaciones` | Resultado del sorteo (privado) | UNIQUE por dador y por receptor |
| `invitaciones` | Enlaces personales con token | → intercambios |
| `notificaciones` | Avisos de la campanita | → usuarios |

---

## 5. El algoritmo del sorteo

Archivo: `app/services/SorteoService.php` (no depende de la base de datos).

1. Para cada persona se arma la lista de a quién **sí** puede regalar (nadie a sí mismo ni a sus restricciones) y se baraja con `random_int` (aleatoriedad criptográfica).
2. Se asigna primero a quien tiene **menos opciones**.
3. A cada persona se le da el primer receptor libre de su lista; si alguien se queda sin opciones, se **retrocede** (backtracking) y se prueba otra combinación.
4. Si existe una asignación válida, siempre la encuentra; si no existe, lo informa sin guardar nada.
5. Antes de guardar, `esValido()` revisa de nuevo todas las reglas.

**Protección contra sortear dos veces:** ventana de confirmación + botón que se desactiva, transacción con `SELECT … FOR UPDATE`, verificación del estado e índices `UNIQUE` en `asignaciones`.

---

## 6. Seguridad implementada

| Riesgo | Protección |
|---|---|
| Contraseñas robadas | `password_hash` (bcrypt) + `password_verify`; rehash automático |
| Inyección SQL | 100 % consultas preparadas con PDO (`ATTR_EMULATE_PREPARES = false`) |
| XSS | Toda salida pasa por `e()` (`htmlspecialchars`) |
| CSRF | Token en todos los formularios POST y en `fetch` |
| Robo de sesión | Cookie `HttpOnly`, `session_regenerate_id` al entrar, cierre por inactividad (2 h) |
| Fuerza bruta | Bloqueo de 5 minutos tras 5 intentos fallidos |
| Ver datos ajenos | Cada consulta filtra por el usuario en sesión; recursos ajenos responden 404 |
| Ver asignaciones ajenas | Solo existe `deDador()` con el participante de la sesión; nadie ve la lista completa |
| Funciones de admin | Todas verifican `admin_id`; sin permiso → 404 |
| Archivos maliciosos | Solo imágenes reales (`getimagesize`), 2 MB, nombre aleatorio, PHP bloqueado en `uploads/` |
| Archivos internos expuestos | Solo `public/` es accesible desde el navegador |

---

## 7. Pruebas

```
cd C:\xampp\htdocs\intercambio_navideno
chcp 65001
C:\xampp\php\php.exe tests\ejecutar_todas.php
```
Ejecuta 50+ pruebas (algoritmo, validaciones y base de datos). El plan manual completo está en **`docs/PRUEBAS.md`**.

---

## 8. Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| Todas las páginas dan **404** o "Not Found" de Apache | `mod_rewrite` desactivado | En `C:\xampp\apache\conf\httpd.conf` quita el `#` de `LoadModule rewrite_module modules/mod_rewrite.so` y en el bloque `<Directory "C:/xampp/htdocs">` pon `AllowOverride All`. Reinicia Apache. |
| "No se pudo conectar a la base de datos" | MySQL apagado, base no importada o datos incorrectos | Enciende MySQL y repite el Paso 3. Revisa los datos en `config/config.local.php`. |
| `Call to undefined function …` | Versión de PHP muy antigua | Usa XAMPP con PHP 7.2 o superior (`http://localhost/dashboard/phpinfo.php`). |
| La página se ve sin estilos | Sin internet (Bootstrap viene de CDN) | Conéctate a internet y recarga con `Ctrl+F5`. |
| Las fotos no se guardan | Carpeta sin permisos o archivo grande | Revisa que exista `public/uploads/`; en `php.ini`, `upload_max_filesize` ≥ 2M y `post_max_size` ≥ 8M. |
| "La sesión del formulario expiró" (419) | El formulario quedó abierto mucho tiempo | Recarga la página y vuelve a enviarlo. |
| Las fechas u horas salen corridas | Zona horaria distinta | Cambia `date_default_timezone_set` en `config/config.php`. |
| Acentos raros en la terminal al correr pruebas | La consola no usa UTF-8 | Ejecuta `chcp 65001` antes de las pruebas. |
| Apache no enciende (puerto 80 ocupado) | Skype/IIS usan el puerto 80 | Cierra ese programa o cambia el puerto de Apache en XAMPP (Config → httpd.conf → `Listen 8080`) y entra con `http://localhost:8080/intercambio_navideno/`. |


---

## 9. Publicar en internet (GitHub + InfinityFree, gratis)

> **GitHub Pages no sirve para este proyecto**: solo publica sitios estáticos y no ejecuta PHP ni MySQL.
> Por eso el **código** vive en GitHub y el **sitio** se ejecuta en un hosting gratuito con PHP (InfinityFree).

### 9.1 Subir el código a GitHub (con GitHub Desktop)
1. Crea tu cuenta en <https://github.com> e instala **GitHub Desktop** (<https://desktop.github.com>). Inicia sesión.
2. **File → Add local repository…** → elige `C:\xampp\htdocs\intercambio_navideno`.
   Si dice *"This directory does not appear to be a Git repository"*, haz clic en **create a repository** → **Create repository**.
3. En la pestaña **Changes** verás los archivos. Revisa que **no** aparezca `config/config.local.php`.
4. Escribe un resumen (ej. `Versión inicial`) → **Commit to main**.
5. **Publish repository** → elige el nombre → desmarca *Keep this code private* si quieres que sea público → **Publish**.
6. Cada vez que cambies algo: **Commit to main** → **Push origin**.

### 9.2 Publicar el sitio en InfinityFree
1. Crea tu cuenta en <https://www.infinityfree.com> → **Create Account** (de hosting) → elige un subdominio gratis.
2. En el panel (**Control Panel**) → **MySQL Databases** → crea una base (ej. `intercambio`).
   Anota: **MySQL Hostname**, **MySQL DB Name**, **MySQL Username** y la contraseña de la cuenta de hosting.
3. **phpMyAdmin** (junto a tu base) → selecciona tu base a la izquierda → **Importar** → `database/intercambio_hosting.sql`.
   (No uses `intercambio.sql`: tiene `CREATE DATABASE`, que el hosting no permite.)
4. **File Manager** (o FileZilla por FTP con los datos de *FTP Details*) → entra a la carpeta **`htdocs`** → borra el `index2.html` de bienvenida → sube **todo el contenido** del proyecto (`.htaccess`, `app/`, `config/`, `public/`, …) directamente dentro de `htdocs/`.
5. En el servidor, dentro de `htdocs/config/`, crea **`config.local.php`** copiando `config.local.ejemplo.php` y pon los datos del paso 2 (deja `MODO_DESARROLLO` en `false`).
6. Abre `https://tu-subdominio/prueba-conexion` → debe decir **"Conexión correcta"**.
7. En el panel → **SSL Certificates** → solicita el certificado gratis para tener `https://`.

### 9.3 Notas del hosting gratuito
- Un sitio nuevo puede tardar unos minutos (hasta 72 h en casos raros) en verse por la propagación del DNS.
- No hay envío de correos con `mail()`; las notificaciones de la plataforma sí funcionan porque son internas.
- Las pruebas de `tests/` se ejecutan en tu computadora, no en el hosting.
- Si cambias código: súbelo a GitHub (Push) y reemplaza los archivos modificados en el File Manager.

---

## 10. Posibles mejoras futuras

- Envío de correos reales (PHPMailer + SMTP) desde `NotificacionService.php`.
- Recuperar contraseña por correo.
- Exportar la lista de participantes a Excel.
- Chat anónimo entre el amigo secreto y quien recibe el regalo.
