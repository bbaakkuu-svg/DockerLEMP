# Pila LEMP con Docker Compose (2º DAW - Despliegue de Aplicaciones Web)

Este repositorio contiene la solución completa de la práctica **UD01_Ejercicio05: Arquitecturas Dinámicas LEMP mediante Docker Compose** del ciclo formativo de grado superior en Desarrollo de Aplicaciones Web (2º DAW).

> 💡 **¿Quieres aprender a montarlo desde cero?** Consulta la [**Guía Paso a Paso (GUIA_PASO_A_PASO.md)**](GUIA_PASO_A_PASO.md).

---

## 🏗️ Arquitectura del Entorno

La arquitectura está dividida en tres capas desacopladas orquestadas mediante Docker Compose y comunicadas a través de una red interna bridge (`lemp-network`):

```
                     ┌─────────────────────────────────────────┐
                     │          HOST (Navegador Web)           │
                     │          http://localhost:8085          │
                     └────────────────────┬────────────────────┘
                                          │ Puerto 8085:80
                                          ▼
                     ┌─────────────────────────────────────────┐
                     │          Servicio 1: Nginx              │
                     │  (Reverse Proxy FastCGI / Estáticos)    │
                     └────────────────────┬────────────────────┘
                                          │ FastCGI (php:9000)
                                          ▼
                     ┌─────────────────────────────────────────┐
                     │         Servicio 2: PHP-FPM             │
                     │    (Lógica PHP 8.2 + MySQLi + PDO)      │
                     └────────────────────┬────────────────────┘
                                          │ TCP (mysql:3306)
                                          ▼
                     ┌─────────────────────────────────────────┐
                     │          Servicio 3: MySQL 8.0          │
                     │    (Persistencia en volumen local)      │
                     └─────────────────────────────────────────┘
```

1. **`nginx`**: Servidor perimetral que recibe tráfico HTTP en el puerto host `8085` (mapeado al puerto `80` del contenedor) y reenvía las peticiones dinámicas `.php` a FastCGI.
2. **`php`**: Contenedor basado en `php:8.2-fpm-alpine` con un `Dockerfile` que incorpora `mysqli` y `pdo_mysql`.
3. **`mysql`**: Gestor de base de datos relacional MySQL 8.0 con volumen persistente `mysql_data`.

---

## 📁 Estructura del Proyecto

```plaintext
DockerLEMP/
├── .env.example                       # Plantilla de variables de entorno
├── docker-compose.yml                 # Manifiesto de orquestación de servicios
├── index.php                          # Script dinámico de validación y estado
├── nginx/
│   └── default.conf                   # Configuración del Virtual Host de Nginx
├── php/
│   └── Dockerfile                     # Construcción de PHP-FPM con drivers MySQL
├── report_assets/                     # Recursos visuales e HTML del informe técnico
│   ├── captura_navegador.png          # Captura en http://localhost:8085 (Conexión Exitosa)
│   ├── captura_terminal.png           # Captura de docker compose up y docker ps
│   └── informe_tecnico.html           # Documento fuente del informe en HTML/CSS
├── DAW_UD01_DockerLEMP_BakuninSosa.pdf# INFORME TÉCNICO OFICIAL DE ENTREGA (PDF)
├── UD01_Ejercicio05_DockerLEMP.pdf    # Enunciado oficial de la actividad
└── README.md                          # Documentación del proyecto
```

---

## 🚀 Puesta en Marcha

### 1. Variables de entorno
Copiar la plantilla de variables de entorno y configurar los valores deseados:
```bash
cp .env.example .env
```

### 2. Despliegue de los contenedores
Iniciar toda la pila en segundo plano:
```bash
docker compose up -d
```

### 3. Verificación de estado
Comprobar que los tres servicios están en estado `Up`:
```bash
docker ps
```

### 4. Acceso a la aplicación
Abrir en el navegador host:
```text
http://localhost:8085
```

---

## 📋 Documento de Entrega

El informe técnico final solicitado en el enunciado se encuentra generado con la nomenclatura obligatoria:
- 📄 [DAW_UD01_DockerLEMP_BakuninSosa.pdf](DAW_UD01_DockerLEMP_BakuninSosa.pdf)