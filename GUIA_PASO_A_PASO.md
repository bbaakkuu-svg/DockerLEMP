# 📘 Guía Paso a Paso: Cómo montar una Pila LEMP con Docker Compose

> **Autor:** Bakunin Sosa  
> **Curso:** 2º DAW (Desarrollo de Aplicaciones Web)  
> **Módulo:** Despliegue de Aplicaciones Web  

¡Buenas! En esta guía explico de forma clara y directa cómo desarrollar desde cero la práctica de despliegue de una arquitectura **LEMP** (Linux + Nginx + MySQL + PHP) utilizando **Docker Compose**.

El objetivo es no tener que instalar servidores ni bases de datos a mano en la máquina física, sino empaquetar cada servicio en su propio contenedor y hacer que se comuniquen entre sí a través de una red interna.

---

## 🎯 ¿Qué vamos a montar?

Una arquitectura dividida en 3 piezas clave:

1. **Nginx:** Servidor web rápido y ligero que recibe las visitas por el puerto `8085` del host y le pasa las peticiones dinámicas `.php` al procesador.
2. **PHP-FPM:** El motor que compila y ejecuta el código PHP (en el puerto interno `9000`). Le añadiremos los drivers necesarios para que pueda hablar con MySQL.
3. **MySQL 8:** El motor de base de datos relacional, con un volumen persistente para que los datos no se borren cuando apaguemos los contenedores.

---

## 📁 Paso 1: Crear la estructura del proyecto

Antes de tirar código, organizamos las carpetas en el directorio del proyecto:

```plaintext
DockerLEMP/
├── nginx/
│   └── default.conf          # Configuración del servidor Nginx
├── php/
│   └── Dockerfile            # Imagen de PHP con extensiones de MySQL
├── .env                      # Variables de entorno con contraseñas (no subir a git)
├── .env.example              # Plantilla pública de variables de entorno
├── docker-compose.yml        # Manifiesto que une y arranca los tres servicios
├── index.php                 # Página de prueba que valida la conexión a la BD
└── README.md                 # Documentación general
```

---

## 🌐 Paso 2: Configurar Nginx (`nginx/default.conf`)

Nginx por sí mismo no entiende código PHP; actúa como un **Reverse Proxy**. Por eso debemos indicarle que cuando alguien pida un archivo terminado en `.php`, lo redirija al contenedor `php` a través del protocolo FastCGI.

Creamos el archivo `nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;

    root /var/www/html;
    index index.php index.html;

    # Gestión de rutas normales
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Redirección de peticiones PHP hacia el contenedor PHP-FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Bloqueo de archivos ocultos por seguridad (.git, .env, etc.)
    location ~ /\.ht {
        deny all;
    }
}
```

---

## 🐘 Paso 3: El "truco" de PHP-FPM (`php/Dockerfile`)

Si usamos directamente la imagen oficial `php:8.2-fpm` o `php:fpm-alpine`, nos encontraremos con un problema clásico: **viene sin los drivers de MySQL**. Si intentas hacer `new mysqli()` o conectar por `PDO`, PHP lanza un error fatal diciendo que la función no existe.

Para solucionarlo de forma limpia, creamos `php/Dockerfile`:

```dockerfile
FROM php:8.2-fpm-alpine

# Instalamos y habilitamos las extensiones mysqli y pdo_mysql
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo_mysql

WORKDIR /var/www/html
```

---

## 🔐 Paso 4: Variables de entorno (`.env.example` y `.env`)

Siguiendo las buenas prácticas que nos piden en DAW, nunca debemos dejar contraseñas quemadas (*hardcoded*) dentro del archivo Compose.

Creamos `.env.example`:

```env
# Variables para la base de datos MySQL
MYSQL_ROOT_PASSWORD=cambiar_password_root
MYSQL_DATABASE=lemp_database
MYSQL_USER=lemp_user
MYSQL_PASSWORD=cambiar_password_usuario

# Puerto web en el host
NGINX_PORT=8085
```

Copia este archivo como `.env` y pon las contraseñas que quieras para tu entorno local.

---

## 🐳 Paso 5: El orquestador (`docker-compose.yml`)

Este fichero es el cerebro del despliegue. Define los 3 servicios, los conecta a una red puente (`lemp-network`) y crea un volumen para no perder datos.

Creamos `docker-compose.yml`:

```yaml
services:
  # 1. Servidor Web
  nginx:
    image: nginx:alpine
    container_name: lemp_nginx
    restart: unless-stopped
    ports:
      - "${NGINX_PORT:-8085}:80"
    volumes:
      - ./:/var/www/html:ro
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - php
    networks:
      - lemp-network

  # 2. Motor PHP
  php:
    build:
      context: ./php
      dockerfile: Dockerfile
    image: php:8.2-fpm-alpine
    container_name: lemp_php
    restart: unless-stopped
    volumes:
      - ./:/var/www/html
    environment:
      - MYSQL_HOST=mysql
      - MYSQL_DATABASE=${MYSQL_DATABASE:-lemp_database}
      - MYSQL_USER=${MYSQL_USER:-lemp_user}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD:-secret_user_pass_2026}
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-secret_root_pass_2026}
    networks:
      - lemp-network

  # 3. Base de Datos
  mysql:
    image: mysql:8.0
    container_name: lemp_mysql
    restart: unless-stopped
    command: --default-authentication-plugin=mysql_native_password
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-secret_root_pass_2026}
      - MYSQL_DATABASE=${MYSQL_DATABASE:-lemp_database}
      - MYSQL_USER=${MYSQL_USER:-lemp_user}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD:-secret_user_pass_2026}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - lemp-network

networks:
  lemp-network:
    driver: bridge

volumes:
  mysql_data:
    driver: local
```

> **Ojo a dos detalles importantes:**
> - `depends_on: [php]` le dice a Docker que levante primero PHP para que cuando Nginx empiece a recibir visitas no devuelva error 502.
> - `command: --default-authentication-plugin=mysql_native_password` evita fallos de autenticación entre clientes PHP estándar y MySQL 8.

---

## 💻 Paso 6: El archivo de prueba (`index.php`)

Creamos un archivo `index.php` en la raíz del proyecto para comprobar tres cosas:
1. Que Nginx y PHP-FPM se entienden.
2. Que PHP puede resolver el hostname `mysql` y conectar a la base de datos con éxito.
3. Mostrar las opciones del entorno PHP (`phpinfo()`).

El código lee las credenciales con `getenv()`:

```php
<?php
$dbHost = getenv('MYSQL_HOST') ?: 'mysql';
$dbName = getenv('MYSQL_DATABASE') ?: 'lemp_database';
$dbUser = getenv('MYSQL_USER') ?: 'lemp_user';
$dbPass = getenv('MYSQL_PASSWORD') ?: 'secret_user_pass_2026';

try {
    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    $status = "Exitosa";
} catch (Throwable $e) {
    $status = "Error: " . $e->getMessage();
}
?>
<h1>Estado de Conexión: <?= $status ?></h1>
```

---

## 🚀 Paso 7: Comandos de ejecución y comprobación

Una vez creados los archivos, abrimos una terminal en la carpeta del proyecto y ejecutamos:

### 1. Construir y levantar todo en segundo plano
```bash
docker compose up -d
```
Docker descargará las imágenes oficiales, compilará nuestro Dockerfile de PHP y levantará los 3 contenedores.

### 2. Comprobar que los tres contenedores están corriendo
```bash
docker ps
```
Deberías ver `lemp_nginx`, `lemp_php` y `lemp_mysql` con el estado `Up`.

### 3. Ver los registros (logs) por si algo falla
```bash
docker compose logs -f
```

### 4. Probar en el navegador
Entra en:
```text
http://localhost:8085
```
Si todo ha ido bien, verás la tarjeta de estado en verde con el mensaje **Conexión a MySQL: Exitosa**.

---

## 🛠️ Paso 8: Problemas frecuentes y cómo resolverlos

1. **"Cannot connect to the Docker daemon" en Windows:**
   - Si tienes CPU AMD Ryzen o Intel y Docker Desktop no arranca, comprueba que la virtualización por hardware (SVM en AMD o VT-x en Intel) esté activada en la BIOS de tu placa base. Puedes comprobarlo en PowerShell con:
     ```powershell
     Get-CimInstance Win32_Processor | Select-Object VirtualizationFirmwareEnabled
     ```
     Si sale `False`, reinicia el equipo, entra a la BIOS y actívalo.

2. **Error 502 Bad Gateway al entrar en localhost:8085:**
   - Significa que Nginx está corriendo pero PHP-FPM está caído o reiniciándose. Mira los logs con `docker compose logs php` para ver qué línea de código o extensión ha fallado.

3. **Puerto 8085 ocupado:**
   - Si otro programa está usando el 8085, simplemente cambia en tu archivo `.env`:
     ```env
     NGINX_PORT=8090
     ```
     Y vuelve a levantar con `docker compose up -d`.
