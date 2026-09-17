# CV ATS - Base del proyecto (Fase 1 a 8, completo)

Este paquete contiene:
- **Fase 1**: migraciones y modelos Eloquent (perfil, estudios, experiencia, habilidades, idiomas).
- **Fase 2**: el wizard de creación de CV con Livewire (4 pasos).
- **Fase 3**: subida y recorte automático de foto (Intervention Image), integrado en el paso 1 del wizard.
- **Fase 4**: plantilla ATS-friendly (una sola columna) para la previsualización, reutilizable para el PDF.
- **Fase 5**: exportación a PDF con dompdf, reutilizando la misma plantilla.
- **Fase 6**: exportación a DOCX con PhpWord.
- **Fase 7**: enlace público para compartir el CV sin necesidad de login.
- **Fase 8**: ajuste automático de la foto (brillo/contraste/nitidez), 100% local, sin API key ni servicios externos, procesado en cola.
- **Landing page**: `resources/views/welcome.blade.php`, pensada para explicar el problema (los ATS rechazan CVs mal formateados) y llevar al registro.

## Cómo instalarlo en tu proyecto Laravel

1. Si aún no tienes el proyecto creado:
   ```bash
   composer create-project laravel/laravel cv-ats
   cd cv-ats
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   npm install && npm run build
   ```

2. Instala Livewire, Intervention Image, dompdf y PhpWord:
   ```bash
   composer require livewire/livewire
   composer require intervention/image
   composer require barryvdh/laravel-dompdf
   composer require phpoffice/phpword
   ```

3. Crea el enlace simbólico de storage (para que las fotos sean accesibles públicamente):
   ```bash
   php artisan storage:link
   ```

4. Copia las carpetas de este paquete dentro de tu proyecto:
   - `database/migrations/*.php` → a tu carpeta `database/migrations/`
   - `app/Models/*.php` → a tu carpeta `app/Models/` (EXCEPTO `User_RELATION_SNIPPET.php`)
   - `app/Livewire/*.php` → a tu carpeta `app/Livewire/`
   - `app/Http/Controllers/*.php` → a tu carpeta `app/Http/Controllers/`
   - `resources/views/livewire/*.blade.php` → a tu carpeta `resources/views/livewire/`

5. Abre tu `app/Models/User.php` y agrega la relación `profile()` que está
   en `User_RELATION_SNIPPET.php` (o reemplaza tu archivo directamente por
   `app/Models/User.php` incluido en este paquete, que ya la trae agregada).

6. Abre tu `routes/web.php` y agrega las rutas que están en `routes/web_cv_routes.php`
   (cópialas dentro del archivo, no reemplaces tu `web.php`).

7. Copia la carpeta de plantillas de CV:
   ```bash
   mkdir -p resources/views/cv/templates
   ```
   - `resources/views/cv/templates/ats-classic.blade.php` → a esa carpeta
   - `resources/views/cv/preview.blade.php` → a tu `resources/views/cv/`
   - `resources/views/cv/pdf.blade.php` → a tu `resources/views/cv/`
   - `resources/views/cv/public.blade.php` → a tu `resources/views/cv/`

8. Corre las migraciones:
   ```bash
   php artisan migrate
   ```

9. Visita `/cv/crear` (con un usuario logueado) para llenar tus datos, incluyendo la foto en el paso 1, luego `/cv/preview` para ver el resultado, descargar el PDF/DOCX, y activar el enlace público con el switch correspondiente.

⚠️ **Importante sobre el orden de rutas**: en tu `routes/web.php`, la ruta pública
`Route::get('/cv/{slug}', ...)` debe quedar **después** de las rutas `/cv/crear`,
`/cv/preview`, etc. Como es un comodín (`{slug}`), si la pones antes, Laravel
intentará interpretar "crear" o "preview" como si fueran un slug de perfil.

## Configuración del ajuste automático de foto (Fase 8)

Esta fase **no requiere ninguna API key ni servicio externo**. Todo el
procesamiento (brillo, contraste, nitidez) corre localmente con Intervention
Image, dentro de un job en cola.

1. Copia `app/Jobs/EnhancePhotoWithAi.php` a tu carpeta `app/Jobs/`.

2. Configura una cola real (en desarrollo puedes usar la de base de datos):
   ```bash
   php artisan queue:table
   php artisan migrate
   ```
   Y en tu `.env`:
   ```
   QUEUE_CONNECTION=database
   ```

3. Corre el worker de colas (en una terminal aparte, mientras desarrollas):
   ```bash
   php artisan queue:work
   ```

   Sin este comando corriendo, el job queda "pendiente" para siempre y la
   foto nunca se actualiza — es el error más común al probar esta fase.

### Cómo funciona el ajuste automático de foto

- Al subir una foto, `PhotoUpload` guarda el recorte estándar (Fase 3) y
  **además** despacha `EnhancePhotoWithAi::dispatch($profile)` en segundo
  plano — el usuario no espera, puede seguir llenando el formulario.
- El job aplica localmente brillo (+5), contraste (+8) y nitidez (+8) sobre
  el recorte ya generado, con Intervention Image. **No quita el fondo**:
  eso requeriría una API de IA (remove.bg, OpenAI, etc.) o un modelo de
  ML corriendo en el servidor, con el costo/complejidad que eso implica.
  Si más adelante quieres agregar remoción de fondo real, este job (`handle()`)
  es el punto exacto donde conectar esa llamada externa.
- El estado se guarda en `profiles.photo_ai_status`
  (`pending` → `processing` → `completed` o `failed`), y la vista hace
  `wire:poll.2s` **solo mientras está pendiente/procesando**.
- **Diseño a prueba de fallos**: como todo es local, prácticamente siempre
  termina en `completed`; si por algún motivo falla (imagen corrupta, etc.),
  el usuario conserva el recorte "normal" de la Fase 3 con un botón de
  "Reintentar".
- El original que subió el usuario (`photo_path`) nunca se sobrescribe;
  solo se reemplaza `photo_ats_path` (la versión que se usa en el CV).

### Cómo funciona la subida de foto

- `PhotoUpload.php` es un componente Livewire independiente (usa `WithFileUploads`),
  embebido dentro del paso 1 del wizard con `<livewire:photo-upload />`.
- Al seleccionar una imagen, se dispara `updatedPhoto()` automáticamente (no hace
  falta botón "subir"): valida el archivo, guarda el original, y genera una
  versión recortada 400x500px (`cover()` de Intervention Image, mantiene proporción
  y recorta centrado) en formato JPG optimizado.
- Se guardan **dos rutas** en `profiles`: `photo_path` (original) y `photo_ats_path`
  (versión recortada, la que se usará en el CV). Esto es clave porque la Fase 6
  (mejora con IA) trabajará sobre `photo_ats_path` sin perder el original.
- Hay un comentario en el código marcando dónde despachar el job de IA
  (`EnhancePhotoWithAi::dispatch($profile)`) cuando lleguemos a esa fase.

### Cómo funciona el wizard

- `CvWizard.php` maneja 4 pasos: datos personales, estudios, experiencia, y
  perfil+habilidades+idiomas. Cada "Siguiente" valida y **guarda automáticamente**
  ese paso en la base de datos (no se pierde el progreso si el usuario cierra el navegador).
- Estudios, experiencia, habilidades e idiomas son **repetibles**: el usuario
  puede agregar/quitar filas dinámicamente con Livewire, sin recargar la página.
- El método `syncCollection()` es genérico: compara lo que hay en el array del
  formulario contra la base de datos, actualiza lo existente, crea lo nuevo y
  borra lo que el usuario haya quitado.

## Estructura de datos creada

- **profiles**: datos personales, contacto, perfil profesional, foto, slug público
- **educations**: historial académico
- **experiences**: historial laboral
- **skills**: habilidades
- **languages**: idiomas
- **cv_templates**: catálogo de plantillas (para más adelante, cuando hagamos las vistas Blade de cada diseño)
- **cv_exports**: historial de exportaciones (pdf/docx/link)

### Cómo funciona la plantilla ATS-friendly

- `ats-classic.blade.php` es un **partial reutilizable**: contiene solo el HTML/CSS
  del CV (nada de layout, navbar, etc.), para poder incluirlo tal cual tanto en la
  previsualización (`cv/preview.blade.php`) como en el PDF (Fase 5).
- Diseño de **una sola columna**, sin flexbox/grid (dompdf no los soporta bien),
  sin tablas, y con los datos de contacto siempre en texto plano — nunca solo
  dentro de la foto o como iconos sin texto al lado.
- La foto (`photo_ats_path`) se muestra como elemento puramente decorativo con
  `float`, nunca reemplaza información textual.

### Cómo funciona la exportación a PDF

- `CvExportController@pdf` usa `barryvdh/laravel-dompdf` para renderizar
  `cv/pdf.blade.php`, que a su vez incluye el mismo `ats-classic.blade.php`
  usado en la previsualización — cero duplicación de la plantilla.
- La foto se embebe como **base64** en el PDF (no como URL), porque dompdf
  no siempre resuelve bien rutas relativas de `/storage/...`; así el PDF
  nunca sale con la foto rota, sin importar el entorno del servidor.
- Cada descarga queda registrada en `cv_exports` (útil más adelante para
  mostrarle al usuario un historial de descargas).

### Cómo funciona la exportación a DOCX

- `CvExportController@docx` **no reutiliza la plantilla Blade** (HTML y DOCX
  son formatos distintos); en su lugar construye el documento programáticamente
  con `phpoffice/phpword`, pero replica la misma estructura ATS-friendly:
  una columna, texto plano, fuente Arial, sin tablas para el contenido principal.
- El archivo se genera en un temporal (`tempnam`) y se transmite con
  `streamDownload`, borrándose el temporal justo después — no se acumulan
  archivos en el servidor.
- No usa la foto (los ATS que procesan `.docx` suelen ignorarla o incluso
  fallar con imágenes incrustadas mal formadas); si más adelante quieres
  incluirla, se puede agregar con `Section::addImage()`.

### Cómo funciona el enlace público

- Cada `Profile` ya tenía un `public_slug` (UUID) generado automáticamente al
  crearse (desde la Fase 1), y un campo `is_public` que por defecto es `false`.
- `PublicLinkManager.php` (Livewire) muestra un switch en la vista de preview:
  al activarlo, pone `is_public = true` y genera la URL completa
  (`route('cv.public', $slug)`) con botones de "Copiar" y "Ver".
- `PublicCvController@show` es la única puerta de entrada pública: busca el
  perfil por `public_slug` **y** exige `is_public = true`. Si el dueño desactiva
  el switch, el enlace deja de funcionar automáticamente (404), sin borrar nada.
- La vista pública (`cv/public.blade.php`) es standalone — no usa el layout de
  la app (no tiene sentido mostrar el menú de "cerrar sesión" a un reclutador
  que no está logueado) y reutiliza la misma plantilla `ats-classic.blade.php`.
- Se agregó `<meta name="robots" content="noindex, nofollow">` para que estos
  CVs no aparezcan indexados en buscadores por accidente.

## ¡Proyecto base completo! 🎉

Las 8 fases del plan original ya están cubiertas:

1. ~~CRUD del perfil con Livewire (formulario tipo wizard)~~ ✅
2. ~~Subida y recorte de foto con `intervention/image`~~ ✅
3. ~~Vista/plantilla ATS-friendly en Blade~~ ✅
4. ~~Exportación a PDF con `barryvdh/laravel-dompdf`~~ ✅
5. ~~Exportación a DOCX con `phpoffice/phpword`~~ ✅
6. ~~Ruta pública `/cv/{slug}`~~ ✅
7. ~~Integración de IA para mejorar la foto~~ ✅ (ajuste local, sin API key)

## Ideas para seguir puliendo el proyecto (opcional)

- **Checklist / score ATS**: validar que no falten secciones clave (teléfono,
  email, al menos una experiencia, etc.) y mostrarle al usuario un puntaje.
- **Múltiples plantillas**: usar la tabla `cv_templates` (ya creada en la Fase 1
  pero sin usar aún) para dejar elegir entre 2-3 diseños ATS-friendly distintos.
- **Notificación al terminar la IA**: usar Laravel Echo/Reverb para notificar
  en tiempo real en vez de `wire:poll`, si el proyecto crece.
- **Panel de administración** para el "aprendiz" instructor: ver cuántos CVs
  se han creado, cuáles están completos, etc.

## Notas sobre el diseño ATS-friendly

- Todo el contenido vive en la base de datos como texto plano (no en imágenes).
- Las plantillas Blade para PDF deben evitar tablas complejas, columnas múltiples,
  cabeceras/pies con texto importante, e iconos que reemplacen texto (ej: un ícono
  de teléfono sin la palabra "Teléfono" al lado puede no ser leído por el parser).
- El campo `achievements` en `experiences` está pensado para logros medibles
  (ej: "Aumenté un 20% la eficiencia del proceso X"), ideal para palabras clave ATS.

## Landing page

`resources/views/welcome.blade.php` reemplaza la página de bienvenida por
defecto de Laravel. Es una sola página HTML autocontenida (CSS embebido,
sin dependencias de Tailwind ni de ningún build), así que solo tienes que
copiarla a tu `resources/views/` y sobrescribir el archivo existente.

Qué contiene:
- Un **hero** con una pequeña animación (una línea que "escanea" un CV de
  ejemplo, en CSS puro) explicando la propuesta: un CV que un sistema ATS
  puede leer.
- Una comparación **antes/después** mostrando por qué un diseño de columnas
  falla con un parser, frente a uno de una sola columna.
- Los **5 pasos** del wizard, en el mismo orden que ya construimos.
- Dos funciones destacadas: foto lista para CV, y exportar/compartir.
- Los botones ya usan `@auth`/`@else` con las rutas reales del proyecto
  (`register`, `login`, `cv.wizard`, `cv.preview`) — no hay que tocar nada
  si ya seguiste los pasos de instalación de las fases anteriores.

Si tu proyecto usa Breeze/Jetstream, esas rutas (`register`, `login`) ya
existen por defecto, así que la página funciona sin configuración adicional.

## Nota: redirección después de iniciar sesión

Breeze crea por defecto una ruta `dashboard` que solo muestra una vista
genérica ("You're logged in!"), sin relación con el CV. Busca este bloque
en tu `routes/web.php` (lo genera `breeze:install`):

```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

Y reemplázalo por:

```php
Route::get('/dashboard', function () {
    $profile = auth()->user()->profile;

    return $profile
        ? redirect()->route('cv.preview')
        : redirect()->route('cv.wizard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

Así, al iniciar sesión, el usuario cae directo en su CV (o en el wizard si
todavía no ha creado uno), en vez de ver la pantalla vacía de Breeze.
