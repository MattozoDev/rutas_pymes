# Conectar el proyecto a Supabase en otro PC

## El problema

```
PDOException  SQLSTATE[08006] [7] could not translate host name
"db.<PROJECT_REF>.supabase.co" to address: Name or service not known.
```

El host de **conexión directa** de Supabase (`db.<PROJECT_REF>.supabase.co`) solo tiene
dirección **IPv6**. Si la red o el PC no tienen IPv6 (lo más común en redes domésticas
en Windows), el nombre no se puede resolver y la conexión falla.

La solución es usar el **Session Pooler** de Supabase, que sí tiene dirección IPv4.

---

## Pasos

### 1. Obtener los datos del pooler en Supabase

1. Entra a <https://supabase.com/dashboard> y abre el proyecto.
2. Pulsa el botón **Connect** (arriba en la barra del proyecto).
3. En **Connection String**, elige **Session pooler** (no "Direct connection").
4. Copia los datos. Tendrán esta forma:

   | Campo    | Valor                                   |
   |----------|-----------------------------------------|
   | host     | `aws-0-<REGION>.pooler.supabase.com`    |
   | port     | `5432`                                  |
   | database | `postgres`                              |
   | user     | `postgres.<PROJECT_REF>`                |

   > Ojo: el usuario **cambia** de `postgres` a `postgres.<PROJECT_REF>`.

### 2. Crear el `.env`

El `.env` no se sube a git, así que en el PC nuevo hay que crearlo:

```bash
cp .env.example .env
php artisan key:generate
```

> Si el resto del equipo comparte la misma base de datos, copia también el `APP_KEY`
> y las demás claves (`config/services.php`) del `.env` original por un canal privado,
> no por git.

### 3. Configurar la base de datos en el `.env`

Reemplaza el bloque `DB_*` por:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-<REGION>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<PROJECT_REF>
DB_PASSWORD=<tu-contraseña-de-la-base-de-datos>
DB_SSLMODE=require
```

Usa el puerto **5432** (Session pooler). El puerto `6543` es el Transaction pooler y no
admite sentencias preparadas, lo que da errores con Laravel.

### 4. Verificar que PHP tenga el driver de PostgreSQL

```bash
php -m | grep -i pgsql
```

Deben aparecer `pdo_pgsql` y `pgsql`. Si no aparecen, abre el `php.ini`
(se ve la ruta con `php --ini`) y descomenta:

```ini
extension=pdo_pgsql
extension=pgsql
```

### 5. Limpiar la caché de configuración

```bash
php artisan config:clear
```

### 6. Probar la conexión

```bash
php artisan db:show
```

o bien:

```bash
php artisan tinker --execute 'DB::connection()->getPdo(); echo "OK";'
```

### 7. Instalar dependencias y levantar el proyecto

```bash
composer install
npm install
npm run build
composer run dev
```

> **No** ejecutes `php artisan migrate:fresh` ni `db:seed` contra Supabase si la base
> ya tiene datos: es la misma base que usa el otro PC.

---

## Opcional: quitar los avisos `PHP Deprecated` de la sesión

```
PHP Deprecated: PHP Startup: session.sid_bits_per_character INI setting is deprecated
```

No impide la conexión, solo es ruido. En PHP 8.4+ esas opciones quedaron obsoletas.
Abre el `php.ini` (`php --ini`) y comenta o borra estas líneas:

```ini
;session.sid_length = 26
;session.sid_bits_per_character = 5
```

---

## Si todavía falla

| Error | Causa probable |
|-------|----------------|
| `could not translate host name "db.…supabase.co"` | Sigues usando el host directo, o no corriste `config:clear`. |
| `password authentication failed` | Contraseña incorrecta, o el usuario no es `postgres.<PROJECT_REF>`. |
| `Tenant or user not found` | Región del host o `PROJECT_REF` del usuario incorrectos. |
| `could not find driver` | Falta habilitar `pdo_pgsql` (paso 4). |
| `prepared statement "…" already exists` | Estás usando el puerto `6543`; cambia a `5432`. |
